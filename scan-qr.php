<?php
session_start();
require_once 'config.php';

// Enhanced debugging information
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? 0;
$isStationOwner = isset($_SESSION['is_station_owner']) && $_SESSION['is_station_owner'];
$ownerName = $_SESSION['name'] ?? '';
$sessionData = $_SESSION; // Capture all session data for debugging

// Debug information
$debugInfo = [
    'isLoggedIn' => $isLoggedIn ? 'Yes' : 'No',
    'userId' => $userId,
    'isStationOwner' => $isStationOwner ? 'Yes' : 'No',
    'ownerName' => $ownerName,
    'sessionData' => $sessionData
];

// TEMPORARY FIX: Allow manual selection of stations for all users
// Remove this after testing and debugging
$allowStationSelection = true; // Set to true to override station owner check temporarily

// Get stations based on user ID or owner_name
$stations = [];
try {
    // More flexible query to find stations
    $stmt = $pdo->prepare("
        SELECT station_id, name, address, total_slots, available_slots 
        FROM charging_stations 
        WHERE owner_name LIKE ? OR operator_id = ?
        ORDER BY name ASC
    ");
    
    // Use % wildcard to be more forgiving with owner_name matching
    $stmt->execute(["%$ownerName%", $userId]);
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no stations found, try a broader search (for debugging)
    if (empty($stations) && $isLoggedIn) {
        $debugInfo['broadSearch'] = 'Attempted';
        // Get all stations (for debugging - limit to 5)
        $stmt = $pdo->prepare("
            SELECT station_id, name, address, total_slots, available_slots, owner_name, operator_id
            FROM charging_stations 
            LIMIT 5
        ");
        $stmt->execute();
        $debugInfo['allStations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $debugInfo['error'] = $e->getMessage();
}

// Get current station ID from URL
$currentStationId = isset($_GET['station_id']) ? $_GET['station_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVolve - Scan QR Code</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #result-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .button {
            background-color: #2C7873;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .station-selector {
            background-color: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: 100%;
            max-width: 400px;
            margin-bottom: 10px;
        }
        .station-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .station-card:hover {
            background-color: #f0f0f0;
            border-color: #2C7873;
        }
        .station-card.selected {
            background-color: #e0f7fa;
            border-color: #2C7873;
            border-width: 2px;
        }
        .station-name {
            font-weight: bold;
            font-size: 18px;
        }
        .station-address {
            color: #666;
            font-size: 14px;
            margin-top: 4px;
        }
        .station-slots {
            margin-top: 8px;
            font-size: 14px;
        }
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #2C7873;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #booking-details {
            margin-top: 15px;
        }
        .detail-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .detail-label {
            font-weight: bold;
            width: 40%;
        }
        .action-buttons {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        .upload-option {
            margin-top: 20px;
            text-align: center;
        }
        .file-upload {
            display: none;
        }
        .upload-label {
            background-color: #6c757d;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
        }
        /* Add info style for completed bookings */
        .info {
            background-color: #cce5ff;
            border-color: #b8daff;
        }
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            border: 1px solid #ddd;
        }
        
        .station-option {
            display: block;
            padding: 10px;
            margin: 5px 0;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        
        .station-option:hover {
            background-color: #e0e0e0;
        }
        
        .station-option.active {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alerts {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <h1>EVolve Charging Station</h1>
    
    <!-- Station ID indicator -->
    <div style="background-color: #f8f9fa; padding: 8px; margin-bottom: 10px; border-radius: 4px; font-size: 12px;">
        Station ID: <span id="current-station-id"><?php echo $currentStationId ?: 'Not specified'; ?></span>
    </div>
    
    <!-- Login status banner -->
    <?php if (!$isLoggedIn): ?>
    <div class="alerts alert-warning">
        <h3>Not Logged In</h3>
        <p>You are not currently logged in. Some features may be limited.</p>
        <a href="login.php" class="button">Log In</a>
    </div>
    <?php endif; ?>
    
    <!-- Station Selection Section -->
    <div class="station-list">
        <h2>Select a Station</h2>
        
        <?php if (count($stations) > 0): ?>
            <p>Please select the station you want to manage:</p>
            <?php foreach ($stations as $station): ?>
                <a href="scan-qr.php?station_id=<?php echo $station['station_id']; ?>" 
                   class="station-option <?php echo $currentStationId == $station['station_id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($station['name']); ?> 
                    (<?php echo htmlspecialchars($station['address']); ?>)
                </a>
            <?php endforeach; ?>
        <?php elseif ($isLoggedIn): ?>
            <div class="alerts alert-info">
                <h3>No Stations Found</h3>
                <p>No stations were found associated with your account.</p>
                <p>If you believe this is an error, please contact support.</p>
            </div>
            
            <!-- For testing - hardcoded station options -->
            <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px;">
                <h3>Manual Station Selection</h3>
                <p>For testing purposes, you can manually select a station by ID:</p>
                <form action="scan-qr.php" method="get">
                    <input type="number" name="station_id" placeholder="Enter Station ID" style="padding: 8px; width: 200px;">
                    <button type="submit" class="button">Select Station</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alerts alert-warning">
                <h3>Station Selection Unavailable</h3>
                <p>You need to be logged in to select stations.</p>
                <a href="login.php" class="button">Log In</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- QR Scanner Section -->
    <h2>Scan Booking QR Code</h2>
    <p>Please scan the QR code from your booking email or the mobile app.</p>
    
    <div id="scanner-container">
        <div id="qr-reader" style="width: 100%"></div>
        <div id="loader" style="display: none; text-align: center; margin-top: 20px;">
            <div class="spinner"></div>
            <p>Processing...</p>
        </div>
    </div>
    
    <div id="result-container" style="display: none;">
        <h2>Booking Verification</h2>
        <p id="result-message"></p>
        <div id="booking-details"></div>
        
        <button id="checkInButton" class="button" style="display: none;">Check In</button>
        <button id="checkOutButton" class="button" style="display: none;">Check Out</button>
        <button id="scanAgainButton" class="button">Scan Again</button>
    </div>
    
    <!-- Technical Debug Info - Collapsible -->
    <div style="margin-top: 40px;">
        <details>
            <summary style="cursor: pointer; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                Technical Debug Information
            </summary>
            <div style="background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 0 0 4px 4px;">
                <pre><?php echo htmlspecialchars(json_encode($debugInfo, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </details>
    </div>

    <script>
        let html5QrCode;
        let currentBooking = null;
        let debugInfo = {};
        
        function selectStation(stationId) {
            // Update URL with the selected station ID
            const url = new URL(window.location.href);
            url.searchParams.set('station_id', stationId);
            window.location.href = url.toString();
        }
        
        function logDebugInfo(key, value) {
            debugInfo[key] = value;
            console.log(`Debug: ${key} = `, value);
        }
        
        function onScanSuccess(decodedText) {
            // Stop scanning
            html5QrCode.stop();
            
            document.getElementById('loader').style.display = 'block';
            
            // Log the decoded text for debugging
            console.log("QR Code detected:", decodedText);
            
            // Get current station ID for verification
            const urlParams = new URLSearchParams(window.location.search);
            const currentStationId = urlParams.get('station_id');
            
            // Process the QR data directly in JavaScript
            let bookingData;
            try {
                // Try to parse the QR data as JSON
                bookingData = JSON.parse(decodedText);
                logDebugInfo("parsedQRData", bookingData);
                
                // Verify the booking data has the expected fields
                if (!bookingData.booking_id || !bookingData.user_id || !bookingData.station_id) {
                    throw new Error("Invalid booking data format");
                }
                
                // Check if this booking is for this station
                if (currentStationId && String(bookingData.station_id) !== String(currentStationId)) {
                    throw new Error("This booking is for a different station");
                }
                
                // Process the successful scan
                processBooking(bookingData);
                
            } catch (e) {
                console.error("Error processing QR code:", e);
                displayError(e.message || "Invalid QR code format. Please try again.");
            }
        }
        
        function processBooking(bookingData) {
            const bookingId = bookingData.booking_id;
            const userId = bookingData.user_id;
            const bookingStationId = String(bookingData.station_id);
            const resultContainer = document.getElementById('result-container');
            const resultMessage = document.getElementById('result-message');
            const bookingDetails = document.getElementById('booking-details');
            
            // Get the current station ID from the URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentStationId = String(urlParams.get('station_id'));
            
            // Log for debugging
            logDebugInfo("bookingStationId", bookingStationId);
            logDebugInfo("currentStationId", currentStationId);
            
            // Update the station ID display
            document.getElementById('current-station-id').textContent = currentStationId || 'Not specified';
            
            // CRITICAL: Strict station verification - do not allow automatic station ID assignment
            // If the station ID is not in the URL or doesn't match the booking, reject the scan
            if (!currentStationId || currentStationId === 'null') {
                // No station context - display error
                resultContainer.className = 'error';
                resultContainer.style.display = 'block';
                document.getElementById('scanner-container').style.display = 'none';
                resultMessage.textContent = `Error: Please scan the station QR code first before scanning your booking QR code.`;
                
                // Show details for debugging
                bookingDetails.innerHTML = `
                    <div class="detail-row">
                        <div class="detail-label">Booking Station ID:</div>
                        <div>${bookingStationId}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Station ID:</div>
                        <div>Not set - please scan station QR first</div>
                    </div>
                `;
                
                // Add a button to scan station QR - with proper URL
                bookingDetails.innerHTML += `
                    <div class="detail-row" style="margin-top: 20px;">
                        <a href="scan-qr.php?station_id=${bookingStationId}" class="button">Proceed to Station ${bookingStationId}</a>
                    </div>
                `;
                return;
            }
            
            // Strict station ID comparison
            if (bookingStationId !== currentStationId) {
                console.error(`Station ID mismatch: Booking is for station "${bookingStationId}", but current station is "${currentStationId}"`);
                
                // Station mismatch - display error
                resultContainer.className = 'error';
                resultContainer.style.display = 'block';
                document.getElementById('scanner-container').style.display = 'none';
                resultMessage.textContent = `Error: This booking is for station #${bookingStationId} but you're at station #${currentStationId}`;
                
                // Show details for debugging
                bookingDetails.innerHTML = `
                    <div class="detail-row">
                        <div class="detail-label">Booking Station ID:</div>
                        <div>${bookingStationId}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Station ID:</div>
                        <div>${currentStationId}</div>
                    </div>
                    <div class="detail-row" style="margin-top: 15px;">
                        <div class="detail-label">What to do:</div>
                        <div>Please use this booking at station #${bookingStationId} only.</div>
                    </div>
                `;
                return;
            }
            
            // Get the booking state from localStorage or initialize new
            const storageKey = `booking_${bookingId}`;
            let booking = JSON.parse(localStorage.getItem(storageKey) || 'null');
            
            // Hide action buttons as we're auto-processing
            document.getElementById('checkInButton').style.display = 'none';
            document.getElementById('checkOutButton').style.display = 'none';
            
            if (!booking) {
                // First scan - automatically check in
                booking = {
                    id: bookingData.booking_id,
                    user_id: bookingData.user_id,
                    station_id: bookingData.station_id,
                    station_name: "EV Charging Station",
                    date: new Date().toISOString().split('T')[0],
                    time: new Date().toTimeString().split(' ')[0],
                    status: "checked_in",  // Auto check-in
                    scan_count: 1
                };
                
                // Show processing message
                resultContainer.className = 'success';
                resultMessage.textContent = 'Processing check-in...';
                
                // Automatically perform check-in
                performCheckIn(booking);
            } 
            else if (booking.status === 'checked_in') {
                // Second scan - automatically check out
                booking.scan_count = 2;
                
                // Show processing message
                resultContainer.className = 'info';
                resultMessage.textContent = 'Processing check-out...';
                
                // Automatically perform check-out
                performCheckOut(booking);
            } 
            else if (booking.status === 'completed') {
                // Already completed
                resultContainer.className = 'info';
                resultMessage.textContent = 'This booking has already been completed.';
                
                // Update current booking for later use
                currentBooking = booking;
                
                // Display booking details
                displayBookingDetails(booking);
            }
        }
        
        function performCheckIn(booking) {
            document.getElementById('loader').style.display = 'block';
            
            // Make a real API call to update the database
            fetch('api/update-booking-and-slots.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bookingId: booking.id,
                    userId: booking.user_id,
                    stationId: booking.station_id,
                    status: 'checked_in',
                    action: 'decrease_slot'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('loader').style.display = 'none';
                
                if (data.success) {
                    // Update the current booking
                    currentBooking = booking;
                    
                    const resultContainer = document.getElementById('result-container');
                    resultContainer.className = 'success';
                    document.getElementById('result-message').textContent = 
                        'Check-in successful! Station slot has been decreased by 1.';
                    
                    console.log(`Station ${currentBooking.station_id} slots decreased by 1`);
                    
                    // Save the updated booking to localStorage
                    const storageKey = `booking_${currentBooking.id}`;
                    localStorage.setItem(storageKey, JSON.stringify(currentBooking));
                    
                    // Display booking details
                    displayBookingDetails(currentBooking);
                } else {
                    displayError(data.message || 'Error updating booking status');
                }
            })
            .catch(error => {
                document.getElementById('loader').style.display = 'none';
                console.error('Error:', error);
                displayError('Network error. Please try again.');
            });
        }
        
        function performCheckOut(booking) {
            document.getElementById('loader').style.display = 'block';
            
            // Make a real API call to update the database
            fetch('api/update-booking-and-slots.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bookingId: booking.id,
                    userId: booking.user_id,
                    stationId: booking.station_id,
                    status: 'completed',
                    action: 'increase_slot'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('loader').style.display = 'none';
                
                if (data.success) {
                    // Update the booking status
                    booking.status = 'completed';
                    currentBooking = booking;
                    
                    const resultContainer = document.getElementById('result-container');
                    resultContainer.className = 'info';
                    document.getElementById('result-message').textContent = 
                        'Check-out complete! Station slot has been increased by 1.';
                    
                    console.log(`Station ${currentBooking.station_id} slots increased by 1`);
                    
                    // Save the updated booking to localStorage
                    const storageKey = `booking_${currentBooking.id}`;
                    localStorage.setItem(storageKey, JSON.stringify(currentBooking));
                    
                    // Display booking details
                    displayBookingDetails(currentBooking);
                } else {
                    displayError(data.message || 'Error updating booking status');
                }
            })
            .catch(error => {
                document.getElementById('loader').style.display = 'none';
                console.error('Error:', error);
                displayError('Network error. Please try again.');
            });
        }
        
        function displayBookingDetails(booking) {
            // Show the result container and hide the scanner
            const resultContainer = document.getElementById('result-container');
            resultContainer.style.display = 'block';
            document.getElementById('scanner-container').style.display = 'none';
            
            // Display booking details
            document.getElementById('booking-details').innerHTML = `
                <div class="detail-row">
                    <div class="detail-label">Booking ID:</div>
                    <div>${booking.id}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Station:</div>
                    <div>${booking.station_name}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date:</div>
                    <div>${new Date(booking.date).toLocaleDateString()}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Time:</div>
                    <div>${booking.time}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div>${booking.status}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Scan Count:</div>
                    <div>${booking.scan_count}</div>
                </div>
            `;
        }

        function displayError(message) {
            const resultContainer = document.getElementById('result-container');
            resultContainer.style.display = 'block';
            resultContainer.className = 'error';
            document.getElementById('scanner-container').style.display = 'none';
            document.getElementById('result-message').textContent = 'Error: ' + message;
            document.getElementById('booking-details').innerHTML = '';
        }

        function onScanError(error) {
            console.warn(`QR scan error: ${error}`);
        }

        window.onload = function() {
            // Immediately show the station ID for debugging
            const urlParams = new URLSearchParams(window.location.search);
            const currentStationId = urlParams.get('station_id');
            document.getElementById('current-station-id').textContent = currentStationId || 'Not specified';
            
            console.log("Page loaded with station ID:", currentStationId);
            
            console.log("Initializing QR scanner...");
            html5QrCode = new Html5Qrcode("qr-reader");
            const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            // Add debug statements to track initialization
            html5QrCode.start(
                { facingMode: "environment" }, 
                qrConfig, 
                onScanSuccess, 
                onScanError
            ).then(() => {
                console.log("QR Scanner started successfully");
            }).catch(err => {
                console.error("Error starting QR scanner:", err);
                document.getElementById('result-message').textContent = 
                    "Could not access camera. Please ensure camera permissions are granted.";
                document.getElementById('result-container').style.display = 'block';
                document.getElementById('result-container').className = 'error';
            });
            
            // Add file input listener for QR code upload
            document.getElementById('qr-input-file').addEventListener('change', event => {
                if (event.target.files.length === 0) {
                    return;
                }
                
                // Stop the camera scanning
                html5QrCode.stop().then(() => {
                    document.getElementById('loader').style.display = 'block';
                    
                    const imageFile = event.target.files[0];
                    console.log("Attempting to scan uploaded file:", imageFile.name);
                    
                    // Scan the uploaded file
                    html5QrCode.scanFile(imageFile, true)
                        .then(decodedText => {
                            // Use the same success handler as camera scanning
                            onScanSuccess(decodedText);
                        })
                        .catch(error => {
                            document.getElementById('loader').style.display = 'none';
                            console.error("Error scanning file:", error);
                            
                            displayError('Could not find a valid QR code in the uploaded image.');
                        });
                });
            });
            
            // Event listener for the Scan Again button
            document.getElementById('scanAgainButton').addEventListener('click', function() {
                document.getElementById('result-container').style.display = 'none';
                document.getElementById('scanner-container').style.display = 'block';
                
                html5QrCode.start(
                    { facingMode: "environment" }, 
                    qrConfig, 
                    onScanSuccess, 
                    onScanError
                );
            });
            
            // Hide action buttons as we're auto-processing
            document.getElementById('checkInButton').style.display = 'none';
            document.getElementById('checkOutButton').style.display = 'none';
        };
    </script>
</body>
</html>