<?php
// Remove session_start since it's already started in the parent file
// session_start();

// Fix the config path using __DIR__ for absolute path
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fix the PHPMailer paths using __DIR__ for absolute paths
require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

// The QR code libraries are no longer needed
// require_once __DIR__ . '/../vendor/autoload.php';
// use Endroid\QrCode\QrCode;
// use Endroid\QrCode\Writer\PngWriter;

function getStationIdByName($stationName) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT station_id FROM charging_stations WHERE name = ?");
        $stmt->execute([$stationName]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['station_id'];
        } else {
            throw new Exception("Station not found: " . $stationName);
        }
    } catch (Exception $e) {
        error_log("Failed to get station ID: " . $e->getMessage());
        throw $e;
    }
}

function sendPaymentSuccessEmail($userEmail, $userName, $stationName, $amount, $bookingDate, $bookingTime, $userId, $bookingId = null) {
    global $pdo;
    try {
        // Get booking ID if not provided
        if ($bookingId === null) {
            $stmt = $pdo->prepare("
                SELECT booking_id FROM bookings 
                WHERE user_id = ? AND station_id = (
                    SELECT station_id FROM charging_stations WHERE name = ?
                )
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId, $stationName]);
            $result = $stmt->fetch();
            if ($result) {
                $bookingId = $result['booking_id'];
            } else {
                $bookingId = 0;
            }
        }
        
        // Create URLs
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $viewQrCodeUrl = 'http://' . $host . '/Project/view-qr.php?id=' . $bookingId . '&user=' . $userId;
        
        // Remove reference to generating QR code
        // $qrCodePath = generateBookingQrCode($bookingId, $userId, $stationId);
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'evolve1829@gmail.com';
        $mail->Password = 'qgmg ijoz obaw wvth';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Timeout = 60;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('evolve1829@gmail.com', 'EVolve');
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - EVolve EV Charging';
        
        // ULTRA SIMPLE EMAIL TEMPLATE - NO IMAGES, NO FANCY FORMATTING
        $emailBody = "
            <html>
            <body>
                <h2>Booking Confirmation</h2>
                <p>Dear {$userName},</p>
                <p>Your payment has been successfully processed for your booking at <strong>{$stationName}</strong>.</p>
                
                <h3>Booking Details:</h3>
                <p>Booking ID: {$bookingId}</p>
                <p>Amount Paid: ₹" . number_format($amount, 2) . "</p>
                <p>Booking Date: " . date('d M Y', strtotime($bookingDate)) . "</p>
                <p>Booking Time: " . date('h:i A', strtotime($bookingTime)) . "</p>
                <p>Station: {$stationName}</p>
                
                <p><strong>Important:</strong> You'll need to scan a QR code at the charging station for both check-in and check-out.</p>
                
                <p>To view your QR code for check-in and check-out at the station, please visit this link:</p>
                <p><a href='{$viewQrCodeUrl}'>Click here to view your QR code</a></p>
                
                <p>Thank you for choosing EVolve for your EV charging needs!</p>
            </body>
            </html>
        ";
        
        $mail->Body = $emailBody;
        $mail->AltBody = "Your booking at {$stationName} has been confirmed. Booking ID: {$bookingId}. Amount: ₹" . number_format($amount, 2) . ". Date: " . date('d M Y', strtotime($bookingDate)) . ". Time: " . date('h:i A', strtotime($bookingTime)) . ". To view your QR code for check-in and check-out, please visit: {$viewQrCodeUrl}";

        error_log("Using NEW minimal email template with NO QR images");
        error_log("Attempting to send email to: $userEmail");
        $mail->send();
        error_log("Email sent successfully to: $userEmail");

        // Insert notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, 'booking')
        ");
        $stmt->execute([
            $userId,
            "Booking Confirmed",
            "Your booking at $stationName has been confirmed. Amount paid: ₹$amount"
        ]);

        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
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
            $data['userId'],
            $data['bookingId'] ?? null
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

// Create an endpoint for QR code verification
function verifyBookingQrCode($qrData) {
    global $pdo;
    try {
        // Decode QR data
        $data = json_decode($qrData, true);
        
        // Verify required fields
        if (!isset($data['booking_id']) || !isset($data['user_id']) || !isset($data['station_id'])) {
            return [
                'success' => false,
                'message' => 'Invalid QR code format'
            ];
        }
        
        // Check if booking exists and is valid
        $stmt = $pdo->prepare("
            SELECT b.*, cs.name as station_name 
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            WHERE b.booking_id = ? AND b.user_id = ? AND b.station_id = ?
        ");
        $stmt->execute([$data['booking_id'], $data['user_id'], $data['station_id']]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'Booking not found'
            ];
        }
        
        // Return booking details
        return [
            'success' => true,
            'message' => 'Valid booking',
            'booking' => [
                'id' => $booking['booking_id'],
                'user_id' => $booking['user_id'],
                'station_id' => $booking['station_id'],
                'station_name' => $booking['station_name'],
                'date' => $booking['booking_date'],
                'time' => $booking['booking_time'],
                'status' => $booking['status']
            ]
        ];
    } catch (Exception $e) {
        error_log("QR verification failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// API endpoint for QR code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'verify-qr') {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['qrData'])) {
            throw new Exception("Missing QR data");
        }
        
        $result = verifyBookingQrCode($data['qrData']);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>