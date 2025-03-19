<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    if (empty($email)) {
        throw new Exception('Email is required');
    }

    // Generate OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store OTP in session
    $_SESSION['verification_code'] = $otp;
    $_SESSION['email'] = $email;
    $_SESSION['otp_timestamp'] = time();

    // Send OTP email
    if (sendOtpEmail($email, $otp)) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully'
        ]);
    } else {
        throw new Exception('Failed to send OTP');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function sendOtpEmail($email, $otp) {
    require 'PHPMailer-master/src/Exception.php';
    require 'PHPMailer-master/src/PHPMailer.php';
    require 'PHPMailer-master/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Enable verbose debug output for troubleshooting
        $mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'evolve1829@gmail.com';
        $mail->Password = 'qgmg ijoz obaw wvth';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Add SSL options to handle strict server requirements
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

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
        // Log successful email sending
        error_log("Verification email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        error_log("Full error details: " . $e->getMessage());
        return false;
    }
}
?> 