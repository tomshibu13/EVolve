<?php
session_start();
require_once 'config.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
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
        
        /* Add specific styles for the QR code container */
        #qrcode {
            min-height: 200px;
            min-width: 200px;
            margin: 0 auto;
            display: block;
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
                <div id="qrcode"></div>
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
    document.addEventListener('DOMContentLoaded', function() {
        try {
            // Create QR code with the data
            const qrElement = document.getElementById("qrcode");
            // Clear the element first to ensure no content interferes with the QR code
            qrElement.innerHTML = '';
            const qrData = <?php echo json_encode($qrData); ?>;
            
            if (typeof QRCode === 'undefined') {
                console.error("QR Code library not loaded correctly");
                qrElement.innerHTML = '<p style="color:red">Error loading QR code generator. Please try refreshing the page.</p>';
                return;
            }
            
            new QRCode(qrElement, {
                text: qrData,
                width: 200,
                height: 200,
                colorDark: "#224abe",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            
            console.log("QR code generated with data:", qrData);
        } catch (error) {
            console.error("Error generating QR code:", error);
            document.getElementById("qrcode").innerHTML = 
                '<p style="color:red">Error generating QR code: ' + error.message + '</p>';
        }
    });
    
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