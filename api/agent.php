<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

function validateApiKey($pdo, $apiKey) {
    $stmt = $pdo->prepare("SELECT * FROM agents WHERE api_key = ? AND confirmed = 1");
    $stmt->execute([$apiKey]);
    return $stmt->fetch();
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

function generateApiKey() {
    return bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!isset($_SERVER['HTTP_X_API_KEY'])) {
        switch($action) {
            case 'register':
                try {
                    $stmt = $pdo->prepare("SELECT id FROM agents WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    if ($stmt->fetch()) {
                        throw new Exception('คุณได้สมัครตัวแทนจำหน่ายไปแล้ว');
                    }

                    $shopName = $_POST['shopName'] ?? '';
                    $shopUrl = $_POST['shopUrl'] ?? '';
                    $phone = $_POST['phone'] ?? '';
                    
                    if (!$shopName || !$shopUrl || !$phone) {
                        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
                    }

                    $apiKey = generateApiKey();

                    $stmt = $pdo->prepare("
                        INSERT INTO agents (user_id, shop_name, shop_url, phone, api_key)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $shopName,
                        $shopUrl,
                        $phone,
                        $apiKey
                    ]);

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Registration successful'
                    ]);
                } catch(Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            case 'regenerateKey':
                try {
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('Unauthorized access');
                    }

                    $newApiKey = generateApiKey();
                    $stmt = $pdo->prepare("UPDATE agents SET api_key = ? WHERE user_id = ?");
                    $stmt->execute([$newApiKey, $_SESSION['user_id']]);

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'API key regenerated successfully',
                        'new_key' => $newApiKey
                    ]);
                } catch(Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;
        }
    } 
    else {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
        $agent = validateApiKey($pdo, $apiKey);
        
        if (!$agent) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or unauthorized API key'
            ]);
            exit;
        }

        switch($action) {
            case 'checkBalance':
                try {
                    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                    $stmt->execute([$agent['user_id']]);
                    $balance = $stmt->fetchColumn();

                    echo json_encode([
                        'status' => 'success',
                        'balance' => floatval($balance)
                    ]);
                } catch(Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            case 'getPackages':
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            hp.id,
                            hp.name,
                            hp.agent_price as price,
                            hp.domains_limit,
                            hp.subdomains_limit,
                            hp.space_mb,
                            hp.bandwidth_gb,
                            hp.email_accounts,
                            hp.db_count,
                            hp.package_id,
                            hc.name as category_name
                        FROM hosting_packages hp 
                        JOIN hosting_categories hc ON hp.category_id = hc.id 
                        WHERE hp.is_active = 1
                    ");
                    $stmt->execute();
                    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'status' => 'success',
                        'packages' => $packages
                    ]);
                } catch(Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            case 'createOrder':
                try {
                    $packageId = $_POST['package_id'] ?? 0;
                    $domain = $_POST['domain'] ?? '';
                    
                    if (!$packageId || !$domain) {
                        throw new Exception('Missing required parameters');
                    }

                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM hosting_orders WHERE domain = ? AND status != 'cancelled'");
                    $stmt->execute([$domain]);
                    if ($stmt->fetchColumn() > 0) {
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

                    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
                    $stmt->execute([$agent['user_id']]);
                    $balance = $stmt->fetchColumn();

                    if ($balance < $package['agent_price']) {
                        throw new Exception('Insufficient balance');
                    }

					function generateUsername($domain) {
						$base = str_replace('.', '', $domain);

						$number = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

						$base = substr($base, 0, 6);

						$username = $base . $number;

						return $username;
					}

                    $hosting_username = generateUsername($domain);

                    $hosting_password = bin2hex(random_bytes(8));


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

					
                    $stmt = $pdo->prepare("
                        INSERT INTO hosting_orders (
                            user_id, package_id, domain, hosting_username, hosting_password,
                            status, start_date, end_date
                        ) VALUES (
                            ?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH)
                        )
                    ");
                    $stmt->execute([
                        $agent['user_id'],
                        $packageId,
                        $domain,
                        $hosting_username,
                        $hosting_password
                    ]);
                    $orderId = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET balance = balance - ?,
                            balance_used = balance_used + ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $package['agent_price'],
                        $package['agent_price'],
                        $agent['user_id']
                    ]);

                    $pdo->commit();

                    echo json_encode([
                        'status' => 'success',
                        'order_id' => $orderId,
                        'credentials' => [
                            'username' => $hosting_username,
                            'password' => $hosting_password
                        ]
                    ]);
                } catch (Exception $e) {
						if ($pdo->inTransaction()) $pdo->rollBack();
						echo json_encode([
						  'status' => 'error',
						  'message' => $e->getMessage()
						]);
					}
                break;

            case 'renewHosting':
                try {
                    $orderId = $_POST['order_id'] ?? 0;
                    $period = (int)($_POST['period'] ?? 1);

                    if ($period < 1 || $period > 12) {
                        throw new Exception('Invalid renewal period');
                    }

                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        SELECT ho.*, hp.agent_price 
                        FROM hosting_orders ho
                        JOIN hosting_packages hp ON ho.package_id = hp.id
                        WHERE ho.id = ? AND ho.user_id = ?
                        FOR UPDATE
                    ");
                    $stmt->execute([$orderId, $agent['user_id']]);
                    $order = $stmt->fetch();

                    if (!$order) {
                        throw new Exception('Order not found');
                    }

                    $totalPrice = $order['agent_price'] * $period;

                    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
                    $stmt->execute([$agent['user_id']]);
                    $balance = $stmt->fetchColumn();

                    if ($balance < $totalPrice) {
                        throw new Exception('Insufficient balance');
                    }

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
                    $stmt->execute([$period, $orderId]);

                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET balance = balance - ?,
                            balance_used = balance_used + ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$totalPrice, $totalPrice, $agent['user_id']]);

                    $pdo->commit();

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Hosting renewed successfully'
                    ]);
                } catch(Exception $e) {
                    $pdo->rollBack();
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            case 'getOrderHistory':
                try {
                    $stmt = $pdo->prepare("
                        SELECT 
                            ho.id,
                            ho.domain,
                            ho.status,
                            ho.start_date,
                            ho.end_date,
                            hp.name as package_name,
                            hp.agent_price as price
                        FROM hosting_orders ho 
                        JOIN hosting_packages hp ON ho.package_id = hp.id 
                        WHERE ho.user_id = ?
                        ORDER BY ho.created_at DESC
                    ");
                    $stmt->execute([$agent['user_id']]);
                    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode([
                        'status' => 'success',
                        'orders' => $orders
                    ]);
                } catch(Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            case 'getHostingDetails':
                try {
                    $orderId = $_POST['order_id'] ?? 0;
                    
                    $stmt = $pdo->prepare("
                        SELECT 
                            ho.*,
                            hp.name as package_name,
                            hp.agent_price as price,
                            hp.domains_limit,
                            hp.subdomains_limit,
                            hp.space_mb,
                            hp.bandwidth_gb,
                            hp.email_accounts,
                            hp.db_count,
                            hc.name as category_name
                        FROM hosting_orders ho 
                        JOIN hosting_packages hp ON ho.package_id = hp.id 
                        JOIN hosting_categories hc ON hp.category_id = hc.id 
                        WHERE ho.id = ? AND ho.user_id = ?
                    ");
                    $stmt->execute([$orderId, $agent['user_id']]);
                    $hosting = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($hosting) {
                        echo json_encode([
                            'status' => 'success',
                            'hosting' => $hosting
                        ]);
                    } else {
                        throw new Exception('Hosting not found');
                    }
                } catch(Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;

            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid action'
                ]);
        }
    }
}
?>
