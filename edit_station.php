<?php
// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

$success_message = '';
$error_message = '';
$station = null;

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: admindash.php");
    exit();
}

$station_id = intval($_GET['id']);

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $charger_types = json_encode(['types' => $_POST['charger_types']]);
        $total_slots = intval($_POST['total_slots']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $price = floatval($_POST['price']);
        $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);

        // Create POINT object for location
        $point = "POINT($longitude $latitude)";

        $update_query = "UPDATE charging_stations SET 
                 name = ?, 
                 location = ST_GeomFromText(?),
                 address = ?,
                 charger_types = ?,
                 total_slots = ?,
                 status = ?,
                 price = ?,
                 owner_name = ?
                 WHERE station_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if ($update_stmt === false) {
            throw new Exception("Error preparing update statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($update_stmt, "ssssisssi", 
            $name, 
            $point, 
            $address, 
            $charger_types,
            $total_slots,
            $status,
            $price,
            $owner_name,
            $station_id
        );

        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("Error updating station: " . mysqli_error($conn));
        }

        $success_message = "Station updated successfully!";
        mysqli_stmt_close($update_stmt);
    }

    // Fetch station data
    $select_query = "
        SELECT 
            station_id,
            name,
            address,
            ST_X(location) as longitude,
            ST_Y(location) as latitude,
            charger_types,
            total_slots,
            IFNULL(status, 'active') as status,
            price,
            owner_name
        FROM charging_stations 
        WHERE station_id = ?
    ";

    $select_stmt = mysqli_prepare($conn, $select_query);
    
    if ($select_stmt === false) {
        throw new Exception("Error preparing select statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($select_stmt, "i", $station_id);
    
    if (!mysqli_stmt_execute($select_stmt)) {
        throw new Exception("Error executing select query: " . mysqli_error($conn));
    }

    $result = mysqli_stmt_get_result($select_stmt);

    if (!$result) {
        throw new Exception("Error getting result: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $station = mysqli_fetch_assoc($result);
        
        // Ensure charger_types is an array
        if (!empty($station['charger_types'])) {
            $station['charger_types'] = json_decode($station['charger_types'], true) ?? [];
        } else {
            $station['charger_types'] = [];
        }
    } else {
        throw new Exception("Station not found with ID: " . $station_id);
    }

    mysqli_stmt_close($select_stmt);

} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Error in edit_station.php: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn) {
        mysqli_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Charging Station - EVolve Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
            --error-color: #e74c3c;
            --text-color: #2c3e50;
            --bg-color: #f9f9f9;
            --input-bg: #ffffff;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        h1 {
            color: var(--text-color);
            margin: 0;
            font-size: 2rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-container {
            background-color: var(--input-bg);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        select[multiple] {
            height: auto;
            min-height: 120px;
        }

        #map {
            height: 300px;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        .location-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            position: relative;
        }

        .location-input-group input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .geolocation-button {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .geolocation-button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .geolocation-button:active {
            transform: translateY(0);
        }

        .loading-indicator {
            position: absolute;
            bottom: -30px;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            text-align: center;
            border-radius: 4px;
            font-size: 14px;
            color: #666;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #map {
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .coordinates-display {
            display: flex;
            gap: 20px;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 14px;
        }

        .coordinate {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .coordinate label {
            font-weight: 500;
            color: #666;
        }

        .coordinate span {
            font-family: monospace;
            background: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 100px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="admindash.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <h1>Edit Charging Station</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($station): ?>
        <div class="form-container">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $station_id; ?>">
                <div class="form-group">
                    <label for="name">Station Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($station['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <div class="location-input-group">
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($station['address']); ?>" required>
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($station['latitude']); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($station['longitude']); ?>">
                        <button type="button" class="geolocation-button" onclick="getCurrentLocation()">
                            <i class="fas fa-location-arrow"></i>
                            Use Current Location
                        </button>
                    </div>
                    <div class="coordinates-display">
                        <div class="coordinate">
                            <label>Latitude:</label>
                            <span id="lat-display"><?php echo htmlspecialchars($station['latitude']); ?></span>
                        </div>
                        <div class="coordinate">
                            <label>Longitude:</label>
                            <span id="lng-display"><?php echo htmlspecialchars($station['longitude']); ?></span>
                        </div>
                    </div>
                    <div id="map"></div>
                </div>

                <div class="form-group">
                    <label for="charger_types">Charger Types</label>
                    <select id="charger_types" name="charger_types[]" multiple required>
                        <?php
                        $types = ["Type1", "Type2", "CCS", "CHAdeMO"];
                        foreach ($types as $type) {
                            $selected = in_array($type, $station['charger_types']) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($type) . "\" $selected>" . htmlspecialchars($type) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="total_slots">Total Charging Slots</label>
                    <input type="number" id="total_slots" name="total_slots" min="1" value="<?php echo htmlspecialchars($station['total_slots']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo $station['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $station['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="owner_name">Owner Name</label>
                    <input type="text" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($station['owner_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="price">Price per kWh (₹)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($station['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Slots per Charger Type</label>
                    <div id="slots-container">
                        <!-- Slots inputs will be dynamically added here -->
                    </div>
                </div>

                <button type="submit" class="submit-btn">Update Station</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;

        // Initialize map
        function initMap() {
            const initialLat = <?php echo $station['latitude']; ?>;
            const initialLng = <?php echo $station['longitude']; ?>;

            // Initialize map with station's location
            map = L.map('map').setView([initialLat, initialLng], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add initial marker
            marker = L.marker([initialLat, initialLng], {
                draggable: true
            }).addTo(map);

            // Add click event to map
            map.on('click', function(e) {
                setLocation(e.latlng.lat, e.latlng.lng);
            });

            // Update coordinates when marker is dragged
            marker.on('dragend', function(e) {
                const position = marker.getLatLng();
                setLocation(position.lat, position.lng);
            });

            // Add mousemove event
            map.on('mousemove', function(e) {
                document.getElementById('lat-display').textContent = e.latlng.lat.toFixed(6);
                document.getElementById('lng-display').textContent = e.latlng.lng.toFixed(6);
            });

            // Reset coordinate display when mouse leaves map
            map.on('mouseout', function() {
                const lat = document.getElementById('latitude').value;
                const lng = document.getElementById('longitude').value;
                document.getElementById('lat-display').textContent = lat ? parseFloat(lat).toFixed(6) : '-';
                document.getElementById('lng-display').textContent = lng ? parseFloat(lng).toFixed(6) : '-';
            });
        }

        // Set location on map and update form fields
        function setLocation(lat, lng) {
            const position = new L.LatLng(lat, lng);
            marker.setLatLng(position);
            map.panTo(position);
            
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
            document.getElementById('lat-display').textContent = lat.toFixed(6);
            document.getElementById('lng-display').textContent = lng.toFixed(6);

            // Perform reverse geocoding to get address
            reverseGeocode(lat, lng);
        }

        // Get current location using browser geolocation
        function getCurrentLocation() {
            if ("geolocation" in navigator) {
                showLoadingIndicator();
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        setLocation(position.coords.latitude, position.coords.longitude);
                        hideLoadingIndicator();
                    },
                    function(error) {
                        handleGeolocationError(error);
                        hideLoadingIndicator();
                    }
                );
            } else {
                alert("Geolocation is not supported by your browser");
            }
        }

        // Handle geolocation errors
        function handleGeolocationError(error) {
            let errorMessage;
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = "Location access denied by user.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = "Location information unavailable.";
                    break;
                case error.TIMEOUT:
                    errorMessage = "Location request timed out.";
                    break;
                default:
                    errorMessage = "An unknown error occurred.";
            }
            alert(errorMessage);
        }

        // Show loading indicator while getting location
        function showLoadingIndicator() {
            const container = document.querySelector('.location-input-group');
            const indicator = document.createElement('div');
            indicator.className = 'loading-indicator';
            indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
            container.appendChild(indicator);
        }

        // Hide loading indicator
        function hideLoadingIndicator() {
            const indicator = document.querySelector('.loading-indicator');
            if (indicator) {
                indicator.remove();
            }
        }

        // Reverse geocode coordinates to get address
        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=hi`)
                .then(response => response.json())
                .then(data => {
                    if (data.display_name) {
                        document.getElementById('address').value = data.display_name;
                    }
                })
                .catch(error => {
                    console.error('Error reverse geocoding:', error);
                });
        }

        // Initialize map when page loads
        window.onload = initMap;

        document.getElementById('charger_types').addEventListener('change', function() {
            const selectedOptions = Array.from(this.selectedOptions);
            const slotsContainer = document.getElementById('slots-container');
            const totalSlotsInput = document.getElementById('total_slots');
            
            // Clear previous slots inputs
            slotsContainer.innerHTML = '';
            
            // Create input for each selected charger type
            selectedOptions.forEach(option => {
                const div = document.createElement('div');
                div.style.marginBottom = '10px';
                div.innerHTML = `
                    <label style="display: inline-block; width: 150px;">${option.text}:</label>
                    <input type="number" 
                           class="charger-slots" 
                           data-type="${option.value}" 
                           min="0" 
                           value="0" 
                           style="width: 100px;">
                `;
                slotsContainer.appendChild(div);
            });

            // Add event listeners to new inputs
            document.querySelectorAll('.charger-slots').forEach(input => {
                input.addEventListener('input', updateTotalSlots);
            });

            updateTotalSlots();
        });

        function updateTotalSlots() {
            const slots = Array.from(document.querySelectorAll('.charger-slots'))
                .map(input => parseInt(input.value) || 0)
                .reduce((sum, current) => sum + current, 0);
            
            document.getElementById('total_slots').value = slots;
        }
    </script>
</body>
</html> 