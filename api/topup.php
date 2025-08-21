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

define('BYSHOP_SLIP_KEY', '');
define('SLIP_NAME', '');
define('BYSHOP_WALLET_KEY', '');
define('TRUEWALLET_PHONE', '');
define('TRUEWALLET_KEYAPI', '');
define('TRUEWALLET_WEBHOOK_URL', '');
define('BANK_WEBHOOK_URL', '');

function getUserInfo($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sendDiscordNotification($pdo, $amount, $method, $user_id, $receiverName = null) {
    $webhookUrl = $method === 'TrueWallet' ? TRUEWALLET_WEBHOOK_URL : BANK_WEBHOOK_URL;
    $userInfo = getUserInfo($pdo, $user_id);
    $username = $userInfo['username'] ?? 'ไม่ระบุชื่อ';

    $description = "💸 เติมเงินสำเร็จบัญชี {$username}";
    if ($method === 'โอนเงินผ่านธนาคาร' && $receiverName) {
        $description .= "\n🏦 โอนโดย {$receiverName}";
    } else if ($method === 'TrueWallet') {
        $description .= "\n📱 รับเงินโดย " . TRUEWALLET_PHONE;
    }
    $description .= "\n💰 จำนวนเงิน: {$amount} บาท";
    $description .= "\n💳 วิธีการ: {$method}";
    $description .= "\n⏰ วันที่ เวลา: " . date("d/m/Y H:i");

    $data = [
        'content' => null,
        'embeds' => [
            [
                'title' => $method === 'TrueWallet' ? '📱 การเติมเงินผ่าน TrueWallet' : '🏦 การเติมเงินผ่านธนาคาร',
                'description' => $description,
                'color' => $method === 'TrueWallet' ? 0xFF0000 : 0x00FF00,
                'timestamp' => date("c")
            ]
        ]
    ];

    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true
    ]);
    curl_exec($ch);
    curl_close($ch);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
		case 'bank_transfer':
			try {
				$amount = floatval($_POST['amount'] ?? 0);
				$qr_data = $_POST['qr_data'] ?? '';

				if ($amount <= 0) {
					throw new Exception('จำนวนเงินไม่ถูกต้อง');
				}

				if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
					throw new Exception('กรุณาอัปโหลดสลิป');
				}

				$file = $_FILES['slip'];
				$allowed_types = ['image/jpeg', 'image/png'];
				if (!in_array($file['type'], $allowed_types)) {
					throw new Exception('รองรับเฉพาะไฟล์ภาพ jpg, png เท่านั้น');
				}

				if ($file['size'] > 2 * 1024 * 1024) { // 2MB
					throw new Exception('ขนาดไฟล์ต้องไม่เกิน 2MB');
				}

				$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
				$filename = uniqid() . '.' . $extension;
				$upload_path = '../assets/uploads/slips/' . $filename;

				if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
					throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
				}

				$slip_verified = false;
				$slip_data = null;

				if ($qr_data) {
					$stmt = $pdo->prepare("SELECT COUNT(*) FROM topup_transactions WHERE qr_code_data = ?");
					$stmt->execute([$qr_data]);
					$qr_used = $stmt->fetchColumn();

					if ($qr_used > 0) {
						throw new Exception('สลิปนี้ถูกตรวจสอบไปแล้ว');
					}

					$amount = number_format($_POST['amount'], 2, '.', '');
					$apiUrl = "https://slip-c.oiioioiiioooioio.download/api/slip/{$amount}/no_slip";

					$ch = curl_init($apiUrl);
					curl_setopt_array($ch, [
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => json_encode(['qrcode_data' => $qr_data]),
						CURLOPT_HTTPHEADER => [
							'Content-Type: application/json',
							'Accept: application/json',
						],
						CURLOPT_TIMEOUT => 30,
						CURLOPT_CONNECTTIMEOUT => 10,
						CURLOPT_ENCODING => '',
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_SSL_VERIFYPEER => false
					]);

					$response = curl_exec($ch);
					$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);

					if ($httpCode !== 200) {
						throw new Exception("เกิดข้อผิดพลาดจาก API (HTTP $httpCode)");
					}

					$slip_data = json_decode($response, true);
					if (!isset($slip_data['data'])) {
						throw new Exception($slip_data['message'] ?? 'เกิดข้อผิดพลาดจาก API');
					}

					$slip_amount = floatval($slip_data['data']['amount'] ?? 0);
					$receiver_name = $slip_data['data']['receiver_name'] ?? '';

					if (floatval($slip_amount) !== floatval($amount)) {
						throw new Exception('จำนวนเงินในสลิปไม่ตรงกับที่ระบุ');
					}

					if ($receiver_name !== SLIP_NAME) {
						throw new Exception('ไม่พบข้อมูลบัญชีนี้'.$receiver_name);
					}

					$slip_verified = true;
				}


				$pdo->beginTransaction();
				$transactionStarted = true;

				$stmt = $pdo->prepare("
					INSERT INTO topup_transactions (
						user_id, amount, method, status,
						slip_image, qr_code_data, slip_data,
						created_at
					) VALUES (?, ?, 'bank', ?, ?, ?, ?, NOW())
				");
				$stmt->execute([
					$_SESSION['user_id'],
					$amount,
					$slip_verified ? 'approved' : 'pending',
					$filename,
					$qr_data,
					$slip_data ? json_encode($slip_data) : null
				]);
				$transaction_id = $pdo->lastInsertId();

				if ($slip_verified) {
					$stmt = $pdo->prepare("
						UPDATE users
						SET balance = balance + ?,
							balance_used = balance_used + ?
						WHERE id = ?
					");
					$stmt->execute([$amount, 0, $_SESSION['user_id']]);

					// Update transaction
					$stmt = $pdo->prepare("
						UPDATE topup_transactions
						SET approved_at = NOW(),
							approved_by = ?,
							admin_note = 'ตรวจสอบอัตโนมัติผ่าน QR Code'
						WHERE id = ?
					");
					$stmt->execute([1, $transaction_id]); // 1 = system

					if ($receiver_name === SLIP_NAME) {
						sendDiscordNotification($pdo, $amount, 'โอนเงินผ่านธนาคาร', $_SESSION['user_id'], $receiver_name);
					}
				}

				$pdo->commit();

				echo json_encode([
					'status' => 'success',
					'message' => $slip_verified ? 'เติมเงินสำเร็จ' : 'ส่งข้อมูลสำเร็จ กรุณารอการตรวจสอบ',
					'verified' => $slip_verified
				]);
			} catch(Exception $e) {
				if (isset($transactionStarted) && $transactionStarted) {
					$pdo->rollBack();
				}
				echo json_encode([
					'status' => 'error',
					'message' => $e->getMessage()
				]);
			}
			break;
        case 'truewallet':
            try {
                $gift_link = $_POST['link'] ?? '';

                if (empty($gift_link)) {
                    throw new Exception('กรุณาระบุลิงก์ TrueMoney');
                }

                $ch = curl_init('https://api.onimai.cloud/v1/api/truewallet/angpao');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'keyapi' => TRUEWALLET_KEYAPI,
                    'voucher' => $gift_link,
                    'phone' => TRUEWALLET_PHONE
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
                $response = curl_exec($ch);
                curl_close($ch);

                if (!$response) {
                    throw new Exception('ไม่สามารถติดต่อ API ได้');
                }

                $result = json_decode($response);
                if (!$result || !$result->status->success) {
                    $errMsg = $result->status->message ?? 'เกิดข้อผิดพลาด';
                    throw new Exception($errMsg);
                }

                $amount = floatval($result->details->amount->redeemed ?? 0);
                if ($amount <= 0) {
                    throw new Exception('จำนวนเงินไม่ถูกต้อง');
                }

                $pdo->beginTransaction();
                $transactionStarted = true;

                $stmt = $pdo->prepare("
                    INSERT INTO topup_transactions (
                        user_id, amount, method, status,
                        true_link, created_at, approved_at,
                        approved_by, admin_note
                    ) VALUES (
                        ?, ?, 'truewallet', 'approved',
                        ?, NOW(), NOW(), 1,
                        'ตรวจสอบอัตโนมัติผ่าน OniMai TrueWallet API'
                    )
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $amount,
                    $gift_link
                ]);

                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET balance = balance + ?,
                        balance_used = balance_used + ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $amount,
                    0,
                    $_SESSION['user_id']
                ]);

                $pdo->commit();

                sendDiscordNotification($pdo, $amount, 'TrueWallet', $_SESSION['user_id']);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'เติมเงินสำเร็จ',
                    'amount' => $amount
                ]);
            } catch (Exception $e) {
                if (isset($transactionStarted) && $transactionStarted) {
                    $pdo->rollBack();
                }
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            break;


        case 'redeem':
            try {
                $code = $_POST['code'] ?? '';
                
                if (!$code) {
                    throw new Exception('กรุณาระบุรหัสคูปอง');
                }

                // Begin transaction
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT * FROM redeem_codes 
                    WHERE code = ? AND is_active = 1
                    AND (end_date IS NULL OR end_date > NOW())
                    AND (usage_limit = 0 OR used_count < usage_limit)
                    FOR UPDATE
                ");
                $stmt->execute([$code]);
                $redeem = $stmt->fetch();

                if (!$redeem) {
                    throw new Exception('รหัสคูปองไม่ถูกต้องหรือไม่สามารถใช้งานได้');
                }

                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM redeem_history 
                    WHERE code_id = ? AND user_id = ?
                ");
                $stmt->execute([$redeem['id'], $_SESSION['user_id']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('คุณเคยใช้รหัสคูปองนี้ไปแล้ว');
                }

                $stmt = $pdo->prepare("
                    UPDATE redeem_codes 
                    SET used_count = used_count + 1
                    WHERE id = ?
                ");
                $stmt->execute([$redeem['id']]);

                $stmt = $pdo->prepare("
                    INSERT INTO redeem_history (
                        code_id, user_id, credit_amount
                    ) VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $redeem['id'],
                    $_SESSION['user_id'],
                    $redeem['credit_amount']
                ]);

                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET balance = balance + ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $redeem['credit_amount'],
                    $_SESSION['user_id']
                ]);

                $pdo->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'ใช้คูปองสำเร็จ',
                    'credit_amount' => $redeem['credit_amount']
                ]);
            } catch(Exception $e) {
                $pdo->rollBack();
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
?>
