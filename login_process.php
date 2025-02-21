<?php
session_start();
require_once 'config.php'; // Add this line to include the database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password";
        header("Location: index.php#loginForm");
        exit();
    }

    try {
        // Modified query to also select is_admin
        $stmt = $mysqli->prepare("SELECT user_id, username, passwordhash, is_admin FROM tbl_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $user['passwordhash'])) {
                // Password is correct, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Return success response for AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => true, 'redirect' => $user['is_admin'] ? 'admindash.php' : 'index.php']);
                    exit();
                }
                
                // Regular form submission redirect
                header("Location: " . ($user['is_admin'] ? 'admindash.php' : 'index.php'));
                exit();
            }
        }
        
        // Invalid credentials
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
            exit();
        }
        
        $_SESSION['login_error'] = "Invalid username or password";
        header("Location: index.php#loginForm");
        exit();

    } catch (Exception $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => false, 'error' => 'An error occurred during login']);
            exit();
        }
        
        $_SESSION['login_error'] = "An error occurred during login. Please try again.";
        error_log("Login error: " . $e->getMessage());
        header("Location: index.php#loginForm");
        exit();
    }
}
?>
