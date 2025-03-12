<?php
session_start();
require_once 'config.php'; // Include your database configuration
require_once 'vendor/autoload.php'; // Add this line to include Google Client Library

// Your Google OAuth credentials
$client_id = "767546662883-n1srtf3ane5krtkm89okulrq4fr12ekq.apps.googleusercontent.com";
$client_secret = "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";
$redirect_uri = "http://localhost/Project/callback.php";

// Initialize the Google Client
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope('email');
$client->addScope('profile');

try {
    if (isset($_GET['code'])) {
        // Get token from code
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        // Get user info
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $picture = $google_account_info->picture ?? '';

        // Debug log
        error_log("Google User Info - Email: $email, Name: $name");

        // Connect to database
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Generate a random password hash (required field)
        $random_password = bin2hex(random_bytes(16));
        $password_hash = password_hash($random_password, PASSWORD_DEFAULT);

        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, email, name, username FROM tbl_users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists - update their information
            error_log("Updating existing user: $email");
            $user = $result->fetch_assoc();
            $update_stmt = $conn->prepare("UPDATE tbl_users SET 
                name = ?,
                profile_picture = ?,
                updated_at = CURRENT_TIMESTAMP,
                status = 'active'
                WHERE email = ?");
            
            if (!$update_stmt) {
                throw new Exception("Update prepare failed: " . $conn->error);
            }

            $update_stmt->bind_param("sss", $name, $picture, $email);
            if (!$update_stmt->execute()) {
                throw new Exception("Update execute failed: " . $update_stmt->error);
            }
            
            $user_id = $user['user_id'];
            $username = $user['username'] ?? null;
            $update_stmt->close();
        } else {
            // Generate username (required field)
            $base_username = strtolower(str_replace(' ', '', $name));
            $username = $base_username;
            $counter = 1;

            // Check for username uniqueness
            while (true) {
                $check_stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE username = ?");
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows === 0) {
                    break;
                }
                $username = $base_username . $counter;
                $counter++;
            }
            $check_stmt->close();

            // New user - insert into database with all required fields
            error_log("Creating new user: $email with username: $username");
            
            $insert_stmt = $conn->prepare("INSERT INTO tbl_users 
                (email, passwordhash, name, username, profile_picture, status, is_admin, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', FALSE, CURRENT_TIMESTAMP)");
            
            if (!$insert_stmt) {
                throw new Exception("Insert prepare failed: " . $conn->error);
            }

            $insert_stmt->bind_param("sssss", 
                $email,
                $password_hash,
                $name,
                $username,
                $picture
            );

            if (!$insert_stmt->execute()) {
                error_log("Insert Error: " . $insert_stmt->error);
                throw new Exception("Insert execute failed: " . $insert_stmt->error);
            }
            
            $user_id = $conn->insert_id;
            error_log("New user created with ID: $user_id");
            $insert_stmt->close();
        }

        // Store user info in session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user'] = [
            'email' => $email,
            'name' => $name,
            'picture' => $picture,
            'username' => $username ?? null
        ];

        error_log("Session stored for user: $user_id");

        // Close database connection
        $stmt->close();
        $conn->close();

        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();

    } else {
        // Generate login URL
        $auth_url = $client->createAuthUrl();
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        exit();
    }

} catch (Exception $e) {
    error_log('Google Auth Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Authentication failed: ' . $e->getMessage()));
    exit();
}
?> 