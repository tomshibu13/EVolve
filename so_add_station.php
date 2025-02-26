<?php
session_start();

// Database connection credentials - Move these to the top
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";
$port = 3306;

// Now fetch the owner name
$owner_name = '';
if (isset($_SESSION['user_id'])) {
    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Fetch owner name from station_owner_requests table
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT owner_name FROM station_owner_requests WHERE user_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $owner_name = $row['owner_name'];
        $_SESSION['owner_name'] = $owner_name; // Store in session for future use
    }
    
    $stmt->close();
    $conn->close();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Handle image upload
        $image_paths = [];
        if (!empty($_FILES['station_images']['name'][0])) {
            $upload_dir = 'uploads/stations/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['station_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['station_images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $unique_file_name = uniqid() . '.' . $file_ext;
                $target_file = $upload_dir . $unique_file_name;
                
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($file_ext, $allowed_types)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
                }
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_paths[] = $target_file;
                }
            }
        }

        // Validate input
        if (empty($_POST['name'])) throw new Exception("Station name is required.");
        if (empty($_POST['address'])) throw new Exception("Address is required.");
        if (empty($_POST['latitude']) || empty($_POST['longitude'])) throw new Exception("Location coordinates are required.");
        if (empty($_POST['total_slots'])) throw new Exception("Total slots is required.");

        $name = $conn->real_escape_string($_POST['name']);
        $address = $conn->real_escape_string($_POST['address']);
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $charger_types = isset($_POST['charger_types']) ? json_encode(['types' => $_POST['charger_types']]) : '{"types":[]}';
        $total_slots = intval($_POST['total_slots']);
        $price = floatval($_POST['price']);
        $owner_name = $conn->real_escape_string($_POST['owner_name']);

        $point = "POINT($longitude $latitude)";

        $stmt = $conn->prepare("
            INSERT INTO charging_stations 
            (name, location, address, charger_types, total_slots, available_slots, owner_name, price) 
            VALUES (?, ST_GeomFromText(?), ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssiisd", 
            $name, 
            $point, 
            $address, 
            $charger_types, 
            $total_slots, 
            $total_slots,
            $owner_name,
            $price
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $success_message = "Station added successfully!";

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Charging Station - EVolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        /* Reuse most of your existing CSS styles */
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2C3E50;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #2d3436;
            --border-radius: 10px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .back-button {
            color: var(--secondary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: var(--primary-color);
        }

        .form-container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed #ddd;
            border-radius: 5px;
            cursor: pointer;
        }

        .location-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .geolocation-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .geolocation-button:hover {
            background-color: #45a049;
        }

        #map {
            height: 300px;
            border-radius: var(--border-radius);
            margin-top: 10px;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
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

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
        }

        #slots-container {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .slot-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .slot-input label {
            min-width: 100px;
        }

        .slot-input input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        select[multiple] {
            height: auto;
            min-height: 100px;
        }

        .coordinates-display {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px; 
            margin: 5px 0;
            font-size: 14px;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="station-owner-dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <h1>Add New Charging Station</h1>
        </div>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Station Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="owner_name">Owner Name</label>
                    <input type="text" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($owner_name); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <div class="location-input-group">
                        <input type="text" id="address" name="address" placeholder="Enter station address" required>
                        <div class="coordinates-display">
                            <span id="coordinates-text">Lat: ---, Lng: ---</span>
                        </div>
                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                        <button type="button" class="geolocation-button" onclick="getCurrentLocation()">
                            <i class="fas fa-location-arrow"></i>
                            Use Current Location
                        </button>
                    </div>
                    <div id="map"></div>
                </div>

                <div class="form-group">
                    <label for="charger_types">Available Charger Types</label>
                    <select id="charger_types" name="charger_types[]" multiple required>
                        <option value="Type1">Type 1</option>
                        <option value="Type2">Type 2</option>
                        <option value="CCS">CCS</option>
                        <option value="CHAdeMO">CHAdeMO</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Slots per Charger Type</label>
                    <div id="slots-container"></div>
                </div>

                <div class="form-group">
                    <label for="total_slots">Total Charging Slots</label>
                    <input type="number" id="total_slots" name="total_slots" min="1" required readonly>
                </div>

                <div class="form-group">
                    <label for="price">Price per kWh (₹)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="station_images">Station Images</label>
                    <input type="file" id="station_images" name="station_images[]" accept="image/*" multiple>
                    <div id="image_preview" class="image-preview"></div>
                </div>

                <button type="submit" class="submit-btn">Add Station</button>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
 <!-- Replace Google Maps script with Leaflet -->
 <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map;
        let marker;

        // Initialize map
        function initMap() {
            // Center the map on Kerala
            map = L.map('map').setView([10.8505, 76.2711], 7);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add click event to map
            map.on('click', function(e) {
                setLocation(e.latlng.lat, e.latlng.lng);
            });
        }

        // Set location on map and in form
        function setLocation(lat, lng) {
            // Update hidden inputs
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            // Update coordinates display
            document.getElementById('coordinates-text').textContent = 
                `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;

            // Update or add marker
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);

                // Update coordinates when marker is dragged
                marker.on('dragend', function(e) {
                    const position = marker.getLatLng();
                    setLocation(position.lat, position.lng);
                });
            }

            // Center map on marker
            map.setView([lat, lng], 15);

            // Reverse geocode to get address
            reverseGeocode(lat, lng);
        }

        // Get current location
        function getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            // Show loading indicator
            showLoadingIndicator();

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };

            navigator.geolocation.getCurrentPosition(
                // Success callback
                (position) => {
                    hideLoadingIndicator();
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Set zoom level closer for better visibility
                    map.setView([lat, lng], 16);
                    setLocation(lat, lng);
                },
                // Error callback
                (error) => {
                    hideLoadingIndicator();
                    handleGeolocationError(error);
                },
                // Options
                options
            );
        }

        // Handle geolocation errors
        function handleGeolocationError(error) {
            let errorMessage = "Error getting your location: ";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += "Location permission denied. Please enable location services in your browser settings.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += "Location information unavailable. Please try again.";
                    break;
                case error.TIMEOUT:
                    errorMessage += "Location request timed out. Please try again.";
                    break;
                default:
                    errorMessage += "An unknown error occurred.";
                    break;
            }
            alert(errorMessage);
        }

        function showLoadingIndicator() {
            const locationGroup = document.querySelector('.location-input-group');
            // Remove any existing loading indicator first
            hideLoadingIndicator();
            
            const loader = document.createElement('div');
            loader.className = 'loading-indicator';
            loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting your location...';
            locationGroup.appendChild(loader);
        }

        function hideLoadingIndicator() {
            const loader = document.querySelector('.loading-indicator');
            if (loader) {
                loader.remove();
            }
        }

        // Reverse geocoding function with error handling
        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=en`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.display_name) {
                        document.getElementById('address').value = data.display_name;
                    } else {
                        throw new Error('No address found');
                    }
                })
                .catch(error => {
                    console.error('Error getting address:', error);
                    // Set a default message if geocoding fails
                    document.getElementById('address').value = `Location at ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                });
        }

        // Initialize map when page loads
        window.onload = initMap;

        // Add this new code for handling charger types and slots
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

        // Add form validation before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('Please select a location on the map or use current location');
            }
        });
    </script>
</body>
</html>
