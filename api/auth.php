<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[a-zA-Z\d!@#$%^&*]{8,}$/', $password);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function checkRateLimit($ip) {
    $timeout = 1 * 60; // 15 minutes
    $max_attempts = 5;
    
    $attempts_file = "../temp/login_attempts.json";
    $attempts = [];
    
    if (!file_exists("../temp")) {
        mkdir("../temp", 0777, true);
    }
    
    if (file_exists($attempts_file)) {
        $json_data = file_get_contents($attempts_file);
        if ($json_data) {
            $attempts = json_decode($json_data, true) ?? [];
        }
    }
    
    if (is_array($attempts)) {
        foreach ($attempts as $attempt_ip => $data) {
            if (time() - $data['timestamp'] > $timeout) {
                unset($attempts[$attempt_ip]);
            }
        }
    }
    
    if (isset($attempts[$ip])) {
        if ($attempts[$ip]['count'] >= $max_attempts && 
            time() - $attempts[$ip]['timestamp'] < $timeout) {
            return false;
        }
    }
    
    return true;
}

function updateRateLimit($ip) {
    $attempts_file = "../temp/login_attempts.json";
    $attempts = [];
    
    if (!file_exists("../temp")) {
        mkdir("../temp", 0777, true);
    }
    
    if (file_exists($attempts_file)) {
        $json_data = file_get_contents($attempts_file);
        if ($json_data) {
            $attempts = json_decode($json_data, true) ?? [];
        }
    }
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 1, 'timestamp' => time()];
    } else {
        $attempts[$ip]['count']++;
        $attempts[$ip]['timestamp'] = time();
    }
    
    file_put_contents($attempts_file, json_encode($attempts));
}

function logError($message) {
    $log_file = "../temp/error.log";
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    
    if (!file_exists("../temp")) {
        mkdir("../temp", 0777, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (!checkRateLimit($ip)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Too many attempts. Please try again later.'
        ]);
        exit;
    }

    switch ($action) {
        case 'login':
            try {
                $username = sanitizeInput($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($username) || empty($password)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Username and password are required'
                    ]);
                    exit;
                }

                if (!validateUsername($username)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid username format'
                    ]);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['realname'] = $user['realname'];
                    $_SESSION['surname'] = $user['surname'];

                    // Update last login
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), ip = ? WHERE id = ?");
                    $stmt->execute([$ip, $user['id']]);

                    echo json_encode(['status' => 'success']);
                } else {
                    updateRateLimit($ip);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid username or password'
                    ]);
                }
            } catch(PDOException $e) {
                logError("Database error: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => 'A database error occurred'
                ]);
            } catch(Exception $e) {
                logError("General error: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => 'An error occurred'
                ]);
            }
            break;


        case 'register':
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $realname = sanitizeInput($_POST['realname']);
            $surname = sanitizeInput($_POST['surname']);

            if (!validateUsername($username)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Username must be 3-20 characters and contain only letters, numbers, and underscores'
                ]);
                exit;
            }

            if (!validateEmail($email)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email format'
                ]);
                exit;
            }

            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Username already exists'
                    ]);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Email already exists'
                    ]);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, realname, surname, ip) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $realname,
                    $surname,
                    $ip
                ]);

                echo json_encode(['status' => 'success']);
            } catch(PDOException $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Database error occurred'
                ]);
            }
            break;
        case 'logout':
            try {
                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, 'logout', ?)");
                    $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR']]);
                }

                $_SESSION = array();

                if (isset($_COOKIE[session_name()])) {
                    setcookie(session_name(), '', time()-3600, '/');
                }

                session_destroy();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Logged out successfully'
                ]);
            } catch(Exception $e) {
                error_log("Logout Error: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => 'An error occurred during logout'
                ]);
            }
            break;

        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
