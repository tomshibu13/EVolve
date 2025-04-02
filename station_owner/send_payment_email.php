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

// Include the QR code library - try different locations
if (file_exists(__DIR__ . '/../phpqrcode/qrlib.php')) {
    require_once __DIR__ . '/../phpqrcode/qrlib.php';
} elseif (file_exists(__DIR__ . '/../lib/phpqrcode/qrlib.php')) {
    require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';
} else {
    // Define fallback QR code generation function
    function QRcode_png($data, $file, $eclevel = 'H', $size = 10) {
        // Use QR Code API for fallback
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($data);
        
        // Use cURL for better error handling
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $qrImage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($qrImage !== false && $httpCode == 200) {
            // Ensure the directory exists
            $dir = dirname($file);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($file, $qrImage);
            return true;
        }
        
        return false;
    }
}

// Include the PDF receipt generation function
// Check if file exists before requiring it
if (file_exists(__DIR__ . '/../generate-pdf-receipt.php')) {
    require_once __DIR__ . '/../generate-pdf-receipt.php';
} else {
    // Define a basic version of the function if the file doesn't exist
    // We'll enhance this with QR code functionality
}

// Generate QR code image from data
function generateQRCodeImage($data, $filename = null) {
    if ($filename === null) {
        $filename = __DIR__ . '/../qrcodes/' . md5($data . time()) . '.png';
    }
    
    // Ensure directory exists
    $dir = dirname($filename);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Generate QR code based on available methods
    if (function_exists('QRcode::png')) {
        QRcode::png($data, $filename, QR_ECLEVEL_H, 10);
    } else {
        QRcode_png($data, $filename, 'H', 10);
    }
    
    return $filename;
}

// Enhance PDF generation to include QR code
function generatePaymentPDFWithQR($paymentData, $pdfPath = null) {
    global $pdo;
    
    // Default file name if not provided
    if ($pdfPath === null) {
        $pdfPath = __DIR__ . '/../receipts/payment_' . $paymentData['payment_id'] . '.pdf';
    }
    
    // Generate QR code with payment/booking data
    $qrData = json_encode([
        'payment_id' => $paymentData['payment_id'],
        'booking_id' => $paymentData['booking_id'],
        'user_id' => $paymentData['user_id'],
        'station_id' => $paymentData['station_id']
    ]);
    
    $qrImageFile = generateQRCodeImage($qrData, __DIR__ . '/../qrcodes/payment_' . $paymentData['payment_id'] . '.png');
    
    try {
        // Check for PDF libraries
        if (class_exists('FPDF')) {
            // Use FPDF
            $pdf = new FPDF();
            $pdf->AddPage();
            
            // Add header
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'EVolve Booking Receipt', 0, 1, 'C');
            
            // Add booking details
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Payment Details:', 0, 1);
            
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, 'Booking ID:', 0);
            $pdf->Cell(0, 8, $paymentData['booking_id'], 0, 1);
            
            $pdf->Cell(60, 8, 'User:', 0);
            $pdf->Cell(0, 8, $paymentData['username'], 0, 1);
            
            $pdf->Cell(60, 8, 'Station:', 0);
            $pdf->Cell(0, 8, $paymentData['station_name'], 0, 1);
            
            $pdf->Cell(60, 8, 'Amount:', 0);
            $pdf->Cell(0, 8, 'Rs.' . number_format($paymentData['amount'], 2), 0, 1);
            
            $pdf->Cell(60, 8, 'Date:', 0);
            $pdf->Cell(0, 8, date('Y-m-d', strtotime($paymentData['payment_date'])), 0, 1);
            
            $pdf->Cell(60, 8, 'Time:', 0);
            $pdf->Cell(0, 8, date('H:i:s', strtotime($paymentData['payment_time'])), 0, 1);
            
            // Add QR Code section
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'QR CODE ACCESS INFORMATION:', 0, 1);
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 8, 'Scan this QR code for check-in/check-out at the station:', 0, 1, 'C');
            
            // Add QR code image
            if (file_exists($qrImageFile)) {
                try {
                    $pdf->Ln(5);
                    // Center the image
                    $imageWidth = 60;
                    $x = ($pdf->GetPageWidth() - $imageWidth) / 2;
                    $pdf->Image($qrImageFile, $x, $pdf->GetY(), $imageWidth);
                    $pdf->Ln($imageWidth + 10);
                } catch (Exception $e) {
                    // If image insertion fails
                    $pdf->Ln(5);
                    $pdf->Cell(0, 10, 'QR Code not available', 0, 1, 'C');
                }
            }
            
            // Add footer
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 10, 'Thank you for choosing EVolve for your EV charging needs!', 0, 1, 'C');
            
            // Output the PDF
            $pdf->Output('F', $pdfPath);
            
            return $pdfPath;
        } else {
            // If no PDF library is available, create a basic HTML file
            $html = '<!DOCTYPE html>
            <html>
            <head>
                <title>EVolve Booking Receipt</title>
                <style>
                    body {font-family: Arial, sans-serif; margin: 40px;}
                    .header {text-align: center; margin-bottom: 30px;}
                    .details {margin-bottom: 30px;}
                    .row {margin: 10px 0;}
                    .label {font-weight: bold; display: inline-block; width: 100px;}
                    .qr-section {text-align: center; margin: 20px 0;}
                    .footer {text-align: center; margin-top: 50px; font-style: italic;}
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>EVolve Booking Receipt</h1>
                </div>
                
                <div class="details">
                    <div class="row"><span class="label">Booking ID:</span> ' . $paymentData['booking_id'] . '</div>
                    <div class="row"><span class="label">User:</span> ' . $paymentData['username'] . '</div>
                    <div class="row"><span class="label">Station:</span> ' . $paymentData['station_name'] . '</div>
                    <div class="row"><span class="label">Amount:</span> Rs.' . number_format($paymentData['amount'], 2) . '</div>
                    <div class="row"><span class="label">Date:</span> ' . date('Y-m-d', strtotime($paymentData['payment_date'])) . '</div>
                    <div class="row"><span class="label">Time:</span> ' . date('H:i:s', strtotime($paymentData['payment_time'])) . '</div>
                </div>
                
                <div class="qr-section">
                    <h3>QR CODE ACCESS INFORMATION:</h3>
                    <p>Scan this QR code for check-in/check-out at the station:</p>';
            
            // Convert QR image to base64 for direct embedding
            if (file_exists($qrImageFile)) {
                $qrImageData = base64_encode(file_get_contents($qrImageFile));
                $html .= '<img src="data:image/png;base64,' . $qrImageData . '" width="200" height="200">';
            } else {
                $html .= '<p>QR Code not available</p>';
            }
            
            $html .= '</div>
                
                <div class="footer">
                    <p>Thank you for choosing EVolve for your EV charging needs!</p>
                </div>
            </body>
            </html>';
            
            $htmlPath = __DIR__ . '/../receipts/payment_' . $paymentData['payment_id'] . '.html';
            file_put_contents($htmlPath, $html);
            return $htmlPath;
        }
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        return false;
    }
}

// Function to send payment receipt email with QR code
function sendPaymentReceiptEmail($paymentId, $recipientEmail = null) {
    global $pdo;
    
    // Get payment details
    $stmt = $pdo->prepare("
        SELECT p.*, b.booking_date, b.booking_time, b.user_id, b.station_id, 
               cs.name as station_name, u.name as username, u.email
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        JOIN charging_stations cs ON b.station_id = cs.station_id
        JOIN users u ON b.user_id = u.user_id
        WHERE p.payment_id = ?
    ");
    
    $stmt->execute([$paymentId]);
    $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$paymentData) {
        return [
            'success' => false,
            'message' => 'Payment not found'
        ];
    }
    
    // If no specific recipient is provided, send to user's email
    if ($recipientEmail === null) {
        $recipientEmail = $paymentData['email'];
    }
    
    // Generate QR code data
    $qrData = json_encode([
        'payment_id' => $paymentData['payment_id'],
        'booking_id' => $paymentData['booking_id'],
        'user_id' => $paymentData['user_id'],
        'station_id' => $paymentData['station_id'],
        'timestamp' => time()
    ]);
    
    // Create QR code directory if it doesn't exist
    $qrDir = __DIR__ . '/../qrcodes';
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
    }
    
    // Generate QR code image
    $qrImageFile = $qrDir . '/payment_' . $paymentData['payment_id'] . '.png';
    
    // Generate QR code using available methods
    if (class_exists('QRcode')) {
        require_once __DIR__ . '/../phpqrcode/qrlib.php';
        \QRcode::png($qrData, $qrImageFile, QR_ECLEVEL_H, 10);
    } else {
        // Fallback to online QR code generator
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);
        $qrImage = file_get_contents($qrCodeUrl);
        file_put_contents($qrImageFile, $qrImage);
    }
    
    // Convert QR image to base64 for embedding in email
    $qrImageData = base64_encode(file_get_contents($qrImageFile));
    $qrCodeImgTag = '<img src="data:image/png;base64,' . $qrImageData . '" alt="QR Code" style="width: 200px; height: 200px;">';
    
    // Create PDF receipt using generate-pdf-receipt.php if available
    // Otherwise create a simple PDF with the payment details
    $pdfPath = '';
    if (function_exists('generatePdfReceipt') && $paymentData['booking_id']) {
        $pdfPath = generatePdfReceipt($paymentData['booking_id']);
    } else {
        // Generate a simple PDF with payment details and QR code
        if (class_exists('FPDF')) {
            require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';
            
            $pdf = new FPDF();
            $pdf->AddPage();
            
            // Header
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'EVolve Payment Receipt', 0, 1, 'C');
            
            // Payment Details
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Payment Details:', 0, 1);
            
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, 'Payment ID:', 0);
            $pdf->Cell(0, 8, $paymentData['payment_id'], 0, 1);
            
            $pdf->Cell(60, 8, 'Booking ID:', 0);
            $pdf->Cell(0, 8, $paymentData['booking_id'], 0, 1);
            
            $pdf->Cell(60, 8, 'User:', 0);
            $pdf->Cell(0, 8, $paymentData['username'], 0, 1);
            
            $pdf->Cell(60, 8, 'Station:', 0);
            $pdf->Cell(0, 8, $paymentData['station_name'], 0, 1);
            
            $pdf->Cell(60, 8, 'Amount:', 0);
            $pdf->Cell(0, 8, 'Rs.' . number_format($paymentData['amount'], 2), 0, 1);
            
            $pdf->Cell(60, 8, 'Date:', 0);
            $pdf->Cell(0, 8, date('Y-m-d', strtotime($paymentData['booking_date'])), 0, 1);
            
            $pdf->Cell(60, 8, 'Time:', 0);
            $pdf->Cell(0, 8, date('H:i:s', strtotime($paymentData['booking_time'])), 0, 1);
            
            // QR Code Section
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Check-in QR Code:', 0, 1);
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 8, 'Scan this QR code at the charging station for check-in and check-out', 0, 1, 'C');
            
            // Add QR code image (centered)
            if (file_exists($qrImageFile)) {
                try {
                    $pdf->Ln(5);
                    $imageWidth = 60;
                    $x = ($pdf->GetPageWidth() - $imageWidth) / 2;
                    $pdf->Image($qrImageFile, $x, $pdf->GetY(), $imageWidth);
                    $pdf->Ln($imageWidth + 10);
                } catch (Exception $e) {
                    $pdf->Ln(5);
                    $pdf->Cell(0, 10, 'QR Code not available', 0, 1, 'C');
                }
            }
            
            // Footer
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 10, 'Thank you for choosing EVolve for your EV charging needs!', 0, 1, 'C');
            
            // Save PDF
            $pdfPath = __DIR__ . '/../receipts/payment_' . $paymentData['payment_id'] . '.pdf';
            $pdf->Output('F', $pdfPath);
        } else {
            // Generate HTML receipt if FPDF is not available
            $htmlContent = '<!DOCTYPE html>
            <html>
            <head>
                <title>EVolve Payment Receipt</title>
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
                    <h1>EVolve Payment Receipt</h1>
                </div>
                
                <div class="details">
                    <div class="row"><span class="label">Payment ID:</span> ' . $paymentData['payment_id'] . '</div>
                    <div class="row"><span class="label">Booking ID:</span> ' . $paymentData['booking_id'] . '</div>
                    <div class="row"><span class="label">User:</span> ' . $paymentData['username'] . '</div>
                    <div class="row"><span class="label">Station:</span> ' . $paymentData['station_name'] . '</div>
                    <div class="row"><span class="label">Amount:</span> Rs.' . number_format($paymentData['amount'], 2) . '</div>
                    <div class="row"><span class="label">Date:</span> ' . date('Y-m-d', strtotime($paymentData['booking_date'])) . '</div>
                    <div class="row"><span class="label">Time:</span> ' . date('H:i:s', strtotime($paymentData['booking_time'])) . '</div>
                </div>
                
                <div class="qr-section">
                    <h3>Check-in QR Code</h3>
                    <p>Scan this QR code at the charging station for check-in and check-out</p>
                    ' . $qrCodeImgTag . '
                </div>
                
                <div class="footer">
                    <p>Thank you for choosing EVolve for your EV charging needs!</p>
                </div>
            </body>
            </html>';
            
            $pdfPath = __DIR__ . '/../receipts/payment_' . $paymentData['payment_id'] . '.html';
            file_put_contents($pdfPath, $htmlContent);
        }
    }
    
    if (!$pdfPath || !file_exists($pdfPath)) {
        return [
            'success' => false,
            'message' => 'Failed to generate receipt'
        ];
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
        $mail->addAddress($recipientEmail);
        
        // Attach the PDF receipt
        $attachmentName = 'EVolve_Receipt_' . $paymentData['payment_id'] . '.' . pathinfo($pdfPath, PATHINFO_EXTENSION);
        $mail->addAttachment($pdfPath, $attachmentName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'EVolve Payment Receipt - ' . $paymentData['payment_id'];
        $mail->Body = '
            <h2>EVolve Payment Receipt</h2>
            <p>Thank you for your payment. Please find your receipt attached.</p>
            <table style="border-collapse: collapse; width: 100%; max-width: 600px;">
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Booking ID:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $paymentData['booking_id'] . '</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Amount:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">Rs.' . number_format($paymentData['amount'], 2) . '</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Date:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . date('Y-m-d', strtotime($paymentData['booking_date'])) . '</td>
                </tr>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Station:</th>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $paymentData['station_name'] . '</td>
                </tr>
            </table>
            
            <div style="text-align: center; margin: 30px 0;">
                <h3>Check-in QR Code</h3>
                <p>You can also use this QR code to check in at the station:</p>
                ' . $qrCodeImgTag . '
                <p style="font-size: 12px; color: #666;">Scan this QR code with the EVolve scanner at the charging station</p>
            </div>
            
            <p>Thank you for choosing EVolve for your EV charging needs!</p>
        ';
        
        $mail->send();
        
        // Clean up temporary files after sending
        // Keep for 24 hours for troubleshooting, then set up a cron job to clean old files
        //if (file_exists($pdfPath)) unlink($pdfPath);
        //if (file_exists($qrImageFile)) unlink($qrImageFile);
        
        return [
            'success' => true,
            'message' => 'Payment receipt sent to ' . $recipientEmail
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo
        ];
    }
}

// API endpoint for sending payment email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a direct API call
    if (isset($_POST['action']) && $_POST['action'] === 'send_payment_receipt') {
        header('Content-Type: application/json');
        
        if (!isset($_POST['payment_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Payment ID is required'
            ]);
            exit;
        }
        
        $paymentId = intval($_POST['payment_id']);
        $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : null;
        
        $result = sendPaymentReceiptEmail($paymentId, $email);
        echo json_encode($result);
        exit;
    }
}

// Add HTML to PDF conversion capability
require_once __DIR__ . '/../vendor/autoload.php';
// If using mPDF
// use Mpdf\Mpdf;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

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

// Function to convert HTML to PDF
function convertHtmlToPdf($htmlPath) {
    // Get HTML content
    if (!file_exists($htmlPath)) {
        error_log("HTML file not found: " . $htmlPath);
        return false;
    }
    
    $htmlContent = file_get_contents($htmlPath);
    $pdfPath = str_replace('.html', '.pdf', $htmlPath);
    
    try {
        // Check if mPDF class exists before trying to use it
        if (class_exists('Mpdf\Mpdf')) {
            try {
                $mpdf = new Mpdf\Mpdf([
                    'tempDir' => __DIR__ . '/../temp',
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
                // Continue to use the HTML version as fallback
            }
        } else {
            error_log("mPDF library not available, using HTML receipt instead");
            // Continue to use the HTML version as fallback
        }
    } catch (Exception $e) {
        error_log("PDF conversion error: " . $e->getMessage());
        return $htmlPath; // Fallback to HTML
    }
}

// Function to generate QR code image
function generateQrCode($data, $size = 200) {
    try {
        // Check if Endroid QR Code library is available
        if (class_exists('\\Endroid\\QrCode\\QrCode')) {
            $qrCode = new QrCode($data);
            $qrCode->setSize($size);
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            // Return base64 encoded image
            return 'data:image/png;base64,' . base64_encode($result->getString());
        } else {
            // Fallback to simple QR code generation using PHP QR Code library
            if (class_exists('\\QRcode')) {
                ob_start();
                \QRcode::png($data, null, QR_ECLEVEL_L, 10, 2);
                $imageData = ob_get_contents();
                ob_end_clean();
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
            // If no QR library is available, return empty string
            error_log("No QR code library available");
            return '';
        }
    } catch (Exception $e) {
        error_log("QR code generation failed: " . $e->getMessage());
        return '';
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
        
        // Generate the HTML receipt
        $htmlPath = generatePdfReceipt($bookingId);
        
        if (!$htmlPath) {
            error_log("Failed to generate HTML receipt for booking ID: " . $bookingId);
            $attachmentPath = null;
            $attachmentName = null;
        } else {
            // Try to convert HTML to PDF
            $attachmentPath = convertHtmlToPdf($htmlPath);
            // Make sure we always use PDF extension in the filename, even if conversion failed
            $attachmentName = 'EVolve_Booking_Receipt.pdf';
            
            // If conversion failed and we're using HTML, force conversion to PDF using basic method
            if ($attachmentPath == $htmlPath) {
                // Try one more fallback option using PHP's built-in capabilities
                try {
                    $pdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
                    $pdf->WriteHTML(file_get_contents($htmlPath));
                    $pdfPath = sys_get_temp_dir() . '/receipt_' . $bookingId . '.pdf';
                    $pdf->Output($pdfPath, 'F');
                    if (file_exists($pdfPath)) {
                        $attachmentPath = $pdfPath;
                    }
                } catch (Exception $e) {
                    error_log("Final PDF conversion attempt failed: " . $e->getMessage());
                }
            }
            
            error_log("Generated attachment: " . $attachmentPath);
        }
        
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
        
        // Attach receipt if available
        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath, $attachmentName);
            error_log("Attached receipt: " . $attachmentPath);
        } else {
            // Create a simple PDF if all else fails
            try {
                $emergencyPdfPath = sys_get_temp_dir() . '/emergency_receipt_' . $bookingId . '.pdf';
                
                // Generate QR code data
                $qrData = json_encode([
                    'booking_id' => $bookingId,
                    'user_id' => $userId,
                    'station_id' => getStationIdByName($stationName)
                ]);
                
                // Generate QR code using our function instead of Google Charts API
                $qrCodeImage = generateQrCode($qrData);
                
                // Create host URL for QR view page
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                $viewQrCodeUrl = 'http://' . $host . '/Project/view-qr.php?id=' . $bookingId . '&user=' . $userId;
                
                // Check if mPDF class exists before attempting to use it
                if (class_exists('\\Mpdf\\Mpdf')) {
                    $pdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
                    $pdf->WriteHTML("
                        <h1 style='color: #224abe; text-align: center;'>EVolve Booking Receipt</h1>
                        <div style='text-align: center;'>
                            <p style='font-size: 14px;'>Booking ID: {$bookingId}</p>
                            <p style='font-size: 14px;'>User: {$userName}</p>
                            <p style='font-size: 14px;'>Station: {$stationName}</p>
                            <p style='font-size: 14px;'>Amount: ₹{$amount}</p>
                            <p style='font-size: 14px;'>Date: {$bookingDate}</p>
                            <p style='font-size: 14px;'>Time: {$bookingTime}</p>
                            
                            <div style='margin: 30px auto; padding: 10px; border: 2px dashed #ccc; width: 220px;'>
                                <h3 style='color: #224abe;'>Check-in/Check-out QR Code</h3>
                                <img src='{$qrCodeImage}' style='width: 200px; height: 200px;'>
                                <p style='font-size: 12px;'>Scan this QR code at the charging station</p>
                            </div>
                            
                            <p style='font-size: 16px; font-weight: bold; margin-top: 30px;'>Thank you for choosing EVolve for your EV charging needs!</p>
                            
                            <p style='font-size: 12px; margin-top: 50px;'>
                                If you can't scan the QR code above, please visit:<br>
                                <a href='{$viewQrCodeUrl}'>{$viewQrCodeUrl}</a>
                            </p>
                        </div>
                    ");
                    $pdf->Output($emergencyPdfPath, 'F');
                } else {
                    // Create a very basic but valid PDF file using pure PHP
                    // PDF structure reference: https://www.adobe.com/content/dam/acom/en/devnet/pdf/pdfs/PDF32000_2008.pdf
                    
                    // PDF header
                    $pdf = "%PDF-1.4\n";
                    
                    // Object 1 - catalog
                    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
                    
                    // Object 2 - pages
                    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
                    
                    // Object 3 - page
                    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
                    
                    // Object 4 - font resource
                    $pdf .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";
                    
                    // Object 5 - content - adding QR code information
                    $content = "BT
/F1 16 Tf
50 750 Td
(EVolve Booking Receipt) Tj
/F1 12 Tf
0 -30 Td
(Booking ID: {$bookingId}) Tj
0 -20 Td
(User: {$userName}) Tj
0 -20 Td
(Station: {$stationName}) Tj
0 -20 Td
(Amount: Rs.{$amount}) Tj
0 -20 Td
(Date: {$bookingDate}) Tj
0 -20 Td
(Time: {$bookingTime}) Tj
0 -40 Td
(QR CODE ACCESS INFORMATION:) Tj
0 -20 Td
(To view your QR code for check-in/check-out, please visit:) Tj
0 -20 Td
({$viewQrCodeUrl}) Tj
0 -40 Td
(Thank you for choosing EVolve for your EV charging needs!) Tj
ET";
                    
                    $pdf .= "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n";
                    
                    // xref table
                    $startxref = strlen($pdf);
                    $pdf .= "xref\n0 6\n";
                    $pdf .= "0000000000 65535 f\n";
                    $pdf .= "0000000009 00000 n\n";
                    $pdf .= "0000000056 00000 n\n";
                    $pdf .= "0000000111 00000 n\n";
                    $pdf .= "0000000212 00000 n\n";
                    $pdf .= "0000000293 00000 n\n";
                    
                    // trailer
                    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
                    $pdf .= "startxref\n" . $startxref . "\n";
                    $pdf .= "%%EOF";
                    
                    file_put_contents($emergencyPdfPath, $pdf);
                }
                
                if (file_exists($emergencyPdfPath)) {
                    $mail->addAttachment($emergencyPdfPath, 'EVolve_Booking_Receipt.pdf');
                    error_log("Attached emergency receipt with QR code: " . $emergencyPdfPath);
                    // Add this file to cleanup list
                    $attachmentPath = $emergencyPdfPath;
                }
            } catch (Exception $e) {
                error_log("Emergency receipt generation failed: " . $e->getMessage());
                // If all else fails, just include the receipt information in the email body
                $mail->Body .= "\n\n<h3>Receipt Information</h3>
                    <p>Booking ID: {$bookingId}</p>
                    <p>Amount: ₹{$amount}</p>
                    <p>Date: {$bookingDate}</p>
                    <p>Time: {$bookingTime}</p>
                    <p>To access your QR code, please visit: {$viewQrCodeUrl}</p>";
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - EVolve EV Charging';
        
        // Update email body to mention the attached PDF
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
                
                <p><strong>Important:</strong> Please find your booking receipt with QR code in the attached PDF. You'll need to scan this QR code at the charging station for both check-in and check-out.</p>
                
                <p>To view your QR code for check-in and check-out at the station, you can also visit this link:</p>
                <p><a href='{$viewQrCodeUrl}'>Click here to view your QR code</a></p>
                
                <p>Thank you for choosing EVolve for your EV charging needs!</p>
            </body>
            </html>
        ";
        
        $mail->Body = $emailBody;
        $mail->AltBody = "Your booking at {$stationName} has been confirmed. Booking ID: {$bookingId}. Amount: ₹" . number_format($amount, 2) . ". Date: " . date('d M Y', strtotime($bookingDate)) . ". Time: " . date('h:i A', strtotime($bookingTime)) . ". Please check the attached PDF for your booking QR code. Alternatively, you can view your QR code at: {$viewQrCodeUrl}";

        error_log("Using email template with PDF attachment containing station details and QR code");
        error_log("Attempting to send email to: $userEmail");
        $mail->send();
        error_log("Email sent successfully to: $userEmail");

        // Clean up files - improved to handle emergency PDF files too
        if ($attachmentPath && file_exists($attachmentPath)) {
            @unlink($attachmentPath);
        }
        if ($htmlPath && $htmlPath != $attachmentPath && file_exists($htmlPath)) {
            @unlink($htmlPath);
        }
        // Clean up any emergency PDFs
        $emergencyPdfPath = sys_get_temp_dir() . '/emergency_receipt_' . $bookingId . '.pdf';
        if (file_exists($emergencyPdfPath)) {
            @unlink($emergencyPdfPath);
        }

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