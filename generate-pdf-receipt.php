<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the configuration file
require_once __DIR__ . '/config.php';

// Include required libraries for PDF generation
require_once __DIR__ . '/vendor/autoload.php';
use Mpdf\Mpdf;

/**
 * Generate a PDF receipt for a booking
 * 
 * @param int $bookingId The booking ID
 * @return string|false Path to the generated PDF file or false on failure
 */
function generatePdfReceipt($bookingId) {
    global $pdo;
    
    try {
        // Get booking details with all necessary information
        $stmt = $pdo->prepare("
            SELECT b.*, cs.name as station_name, cs.address as station_address, 
                   u.name as user_name, u.email, u.phone
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            error_log("Booking not found for receipt generation: " . $bookingId);
            return false;
        }
        
        // Format date and time
        $formattedDate = date('F j, Y', strtotime($booking['booking_date']));
        $formattedTime = date('h:i A', strtotime($booking['booking_time']));
        
        // Generate QR code data for easy check-in/check-out
        $qrData = json_encode([
            'booking_id' => $booking['booking_id'],
            'user_id' => $booking['user_id'],
            'station_id' => $booking['station_id'],
            'timestamp' => time()
        ]);
        
        // Generate QR code using a temporary file
        $qrTempFile = sys_get_temp_dir() . '/qrcode_' . $bookingId . '.png';
        
        // Create QR code using different available methods
        $qrCodeImgTag = '';
        if (class_exists('QRcode')) {
            // Using PHPQRCode library
            require_once __DIR__ . '/phpqrcode/qrlib.php';
            \QRcode::png($qrData, $qrTempFile, QR_ECLEVEL_H, 10);
            
            // Convert to base64 for embedding
            $qrImageData = base64_encode(file_get_contents($qrTempFile));
            $qrCodeImgTag = '<img src="data:image/png;base64,' . $qrImageData . '" alt="QR Code" style="width: 150px; height: 150px;">';
            
            // Clean up
            @unlink($qrTempFile);
        } else {
            // Fallback to online QR code generator
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrData);
            $qrImageData = base64_encode(file_get_contents($qrCodeUrl));
            $qrCodeImgTag = '<img src="data:image/png;base64,' . $qrImageData . '" alt="QR Code" style="width: 150px; height: 150px;">';
        }
        
        // Create a more professional HTML receipt with embedded QR code
        $htmlContent = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>EVolve Booking Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 0; color: #333; }
                    .receipt { max-width: 800px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
                    .logo { font-size: 24px; font-weight: bold; color: #4CAF50; }
                    .title { font-size: 20px; margin: 10px 0; }
                    .receipt-id { color: #777; }
                    .details { margin-bottom: 30px; }
                    .section { margin-bottom: 20px; }
                    .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
                    .row { display: flex; margin-bottom: 5px; }
                    .label { width: 40%; font-weight: bold; }
                    .value { width: 60%; }
                    .total { font-weight: bold; margin-top: 20px; font-size: 16px; border-top: 1px solid #eee; padding-top: 10px; }
                    .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
                    .qr-section { text-align: center; margin: 20px 0; }
                    .qr-note { font-size: 12px; color: #777; margin-top: 10px; }
                    .thank-you { text-align: center; font-weight: bold; margin: 20px 0; font-size: 16px; color: #4CAF50; }
                </style>
            </head>
            <body>
                <div class='receipt'>
                    <div class='header'>
                        <div class='logo'>EVolve</div>
                        <div class='title'>Booking Receipt</div>
                        <div class='receipt-id'>Receipt #" . $booking['booking_id'] . "</div>
                        <div>Date: " . date('Y-m-d H:i:s') . "</div>
                    </div>
                    
                    <div class='details'>
                        <div class='section'>
                            <div class='section-title'>Customer Information</div>
                            <div class='row'><div class='label'>Name:</div><div class='value'>" . htmlspecialchars($booking['user_name']) . "</div></div>
                            <div class='row'><div class='label'>Email:</div><div class='value'>" . htmlspecialchars($booking['email']) . "</div></div>
                            <div class='row'><div class='label'>Phone:</div><div class='value'>" . htmlspecialchars($booking['phone'] ?? 'N/A') . "</div></div>
                        </div>
                        
                        <div class='section'>
                            <div class='section-title'>Booking Details</div>
                            <div class='row'><div class='label'>Booking ID:</div><div class='value'>" . htmlspecialchars($booking['booking_id']) . "</div></div>
                            <div class='row'><div class='label'>Station:</div><div class='value'>" . htmlspecialchars($booking['station_name']) . "</div></div>
                            <div class='row'><div class='label'>Address:</div><div class='value'>" . htmlspecialchars($booking['station_address'] ?? 'N/A') . "</div></div>
                            <div class='row'><div class='label'>Date:</div><div class='value'>" . htmlspecialchars($formattedDate) . "</div></div>
                            <div class='row'><div class='label'>Time:</div><div class='value'>" . htmlspecialchars($formattedTime) . "</div></div>
                            <div class='row'><div class='label'>Duration:</div><div class='value'>" . htmlspecialchars($booking['duration'] ?? '1 hour') . "</div></div>
                            <div class='row'><div class='label'>Status:</div><div class='value'>" . htmlspecialchars($booking['status'] ?? 'Confirmed') . "</div></div>
                        </div>
                        
                        <div class='section'>
                            <div class='section-title'>Payment Information</div>
                            <div class='row'><div class='label'>Amount:</div><div class='value'>₹" . htmlspecialchars(number_format($booking['amount'], 2)) . "</div></div>
                            <div class='row'><div class='label'>Payment Method:</div><div class='value'>" . htmlspecialchars($booking['payment_method'] ?? 'Online Payment') . "</div></div>
                            <div class='row'><div class='label'>Transaction ID:</div><div class='value'>" . htmlspecialchars($booking['transaction_id'] ?? 'N/A') . "</div></div>
                            
                            <div class='total'>Total Paid: ₹" . htmlspecialchars(number_format($booking['amount'], 2)) . "</div>
                        </div>
                    </div>
                    
                    <div class='qr-section'>
                        <div class='section-title'>Check-in QR Code</div>
                        " . $qrCodeImgTag . "
                        <div class='qr-note'>Please scan this QR code at the charging station for check-in and check-out</div>
                    </div>
                    
                    <div class='thank-you'>Thank you for choosing EVolve for your EV charging needs!</div>
                    
                    <div class='footer'>
                        <p>For any questions or assistance, please contact us at support@evolve.com</p>
                        <p>© " . date('Y') . " EVolve. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Create a temporary file for the HTML content
        $tempFile = sys_get_temp_dir() . '/receipt_' . $bookingId . '_' . time() . '.html';
        file_put_contents($tempFile, $htmlContent);
        
        // Try to convert to PDF using mPDF if available
        if (class_exists('Mpdf\Mpdf')) {
            try {
                $mpdf = new Mpdf([
                    'tempDir' => __DIR__ . '/temp',
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 15,
                    'margin_bottom' => 15,
                ]);
                $mpdf->WriteHTML($htmlContent);
                $pdfFile = sys_get_temp_dir() . '/receipt_' . $bookingId . '_' . time() . '.pdf';
                $mpdf->Output($pdfFile, 'F');
                
                // Clean up HTML file if PDF was successfully created
                if (file_exists($pdfFile)) {
                    @unlink($tempFile);
                    return $pdfFile;
                }
            } catch (Exception $e) {
                error_log("mPDF conversion failed, falling back to HTML: " . $e->getMessage());
            }
        }
        
        // Return HTML file path if PDF conversion failed or mPDF is not available
        return $tempFile;
    } catch (Exception $e) {
        error_log("Error generating receipt: " . $e->getMessage());
        return false;
    }
}

// If this file is accessed directly, check for a test parameter
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    if (isset($_GET['test']) && isset($_GET['booking_id'])) {
        $bookingId = intval($_GET['booking_id']);
        $receiptPath = generatePdfReceipt($bookingId);
        
        if ($receiptPath) {
            $fileType = (pathinfo($receiptPath, PATHINFO_EXTENSION) == 'pdf') ? 'application/pdf' : 'text/html';
            
            header('Content-Type: ' . $fileType);
            header('Content-Disposition: inline; filename="receipt_' . $bookingId . '.' . pathinfo($receiptPath, PATHINFO_EXTENSION) . '"');
            readfile($receiptPath);
            
            // Clean up the file
            @unlink($receiptPath);
            exit;
        } else {
            echo "Failed to generate receipt for booking ID: " . $bookingId;
        }
    }
}
?>