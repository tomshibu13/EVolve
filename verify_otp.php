<?php
session_start();
require_once 'config.php';

// Check if user is trying to verify
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
    // Redirect to registration page with error message
    $_SESSION['error'] = "Please register first to verify your email.";
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$userEmail = isset($_SESSION['temp_email']) ? htmlspecialchars($_SESSION['temp_email']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $otp = $_POST['otp'];
        $userId = $_SESSION['temp_user_id'];
        
        // Verify OTP
        $stmt = $pdo->prepare("SELECT otp, otp_expiration FROM tbl_users WHERE user_id = ? AND is_verified = 0");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($user['otp'] === $otp) {
                if (strtotime($user['otp_expiration']) >= time()) {
                    // OTP is valid and not expired
                    $stmt = $pdo->prepare("
                        UPDATE tbl_users 
                        SET is_verified = 1, 
                            otp = NULL, 
                            otp_expiration = NULL,
                            status = 'active'
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$userId]);
                    
                    // Set success message
                    $success = "Email verified successfully!";
                    
                    // Clear temporary session data
                    unset($_SESSION['temp_user_id']);
                    unset($_SESSION['temp_email']);
                    
                    // Set login success message
                    $_SESSION['login_success'] = "Registration successful! You can now log in.";
                    
                    // Redirect to login page after 2 seconds
                    header("refresh:2;url=index.php");
                } else {
                    $error = "OTP has expired. Please request a new one.";
                }
            } else {
                $error = "Invalid OTP. Please try again.";
            }
        } else {
            $error = "User not found or already verified.";
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again.";
        error_log($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - EVolve</title>
    <link rel="stylesheet" href="main.css">
    <style>
        .otp-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .otp-input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .verify-btn {
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .verify-btn:hover {
            background: #2980b9;
        }
        
        .error {
            color: #e74c3c;
            margin: 10px 0;
        }
        
        .success {
            color: #2ecc71;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <h2>Verify Your Email</h2>
        <?php if ($userEmail): ?>
            <p>Please enter the verification code sent to <?php echo $userEmail; ?></p>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <input type="text" name="otp" class="otp-input" placeholder="Enter verification code" required>
                <button type="submit" class="verify-btn">Verify</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 