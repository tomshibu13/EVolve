<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get all active stations
try {
    $stmt = $pdo->query("SELECT station_id, name, address FROM charging_stations WHERE status = 'active'");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $stations = [];
}

// Generate base URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Station QR Codes - EVolve Admin</title>
    <!-- Include the qrcode-generator directly to ensure it's loaded -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <!-- Include our custom QR generator library -->
    <script src="js/qr-generator.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4e73df;
            text-align: center;
        }
        .station-qr {
            margin: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .station-qr-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .print-button {
            background-color: #4e73df;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
        }
        .download-button {
            background-color: #1cc88a;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        @media print {
            .no-print {
                display: none;
            }
            .station-qr {
                page-break-inside: avoid;
                margin: 0;
                padding: 10px;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="no-print">Station QR Codes</h1>
        <p class="no-print" style="text-align: center;">Print these QR codes and place them at each charging station. Users need to scan these before scanning their booking QR codes.</p>
        
        <div class="no-print" style="text-align: center;">
            <button class="print-button" onclick="window.print();">Print All QR Codes</button>
            <a href="admin_dashboard.php" class="print-button">Back to Dashboard</a>
        </div>
        
        <div class="station-qr-container">
            <?php if (count($stations) > 0): ?>
                <?php foreach ($stations as $station): ?>
                    <?php 
                    $scanUrl = $baseUrl . "/scan_station.php?station_id=" . $station['station_id']; 
                    $qrCodeId = "qrcode-" . $station['station_id'];
                    ?>
                    <div class="station-qr">
                        <h3><?php echo htmlspecialchars($station['name']); ?></h3>
                        <p><?php echo htmlspecialchars($station['address']); ?></p>
                        <div id="<?php echo $qrCodeId; ?>" class="qrcode-container"></div>
                        <p>Station ID: <?php echo $station['station_id']; ?></p>
                        <p>Scan this QR code to use this charging station</p>
                        <button class="download-button no-print" onclick="downloadStationQR(<?php echo $station['station_id']; ?>, '<?php echo addslashes($station['name']); ?>')">Download QR Code</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No active stations found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Function to download QR code as an image using our library
        function downloadStationQR(stationId, stationName) {
            const containerId = 'qrcode-' + stationId;
            const filename = 'EVolve-Station-' + stationId + '-' + stationName.replace(/[^a-z0-9]/gi, '-').toLowerCase() + '.png';
            
            // Use our library's download function
            EVolveQR.download(containerId, filename);
        }
        
        // Generate QR codes for all stations when page loads using our library
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($stations as $station): ?>
            // Generate QR code using our custom library
            EVolveQR.generate(
                "<?php echo $scanUrl = $baseUrl . "/scan_station.php?station_id=" . $station['station_id']; ?>", 
                "qrcode-<?php echo $station['station_id']; ?>",
                {
                    size: 200,
                    // Optional: Add your logo
                    // logo: "img/evolve-logo.png",
                    // logoWidth: 40,
                    // logoHeight: 40
                }
            );
            <?php endforeach; ?>
            
            console.log("QR codes generated for all stations");
        });
    </script>
</body>
</html> 