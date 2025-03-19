<?php

session_start();

if (!isset($_SESSION['user_id'])) { // Assuming 'user_id' is set upon login
    header("Location: stationlogin.php"); // Redirect to the login page
    exit();
}

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .analytics-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-card p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
            letter-spacing: 0.5px;
        }
        
        /* Sidebar Styles */
        .page-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-container {
            width: 250px;
            background: linear-gradient(180deg, #4e73df 0%, #224abe 100%);
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand:hover {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar-link {
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
        }

        /* Main Content adjustment */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: #f8f9fc;
            min-height: 100vh;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar-container {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar-container.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Header and user info styles */
        .main-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: left;
            margin-left: 15px;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .user-role {
            font-size: 0.85rem;
            color: #6c757d;
        }

        #sidebarToggle {
            padding: 8px;
            border-radius: 8px;
            color: #4e73df;
            transition: all 0.3s ease;
        }

        #sidebarToggle:hover {
            background-color: #f8f9fa;
        }
        
        /* Map styling */
        #map {
            height: 400px;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .coordinates-display {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px; 
            margin-top: 10px;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .loading-indicator {
            color: #4e73df;
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .geolocation-button {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .geolocation-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Sidebar Container -->
        <div class="sidebar-container" id="sidebar">
            <div class="sidebar-header">
                <a href="station-owner-dashboard.php" class="sidebar-brand">
                    <i class='bx bx-car'></i>
                    <span>EV Station</span>
                </a>
            </div>
            
            <div class="sidebar-nav">
                <a href="station-owner-dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'station-owner-dashboard.php' ? 'active' : ''; ?>">
                    <i class='bx bx-home'></i>
                    <span>Dashboard</span>
                </a>
                <a href="so_add_station.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'so_add_station.php' ? 'active' : ''; ?>">
                    <i class='bx bx-plus-circle'></i>
                    <span>Add Station</span>
                </a>
                <a href="manage-booking.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-bookings.php' ? 'active' : ''; ?>">
                    <i class='bx bx-calendar'></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="station_owner/payment_analytics.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment_analytics.php' ? 'active' : ''; ?>">
                    <i class='bx bx-money'></i>
                    <span>Payment Analytics</span>
                </a>
                <a href="station_owner/so_profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'so_profile.php' ? 'active' : ''; ?>">
                    <i class='bx bx-user'></i>
                    <span>Profile</span>
                </a>
                <a href="reports.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class='bx bx-line-chart'></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class='bx bx-cog'></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="btn btn-link" id="sidebarToggle">
                        <i class='bx bx-menu'></i>
                    </button>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['owner_name'] ?? ''); ?></div>
                            <div class="user-role">Station Owner</div>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-link" type="button" id="userMenuButton" data-bs-toggle="dropdown">
                        <i class='bx bx-user-circle fs-4'></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                        <li><a class="dropdown-item" href="station_owner/so_profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </header>

            <!-- Add Station Content -->
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-plus-circle'></i> Add New Charging Station</h2>
                    <a href="station-owner-dashboard.php" class="btn btn-primary">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">Station Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="owner_name" class="form-label">Owner Name</label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($owner_name); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="location" class="form-label">Location</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="address" name="address" placeholder="Enter station address" required>
                                <button type="button" class="geolocation-button" onclick="getCurrentLocation()">
                                    <i class='bx bx-current-location'></i>
                                    Use Current Location
                                </button>
                            </div>
                            <div class="coordinates-display">
                                <span id="coordinates-text">Lat: ---, Lng: ---</span>
                            </div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                            <div id="map"></div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="charger_types" class="form-label">Available Charger Types</label>
                                    <select class="form-select" id="charger_types" name="charger_types[]" multiple required>
                                        <option value="Type1">Type 1</option>
                                        <option value="Type2">Type 2</option>
                                        <option value="CCS">CCS</option>
                                        <option value="CHAdeMO">CHAdeMO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Slots per Charger Type</label>
                                    <div id="slots-container" class="p-3 bg-light rounded"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="total_slots" class="form-label">Total Charging Slots</label>
                                    <input type="number" class="form-control" id="total_slots" name="total_slots" min="1" required readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="price" class="form-label">Price per kWh (₹)</label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class='bx bx-plus-circle'></i> Add Station
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
            const locationGroup = document.querySelector('.input-group');
            // Remove any existing loading indicator first
            hideLoadingIndicator();
            
            const loader = document.createElement('div');
            loader.className = 'loading-indicator';
            loader.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Getting your location...';
            locationGroup.after(loader);
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
                div.className = 'mb-2';
                div.innerHTML = `
                    <div class="input-group">
                        <span class="input-group-text" style="width: 120px;">${option.text}</span>
                        <input type="number" 
                               class="form-control charger-slots" 
                               data-type="${option.value}" 
                               min="0" 
                               value="0">
                    </div>
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
        
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
