<?php
session_start();
require_once 'config.php'; // Make sure this points to your database configuration file

// Your Google OAuth credentials
$client_id = "767546662883-n1srtf3ane5krtkm89okulrq4fr12ekq.apps.googleusercontent.com";
$client_secret = "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";
$redirect_uri = "http://localhost/Project/callback.php";

if (isset($_GET['code'])) {
    try {
        // Exchange authorization code for access token
        $token_url = "https://oauth2.googleapis.com/token";
        
        $post_data = [
            "code" => $_GET['code'],
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri" => $redirect_uri,
            "grant_type" => "authorization_code"
        ];

        $ch = curl_init($token_url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);

        $token_data = json_decode($response, true);
        if (!isset($token_data['access_token'])) {
            throw new Exception('Error getting access token: ' . print_r($token_data, true));
        }

        // Get user information
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($user_info_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token_data['access_token']]
        ]);
        
        $user_info_response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);

        $user_info = json_decode($user_info_response, true);
        if (!isset($user_info['email'])) {
            throw new Exception('Error getting user info: ' . print_r($user_info, true));
        }

        // Connect to database
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, email, name FROM tbl_users WHERE email = ?");
        $stmt->bind_param("s", $user_info['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists - update their information
            $user = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE tbl_users SET 
                name = ?,
                profile_picture = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE email = ?");
            $stmt->bind_param("sss", 
                $user_info['name'],
                $user_info['picture'],
                $user_info['email']
            );
            $stmt->execute();
            $user_id = $user['user_id'];
        } else {
            // New user - insert into database
            $username = strtolower(str_replace(' ', '', $user_info['name'])) . rand(100, 999);
            $stmt = $conn->prepare("INSERT INTO tbl_users 
                (email, passwordhash, name, username, profile_picture, status) 
                VALUES (?, '', ?, ?, ?, 'active')");
            $stmt->bind_param("ssss", 
                $user_info['email'],
                $user_info['name'],
                $username,
                $user_info['picture']
            );
            $stmt->execute();
            $user_id = $conn->insert_id;
        }

        // Store user info in session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user'] = [
            'email' => $user_info['email'],
            'name' => $user_info['name'],
            'picture' => $user_info['picture'],
            'username' => $username ?? null
        ];

        // Close database connection
        $stmt->close();
        $conn->close();

        // Redirect to dashboard
        header('Location: dashboard.php');
        exit();

    } catch (Exception $e) {
        // Log error and redirect to login page with error message
        error_log("Google OAuth Error: " . $e->getMessage());
        header('Location: index.php?error=' . urlencode('Authentication failed. Please try again.'));
        exit();
    }
}

// If no authorization code is present, redirect to login page
header('Location: index.php');
exit();
?>