<?php
// Include necessary configuration and libraries
require_once 'config.php';
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Generate QR code image for booking (in JPG format)
 * 
 * @param array $booking Booking data 
 * @return string Path to generated QR code image
 */
function generateBookingQRCode($booking) {
    // Create QR data
    $qrData = json_encode([
        'booking_id' => $booking['booking_id'],
        'user_id' => $booking['user_id'],
        'station_id' => $booking['station_id']
    ]);
    
    // Set QR image path
    $qrDir = __DIR__ . '/qrcodes';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    
    // Using JPG format instead of PNG
    $qrImageFile = $qrDir . '/booking_' . $booking['booking_id'] . '_' . $booking['user_id'] . '.jpg';
    
    // Generate QR code based on available libraries
    if (file_exists(__DIR__ . '/phpqrcode/qrlib.php')) {
        require_once __DIR__ . '/phpqrcode/qrlib.php';
        // Generate temp PNG then convert to JPG
        $tempPng = $qrDir . '/temp_' . $booking['booking_id'] . '.png';
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
        // Fallback to online QR code generator (direct JPG)
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData) . '&format=jpg';
        $qrImage = file_get_contents($url);
        if ($qrImage !== false) {
            file_put_contents($qrImageFile, $qrImage);
        }
    }
    
    return $qrImageFile;
}

/**
 * Generate PDF receipt with embedded QR code image
 * 
 * @param array $booking Booking data
 * @param string $qrImageFile Path to QR code image
 * @return string Path to generated PDF
 */
function generateBookingPDF($booking, $qrImageFile) {
    // Create receipts directory if it doesn't exist
    $receiptDir = __DIR__ . '/receipts';
    if (!file_exists($receiptDir)) {
        mkdir($receiptDir, 0777, true);
    }
    
    $pdfPath = $receiptDir . '/booking_' . $booking['booking_id'] . '.pdf';
    
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
        
        // Booking Details
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
        $pdf->Cell(0, 8, 'Rs.' . number_format($booking['amount'], 2), 0, 1);
        
        $pdf->Cell(60, 8, 'Date:', 0);
        $pdf->Cell(0, 8, date('Y-m-d', strtotime($booking['booking_date'])), 0, 1);
        
        $pdf->Cell(60, 8, 'Time:', 0);
        $pdf->Cell(0, 8, date('H:i:s', strtotime($booking['booking_time'])), 0, 1);
        
        // QR Code Section
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'BOOKING QR CODE:', 0, 1, 'C');
        
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
        
        // Save PDF
        $pdf->Output('F', $pdfPath);
    } else {
        // Fallback to HTML if FPDF is not available
        // Convert QR image to base64 for embedding in HTML
        $qrImageData = '';
        if (file_exists($qrImageFile)) {
            $qrImageData = base64_encode(file_get_contents($qrImageFile));
        }
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>EVolve Booking Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .details { margin-bottom: 30px; }
                .row { margin: 10px 0; }
                .label { font-weight: bold; display: inline-block; width: 100px; }
                .qr-section { text-align: center; margin: 20px 0; }
                .footer { text-align: center; margin-top: 50px; font-style: italic; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>EVolve Booking Receipt</h1>
            </div>
            
            <div class="details">
                <div class="row"><span class="label">Booking ID:</span> ' . $booking['booking_id'] . '</div>
                <div class="row"><span class="label">User:</span> ' . $booking['username'] . '</div>
                <div class="row"><span class="label">Station:</span> ' . $booking['station_name'] . '</div>
                <div class="row"><span class="label">Amount:</span> Rs.' . number_format($booking['amount'], 2) . '</div>
                <div class="row"><span class="label">Date:</span> ' . date('Y-m-d', strtotime($booking['booking_date'])) . '</div>
                <div class="row"><span class="label">Time:</span> ' . date('H:i:s', strtotime($booking['booking_time'])) . '</div>
            </div>
            
            <div class="qr-section">
                <h3>BOOKING QR CODE</h3>
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
        </body>
        </html>';
        
        $pdfPath = $receiptDir . '/booking_' . $booking['booking_id'] . '.html';
        file_put_contents($pdfPath, $html);
    }
    
    return $pdfPath;
}

/**
 * Send booking confirmation email with QR code PDF
 * 
 * @param int $bookingId The booking ID
 * @return bool Success status
 */
function sendBookingConfirmationEmail($bookingId) {
    global $pdo;
    
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, cs.name as station_name, u.name as username, u.email
        FROM bookings b
        JOIN charging_stations cs ON b.station_id = cs.station_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ?
    ");
    
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        return false;
    }
    
    // Generate QR code in JPG format
    $qrImageFile = generateBookingQRCode($booking);
    
    // Generate PDF with QR code
    $pdfPath = generateBookingPDF($booking, $qrImageFile);
    
    if (!file_exists($pdfPath)) {
        return false;
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
        $attachmentName = 'EVolve_Booking_' . $booking['booking_id'] . '.' . $fileExtension;
        $mail->addAttachment($pdfPath, $attachmentName);
        
        // Convert QR image to base64 for embedding in email body
        $qrImageData = '';
        if (file_exists($qrImageFile)) {
            $qrImageData = base64_encode(file_get_contents($qrImageFile));
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'EVolve Booking Confirmation - ' . $booking['booking_id'];
        $mail->Body = '
            <h2>EVolve Booking Confirmation</h2>
            <p>Thank you for your booking. Please find your booking receipt attached.</p>
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
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Date:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . date('Y-m-d', strtotime($booking['booking_date'])) . '</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Time:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . date('H:i:s', strtotime($booking['booking_time'])) . '</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Amount:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">Rs.' . number_format($booking['amount'], 2) . '</td>
                </tr>
            </table>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>Booking QR Code</h3>
                <p>Scan this QR code at the charging station for check-in and check-out:</p>';
        
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
        
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}

// If called directly, check for booking ID
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    if (isset($_GET['booking_id'])) {
        $bookingId = intval($_GET['booking_id']);
        if (sendBookingConfirmationEmail($bookingId)) {
            echo "Booking confirmation email sent successfully.";
        } else {
            echo "Failed to send booking confirmation email.";
        }
    }
}
?> 