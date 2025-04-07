<?php
session_start();
require 'vendor/autoload.php';
use Razorpay\Api\Api;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

$key_id = "rzp_test_R6h0atxxQ4WsUU";  // Your Razorpay Key ID
$key_secret = "5CyNCDCaDKmrRqPWX2K6uLGV";  // Your Razorpay Key Secret
$api = new Api($key_id, $key_secret);

// Function to generate QR code without using view-qr.php
function generateQRCode($data, $filename) {
    if (file_exists('phpqrcode/qrlib.php')) {
        require_once 'phpqrcode/qrlib.php';
        QRcode::png($data, $filename);
    } else {
        // Fallback to QR Server API
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $qrImage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($qrImage !== false && $httpCode == 200) {
            // Ensure the directory exists
            $dir = dirname($filename);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($filename, $qrImage);
            return true;
        }
    }
    return false;
}

// Get the Razorpay payment details from POST
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';

try {
    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_signature' => $razorpay_signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Get booking details from database
    $stmt = $pdo->prepare("
        SELECT b.*, 
               cs.name as station_name,
               cs.address as station_address
        FROM bookings b
        JOIN charging_stations cs ON b.station_id = cs.station_id
        WHERE b.razorpay_order_id = ?
    ");
    $stmt->execute([$razorpay_order_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET payment_status = 'completed',
                status = 'confirmed',
                razorpay_payment_id = ?,
                updated_at = NOW()
            WHERE razorpay_order_id = ?
        ");
        $stmt->execute([$razorpay_payment_id, $razorpay_order_id]);

        // Generate QR code data
        $qrData = json_encode([
            'booking_id' => $booking['booking_id'],
            'user_id' => $booking['user_id'],
            'station_id' => $booking['station_id']
        ]);

        // Create directory if it doesn't exist
        if (!file_exists('qrcodes')) {
            mkdir('qrcodes', 0777, true);
        }

        // Generate QR code
        $qrImageFile = 'qrcodes/booking_' . $booking['booking_id'] . '.png';
        generateQRCode($qrData, $qrImageFile);

        // Generate PDF
        require_once 'fpdf/fpdf.php';

        class PDF extends FPDF {
            function Header() {
                $this->SetFont('Arial', 'B', 20);
                $this->Cell(0, 10, 'EVolve Charging - Booking Confirmation', 0, 1, 'C');
                $this->Ln(10);
            }
        }

        $pdf = new PDF();
        $pdf->AddPage();
        
        // Add QR Code
        if (file_exists($qrImageFile)) {
            $pdf->Image($qrImageFile, 75, 50, 60);
        }
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Ln(80); // Space after QR code

        // Add booking details
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Booking Details:', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        
        $details = [
            'Booking ID' => $booking['booking_id'],
            'Station' => $booking['station_name'],
            'Station Address' => $booking['station_address'],
            'Date' => date('d M Y', strtotime($booking['booking_date'])),
            'Time' => date('h:i A', strtotime($booking['booking_time'])),
            'Duration' => $booking['duration'] . ' minutes',
            'Amount Paid' => '₹' . number_format($booking['amount'], 2),
            'Payment ID' => $razorpay_payment_id,
            'Status' => 'Confirmed'
        ];

        foreach ($details as $key => $value) {
            $pdf->Cell(60, 10, $key . ':', 0);
            $pdf->Cell(0, 10, $value, 0, 1);
        }

        $pdfFile = 'bookings/booking_' . $booking['booking_id'] . '.pdf';
        
        // Create bookings directory if it doesn't exist
        if (!file_exists('bookings')) {
            mkdir('bookings', 0777, true);
        }
        
        $pdf->Output('F', $pdfFile);

        // Send email with PDF attachment
        $mail = new PHPMailer(true);

        try {
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
            $mail->setFrom('evolve1829@gmail.com', 'EVolve Charging');
            $mail->addAddress($_SESSION['email'], $_SESSION['name']);

            // Attach PDF
            if (file_exists($pdfFile)) {
                $mail->addAttachment($pdfFile, 'EVolve_Booking_' . $booking['booking_id'] . '.pdf');
            }

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Booking Confirmation - EVolve Charging #' . $booking['booking_id'];
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #4CAF50;'>Booking Confirmed!</h2>
                    <p>Dear {$_SESSION['name']},</p>
                    <p>Your booking has been confirmed and payment has been received successfully.</p>
                    
                    <div style='background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;'>
                        <h3 style='margin-top: 0;'>Booking Details:</h3>
                        <p><strong>Booking ID:</strong> {$booking['booking_id']}</p>
                        <p><strong>Station:</strong> {$booking['station_name']}</p>
                        <p><strong>Address:</strong> {$booking['station_address']}</p>
                        <p><strong>Date:</strong> " . date('d M Y', strtotime($booking['booking_date'])) . "</p>
                        <p><strong>Time:</strong> " . date('h:i A', strtotime($booking['booking_time'])) . "</p>
                        <p><strong>Duration:</strong> {$booking['duration']} minutes</p>
                        <p><strong>Amount Paid:</strong> ₹" . number_format($booking['amount'], 2) . "</p>
                        <p><strong>Payment ID:</strong> {$razorpay_payment_id}</p>
                    </div>
                    
                    <p>Please find your booking confirmation and QR code in the attached PDF.</p>
                    <p>You will need to scan this QR code at the station for check-in.</p>
                    <p>Thank you for choosing EVolve for your EV charging needs!</p>
                </div>
            ";

            $mail->send();

            // Clean up files
            if (file_exists($qrImageFile)) @unlink($qrImageFile);
            if (file_exists($pdfFile)) @unlink($pdfFile);

            header("Location: my-bookings.php?status=success&payment=completed");
            exit();

        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            if (file_exists($qrImageFile)) @unlink($qrImageFile);
            if (file_exists($pdfFile)) @unlink($pdfFile);
            header("Location: my-bookings.php?status=success&payment=completed&email=failed");
            exit();
        }
    } else {
        throw new Exception("Booking not found");
    }

} catch (Exception $e) {
    error_log("Payment verification failed: " . $e->getMessage());
    header("Location: my-bookings.php?status=failed&error=" . urlencode($e->getMessage()));
    exit();
}

// Fetch payment details
$stmt = $conn->prepare("SELECT 
    b.*, 
    u.name as customer_name,
    u.email as customer_email
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    WHERE b.payment_status = 'completed'
    ORDER BY b.payment_date DESC");
$stmt->execute();
$result = $stmt->get_result();

// Display payment details in a table
?>
<div class="payment-details">
    <h2>Payment Details</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Method</th>
                <th>Payment ID</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td>₹<?php echo htmlspecialchars(number_format($row['payment_amount'], 2)); ?></td>
                    <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($row['payment_date']))); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($row['razorpay_payment_id']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<style>
.payment-details {
    margin: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.payment-details table {
    width: 100%;
    border-collapse: collapse;
}

.payment-details th,
.payment-details td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.payment-details th {
    background-color: #f5f5f5;
}
</style>
