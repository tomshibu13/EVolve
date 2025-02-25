<?php
session_start();
require_once 'config.php'; // Ensure this file correctly initializes $mysqli

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send JSON response
function sendJsonResponse($success, $message, $redirect = null) {
    $response = [
        'success' => $success,
        'error' => $success ? null : $message,
        'message' => $success ? $message : null
    ];
    if ($redirect) {
        $response['redirect'] = $redirect;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Log received data (for debugging)
        error_log("Login attempt - Request data: " . json_encode($_POST));

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($username) || empty($password)) {
            sendJsonResponse(false, 'Please enter both username and password');
        }

        // First, verify the table exists
        $table_check = $mysqli->query("SHOW TABLES LIKE 'tbl_users'");
        if ($table_check->num_rows === 0) {
            throw new Exception("Database error: Table 'tbl_users' does not exist");
        }

        // Prepare statement
        $stmt = $mysqli->prepare("SELECT user_id, username, passwordhash, is_admin FROM tbl_users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $mysqli->error);
        }

        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $user['passwordhash'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Log successful login
                error_log("Successful login for user: " . $username);
                
                // Return success response
                sendJsonResponse(true, 'Login successful', $user['is_admin'] ? 'admindash.php' : 'index.php');
            }
        }
        
        // Invalid credentials
        error_log("Failed login attempt for username: " . $username);
        sendJsonResponse(false, 'Invalid username or password');

    } catch (Exception $e) {
        // Log the error message with more context
        error_log("Login error: " . $e->getMessage() . " | Request Data: " . json_encode($_POST));
        sendJsonResponse(false, 'An error occurred: ' . $e->getMessage());
    }
} else {
    sendJsonResponse(false, 'Invalid request method');
}
?>
