<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send charging completion email to user
 * 
 * @param array $booking The booking details array
 * @param string $checkoutType The type of checkout (manual or automatic)
 * @param string $userEmail The user's email address
 * @param string $userName The user's name
 * @return bool Whether the email was sent successfully
 */
function sendChargingCompletionEmail($booking, $checkoutType, $userEmail, $userName) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'evolve1829@gmail.com';
        $mail->Password = 'qgmg ijoz obaw wvth';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('evolve1829@gmail.com', 'EVolve');
        $mail->addAddress($userEmail, $userName);
        
        // Calculate total minutes charged
        $startDateTime = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
        $endDateTime = time();
        $actualDuration = round(($endDateTime - $startDateTime) / 60);
        
        if ($checkoutType == 'automatic') {
            $actualDuration = $booking['duration']; // For automatic checkout, use the full booked duration
        }
        
        // Calculate approximate electricity consumed (example calculation - adjust as needed)
        // Assuming average EV charging rate of 7.2 kW
        $chargingRate = 7.2; // kW
        $energyConsumed = round(($chargingRate * $actualDuration / 60), 2); // in kWh
        
        // Calculate cost (based on booking price)
        $costPerUnit = $booking['price'] ?? 10; // Default to ₹10 if price not available
        $totalCost = round($energyConsumed * $costPerUnit, 2);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'EVolve - Charging Session Completed';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h1 style='color: #4e73df;'>Charging Session Complete</h1>
                </div>
                
                <p>Hello <strong>{$userName}</strong>,</p>
                <p>Your charging session at <strong>{$booking['station_name']}</strong> has been completed.</p>
                
                <div style='background-color: #f8f9fc; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #4e73df;'>Session Details</h3>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($booking['booking_date'])) . "</p>
                    <p><strong>Start Time:</strong> " . date('h:i A', strtotime($booking['booking_time'])) . "</p>
                    <p><strong>Duration:</strong> Approximately {$actualDuration} minutes</p>
                    <p><strong>Approximate Energy:</strong> {$energyConsumed} kWh</p>
                    <p><strong>Cost:</strong> ₹{$totalCost}</p>
                    <p><strong>Checkout Type:</strong> " . ucfirst($checkoutType) . "</p>
                </div>";
                
        if ($checkoutType == 'automatic') {
            $mail->Body .= "
                <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <p style='margin: 0;'><strong>Note:</strong> Your session was automatically completed as it reached the end of the booked duration.</p>
                </div>";
        }
        
        $mail->Body .= "
                <p>Thank you for using EVolve for your charging needs. We hope to serve you again soon!</p>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px;'>
                    <p>This is an automated email from EVolve. Please do not reply to this email.</p>
                    <p>If you have any questions, please contact our customer support.</p>
                </div>
            </div>
        ";
        
        $mail->AltBody = "
            Charging Session Complete
            
            Hello {$userName},
            
            Your charging session at {$booking['station_name']} has been completed.
            
            Session Details:
            Date: " . date('F j, Y', strtotime($booking['booking_date'])) . "
            Start Time: " . date('h:i A', strtotime($booking['booking_time'])) . "
            Duration: Approximately {$actualDuration} minutes
            Approximate Energy: {$energyConsumed} kWh
            Cost: ₹{$totalCost}
            Checkout Type: " . ucfirst($checkoutType) . "
            
            " . ($checkoutType == 'automatic' ? "Note: Your session was automatically completed as it reached the end of the booked duration.\n\n" : "") . "
            
            Thank you for using EVolve for your charging needs. We hope to serve you again soon!
            
            This is an automated email from EVolve. Please do not reply to this email.
            If you have any questions, please contact our customer support.
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
?> 