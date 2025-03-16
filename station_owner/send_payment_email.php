<?php
session_start();
require_once '../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

function sendPaymentSuccessEmail($userEmail, $userName, $stationName, $amount, $bookingDate, $bookingTime, $userId) {
    try {
        $mail = new PHPMailer(true);
        
        // Enable debug output to see what's happening
        $mail->SMTPDebug = 2;
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'evolve1829@gmail.com';  // Your confirmed email
        $mail->Password = 'qgmg ijoz obaw wvth';   // Your confirmed password
        $mail->SMTPSecure = 'tls';                 // Changed to string 'tls'
        $mail->Port = 587;

        // Set timeout and other options
        $mail->Timeout = 60;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('evolve1829@gmail.com', 'EVolve');
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Successful - EVolve EV Charging';
        
        // Email template
        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h2 style='margin: 0;'>Payment Successful!</h2>
                </div>
                
                <div style='padding: 20px; background: #f8f9fc; border-radius: 0 0 10px 10px;'>
                    <p>Dear {$userName},</p>
                    
                    <p>Your payment has been successfully processed for your booking at <strong>{$stationName}</strong>.</p>
                    
                    <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <h3 style='color: #4e73df; margin-top: 0; border-bottom: 2px solid #e3e6f0; padding-bottom: 10px;'>Booking Details:</h3>
                        <p><strong>Amount Paid:</strong> ₹" . number_format($amount, 2) . "</p>
                        <p><strong>Booking Date:</strong> " . date('d M Y', strtotime($bookingDate)) . "</p>
                        <p><strong>Booking Time:</strong> " . date('h:i A', strtotime($bookingTime)) . "</p>
                        <p><strong>Station:</strong> {$stationName}</p>
                    </div>
                    
                    <div style='background: #e8f5e9; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                        <p style='color: #2e7d32; margin: 0;'>
                            <strong>Important:</strong> Please show this email at the charging station for verification.
                        </p>
                    </div>
                    
                    <p>Thank you for choosing EVolve for your EV charging needs!</p>
                    
                    <p style='margin-top: 20px;'>
                        If you have any questions, please don't hesitate to contact us.
                    </p>
                    
                    <div style='margin-top: 30px; text-align: center; color: #666; border-top: 1px solid #e3e6f0; padding-top: 20px;'>
                        <small>This is an automated email, please do not reply.</small>
                    </div>
                </div>
            </div>
        ";
        
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailBody));

        $mail->send();
        
        // Insert into notifications table
        global $pdo;
        $notificationTitle = "Payment Successful";
        $notificationMessage = "Your payment of ₹{$amount} for booking at {$stationName} has been processed successfully.";
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, 'booking')
        ");
        $stmt->execute([$userId, $notificationTitle, $notificationMessage]);

        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $e->getMessage()
        ];
    }
}

// Modify the API endpoint to show more detailed errors
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Debug: Log the incoming data
        error_log("Received payment email request: " . print_r($data, true));
        
        // Validate required parameters
        $requiredParams = ['userEmail', 'userName', 'stationName', 'amount', 'bookingDate', 'bookingTime', 'userId'];
        foreach ($requiredParams as $param) {
            if (!isset($data[$param])) {
                throw new Exception("Missing required parameter: {$param}");
            }
        }
        
        $result = sendPaymentSuccessEmail(
            $data['userEmail'],
            $data['userName'],
            $data['stationName'],
            $data['amount'],
            $data['bookingDate'],
            $data['bookingTime'],
            $data['userId']
        );
        
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'debug_info' => [
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    }
}
?> 