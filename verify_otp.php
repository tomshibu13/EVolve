<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Set up error logging
$logFile = __DIR__ . '/otp_verification.log';
file_put_contents($logFile, "=== OTP Verification attempt: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

try {
    // Check if we have the necessary session data
    if (!isset($_SESSION['pending_registration']) || !isset($_POST['otp'])) {
        file_put_contents($logFile, "Missing required data\n", FILE_APPEND);
        throw new Exception("Missing required verification data");
    }

    $submittedOtp = $_POST['otp'];
    $storedOtp = $_SESSION['pending_registration']['verification_token'];
    $otpExpiry = $_SESSION['pending_registration']['token_expiry'];
    
    file_put_contents($logFile, "Submitted OTP: $submittedOtp\n", FILE_APPEND);
    file_put_contents($logFile, "Stored OTP: $storedOtp\n", FILE_APPEND);
    file_put_contents($logFile, "OTP Expiry: $otpExpiry\n", FILE_APPEND);

    // Check if OTP has expired
    if (strtotime($otpExpiry) < time()) {
        file_put_contents($logFile, "OTP has expired\n", FILE_APPEND);
        throw new Exception("Verification code has expired. Please request a new one.");
    }

    // Verify OTP
    if ($submittedOtp !== $storedOtp) {
        file_put_contents($logFile, "Invalid OTP\n", FILE_APPEND);
        throw new Exception("Invalid verification code");
    }

    // OTP is valid, proceed with user registration
    $userData = $_SESSION['pending_registration'];
    
    file_put_contents($logFile, "OTP verification successful, proceeding with registration\n", FILE_APPEND);

    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // First, check if a user with this email already exists
        $checkStmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE email = ? OR username = ?");
        $checkStmt->execute([$userData['email'], $userData['username']]);
        $existingUser = $checkStmt->fetch();
        
        if ($existingUser) {
            throw new Exception("User with this email or username already exists");
        }
        
        // Insert the user into the database with status 'active'
        $stmt = $pdo->prepare("INSERT INTO tbl_users (username, email, passwordhash, name, profile_picture, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, 'active', NOW())");
        
        $stmt->execute([
            $userData['username'],
            $userData['email'],
            $userData['passwordhash'],
            $userData['name'],
            $userData['profile_picture']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Log the SQL and parameters for debugging
        file_put_contents($logFile, "SQL: INSERT INTO tbl_users (username, email, passwordhash, name, profile_picture, status, created_at) 
                           VALUES (?, ?, ?, ?, ?, 'active', NOW())\n", FILE_APPEND);
        file_put_contents($logFile, "Params: " . json_encode([
            $userData['username'],
            $userData['email'],
            "PASSWORD_HASH_HIDDEN",
            $userData['name'],
            $userData['profile_picture']
        ]) . "\n", FILE_APPEND);
        
        // Debug log the user ID
        file_put_contents($logFile, "New user ID: $userId\n", FILE_APPEND);
        
        // Commit the transaction
        $pdo->commit();
        
        // Clear the pending registration data
        unset($_SESSION['pending_registration']);
        unset($_SESSION['pending_verification']);
        unset($_SESSION['verify_email']);
        
        // Set the user as logged in
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['name'] = $userData['name'];
        
        file_put_contents($logFile, "User registered successfully with ID: $userId\n", FILE_APPEND);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Verification successful! Your account has been created.',
            'redirect' => 'index.php'
        ]);
        
    } catch (PDOException $e) {
        // Rollback the transaction if something failed
        $pdo->rollBack();
        file_put_contents($logFile, "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
        throw new Exception("Registration failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 