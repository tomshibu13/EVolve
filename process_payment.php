<?php
session_start();
require_once 'config.php';
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Process payment and send QR code PDF to the user
 * 
 * @param int $bookingId The booking ID
 * @param float $amount Payment amount
 * @param string $paymentMethod Method of payment (card, upi, etc.)
 * @param string $transactionId Transaction reference ID
 * @return array Result with success status and message
 */
function processPaymentAndSendQR($bookingId, $amount, $paymentMethod, $transactionId) {
    global $pdo;
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Verify booking exists
        $stmt = $pdo->prepare("
            SELECT b.*, cs.name as station_name, u.email, u.name as username 
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Booking not found'
            ];
        }
        
        // Record payment
        $stmt = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, payment_method, transaction_id, payment_date, status)
            VALUES (?, ?, ?, ?, NOW(), 'completed')
        ");
        $stmt->execute([$bookingId, $amount, $paymentMethod, $transactionId]);
        $paymentId = $pdo->lastInsertId();
        
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'paid', payment_id = ? WHERE booking_id = ?");
        $stmt->execute([$paymentId, $bookingId]);
        
        // Commit transaction
        $pdo->commit();
        
        // Generate QR code for check-in
        $qrDir = __DIR__ . '/qrcodes';
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0777, true);
        }
        
        $qrData = json_encode([
            'booking_id' => $bookingId,
            'user_id' => $booking['user_id'],
            'station_id' => $booking['station_id'],
            'payment_id' => $paymentId
        ]);
        
        // Generate QR code file
        $qrImageFile = $qrDir . '/payment_' . $paymentId . '.jpg';
        
        // Use available QR code library
        if (file_exists(__DIR__ . '/phpqrcode/qrlib.php')) {
            require_once __DIR__ . '/phpqrcode/qrlib.php';
            
            // Generate temp PNG then convert to JPG
            $tempPng = $qrDir . '/temp_' . $paymentId . '.png';
            \QRcode::png($qrData, $tempPng, QR_ECLEVEL_H, 10);
            
            // Convert PNG to JPG
            if (function_exists('imagecreatefrompng')) {
                $pngImage = imagecreatefrompng($tempPng);
                $jpgImage = imagecreatetruecolor(imagesx($pngImage), imagesy($pngImage));
                
                // White background
                $white = imagecolorallocate($jpgImage, 255, 255, 255);
                imagefilledrectangle($jpgImage, 0, 0, imagesx($pngImage), imagesy($pngImage), $white);
                
                // Copy PNG to JPG
                imagecopy($jpgImage, $pngImage, 0, 0, 0, 0, imagesx($pngImage), imagesy($pngImage));
                imagejpeg($jpgImage, $qrImageFile, 100);
                
                // Clean up
                imagedestroy($pngImage);
                imagedestroy($jpgImage);
                @unlink($tempPng);
            } else {
                // Fallback if GD not available
                copy($tempPng, $qrImageFile);
                @unlink($tempPng);
            }
        } else {
            // Fallback to online QR code generator
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData) . '&format=jpg';
            $qrImage = file_get_contents($url);
            if ($qrImage !== false) {
                file_put_contents($qrImageFile, $qrImage);
            }
        }
        
        // Generate PDF with QR code
        $receiptDir = __DIR__ . '/receipts';
        if (!file_exists($receiptDir)) {
            mkdir($receiptDir, 0777, true);
        }
        
        $pdfPath = $receiptDir . '/payment_' . $paymentId . '.pdf';
        
        // Create PDF using FPDF if available
        if (class_exists('FPDF') || file_exists(__DIR__ . '/vendor/setasign/fpdf/fpdf.php')) {
            if (!class_exists('FPDF')) {
                require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
            }
            
            $pdf = new FPDF();
            $pdf->AddPage();
            
            // Header
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'EVolve Booking Receipt', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Ln(5);
            
            // Booking & Payment Details
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Booking Details:', 0, 1);
            
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, 'Booking ID:', 0);
            $pdf->Cell(0, 8, $booking['booking_id'], 0, 1);
            
            $pdf->Cell(60, 8, 'User:', 0);
            $pdf->Cell(0, 8, $booking['username'], 0, 1);
            
            $pdf->Cell(60, 8, 'Station:', 0);
            $pdf->Cell(0, 8, $booking['station_name'], 0, 1);
            
            $pdf->Cell(60, 8, 'Amount:', 0);
            $pdf->Cell(0, 8, 'Rs.' . number_format($amount, 2), 0, 1);
            
            $pdf->Cell(60, 8, 'Date:', 0);
            $pdf->Cell(0, 8, date('Y-m-d', strtotime($booking['booking_date'])), 0, 1);
            
            $pdf->Cell(60, 8, 'Time:', 0);
            $pdf->Cell(0, 8, date('H:i:s', strtotime($booking['booking_time'])), 0, 1);
            
            $pdf->Cell(60, 8, 'Payment Method:', 0);
            $pdf->Cell(0, 8, ucfirst($paymentMethod), 0, 1);
            
            $pdf->Cell(60, 8, 'Transaction ID:', 0);
            $pdf->Cell(0, 8, $transactionId, 0, 1);
            
            // QR Code Section
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'CHECK-IN QR CODE:', 0, 1, 'C');
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 8, 'Scan this QR code at the charging station for check-in and check-out', 0, 1, 'C');
            
            // Add QR code image (centered)
            if (file_exists($qrImageFile)) {
                try {
                    $pdf->Ln(5);
                    $imageWidth = 70;
                    $x = ($pdf->GetPageWidth() - $imageWidth) / 2;
                    $pdf->Image($qrImageFile, $x, $pdf->GetY(), $imageWidth);
                    $pdf->Ln($imageWidth + 10);
                } catch (Exception $e) {
                    $pdf->Ln(5);
                    $pdf->Cell(0, 10, 'QR Code not available', 0, 1, 'C');
                    error_log("PDF QR code error: " . $e->getMessage());
                }
            } else {
                $pdf->Cell(0, 10, 'QR Code not available', 0, 1, 'C');
                error_log("QR code file not found: " . $qrImageFile);
            }
            
            // Footer
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 10, 'Thank you for choosing EVolve for your EV charging needs!', 0, 1, 'C');
            
            $pdf->Output($pdfPath, 'F');
        } else {
            // Fallback to HTML if FPDF is not available
            $qrImageData = '';
            if (file_exists($qrImageFile)) {
                $qrImageData = base64_encode(file_get_contents($qrImageFile));
            }
            
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <title>EVolve Payment Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
                    h1 { text-align: center; color: #4CAF50; }
                    .details { margin: 20px 0; }
                    .row { display: flex; margin-bottom: 10px; }
                    .label { font-weight: bold; width: 150px; }
                    .qr-section { text-align: center; margin: 30px 0; }
                    h3 { color: #333; }
                    .footer { text-align: center; margin-top: 30px; font-style: italic; color: #777; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>EVolve Payment Receipt</h1>
                    
                    <div class="details">
                        <div class="row"><span class="label">Booking ID:</span> ' . $booking['booking_id'] . '</div>
                        <div class="row"><span class="label">User:</span> ' . $booking['username'] . '</div>
                        <div class="row"><span class="label">Station:</span> ' . $booking['station_name'] . '</div>
                        <div class="row"><span class="label">Amount:</span> Rs.' . number_format($amount, 2) . '</div>
                        <div class="row"><span class="label">Date:</span> ' . date('Y-m-d', strtotime($booking['booking_date'])) . '</div>
                        <div class="row"><span class="label">Time:</span> ' . date('H:i:s', strtotime($booking['booking_time'])) . '</div>
                        <div class="row"><span class="label">Payment Method:</span> ' . ucfirst($paymentMethod) . '</div>
                        <div class="row"><span class="label">Transaction ID:</span> ' . $transactionId . '</div>
                    </div>
                    
                    <div class="qr-section">
                        <h3>CHECK-IN QR CODE</h3>
                        <p>Scan this QR code at the charging station for check-in and check-out</p>';
            
            if ($qrImageData) {
                $html .= '<img src="data:image/jpeg;base64,' . $qrImageData . '" alt="QR Code" style="width: 200px; height: 200px;">';
            } else {
                $html .= '<p>QR Code not available</p>';
            }
            
            $html .= '</div>
                    
                    <div class="footer">
                        <p>Thank you for choosing EVolve for your EV charging needs!</p>
                    </div>
                </div>
            </body>
            </html>';
            
            $pdfPath = $receiptDir . '/payment_' . $paymentId . '.html';
            file_put_contents($pdfPath, $html);
        }
        
        // Send email with PDF attachment
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, 'EVolve EV Charging');
            $mail->addAddress($booking['email']);
            
            // Attach the PDF receipt
            $fileExtension = pathinfo($pdfPath, PATHINFO_EXTENSION);
            $attachmentName = 'EVolve_Payment_' . $paymentId . '.' . $fileExtension;
            $mail->addAttachment($pdfPath, $attachmentName);
            
            // Convert QR image to base64 for embedding in email body
            $qrImageData = '';
            if (file_exists($qrImageFile)) {
                $qrImageData = base64_encode(file_get_contents($qrImageFile));
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'EVolve Payment Confirmation - ' . $paymentId;
            $mail->Body = '
                <h2>EVolve Payment Confirmation</h2>
                <p>Thank you for your payment. Please find your receipt attached.</p>
                <table style="border-collapse: collapse; width: 100%; max-width: 600px;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Booking ID:</th>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $booking['booking_id'] . '</td>
                    </tr>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Station:</th>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $booking['station_name'] . '</td>
                    </tr>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Amount:</th>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Rs.' . number_format($amount, 2) . '</td>
                    </tr>
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Transaction ID:</th>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $transactionId . '</td>
                    </tr>
                </table>
                
                <div style="text-align: center; margin: 30px 0;">
                    <h3>Check-in QR Code</h3>
                    <p>Scan this QR code at the charging station:</p>';
            
            if ($qrImageData) {
                $mail->Body .= '<img src="data:image/jpeg;base64,' . $qrImageData . '" alt="QR Code" style="width: 200px; height: 200px;">';
            } else {
                $mail->Body .= '<p>QR Code is available in the attached PDF</p>';
            }
            
            $mail->Body .= '
                    <p style="font-size: 12px; color: #666;">Scan this QR code at the EVolve charging station</p>
                </div>
                
                <p>Thank you for choosing EVolve for your EV charging needs!</p>
            ';
            
            $mail->send();
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully and receipt sent',
                'payment_id' => $paymentId,
                'receipt_pdf' => $pdfPath
            ];
        } catch (Exception $e) {
            error_log("Email error: " . $mail->ErrorInfo);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully but failed to send email',
                'payment_id' => $paymentId,
                'receipt_pdf' => $pdfPath,
                'email_error' => $mail->ErrorInfo
            ];
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        return [
            'success' => false,
            'message' => 'Payment processing error: ' . $e->getMessage()
        ];
    }
}

// API endpoint for processing payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if required parameters are set
    if (!isset($_POST['booking_id']) || !isset($_POST['amount']) || 
        !isset($_POST['payment_method']) || !isset($_POST['transaction_id'])) {
        
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    // Get parameters
    $bookingId = intval($_POST['booking_id']);
    $amount = floatval($_POST['amount']);
    $paymentMethod = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
    $transactionId = filter_var($_POST['transaction_id'], FILTER_SANITIZE_STRING);
    
    // Process payment and send receipt email
    $result = processPaymentAndSendQR($bookingId, $amount, $paymentMethod, $transactionId);
    
    echo json_encode($result);
    exit;
}
?> 