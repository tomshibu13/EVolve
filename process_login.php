<?php
require_once 'config.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'error.log');

// Get JSON data from request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Log received data
error_log("Received login data: " . print_r($data, true));

// Initialize response array
$response = ['success' => false, 'message' => '', 'redirect' => ''];

try {
    // Debug: Log the connection status
    error_log("MySQL connection status: " . ($mysqli->ping() ? 'connected' : 'not connected'));

    // Validate input
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception('Email and password are required');
    }

    // Debug: Log the email being checked
    error_log("Checking email: " . $data['email']);

    // First try to find the user
    $sql = "SELECT * FROM tbl_users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        throw new Exception("Database error: " . $mysqli->error);
    }

    $stmt->bind_param("s", $data['email']);
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        throw new Exception("Database error: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    // Debug: Log the number of rows found
    error_log("Number of users found: " . $result->num_rows);

    if ($result->num_rows === 0) {
        throw new Exception('Invalid email or password');
    }

    $user = $result->fetch_assoc();
    
    // Debug: Log user data (remove sensitive info)
    error_log("User found: " . print_r([
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'username' => $user['username']
    ], true));

    // Verify password
    if (!password_verify($data['password'], $user['passwordhash'])) {
        error_log("Password verification failed for user: " . $user['email']);
        throw new Exception('Invalid email or password');
    }

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Store basic user data first
    $_SESSION['user'] = [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'username' => $user['username']
    ];

    // Now check if user is a station owner
    $ownerSql = "SELECT * FROM station_owner_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $ownerStmt = $mysqli->prepare($ownerSql);
    $ownerStmt->bind_param("i", $user['user_id']);
    $ownerStmt->execute();
    $ownerResult = $ownerStmt->get_result();

    if ($ownerResult->num_rows > 0) {
        $ownerData = $ownerResult->fetch_assoc();
        $_SESSION['user']['is_station_owner'] = true;
        $_SESSION['user']['owner_status'] = $ownerData['status'];
        $_SESSION['user']['owner_name'] = $ownerData['owner_name'];
        $_SESSION['user']['business_name'] = $ownerData['business_name'];

        // Debug: Log owner data
        error_log("Owner data found: " . print_r([
            'status' => $ownerData['status'],
            'owner_name' => $ownerData['owner_name']
        ], true));

        // Set redirect based on owner status
        switch($ownerData['status']) {
            case 'approved':
                $response['redirect'] = 'station_owner_dashboard.php';
                break;
            case 'pending':
                $response['redirect'] = 'index.php';
                $response['message'] = 'Your station owner account is pending approval.';
                break;
            case 'rejected':
                $response['redirect'] = 'index.php';
                $response['message'] = 'Your station owner application was rejected.';
                break;
        }
    } else {
        // Regular user
        $_SESSION['user']['is_station_owner'] = false;
        $response['redirect'] = 'index.php';
    }

    // Handle remember me
    if (!empty($data['remember']) && $data['remember'] === true) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $tokenSql = "INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)";
        $tokenStmt = $mysqli->prepare($tokenSql);
        $tokenStmt->bind_param("iss", $user['user_id'], $token, $expires);
        $tokenStmt->execute();

        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
    }

    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['user'] = $_SESSION['user'];

    // Debug: Log successful login
    error_log("Successful login for user: " . $user['email']);
    error_log("Response data: " . print_r($response, true));

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($ownerStmt)) {
        $ownerStmt->close();
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?> 