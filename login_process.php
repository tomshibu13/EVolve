<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Get and validate input
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    if (!$email) {
        throw new Exception('Invalid email format');
    }

    if (empty($password)) {
        throw new Exception('Password is required');
    }

    // Prepare SQL statement using PDO
    $stmt = $pdo->prepare("SELECT user_id, email, passwordhash, name, is_admin FROM tbl_users WHERE email = ? AND status = 'active'");
    if (!$stmt) {
        throw new Exception('Database error: ' . implode(' ', $pdo->errorInfo()));
    }

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['passwordhash'])) {
        throw new Exception('Invalid email or password');
    }

    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['is_admin'] = $user['is_admin'];

    // Handle remember me functionality
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $rememberStmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
        $rememberStmt->execute([$user['user_id'], $token, $expires]);
        
        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'index.php'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
