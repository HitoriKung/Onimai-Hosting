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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'changePassword':
            $currentPassword = $_POST['currentPassword'] ?? '';
            $newPassword = $_POST['newPassword'] ?? '';
            
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!password_verify($currentPassword, $user['password'])) {
                    throw new Exception('รหัสผ่านปัจจุบันไม่ถูกต้อง');
                }
                
                // Validate new password
                if (strlen($newPassword) < 8) {
                    throw new Exception('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
                }
                
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[a-zA-Z\d!@#$%^&*]{8,}$/', $newPassword)) {
                    throw new Exception('รหัสผ่านต้องประกอบด้วยตัวอักษรพิมพ์ใหญ่, พิมพ์เล็ก, ตัวเลข, ตัวอักษรพิเศษ');
                }
                
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Password changed successfully'
                ]);
                
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
?>
