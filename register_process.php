<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users

try {
    // Log incoming data (for debugging)
    error_log("Registration attempt - POST data: " . print_r($_POST, true));
    
    // Validate input
    $required_fields = ['username', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate username (at least 3 characters, alphanumeric and underscore only)
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,}$/', $username)) {
        throw new Exception('Username must start with a letter and contain only letters, numbers, and underscores (minimum 3 characters)');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate password strength (at least 8 characters)
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Check for existing user with more detailed error messages
    $stmt = $pdo->prepare("SELECT username, email FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['username'] === $username) {
            throw new Exception('Username is already taken');
        }
        if ($existing['email'] === $email) {
            throw new Exception('Email is already registered');
        }
    }

    // Handle profile picture (if provided)
    $profilePicturePath = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG and PNG are allowed.');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        $uploadDir = 'uploads/profile_pictures/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload profile picture');
        }
        $profilePicturePath = $uploadPath;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO tbl_users (
                email, 
                passwordhash, 
                username, 
                profile_picture, 
                status
            ) VALUES (?, ?, ?, ?, 'active')
        ");
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->execute([
            $email,
            $hashedPassword,
            $username,
            $profilePicturePath
        ]);

        $userId = $pdo->lastInsertId();
        
        // Commit transaction
        $pdo->commit();

        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['profile_picture'] = $profilePicturePath;

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful!',
            'redirect' => 'index.php'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 