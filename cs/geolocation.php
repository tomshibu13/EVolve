<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Test query to check data
    $test_query = "SELECT * FROM charging_stations LIMIT 1";
    $result = $conn->query($test_query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        error_log("Test row: " . print_r($row, true));  // This will log the data to PHP error log
    } else {
        error_log("Query failed: " . $conn->error);
    }

    // Function to get nearby stations based on coordinates
    function getNearbyStations($lat, $lng, $radius = 10) {
        global $conn;
        
        // Set user location as MySQL variables
        $conn->query("SET @lat = {$lat}");
        $conn->query("SET @lng = {$lng}");
        
        $sql = "SELECT 
                station_id,
                owner_name,
                name,
                ST_X(location) as lng,
                ST_Y(location) as lat,
                status,
                address,
                price,
                total_slots,
                available_slots,
                charger_types,
                image,
                (
                    6371 * acos(
                        cos(radians(@lat)) * cos(radians(ST_Y(location))) *
                        cos(radians(ST_X(location)) - radians(@lng)) +
                        sin(radians(@lat)) * sin(radians(ST_Y(location)))
                    )
                ) AS distance
                FROM charging_stations 
                WHERE status = 'active'
                HAVING distance <= ?
                ORDER BY distance";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("d", $radius);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        
        $stations = array();
        while($row = $result->fetch_assoc()) {
            $stations[] = array(
                'id' => $row['station_id'],
                'ownerName' => $row['owner_name'],
                'name' => $row['name'],
                'lat' => floatval($row['lat']),
                'lng' => floatval($row['lng']),
                'status' => $row['status'],
                'address' => $row['address'],
                'price' => $row['price'],
                'totalSlots' => $row['total_slots'],
                'availableSlots' => $row['available_slots'],
                'chargerTypes' => json_decode($row['charger_types'], true),
                'image' => $row['image'],
                'distance' => round($row['distance'], 2)
            );
        }
        
        return $stations;
    }

    // Handle AJAX requests
    if (isset($_GET['action'])) {
        // Disable error display in output
        ini_set('display_errors', 0);
        error_reporting(E_ALL);
        
        // Ensure no output before headers
        ob_clean();
        header('Content-Type: application/json');
        
        switch ($_GET['action']) {
            case 'getNearby':
                try {
                    if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
                        throw new Exception("Latitude and longitude are required");
                    }
                    
                    $lat = floatval($_GET['lat']);
                    $lng = floatval($_GET['lng']);
                    $radius = floatval($_GET['radius'] ?? 10);
                    
                    // Validate coordinates
                    if ($lat == 0 && $lng == 0) {
                        throw new Exception("Invalid coordinates");
                    }
                    
                    // Log the input parameters
                    error_log("Searching with parameters - lat: $lat, lng: $lng, radius: $radius");
                    
                    $stations = getNearbyStations($lat, $lng, $radius);
                    
                    // Log the result
                    error_log("Found " . count($stations) . " stations");
                    
                    $response = [
                        'success' => true,
                        'stations' => $stations,
                        'count' => count($stations)
                    ];
                    
                    echo json_encode($response, JSON_THROW_ON_ERROR);
                    
                } catch (Exception $e) {
                    error_log("Error in getNearby: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ], JSON_THROW_ON_ERROR);
                }
                break;
            
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid action'
                ], JSON_THROW_ON_ERROR);
        }
        exit;
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EV Charging Station Locator</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="../main.css">
    <link rel="stylesheet" href="../header.css">
    <link rel="stylesheet" href="../booking-styles.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .map-container {
            height: 500px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
        }

        .controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .radius-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 120px;
        }

        .locate-btn {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .locate-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .station-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .station-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .station-card:hover {
            transform: translateY(-5px);
        }

        .station-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .station-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .station-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .station-info {
            color: #666;
            font-size: 14px;
            margin: 8px 0;
        }

        .station-info i {
            width: 20px;
            color: #4CAF50;
            margin-right: 8px;
        }

        .station-distance {
            color: #2196F3;
            font-weight: 500;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 1000;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .user-location-marker {
            color: #2196F3;
            font-size: 24px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .location-button {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            padding: 10px 20px;
            background: white;
            border: none;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .location-button:hover {
            background: #f5f5f5;
            transform: translateY(-2px);
        }

        .location-button i {
            color: #2196F3;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
            }

            .radius-select {
                width: 100%;
            }

            .locate-btn {
                width: 100%;
                justify-content: center;
            }

            .station-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../header.php' ?>
    <div class="container">
        <h1>Find Nearby Charging Stations</h1>
        
        <div class="controls">
            <select id="searchRadius" class="radius-select">
                <option value="5">5 km</option>
                <option value="10" selected>10 km</option>
                <option value="20">20 km</option>
                <option value="50">50 km</option>
            </select>
            
            <button onclick="findNearbyStations()" class="locate-btn">
                <i class="fas fa-location-arrow"></i>
                Find Nearby Stations
            </button>
        </div>

        <div id="error" class="error"></div>
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> Finding nearby stations...
        </div>

        <div id="map" class="map-container"></div>
        <div id="stationList" class="station-list"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        let map;
        let userMarker;
        let radiusCircle;
        const markers = [];

        // Initialize map
        function initMap() {
            map = L.map('map').setView([10.8505, 76.2711], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

        // Find nearby stations
        function findNearbyStations() {
            if (!navigator.geolocation) {
                showError("Geolocation is not supported by your browser");
                return;
            }

            showLoading();

            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const radius = document.getElementById('searchRadius').value;

                    // Update map view
                    updateMapView(lat, lng, radius);

                    // Fetch nearby stations
                    fetch(`geolocation.php?action=getNearby&lat=${lat}&lng=${lng}&radius=${radius}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showStations(data.stations);
                            } else {
                                throw new Error(data.error);
                            }
                        })
                        .catch(error => {
                            showError("Failed to fetch nearby stations: " + error.message);
                        })
                        .finally(() => {
                            hideLoading();
                        });
                },
                error => {
                    hideLoading();
                    handleGeolocationError(error);
                }
            );
        }

        // Update map view with user location and radius
        function updateMapView(lat, lng, radius) {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers.length = 0;

            if (userMarker) map.removeLayer(userMarker);
            if (radiusCircle) map.removeLayer(radiusCircle);

            // Add user marker
            userMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'user-location-marker',
                    html: '<i class="fas fa-user-circle"></i>'
                })
            }).addTo(map);

            // Add radius circle
            radiusCircle = L.circle([lat, lng], {
                radius: radius * 1000,
                color: '#4CAF50',
                fillColor: '#4CAF50',
                fillOpacity: 0.1,
                weight: 1
            }).addTo(map);

            // Fit map to show the entire radius
            map.fitBounds(radiusCircle.getBounds());
        }

        // Show stations on map and in list
        function showStations(stations) {
            const stationList = document.getElementById('stationList');
            stationList.innerHTML = '';

            stations.forEach(station => {
                // Add marker to map
                const marker = L.marker([station.lat, station.lng])
                    .bindPopup(createPopupContent(station))
                    .addTo(map);
                markers.push(marker);

                // Add station card to list
                stationList.innerHTML += createStationCard(station);
            });
        }

        // Create popup content for map markers
        function createPopupContent(station) {
            return `
                <div class="marker-popup">
                    <div class="popup-header">
                        <h3>${station.name}</h3>
                        <span class="status-badge ${station.status}">${station.status}</span>
                    </div>
                    <div class="popup-content">
                        <div class="popup-info">
                            <i class="fas fa-map-marker-alt"></i> ${station.address}
                        </div>
                        <div class="popup-stats">
                            <div class="stat">
                                <i class="fas fa-plug"></i>
                                <span>${station.availableSlots}/${station.totalSlots} Available</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-rupee-sign"></i>
                                <span>${station.price}/kWh</span>
                            </div>
                            <div class="stat">
                                <i class="fas fa-road"></i>
                                <span>${station.distance} km away</span>
                            </div>
                        </div>
                        <a href="../station_details.php?id=${station.id}" class="popup-details-btn">
                            <span>View Details</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>`;
        }

        // Create station card HTML
        function createStationCard(station) {
            return `
                <div class="station-card">
                    <div class="station-header">
                        <h3 class="station-name">${station.name}</h3>
                        <span class="station-status status-${station.status}">${station.status}</span>
                    </div>
                    <div class="station-info">
                        <p><i class="fas fa-map-marker-alt"></i> ${station.address}</p>
                        <p><i class="fas fa-plug"></i> ${station.availableSlots}/${station.totalSlots} slots available</p>
                        <p><i class="fas fa-rupee-sign"></i> ${station.price}/kWh</p>
                    </div>
                    <div class="station-distance">
                        <i class="fas fa-road"></i> ${station.distance} km away
                    </div>
                </div>`;
        }

        // Error handling functions
        function handleGeolocationError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    showError("Please allow location access to find nearby stations.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    showError("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    showError("Location request timed out.");
                    break;
                default:
                    showError("An unknown error occurred.");
                    break;
            }
        }

        function showError(message) {
            const error = document.getElementById('error');
            error.textContent = message;
            error.style.display = 'block';
        }

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        // Initialize map when page loads
        window.onload = initMap;
    </script>
</body>
</html>
