<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get JSON data from the request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$response = ['success' => false, 'message' => '', 'user' => null, 'redirect' => ''];

try {
    // Add debug logging
    error_log("Login attempt - Email: " . ($data['email'] ?? 'not set'));
    
    // Validate required fields
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception("Email and password are required");
    }

    // First, check the user exists in tbl_users
    $userSql = "SELECT u.*, sor.status as owner_status, sor.business_name 
                FROM tbl_users u 
                LEFT JOIN station_owner_requests sor ON u.user_id = sor.user_id 
                WHERE u.email = ?";
    
    $stmt = $mysqli->prepare($userSql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("s", $data['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Add debug logging
    error_log("User query result: " . ($user ? 'User found' : 'User not found'));

    if (!$user) {
        throw new Exception("Invalid email or password");
    }

    // Debug password verification
    error_log("Attempting password verification");
    
    // Verify password
    if (!password_verify($data['password'], $user['passwordhash'])) {
        error_log("Password verification failed");
        throw new Exception("Invalid email or password");
    }

    error_log("Password verified successfully");

    // Prepare user data for response
    $userData = [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'username' => $user['username'],
        'is_admin' => (bool)$user['is_admin'],
        'is_station_owner' => !empty($user['owner_status']),
        'owner_status' => $user['owner_status'] ?? null,
        'business_name' => $user['business_name'] ?? null
    ];

    // Handle remember me functionality if requested
    if (!empty($data['remember']) && $data['remember'] === true) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $tokenSql = "INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)";
        $tokenStmt = $mysqli->prepare($tokenSql);
        $tokenStmt->bind_param("iss", $user['user_id'], $token, $expires);
        $tokenStmt->execute();
        
        // Set remember me cookie
        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
    }

    // Start session and store user data
    session_start();
    $_SESSION['user'] = $userData;

    // Set success response
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['user'] = $userData;
    $response['redirect'] = 'index.php';

    error_log("Login successful, redirecting to: " . $response['redirect']);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($tokenStmt)) $tokenStmt->close();
}

// Debug final response
error_log("Final response: " . json_encode($response));

// Send JSON response
echo json_encode($response);
?> 