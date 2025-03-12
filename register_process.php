<?php
session_start();
require_once 'config.php';
require 'vendor/autoload.php'; // For PHPMailer

header('Content-Type: application/json');
ini_set('display_errors', 0);

try {
    $required_fields = ['username', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,}$/', $username)) {
        throw new Exception('Username must start with a letter and contain only letters, numbers, and underscores (minimum 3 characters)');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    $stmt = $pdo->prepare("SELECT username, email FROM tbl_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        if ($existing['username'] === $username) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Username is already taken'
            ]);
            exit();
        }
        if ($existing['email'] === $email) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Email is already registered'
            ]);
            exit();
        }
    }

    $profilePicturePath = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024;

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

    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $otpExpiration = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO tbl_users (
                email, 
                passwordhash, 
                username,
                profile_picture,
                status,
                otp,
                otp_expiration,
                is_verified
            ) VALUES (?, ?, ?, ?, 'pending', ?, ?, 0)
        ");
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->execute([
            $email,
            $hashedPassword,
            $username,
            $profilePicturePath,
            $otp,
            $otpExpiration
        ]);

        $userId = $pdo->lastInsertId();

        if (sendOtpEmail($email, $otp)) {
            $pdo->commit();
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['temp_user_id'] = $userId;
            $_SESSION['temp_email'] = $email;

            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Please check your email for verification code.',
                'redirect' => 'verify_otp.php'
            ]);
        } else {
            throw new Exception('Failed to send verification email');
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'evolve1829@gmail.com';
        $mail->Password = 'qgmg ijoz obaw wvth';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('evolve1829@gmail.com', 'EVolve');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Your EVolve Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2>Welcome to EVolve!</h2>
                <p>Your verification code is: <strong style='font-size: 24px;'>{$otp}</strong></p>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
            </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?> 