<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function generateUsername($domain) {
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = explode('/', $domain)[0];
    $username = preg_replace('/[^a-zA-Z0-9]/', '', $domain);
    $username = strtolower($username);
    $username = substr($username, 0, 8);
    return $username;
}

function createDirectAdminUser($userhost, $passhost, $username, $password, $domain, $package_id, $ip, $url) {
    $apiParams = http_build_query([
        'action' => 'create',
        'add' => 'Submit',
        'username' => $username,
        'email' => 'useremail@gmail.com',
        'passwd' => $password,
        'passwd2' => $password,
        'domain' => $domain,
        'package' => $package_id,
        'ip' => $ip,
        'notify' => 'no'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://".$url.":2222/CMD_API_ACCOUNT_USER?{$apiParams}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$userhost}:{$passhost}");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: " . $error_msg);
    }
    
    curl_close($ch);
    parse_str($result, $params);
    
    if (isset($params['error']) && $params['error'] == 1) {
        throw new Exception("DirectAdmin API Error: " . ($params['text'] ?? 'Unknown error'));
    }
    
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'getPackages':
            $categoryId = $_POST['category_id'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("SELECT * FROM hosting_packages WHERE category_id = ? AND is_active = 1");
                $stmt->execute([$categoryId]);
                $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'packages' => $packages
                ]);
            } catch(PDOException $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Could not fetch packages'
                ]);
            }
            break;
        case 'createOrder':
            $packageId = $_POST['package_id'] ?? 0;
            $domain = $_POST['domain'] ?? '';
            $userId = $_SESSION['user_id'];
            
            if (!$packageId || !$domain) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid input'
                ]);
                exit;
            }
            
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM hosting_orders WHERE domain = ? AND status != 'cancelled'");
                $stmt->execute([$domain]);
                if ($stmt->fetchColumn() > 0) {
                    $pdo->rollBack();
                    throw new Exception('Domain is already in use');
                }

                $stmt = $pdo->prepare("
                    SELECT hp.*, hc.directadmin_user, hc.directadmin_pass, hc.directadmin_ip, hc.directadmin_url
                    FROM hosting_packages hp 
                    JOIN hosting_categories hc ON hp.category_id = hc.id 
                    WHERE hp.id = ?
                ");
                $stmt->execute([$packageId]);
                $package = $stmt->fetch();
                
                if (!$package) {
                    $pdo->rollBack();
                    throw new Exception('Package not found');
                }
                
                $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE"); // ล็อคแถวที่จะอัพเดท
                $stmt->execute([$userId]);
                $userBalance = $stmt->fetchColumn();
                
                if ($userBalance < $package['price_monthly']) {
                    $pdo->rollBack();
                    throw new Exception('ยอดเงินไม่เพียงพอ กรุณาเติมเงิน');
                }
                
                $hosting_username = generateUsername($domain);
                $hosting_password = generatePassword(12);
                
                try {
                    $daResult = createDirectAdminUser(
                        $package['directadmin_user'],
                        $package['directadmin_pass'],
                        $hosting_username,
                        $hosting_password,
                        $domain,
                        $package['package_id'],
                        $package['directadmin_ip'],
                        $package['directadmin_url']
                    );
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw new Exception('ไม่สามารถสร้าง Hosting ได้: ' . $e->getMessage());
                }
                
                $stmt = $pdo->prepare("INSERT INTO hosting_orders (
                    user_id, 
                    package_id, 
                    domain, 
                    hosting_username,
                    hosting_password,
                    status, 
                    start_date, 
                    end_date
                ) VALUES (
                    ?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH)
                )");
                $stmt->execute([
                    $userId, 
                    $packageId, 
                    $domain, 
                    $hosting_username,
                    $hosting_password
                ]);
                
                $stmt = $pdo->prepare("UPDATE users SET 
                    balance = balance - ?, 
                    balance_used = balance_used + ? 
                    WHERE id = ?");
                $stmt->execute([
                    $package['price_monthly'], 
                    $package['price_monthly'], 
                    $userId
                ]);
                
                $pdo->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Order created successfully',
                    'credentials' => [
                        'username' => $hosting_username,
                        'password' => $hosting_password
                    ]
                ]);
                
            } catch(Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                error_log("Hosting Order Error: " . $e->getMessage());
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'calculateRenewalPrice':
            $hostingId = $_POST['hosting_id'] ?? 0;
            $period = (int)$_POST['period'] ?? 1;
            
            try {
                $stmt = $pdo->prepare("
                    SELECT ho.*, hp.price_monthly 
                    FROM hosting_orders ho
                    JOIN hosting_packages hp ON ho.package_id = hp.id
                    WHERE ho.id = ? AND ho.user_id = ?
                ");
                $stmt->execute([$hostingId, $_SESSION['user_id']]);
                $hosting = $stmt->fetch();
                
                if (!$hosting) {
                    throw new Exception('Hosting not found');
                }
                
                $totalPrice = $hosting['price_monthly'] * $period;
                
                echo json_encode([
                    'status' => 'success',
                    'price' => $totalPrice
                ]);
            } catch(Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;

        case 'renewHosting':
            $hostingId = $_POST['hosting_id'] ?? 0;
            $period = (int)$_POST['period'] ?? 1;
            
            try {
                $pdo->beginTransaction();
                
                // Get hosting and package details
                $stmt = $pdo->prepare("
                    SELECT ho.*, hp.price_monthly 
                    FROM hosting_orders ho
                    JOIN hosting_packages hp ON ho.package_id = hp.id
                    WHERE ho.id = ? AND ho.user_id = ?
                    FOR UPDATE
                ");
                $stmt->execute([$hostingId, $_SESSION['user_id']]);
                $hosting = $stmt->fetch();
                
                if (!$hosting) {
                    throw new Exception('Hosting not found');
                }
                
                // Calculate total price
                $totalPrice = $hosting['price_monthly'] * $period;
                
                // Check user balance
                $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
                $stmt->execute([$_SESSION['user_id']]);
                $userBalance = $stmt->fetchColumn();
                
                if ($userBalance < $totalPrice) {
                    throw new Exception('ยอดเงินไม่เพียงพอ กรุณาเติมเงิน');
                }
                
                // Update hosting end date
                $stmt = $pdo->prepare("
                    UPDATE hosting_orders 
                    SET end_date = DATE_ADD(
                        CASE 
                            WHEN end_date > NOW() THEN end_date 
                            ELSE NOW() 
                        END, 
                        INTERVAL ? MONTH
                    )
                    WHERE id = ?
                ");
                $stmt->execute([$period, $hostingId]);
                
                // Deduct balance
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET balance = balance - ?,
                        balance_used = balance_used + ?
                    WHERE id = ?
                ");
                $stmt->execute([$totalPrice, $totalPrice, $_SESSION['user_id']]);
                
                $pdo->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Hosting renewed successfully'
                ]);
                
            } catch(Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;


    }
}
