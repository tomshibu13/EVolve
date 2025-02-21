<?php
session_start();
require_once 'config.php'; // Add this line to include the database connection

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

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
                $_SESSION['login_success'] = "Welcome back, " . $user['username'] . "!";
                
                // Redirect based on is_admin flag
                if ($user['is_admin']) {
                    header("Location: admindash.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid username or password";
                header("Location: index.php#loginForm");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid username or password";
            header("Location: index.php#loginForm");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = "An error occurred during login. Please try again.";
        error_log("Login error: " . $e->getMessage());
        header("Location: index.php#loginForm");
        exit();
    }
}
?>
