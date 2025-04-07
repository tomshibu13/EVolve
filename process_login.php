<?php
require_once 'config.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_log', 'error.log');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create MySQLi connection using credentials from config.php
$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
    error_log("Connection failed: " . $mysqli->connect_error);
    die("Connection failed. Please try again later.");
}

// Check if this is a JSON or form submission
$isJson = false;
$data = [];

if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    // JSON submission (AJAX)
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    $isJson = true;
} else {
    // Regular form submission 
    $data = $_POST;
}

// Log received data
error_log("Received login data: " . print_r($data, true));

// Initialize response
$success = false;
$message = '';
$redirect = '';

try {
    // Debug: Log the connection status
    error_log("MySQL connection status: " . ($mysqli->ping() ? 'connected' : 'not connected'));

    // Validate input
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception('Email and password are required');
    }

    // Debug: Log the email being checked
    error_log("Checking email: " . $data['email']);

    // Find the user
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

    // Store basic user data
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    
    // For new JavaScript handling
    $_SESSION['user'] = [
        'user_id' => $user['user_id'],
        'email' => $user['email'],
        'username' => $user['username'],
        'name' => $user['name']
    ];

    // Check if user is admin
    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        $_SESSION['user']['is_admin'] = true;
        $redirect = 'admindash.php';
    } else {
        $_SESSION['user']['is_admin'] = false;
    }

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
                $redirect = 'station-owner-dashboard.php';
                break;
            case 'pending':
                $redirect = 'index.php';
                $message = 'Your station owner account is pending approval.';
                break;
            case 'rejected':
                $redirect = 'index.php';
                $message = 'Your station owner application was rejected.';
                break;
            default:
                $redirect = 'index.php';
        }
    } else {
        // Regular user
        $_SESSION['user']['is_station_owner'] = false;
        $redirect = 'index.php';
    }

    // Set success message for page refresh
    $_SESSION['login_success'] = 'Login successful';
    $success = true;
    
    // Handle remember me
    $remember = isset($data['remember']) ? $data['remember'] : false;
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $tokenSql = "INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)";
        $tokenStmt = $mysqli->prepare($tokenSql);
        $tokenStmt->bind_param("iss", $user['user_id'], $token, $expires);
        $tokenStmt->execute();

        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
    }

    // Debug: Log successful login
    error_log("Successful login for user: " . $user['email']);
    error_log("Response data: " . print_r([
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect,
        'user' => $_SESSION['user'] ?? null
    ], true));

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $message = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($ownerStmt)) {
        $ownerStmt->close();
    }
}

// Handle response based on submission type
if ($isJson) {
    // Send JSON response for AJAX
    $response = [
        'success' => $success,
        'message' => $message,
        'redirect' => $redirect,
        'user' => $_SESSION['user'] ?? null
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Regular form submission - redirect
    if ($success) {
        header("Location: $redirect");
        exit;
    } else {
        // Save error message in session and redirect to login
        $_SESSION['login_error'] = $message;
        header("Location: index.php?login_error=true");
        exit;
    }
}
?> 