<?php
session_start();
require_once 'config.php';
// Fix QR code library path - try different common locations
if (file_exists('phpqrcode/qrlib.php')) {
    require_once 'phpqrcode/qrlib.php';
} elseif (file_exists('lib/phpqrcode/qrlib.php')) {
    require_once 'lib/phpqrcode/qrlib.php';
} elseif (file_exists('includes/phpqrcode/qrlib.php')) {
    require_once 'includes/phpqrcode/qrlib.php';
} elseif (file_exists('vendor/phpqrcode/qrlib.php')) {
    require_once 'vendor/phpqrcode/qrlib.php';
} else {
    // QR library not found - you'll need to download it
    // For now, let's define a fallback function that uses a different QR code generator
    function QRcode() {
        // This is just a placeholder to prevent errors
    }
    
    // Fallback QR code generation using QR Server API
    function QRcode_png($data, $file, $eclevel = 'H', $size = 10) {
        // Use QR Code API that's still working
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
        
        // If API fails, create a text-based placeholder image
        if (function_exists('imagecreate')) {
            $im = imagecreate(300, 300);
            $bg = imagecolorallocate($im, 255, 255, 255);
            $textColor = imagecolorallocate($im, 0, 0, 0);
            imagefill($im, 0, 0, $bg);
            $text = "QR Code\nUnavailable";
            imagestring($im, 5, 100, 140, $text, $textColor);
            imagejpeg($im, $file, 90);
            imagedestroy($im);
            return true;
        }
        
        return false;
    }
}

// Add PDF library
require_once 'vendor/autoload.php';

// For email functionality
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'PHPMailer-master/src/Exception.php';
require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';

// Determine which mode we're in: station or booking
$mode = 'unknown';
$stationId = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = isset($_GET['user']) ? intval($_GET['user']) : 0;

// Station mode
if ($stationId > 0) {
    $mode = 'station';
    
    // Get station details
    $stmt = $pdo->prepare("
        SELECT 
            cs.name as station_name,
            cs.station_id
        FROM charging_stations cs
        WHERE cs.station_id = ?
    ");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch();

    // If station not found
    if (!$station) {
        die("Station not found");
    }

    // Generate a URL for this station that points to the scan page
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
    
    // IMPORTANT: Make sure we use the correct scan page filename consistently
    $scanUrl = $baseUrl . "/scan-qr.php?station_id=" . $stationId;
    
    $pageTitle = "Station QR Code - EVolve";
    $headerTitle = "EVolve Station QR Code";
    $subTitle = "Station: " . htmlspecialchars($station['station_name']) . " (ID: " . $stationId . ")";
    $instruction = "Scan to Access Station";
    $qrData = $scanUrl;
    $qrLabel = "Station QR";
}
// Booking mode
else if ($bookingId > 0 && $userId > 0) {
    $mode = 'booking';
    
    // Get booking and station details
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_id,
            b.user_id,
            b.station_id,
            b.booking_date,
            b.booking_time,
            b.status,
            cs.name as station_name
        FROM bookings b
        JOIN charging_stations cs ON b.station_id = cs.station_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();

    // If booking not found
    if (!$booking) {
        die("Booking not found");
    }

    // Create QR data as JSON
    $qrData = json_encode([
        'booking_id' => $booking['booking_id'],
        'user_id' => $booking['user_id'],
        'station_id' => $booking['station_id']
    ]);
    
    $pageTitle = "Booking QR Code - EVolve";
    $headerTitle = "EVolve Booking QR Code";
    $subTitle = "Booking ID: " . $booking['booking_id'] . " at " . htmlspecialchars($booking['station_name']) . 
                " (Station ID: " . $booking['station_id'] . ")";
    $instruction = "Scan this QR code to check-in/check-out at the station";
    $qrLabel = "BookingQR";
}
else {
    die("Invalid parameters. Please provide either station_id or both id and user parameters.");
}

// Modified QR code generation to handle both libraries and our fallback
function generateQRCodeImage($data, $filename = null) {
    if ($filename === null) {
        $filename = 'qrcodes/' . md5($data . time()) . '.png';  // Changed to PNG for better compatibility
    }
    
    // Ensure directory exists
    $dir = dirname($filename);
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Generate QR code - check if we have the proper library function
    if (function_exists('QRcode::png')) {
        // Using phpqrcode library to generate directly as PNG
        QRcode::png($data, $filename, QR_ECLEVEL_H, 10);
        return $filename;
    } else {
        // Using our fallback API method
        QRcode_png($data, $filename);
        return $filename;
    }
}

// Generate PDF with QR code and booking details
function generateBookingPDF($qrImageFile, $title, $subtitle, $instruction, $bookingDetails = null) {
    try {
        // Create PDF file path
        $pdfFile = 'qrcodes/' . basename($qrImageFile, '.png') . '.pdf';
        
        // Check for available PDF libraries
        $useFpdf = false;
        $useMpdf = false;
        
        // Check if FPDF is available
        if (class_exists('FPDF')) {
            $useFpdf = true;
        } else {
            // Try to include FPDF if it exists
            $fpdfPaths = ['fpdf/fpdf.php', 'lib/fpdf/fpdf.php', 'includes/fpdf/fpdf.php', 'vendor/setasign/fpdf/fpdf.php'];
            foreach ($fpdfPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $useFpdf = true;
                    break;
                }
            }
        }
        
        // Check if mPDF is available or can be loaded
        if (class_exists('\\Mpdf\\Mpdf')) {
            $useMpdf = true;
        }
        
        // Generate PDF using available library
        if ($useMpdf) {
            // Use mPDF to generate PDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8', 
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);
            
            // Read the QR code image and convert to base64
            $qrImageData = '';
            if (file_exists($qrImageFile)) {
                $qrImageData = base64_encode(file_get_contents($qrImageFile));
            }
            
            // Create HTML content
            $html = "<html><head><style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { color: #224abe; }
                .qr-section { text-align: center; margin: 20px 0; }
                .qr-container { border: 2px dashed #ccc; padding: 15px; display: inline-block; }
                .instructions { color: #666; font-style: italic; }
                .details { margin: 20px 0; padding: 15px; background: #f8f9fc; border-radius: 5px; }
                .detail-row { margin: 10px 0; }
                .detail-label { font-weight: bold; color: #224abe; }
                </style></head><body>";
                
            $html .= "<div class='header'><h1>{$title}</h1><p>{$subtitle}</p></div>";
            
            if ($bookingDetails !== null) {
                $html .= "<div class='details'><h3>Booking Details</h3>";
                foreach ($bookingDetails as $label => $value) {
                    $html .= "<div class='detail-row'><span class='detail-label'>{$label}:</span> <span>{$value}</span></div>";
                }
                $html .= "</div>";
            }
            
            $html .= "<div class='qr-section'><h2>{$instruction}</h2>";
            $html .= "<p class='instructions'>Scan this QR code with the EVolve scanner app</p>";
            $html .= "<div class='qr-container'>";
            
            if (!empty($qrImageData)) {
                $html .= "<img src='data:image/png;base64,{$qrImageData}' width='200' height='200'>";
            } else {
                $html .= "<p style='color:red'>QR Code image could not be loaded</p>";
            }
            
            $html .= "</div></div>";
            $html .= "<div class='footer'><p>Thank you for choosing EVolve for your EV charging needs!</p></div>";
            $html .= "</body></html>";
            
            $mpdf->WriteHTML($html);
            $mpdf->Output($pdfFile, 'F');
        } 
        elseif ($useFpdf) {
            // Use FPDF to generate PDF
            $pdf = new FPDF();
            $pdf->AddPage();
            
            // Add title
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, $title, 0, 1, 'C');
            
            // Add subtitle
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $subtitle, 0, 1, 'C');
            
            // Add booking details if provided
            if ($bookingDetails !== null) {
                $pdf->Ln(5);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Booking Details', 0, 1);
                
                $pdf->SetFont('Arial', '', 11);
                foreach ($bookingDetails as $label => $value) {
                    $pdf->Cell(40, 8, $label . ':', 0);
                    $pdf->Cell(0, 8, $value, 0, 1);
                }
            }
            
            // Add instruction
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, $instruction, 0, 1, 'C');
            
            // Add QR code instruction
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 5, 'Scan this QR code with the EVolve scanner app', 0, 1, 'C');
            
            // Add QR code image - with error handling
            if (file_exists($qrImageFile)) {
                try {
                    $pdf->Ln(5);
                    // Center the image by calculating the x position
                    $imageWidth = 80;
                    $x = ($pdf->GetPageWidth() - $imageWidth) / 2;
                    
                    // Use png format explicitly
                    $pdf->Image($qrImageFile, $x, $pdf->GetY(), $imageWidth);
                    $pdf->Ln($imageWidth + 10);
                } catch (Exception $e) {
                    // If image insertion fails, create a text placeholder
                    $pdf->Ln(20);
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 10, 'QR Code not available - please visit:', 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->Cell(0, 10, 'Please scan the QR code at your EVolve account page', 0, 1, 'C');
                    $pdf->Ln(20);
                }
            }
            
            // Add footer
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, 'Thank you for choosing EVolve for your EV charging needs!', 0, 1, 'C');
            
            $pdf->Output('F', $pdfFile);
        } 
        else {
            // Fallback: Create a basic HTML file
            $html = "<!DOCTYPE html>
            <html>
            <head>
                <title>{$title}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h1 { color: #224abe; }
                    .qr-section { text-align: center; margin: 20px 0; }
                    .qr-container { border: 2px dashed #ccc; padding: 15px; display: inline-block; margin: 0 auto; }
                    .instructions { color: #666; font-style: italic; }
                    .details { margin: 20px 0; padding: 15px; background: #f8f9fc; border-radius: 5px; }
                    .detail-row { margin: 10px 0; }
                    .detail-label { font-weight: bold; color: #224abe; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h1>{$title}</h1>
                    <p>{$subtitle}</p>
                </div>";
                
            if ($bookingDetails !== null) {
                $html .= "<div class='details'><h3>Booking Details</h3>";
                foreach ($bookingDetails as $label => $value) {
                    $html .= "<div class='detail-row'><span class='detail-label'>{$label}:</span> <span>{$value}</span></div>";
                }
                $html .= "</div>";
            }
            
            $html .= "<div class='qr-section'>
                    <h2>{$instruction}</h2>
                    <p class='instructions'>Scan this QR code with the EVolve scanner app</p>
                    
                    <div class='qr-container'>
                        <img src='{$qrImageFile}' width='200' height='200'>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Thank you for choosing EVolve for your EV charging needs!</p>
                </div>
            </body>
            </html>";
            
            // If no PDF library is available, save as HTML file
            $pdfFile = 'qrcodes/' . basename($qrImageFile, '.png') . '.html';
            file_put_contents($pdfFile, $html);
        }
        
        return $pdfFile;
    } catch (Exception $e) {
        error_log("PDF generation error: " . $e->getMessage());
        return false;
    }
}

// Send email with PDF attachment
function sendEmailWithPDF($to, $pdfFile, $subject, $messageBody) {
    try {
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
        $mail->addAddress($to);
        
        // Attachments
        if ($pdfFile && file_exists($pdfFile)) {
            $mail->addAttachment($pdfFile, 'EVolve_QR_Code.pdf');
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $messageBody;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $messageBody));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

// Process email sending if form submitted
$emailSent = false;
$emailError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $to = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
        // Generate QR code file
        if ($mode === 'station') {
            $qrImageFile = generateQRCodeImage($qrData, 'qrcodes/station_' . $stationId . '.png');
            
            // Generate PDF with QR code
            $pdfFile = generateBookingPDF(
                $qrImageFile, 
                $headerTitle, 
                $subTitle, 
                $instruction,
                [
                    'Station ID' => $stationId,
                    'Station Name' => $station['station_name']
                ]
            );
            
            // Send email
            $subject = "EVolve Station QR Code";
            $messageBody = "
                <h2>EVolve Station QR Code</h2>
                <p>Please find attached your QR code for accessing Station: {$station['station_name']}.</p>
                <p>Scan this QR code at the station to access charging services.</p>
                <p>Thank you for choosing EVolve for your EV charging needs!</p>
            ";
            
        } else if ($mode === 'booking') {
            $qrImageFile = generateQRCodeImage($qrData, 'qrcodes/booking_' . $bookingId . '_' . $userId . '.png');
            
            // Generate PDF with QR code and booking details
            $pdfFile = generateBookingPDF(
                $qrImageFile, 
                $headerTitle, 
                $subTitle, 
                $instruction,
                [
                    'Booking ID' => $booking['booking_id'],
                    'Station' => $booking['station_name'],
                    'Date' => date('d M Y', strtotime($booking['booking_date'])),
                    'Time' => date('h:i A', strtotime($booking['booking_time'])),
                    'Status' => ucfirst($booking['status'])
                ]
            );
            
            // Send email
            $subject = "EVolve Booking QR Code - Booking #{$booking['booking_id']}";
            $messageBody = "
                <h2>EVolve Booking QR Code</h2>
                <p>Please find attached your QR code for Booking #{$booking['booking_id']} at {$booking['station_name']}.</p>
                <p><strong>Booking Details:</strong></p>
                <ul>
                    <li>Date: " . date('d M Y', strtotime($booking['booking_date'])) . "</li>
                    <li>Time: " . date('h:i A', strtotime($booking['booking_time'])) . "</li>
                    <li>Status: " . ucfirst($booking['status']) . "</li>
                </ul>
                <p>Scan this QR code at the station for check-in/check-out.</p>
                <p>Thank you for choosing EVolve for your EV charging needs!</p>
            ";
        }
        
        if (isset($pdfFile) && $pdfFile) {
            $emailSent = sendEmailWithPDF($to, $pdfFile, $subject, $messageBody);
            
            // Clean up files after sending
            if ($emailSent) {
                if (file_exists($qrImageFile)) @unlink($qrImageFile);
                if (file_exists($pdfFile)) @unlink($pdfFile);
            } else {
                $emailError = 'Failed to send email. Please try again.';
            }
        } else {
            $emailError = 'Failed to generate PDF. Please try again.';
        }
    } else {
        $emailError = 'Invalid email address. Please enter a valid email.';
    }
}

// Generate QR code file for display on webpage
$qrImageFile = '';
if ($mode === 'station') {
    $qrImageFile = generateQRCodeImage($qrData, 'qrcodes/station_' . $stationId . '.png');
} else if ($mode === 'booking') {
    $qrImageFile = generateQRCodeImage($qrData, 'qrcodes/booking_' . $bookingId . '_' . $userId . '.png');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fc;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .header h1 {
            color: #224abe;
            margin-bottom: 10px;
        }
        
        .content {
            margin-top: 30px;
        }
        
        .qr-container {
            position: relative;
            display: block;
            margin: 20px auto;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            width: fit-content;
        }
        
        #qrcode {
            margin: 0 auto;
            display: block;
        }
        
        #qrcode img {
            display: block;
            width: 200px;
            height: 200px;
            margin: 0 auto;
        }
        
        .qr-badge {
            position: absolute;
            top: -25px;
            right: 40px;
            background: #224abe;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .qr-badge i {
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .instructions {
            color: #666;
            margin-bottom: 20px;
        }
        
        .qr-data {
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fc;
            border-radius: 5px;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
        }
        
        .button {
            display: inline-block;
            background: #224abe;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .button:hover {
            background: #1a3997;
            transform: translateY(-2px);
        }
        
        .booking-details {
            margin: 20px 0;
            text-align: left;
            background: #f8f9fc;
            padding: 15px;
            border-radius: 10px;
        }
        
        .detail-row {
            margin: 10px 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #224abe;
        }
        
        /* Email form styles */
        .email-form {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 10px;
        }
        
        .email-form input[type="email"] {
            padding: 10px;
            width: 60%;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }
        
        .email-form button {
            background: #224abe;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            margin-left: 10px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $headerTitle; ?></h1>
            <p><?php echo $subTitle; ?></p>
        </div>
        
        <div class="content">
            <h2><?php echo $instruction; ?></h2>
            <p class="instructions">Scan this QR code with the EVolve scanner app</p>
            
            <div class="qr-container">
                <div class="qr-badge"><i class="fas fa-bolt"></i> <?php echo $qrLabel; ?></div>
                <div id="qrcode">
                    <img src="<?php echo $qrImageFile; ?>" alt="QR Code">
                </div>
            </div>
            
            <div class="email-form">
                <h3>Email QR Code as PDF</h3>
                <?php if ($emailSent): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> QR code has been sent to your email!
                    </div>
                <?php endif; ?>
                
                <?php if ($emailError): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $emailError; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="email" name="email" placeholder="Enter your email address" required>
                    <button type="submit"><i class="fas fa-envelope"></i> Send PDF</button>
                </form>
            </div>
            
            <?php if ($mode === 'booking'): ?>
            <div class="booking-details">
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span> 
                    <span><?php echo $booking['booking_id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Station:</span> 
                    <span><?php echo htmlspecialchars($booking['station_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span> 
                    <span><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span> 
                    <span><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span> 
                    <span><?php echo ucfirst($booking['status']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="qr-data">
                <p>QR Data: <span id="qr-data-text"><?php echo htmlspecialchars($qrData); ?></span></p>
                <button onclick="copyToClipboard()">Copy Data</button>
            </div>
            
            <?php if ($mode === 'station'): ?>
            <a href="station-owner-dashboard.php" class="button">Back to Dashboard</a>
            <?php else: ?>
            <a href="bookings.php" class="button">Back to Bookings</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function copyToClipboard() {
        const dataElement = document.getElementById('qr-data-text');
        const text = dataElement.textContent;
        
        navigator.clipboard.writeText(text).then(function() {
            alert('Data copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    </script>
</body>
</html>