<?php
session_start();
require_once 'config.php';
require 'vendor/autoload.php'; // For PHPMailer

// Set proper headers for JSON response
header('Content-Type: application/json');

// Temporarily enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log inputs for debugging
error_log("Register process started");
error_log("POST data: " . print_r($_POST, true));

try {
    // Verify required fields
    $required_fields = ['username', 'email', 'password', 'confirm_password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $name = $username; // Use username as name if not provided
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        throw new Exception("Passwords do not match");
    }

    // Validate username format
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,}$/', $username)) {
        throw new Exception('Username must start with a letter and contain only letters, numbers, and underscores (minimum 3 characters)');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate password length
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Check if username or email already exists
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

    // Generate OTP for verification
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $otpExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store user data in session instead of database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Store registration data in session
    $_SESSION['pending_registration'] = [
        'email' => $email,
        'passwordhash' => $hashedPassword,
        'username' => $username,
        'name' => $name,
        'profile_picture' => null,
        'verification_token' => $otp,
        'token_expiry' => $otpExpiry
    ];
    
    $_SESSION['pending_verification'] = true;
    $_SESSION['verify_email'] = $email;
    
    // Send verification email
    if (sendOtpEmail($email, $otp)) {
        // Return success response with absolute URL for redirect
        echo json_encode([
            'success' => true,
            'message' => 'Please verify your email address with the code we sent you.',
            'redirect' => 'index.php?verify=true'
        ]);
        exit;
    } else {
        throw new Exception('Failed to send verification email. Please try again.');
    }

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// Email sending function
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 2;                      // Enable verbose debug output (2 = client and server messages)
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // SMTP server
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = 'evolve1829@gmail.com'; // SMTP username
        $mail->Password   = 'qgmg ijoz obaw wvth'; // SMTP password - consider using environment variables
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587;                   // TCP port to connect to
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom('evolve1829@gmail.com', 'EVolve');
        $mail->addAddress($email);                 // Add a recipient
        
        // Content
        $mail->isHTML(true);                       // Set email format to HTML
        $mail->Subject = 'Your EVolve Verification Code';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2>Welcome to EVolve!</h2>
                <p>Your verification code is: <strong style='font-size: 24px;'>{$otp}</strong></p>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
            </div>";
        
        $mail->send();
        error_log("Verification email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        error_log("Full error details: " . $e->getMessage());
        return false;
    }
}
?> 