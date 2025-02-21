<?php
// Add this at the very beginning of your file
session_start();

// Add this code after session_start() and before the HTML
require_once 'config.php'; // Make sure this points to your database configuration file

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch stations from database with ST_X and ST_Y to extract coordinates from POINT
    $stmt = $pdo->query("SELECT 
        station_id as id,
        name,
        address,
        ST_X(location) as lng,
        ST_Y(location) as lat,
        status,
        available_slots as availableSlots,
        total_slots as totalSlots,
        price,
        owner_name as ownerName,
        charger_types,
        image
    FROM charging_stations
    WHERE status != 'inactive'");
    
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert charger_types from JSON string to array
    foreach ($stations as &$station) {
        if (isset($station['charger_types'])) {
            $station['charger_types'] = json_decode($station['charger_types'], true);
        }
        
        // Ensure coordinates are numeric
        $station['lat'] = floatval($station['lat']);
        $station['lng'] = floatval($station['lng']);
    }

    // Convert to JSON for JavaScript
    $stationsJson = json_encode($stations);

} catch(PDOException $e) {
    // If there's an error, initialize with empty array
    $stationsJson = '[]';
    error_log("Database Error: " . $e->getMessage());
}

// You might want to add a check for successful login message
if (isset($_SESSION['login_success'])) {
    echo '<script>alert("' . htmlspecialchars($_SESSION['login_success']) . '");</script>';
    unset($_SESSION['login_success']);
}

// Add this near the top of the file after session_start()
function getUserBookings($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.booking_id,
                b.booking_date,
                b.booking_time,
                b.duration,
                b.status,
                cs.name as station_name,
                cs.address as station_address
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC, b.booking_time DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching bookings: " . $e->getMessage());
        return [];
    }
}

// Get user bookings if logged in
$userBookings = [];
if (isset($_SESSION['user_id'])) {
    $userBookings = getUserBookings($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVolve - EV Charging Finder</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" content="767546662883-n1srtf3ane5krtkm89okulrq4fr12ekq.apps.googleusercontent.com">
    <style>
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #c62828;
        }
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
        }
        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 24px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            margin-left: 10px;
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .username {
            color: black;
            font-weight: 500;
            font-size: 14px;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-right: 4px;
        }

        .fa-chevron-down {
            color: #fff;
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .user-profile:hover .fa-chevron-down {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            min-width: 220px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .user-profile:hover .dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .dropdown-content a:hover {
            background-color: #f5f7fa;
        }

        .dropdown-content a i {
            width: 16px;
            color: #666;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #eee;
            margin: 8px 0;
        }

        .logout-link {
            color: #dc3545 !important;
        }

        .logout-link i {
            color: #dc3545 !important;
        }

        .profile-photo {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e0e0e0;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo i {
            font-size: 20px;
            color: #757575;
        }

        /* Add this style to center the Google Sign-In button */
        .google-signin-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px; /* Optional: Add some margin for spacing */
        }

        .marker-popup {
            padding: 0;
            min-width: 280px;
            border-radius: 8px;
            overflow: hidden;
        }

        .popup-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .popup-header h3 {
            margin: 0;
            font-size: 16px;
            color: #2c3e50;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.busy {
            background: #fef2f2;
            color: #dc2626;
        }

        .popup-content {
            padding: 15px;
        }

        .popup-info {
            display: flex;
            align-items: start;
            gap: 8px;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .popup-info i {
            color: #3b82f6;
            margin-top: 3px;
        }

        .popup-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
        }

        .stat i {
            color: #3b82f6;
        }

        .popup-details-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .popup-details-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .popup-details-btn i {
            font-size: 12px;
            transition: transform 0.2s ease;
        }

        .popup-details-btn:hover i {
            transform: translateX(3px);
        }

        /* Leaflet popup customization */
        .leaflet-popup-content-wrapper {
            padding: 0;
            overflow: hidden;
        }

        .leaflet-popup-content {
            margin: 0;
        }

        .leaflet-popup-tip-container {
            margin-top: -1px;
        }

        .ev-feature-label a {
            text-decoration: none;
            color: inherit;
        }

        .booking-panel {
            position: fixed;
            right: -30%;
            top: 0;
            width: 30%;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
        }

        .booking-panel.active {
            right: 0;
        }

        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .booking-status.pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .booking-status.confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .booking-status.cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .cancel-booking-btn {
            display: block;
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            background: #ff5252;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .cancel-booking-btn:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <i class="fas fa-charging-station"></i>
                <span class="logo-text">E<span class="highlight">V</span>olve</span>
            </div>
            <div class="nav-links">
                <a href="#searchInput" class="nav-link active">
                    <i class="fas fa-search"></i>
                    Find Stations
                </a>
                <a href="#" class="nav-link" onclick="toggleBookingPanel(); return false;">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>
                <a href="user_stations.php" class="nav-link">
                    <i class="fas fa-charging-station"></i>
                    Station
                </a>
                <a href="#about" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Debug information -->
    <?php
    echo "<!-- Debug: \n";
    echo "Session user_id: " . $_SESSION['user_id'] . "\n";
    echo "Session profile_picture: " . (isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'not set') . "\n";
    echo "-->";
    ?>
    <div class="user-profile">
        <div class="profile-photo">
            <?php if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
                <!-- Display the user's profile photo -->
                <img src="uploads/profile_pictures/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" 
                     alt="Profile Photo"
                     onerror="this.onerror=null; this.src='assets/default-avatar.png';">
            <?php else: ?>
                <!-- Display a default icon if no profile picture is set -->
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </div>
        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <i class="fas fa-chevron-down"></i>
        <div class="dropdown-content">
            <a href=example.php>
                <i class="fas fa-user"></i>
                My Profile
            </a>
            <a href="my-bookings.php">
                <i class="fas fa-calendar-check"></i>
                My Bookings
            </a>
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
<?php else: ?>
    <a href="#" class="nav-link" onclick="showLoginModal(); return false;">
        <i class="fas fa-user"></i>
        Login/Signup
    </a>
<?php endif; ?>

            </div>
        </nav>
    </header>


        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Image Section -->
            <section class="top-image-section">
                <img src="rb_918.png" alt="EV Charging Station" class="top-image">
                <div class="top-image-text">
                    <h2>Welcome to EVolve Charging Network</h2>
                    <p>Find and book charging stations across the country. Our network provides reliable, fast, and convenient charging solutions for all electric vehicles.</p>
                    <div>
                        <!-- <a href="register_station.php">
                            <button class="owner-button" onclick="showSection('owner')">Become a Station Owner</button>
                        </a> -->
                        
                    </div>
         
                </div>
             
            </section>
    
        </div>
        
    </div>

    <div class="search-container" id="sc">
        <div class="search-box">
            <input type="text" id="searchInput" class="search-input" placeholder="Enter location or zip code">
            <!-- Add range selector -->
            <div class="range-selector">
                <label for="searchRadius">Range:</label>
                <select id="searchRadius" class="radius-select">
                    <option value="5">5 km</option>
                    <option value="10" selected>10 km</option>
                    <option value="20">20 km</option>
                    <option value="50">50 km</option>
                    <option value="100">100 km</option>
                </select>
            </div>
            <button class="search-button" onclick="handleSearch()">Find Stations</button>
            <button class="geolocation-button" onclick="findNearbyStations()">
                <i class="fas fa-location-arrow"></i>
                Near Me
            </button>
        </div>
    </div>

    <!-- Adjust the width of the map container -->
    <div class="map-container">
        <div id="map" style="height: 400px; width: 90%; margin: 0 auto;"></div> <!-- Reduced width and centered -->
    </div>

    <div id="appContent" style="display: none;">
        <div class="content-wrapper">
            <div class="results-container">
                <h2>Available Charging Stations</h2>
                <div id="searchResults"></div>
            </div>
        </div>
    </div>





<div class="ev-features-container">
    <div class="ev-features-header">
        <h1>EV Charging Station</h1>
        <p>Find and book charging stations near you</p>
    </div>

    <div class="ev-features-content">
        <!-- Image Section on Left -->
        <div class="ev-features-image">
            <img src="rb_10364.png" alt="EV Image">
        </div>

        <!-- Features Grid on Right -->
        <div class="ev-features-grid">
            <!-- All 9 features in a 3x3 grid -->
            <div class="ev-feature-card" >
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                    </svg>
                </div>
                <div class="ev-feature-label" >Registration</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Search Option</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">
                    <a href="cs/geolocation.php" style="text-decoration: none; color: inherit;">Geolocation</a>
                </div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.11 0-1.99.89-1.99 2L3 19c0 1.11.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Slot Booking</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM7 10h5v5H7z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Payment Options</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Notifications</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">24/7 Support</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Usage History</div>
            </div>

            <div class="ev-feature-card">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Secure Access</div>
            </div>
        </div>
    </div>
</div>


<div class="how-container">
    <h1 class="how-title">How to Charge</h1>
    
    <div class="how-steps-container">
        <div class="how-step-card" data-aos="fade-up">
            <div class="how-step-number"><span>1</span></div>
            <div class="how-step-icon">
                <i class="fas fa-plug"></i>
                <span class="how-icon-name">Plug In</span>
            </div>
            <h2 class="how-step-title">Plug in</h2>
            <p class="how-step-description">Connect to your charging port.</p>
        </div>

        <div class="how-step-card" data-aos="fade-up" data-aos-delay="100">
            <div class="how-step-number"><span>2</span></div>
            <div class="how-step-icon">
                <i class="fas fa-credit-card"></i>
                <span class="how-icon-name">Tap Card</span>
            </div>
            <h2 class="how-step-title">Tap to Start Your Charge</h2>
            <p class="how-step-description">By EVgo app, RFID, or credit card.</p>
        </div>

        <div class="how-step-card" data-aos="fade-up" data-aos-delay="200">
            <div class="how-step-number"><span>3</span></div>
            <div class="how-step-icon">
                <i class="fas fa-charging-station"></i>
                <span class="how-icon-name">Charge</span>
            </div>
            <h2 class="how-step-title">Charge Up & Go</h2>
            <p class="how-step-description">Your next destination awaits.</p>
        </div>
    </div>

    <a href="#" class="how-learn-more">Learn More</a>
</div>


<div class="about-container" id="about">
    <div class="about-header">
        <span class="about-badge">About Us</span>
        <h2>Empowering Electric Mobility</h2>
        <p>Making EV charging accessible, reliable, and convenient for everyone</p>
    </div>

    <div class="about-grid">
        <div class="about-card mission">
            <div class="card-icon">
                <i class="fas fa-rocket"></i>
            </div>
            <h3>Our Mission</h3>
            <p>To build the world's most reliable EV charging network while promoting sustainable transportation.</p>
        </div>

        <div class="about-card vision">
            <div class="card-icon">
                <i class="fas fa-eye"></i>
            </div>
            <h3>Our Vision</h3>
            <p>A future where every vehicle on the road is powered by clean, renewable energy.</p>
        </div>

        <div class="about-card values">
            <div class="card-icon">
                <i class="fas fa-star"></i>
            </div>
            <h3>Our Values</h3>
            <p>Innovation, Sustainability, Reliability, and Community-driven solutions.</p>
        </div>
    </div>
</div>




</main>
    

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        let map;
        const stations = <?php echo $stationsJson; ?>;

        function initMap() {
            console.log("Stations data:", stations); // Debug log

            // Center the map on Kerala
            map = L.map('map').setView([10.8505, 76.2711], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add markers for each station
            stations.forEach(station => {
                console.log("Adding marker:", station); // Debug log
                
                if (station.lat && station.lng) {
                    const marker = L.marker([station.lat, station.lng])
                        .bindPopup(`
                            <div class="marker-popup">
                                <div class="popup-header">
                                    <h3>${station.name}</h3>
                                    <span class="status-badge ${station.status.toLowerCase()}">${station.status}</span>
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
                                            <i class="fas fa-dollar-sign"></i>
                                            <span>${station.price}/kWh</span>
                                        </div>
                                    </div>
                                    <a href="station_details.php?id=${station.id}" class="popup-details-btn">
                                        <span>View Details</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        `)
                        .addTo(map);
                } else {
                    console.error("Invalid coordinates for station:", station);
                }
            });
        }

        // Initialize map when page loads
        window.onload = initMap;

        function handleSearch() {
            const searchInput = document.getElementById('searchInput').value.trim();
            const searchRadius = parseFloat(document.getElementById('searchRadius').value);
            
            if (!searchInput) {
                alert('Please enter a location or zip code');
                return;
            }

            // Show loading state
            showLoadingIndicator();

            // Create form data
            const formData = new FormData();
            formData.append('action', 'search');
            formData.append('query', searchInput);
            formData.append('radius', searchRadius);

            // Make POST request to search endpoint
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMapWithResults(data.stations);
                    updateSearchResults(data.stations);
                } else {
                    throw new Error(data.error || 'Search failed');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                showErrorMessage('Failed to search stations. Please try again.');
            })
            .finally(() => {
                hideLoadingIndicator();
            });
        }

        function updateMapWithResults(stations) {
            // Clear existing markers
            map.eachLayer(layer => {
                if (layer instanceof L.Marker) {
                    map.removeLayer(layer);
                }
            });

            if (stations.length === 0) {
                showNoResultsMessage();
                return;
            }

            // Add markers for search results
            const bounds = L.latLngBounds();
            
            stations.forEach(station => {
                const marker = L.marker([station.lat, station.lng])
                    .bindPopup(`
                        <div class="marker-popup">
                            <div class="popup-header">
                                <h3>${station.name}</h3>
                                <span class="status-badge ${station.status.toLowerCase()}">${station.status}</span>
                            </div>
                            <div class="popup-content">
                                ${station.image ? `
                                    <div class="popup-image">
                                        <img src="${station.image}" alt="${station.name}">
                                    </div>
                                ` : ''}
                                <div class="popup-info">
                                    <i class="fas fa-user"></i> ${station.ownerName}
                                </div>
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
                                </div>
                                <div class="popup-charger-types">
                                    <i class="fas fa-charging-station"></i>
                                    <span>${JSON.stringify(station.chargerTypes)}</span>
                                </div>
                                <a href="station_details.php?id=${station.id}" class="popup-details-btn">
                                    <span>View Details</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    `)
                    .addTo(map);
                
                bounds.extend([station.lat, station.lng]);
            });

            // Fit map to show all markers
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }

        function updateSearchResults(stations) {
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = '';
            
            if (stations.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No charging stations found. Try adjusting your search criteria.</p>
                    </div>
                `;
                return;
            }

            // Show results count
            resultsContainer.innerHTML = `
                <div class="results-summary">
                    <i class="fas fa-charging-station"></i>
                    Found ${stations.length} charging station${stations.length !== 1 ? 's' : ''}
                </div>
            `;

            // Add each station to the results list
            stations.forEach(station => {
                const resultItem = document.createElement('div');
                resultItem.className = 'result-item';
                resultItem.innerHTML = `
                    <h3>${station.name}</h3>
                    <div class="station-details">
                        <span class="status ${station.status.toLowerCase()}">${station.status}</span>
                        <p><i class="fas fa-map-marker-alt"></i> ${station.address}</p>
                        <p><i class="fas fa-road"></i> ${station.distance} km away</p>
                    </div>
                    <button onclick="focusStation(${station.lat}, ${station.lng})" class="view-details-btn">
                        View on Map
                    </button>
                `;
                resultsContainer.appendChild(resultItem);
            });

            // Show the results container
            document.getElementById('appContent').style.display = 'block';
        }

        // Add these helper functions
        function showLoadingIndicator() {
            const searchBox = document.querySelector('.search-box');
            if (!document.querySelector('.loading-indicator')) {
                const loader = document.createElement('div');
                loader.className = 'loading-indicator';
                loader.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                searchBox.appendChild(loader);
            }
        }

        function hideLoadingIndicator() {
            const loader = document.querySelector('.loading-indicator');
            if (loader) {
                loader.remove();
            }
        }

        function showErrorMessage(message) {
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>${message}</p>
                </div>
            `;
            document.getElementById('appContent').style.display = 'block';
        }

        function showNoResultsMessage() {
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No charging stations found in this area. Try expanding your search radius.</p>
                </div>
            `;
            document.getElementById('appContent').style.display = 'block';
        }

        function getDistrictFromCoordinates(lat, lng) {
            // Simplified district determination based on coordinates
            if (lat < 9.0 && lng < 77.0) return "Thiruvananthapuram";
            if (lat < 10.0 && lng < 76.5) return "Kochi";
            if (lat > 11.0) return "Kozhikode";
            if (lat > 10.4 && lng > 76.0) return "Thrissur";
            return "Alappuzha";
        }

        function focusStation(lat, lng) {
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
        }

        function viewStationDetails(stationId) {
            // You can customize this function to show station details
            // For example, redirect to a details page:
            window.location.href = `station_details.php?id=${stationId}`;
            // Or open a modal with station details
        }

        // Add this function to calculate appropriate zoom level based on radius
        function getZoomForRadius(radius) {
            // Approximate zoom levels for different radiuses (in km)
            if (radius <= 5) return 13;
            if (radius <= 10) return 12;
            if (radius <= 20) return 11;
            if (radius <= 50) return 10;
            return 9; // for 100km or greater
        }

        function findNearbyStations() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            // Show loading indicator
            showLoadingIndicator();

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const radius = parseFloat(document.getElementById('searchRadius').value);

                    // Create form data
                    const formData = new FormData();
                    formData.append('action', 'nearby');
                    formData.append('lat', userLat);
                    formData.append('lng', userLng);
                    formData.append('radius', radius);

                    // Make POST request to search endpoint
                    fetch('index.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear existing markers
                            map.eachLayer((layer) => {
                                if (layer instanceof L.Marker || layer instanceof L.Circle) {
                                    map.removeLayer(layer);
                                }
                            });

                            // Add user location marker
                            const userIcon = L.divIcon({
                                className: 'user-location-marker',
                                html: '<i class="fas fa-user-circle"></i>',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            });

                            const userMarker = L.marker([userLat, userLng], {
                                icon: userIcon
                            }).addTo(map);

                            // Add radius circle
                            const radiusCircle = L.circle([userLat, userLng], {
                                radius: radius * 1000,
                                color: '#4CAF50',
                                fillColor: '#4CAF50',
                                fillOpacity: 0.1,
                                weight: 1
                            }).addTo(map);

                            // Update map view and markers
                            map.setView([userLat, userLng], getZoomForRadius(radius));
                            updateMapWithResults(data.stations);
                            updateSearchResults(data.stations);
                        } else {
                            throw new Error(data.error || 'Search failed');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        showErrorMessage('Failed to find nearby stations. Please try again.');
                    })
                    .finally(() => {
                        hideLoadingIndicator();
                    });
                },
                (error) => {
                    hideLoadingIndicator();
                    handleGeolocationError(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in km
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                     Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * 
                     Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function toRad(value) {
            return value * Math.PI / 180;
        }

        function handleGeolocationError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    alert("Please allow location access to find nearby stations.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    alert("Location request timed out.");
                    break;
                default:
                    alert("An unknown error occurred.");
                    break;
            }
        }

        // Add this JavaScript for the booking panel functionality
        function toggleBookingPanel() {
            const panel = document.getElementById('bookingPanel');
            panel.classList.toggle('active');
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ booking_id: bookingId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh the bookings panel
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel booking');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to cancel booking');
                });
            }
        }
    </script>

    <style>
        .search-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #2196F3;
        }

        .search-button {
            padding: 12px 25px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background-color: #1976D2;
        }

        .content-wrapper {
            display: flex;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 150px);
        }

        .map-container {
            flex: 1;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        #map {
            height: 100%;
            width: 100%;
        }

        .results-container {
            width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .results-container h2 {
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            color: #333;
            border-bottom: 1px solid #eee;
        }

        #searchResults {
            overflow-y: auto;
            padding: 10px;
            flex: 1;
        }

        .result-item {
            background: white;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .result-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .result-item h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.1em;
        }

        .station-details {
            margin: 10px 0;
        }

        .station-details p {
            margin: 5px 0;
            color: #666;
            font-size: 0.9em;
        }

        .status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status.available {
            background-color: #4CAF50;
            color: white;
        }

        .status.busy {
            background-color: #f44336;
            color: white;
        }

        .view-details-btn {
            width: 100%;
            padding: 10px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .view-details-btn:hover {
            background-color: #1976D2;
        }

        .no-results {
            padding: 30px 20px;
            text-align: center;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 10px;
        }

        .no-results p {
            color: #666;
            margin: 0;
            font-size: 1.1em;
            line-height: 1.5;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content-wrapper {
                flex-direction: column;
                height: auto;
            }

            .map-container {
                height: 400px;
            }

            .results-container {
                width: 100%;
                max-height: 500px;
            }
        }

        @media (max-width: 600px) {
            .search-box {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }
        }

        .marker-popup {
            padding: 0;
            min-width: 280px;
            border-radius: 8px;
            overflow: hidden;
        }

        .popup-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .popup-header h3 {
            margin: 0;
            font-size: 16px;
            color: #2c3e50;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge.active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.busy {
            background: #fef2f2;
            color: #dc2626;
        }

        .popup-content {
            padding: 15px;
        }

        .popup-info {
            display: flex;
            align-items: start;
            gap: 8px;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .popup-info i {
            color: #3b82f6;
            margin-top: 3px;
        }

        .popup-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #64748b;
        }

        .stat i {
            color: #3b82f6;
        }

        .popup-details-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .popup-details-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .popup-details-btn i {
            font-size: 12px;
            transition: transform 0.2s ease;
        }

        .popup-details-btn:hover i {
            transform: translateX(3px);
        }

        /* Leaflet popup customization */
        .leaflet-popup-content-wrapper {
            padding: 0;
            overflow: hidden;
        }

        .leaflet-popup-content {
            margin: 0;
        }

        .leaflet-popup-tip-container {
            margin-top: -1px;
        }

        .geolocation-button {
            padding: 12px 20px;
            background-color: #4CAF50;
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
            background-color: #45a049;
            transform: translateY(-1px);
        }

        .loading-indicator {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
            color: #666;
        }

        .user-location-marker {
            color: #2196F3;
            font-size: 24px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .user-location-marker i {
            transform: translateX(-50%) translateY(-50%);
        }

        /* Add these styles */
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .range-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .range-selector label {
            color: #666;
            font-size: 14px;
        }

        .radius-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radius-select:hover {
            border-color: #4CAF50;
        }

        .radius-select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        /* Update existing styles */
        .search-input {
            flex: 1;
            min-width: 200px;
        }

        .results-summary {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .results-summary i {
            font-size: 16px;
        }

        .user-location-marker {
            color: #2196F3;
            font-size: 24px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .user-location-marker i {
            transform: translateX(-50%) translateY(-50%);
        }
    </style>

    <div id="bookingPanel" class="booking-panel">
        <div class="booking-header">
            <h2>My Bookings</h2>
            <button class="close-btn" onclick="toggleBookingPanel()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="booking-content">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (!empty($userBookings)): ?>
                    <div id="bookingsList">
                        <?php foreach ($userBookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-station">
                                    <h3><?php echo htmlspecialchars($booking['station_name']); ?></h3>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['station_address']); ?></p>
                                </div>
                                <div class="booking-details">
                                    <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                                    <p><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($booking['booking_time'])); ?></p>
                                    <p><i class="fas fa-hourglass-half"></i> Duration: <?php echo htmlspecialchars($booking['duration']); ?> minutes</p>
                                </div>
                                <div class="booking-status <?php echo strtolower($booking['status']); ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </div>
                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                    <button class="cancel-booking-btn" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                                        Cancel Booking
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times"></i>
                        <p>No bookings found</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="login-prompt">
                    <i class="fas fa-user-lock"></i>
                    <p>Please log in to view your bookings</p>
                    <button class="login-btn" onclick="showLoginModal()">Log In</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add this JavaScript for handling booking cancellation -->
    <script>
    function cancelBooking(bookingId) {
        if (confirm('Are you sure you want to cancel this booking?')) {
            fetch('cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh the bookings panel
                    location.reload();
                } else {
                    alert(data.message || 'Failed to cancel booking');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to cancel booking');
            });
        }
    }
    </script>

    <!-- Add these additional styles -->
    <style>
    .booking-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .booking-station h3 {
        margin: 0 0 5px 0;
        color: #333;
    }

    .booking-details {
        margin: 10px 0;
        color: #666;
    }

    .booking-details p {
        margin: 5px 0;
    }

    .booking-status {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9em;
        font-weight: 500;
    }

    .booking-status.pending {
        background: #fff3e0;
        color: #f57c00;
    }

    .booking-status.confirmed {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .booking-status.cancelled {
        background: #ffebee;
        color: #c62828;
    }

    .cancel-booking-btn {
        display: block;
        width: 100%;
        padding: 8px;
        margin-top: 10px;
        background: #ff5252;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .cancel-booking-btn:hover {
        background: #d32f2f;
    }

    .login-prompt {
        text-align: center;
        padding: 20px;
    }

    .login-prompt i {
        font-size: 2em;
        color: #666;
        margin-bottom: 10px;
    }
    </style>

    <div id="mybBookingModal" class="myb-modal" style="display: none;">
        <!-- ... existing modal content ... -->
    </div>

    <style>
        
        .booking-panel {
            position: fixed;
            right: -30%;
            top: 0;
            width: 30%;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
        }

        .booking-panel.active {
            right: 0;
        }

        .booking-header {
            padding: 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .booking-header h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #666;
        }

        .close-btn:hover {
            color: #333;
        }

        .booking-content {
            padding: 20px;
        }

        .new-booking-btn {
            width: 100%;
            padding: 1rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            transition: background-color 0.3s ease;
        }

        .new-booking-btn:hover {
            background: #2980b9;
        }

        .no-bookings {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-bookings i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        .myb-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 500px;
            z-index: 1100;
        }

        .myb-modal-content {
            padding: 2rem;
        }

        .myb-modal-content h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.8rem;
        }

        .myb-form-group {
            margin-bottom: 1.5rem;
        }

        .myb-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .myb-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #34495e;
            font-weight: 500;
        }

        .myb-form-group label i {
            margin-right: 0.5rem;
            color: #3498db;
        }

        .myb-form-group select,
        .myb-form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .myb-form-group select:focus,
        .myb-form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }

        .myb-modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .myb-submit-btn,
        .myb-cancel-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: transform 0.2s ease;
        }

        .myb-submit-btn {
            background: #3498db;
            color: white;
        }

        .myb-submit-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .myb-cancel-btn {
            background: #e74c3c;
            color: white;
        }

        .myb-cancel-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Add overlay when modal is open */
        .myb-modal::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
    </style>

    <script>
        function toggleBookingPanel() {
            const panel = document.getElementById('bookingPanel');
            panel.classList.toggle('active');
        }

        function showBookingModal() {
            const modal = document.getElementById('mybBookingModal');
            modal.style.display = 'block';
        }

        function hideBookingModal() {
            const modal = document.getElementById('mybBookingModal');
            modal.style.display = 'none';
        }

        // Add event listener to the cancel button
        document.querySelector('.myb-cancel-btn').addEventListener('click', hideBookingModal);

        // Add event listener to the form submission
        document.getElementById('mybBookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle form submission here
            // After successful submission:
            hideBookingModal();
            // You can add code here to update the bookings list
        });
    </script>

    <!-- Login Modal -->
    <div id="loginModal" class="login-modal">
        <div class="login-container">
            <span class="close-modal" onclick="closeLoginModal()">&times;</span>
            <div class="tabs">
                <a href="#" class="tab active" onclick="showLoginTab(event)">Log In</a>
                <a href="#" class="tab" onclick="showSignupTab(event)">Register</a>
            </div>

            <!-- Login Form -->
             
            
            <form id="loginForm" action="login_process.php" method="post" class="tab-content active">
                <?php
                // Show login modal if there's an error
                if (isset($_SESSION['error'])) {
                    echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
                    echo '<script>document.addEventListener("DOMContentLoaded", function() { showLoginModal(); });</script>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="success-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <div class="input-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" required>
                    <div class="validation-message" id="login-username-validation"></div>
                </div>

                <div class="input-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                    <div class="validation-message" id="login-password-validation"></div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember Me</label>
                </div>

                <button type="submit" class="login-btn">Log in</button>

      
                
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </form>

            <!-- Signup Form -->
<form id="signupForm" action="signup_process.php" method="post" class="tab-content" enctype="multipart/form-data">

    <div class="input-group">
        <label for="signup-username">Username</label>
        <input type="text" id="signup-username" name="username" required>
        <small class="error-text" id="username-error"></small>
    </div>

    <div class="input-group">
        <label for="signup-email">Email Address</label>
        <input type="email" id="signup-email" name="email" required>
        <small class="error-text" id="email-error"></small>
    </div>

    <!-- <div class="input-group">
        <label for="signup-phone">Phone Number</label>
        <input type="tel" id="signup-phone" name="phone" pattern="[0-9]{10}" title="Enter a valid 10-digit phone number" required>
        <small class="error-text" id="phone-error"></small>
    </div> -->

    <div class="input-group">
        <label for="signup-password">Password</label>
        <input type="password" id="signup-password" name="password" required>
        <small class="error-text" id="password-error"></small>
    </div>

    <div class="input-group">
        <label for="confirm-password">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm_password" required>
        <small class="error-text" id="confirm-password-error"></small>
    </div>

    <!-- <div class="input-group">
        <label for="profile-picture">Profile Picture</label>
        <input type="file" id="profile-picture" name="profile_picture" accept="image/*" required>
        <small class="error-text" id="profile-picture-error"></small>
    </div> -->
    

    <button type="submit" class="signup-btn">Create Account</button>

    <div class="terms">
        By signing up, you agree to our 
        <a href="#">Terms of Service</a> and 
        <a href="#">Privacy Policy</a>
    </div>
</form>

            <!-- Google Sign-In Button -->
            <div class="google-signin-container">
                <div class="g-signin2" data-onsuccess="onSignIn"></div>
            </div>
        </div>
    </div>

<style>
    
    /* Modal Base Styles */
.login-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

/* Container Styles */
.login-container {
    position: relative;
    background: linear-gradient(145deg, #ffffff, #f5f5f5);
    padding: 2.5rem;
    border-radius: 20px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Close Button */
.close-modal {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    transition: color 0.3s ease;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8f9fa;
}

.close-modal:hover {
    color: #333;
    background: #e9ecef;
}

/* Tabs Styling */
.tabs {
    display: flex;
    margin: -10px -20px 20px;
    background: #f8f9fa;
    border-radius: 15px;
    padding: 5px;
    gap: 5px;
}

.tab {
    flex: 1;
    padding: 12px;
    text-align: center;
    color: #666;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.tab.active {
    background: #2196F3;
    color: white;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}

.tab:not(.active):hover {
    background: #e9ecef;
    color: #333;
}

/* Form Elements */
.input-group {
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    color: #444;
    font-weight: 500;
    font-size: 0.9rem;
}

.input-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.input-group input:focus {
    border-color: #2196F3;
    background: white;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    outline: none;
}

.input-group input.error {
    border-color: #ff4444;
    background: #fff8f8;
}

/* Buttons */
.login-btn, .signup-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(45deg, #2196F3, #1976D2);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
}

.login-btn:hover, .signup-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.3);
}

.login-btn:active, .signup-btn:active {
    transform: translateY(0);
}

/* Remember Me Checkbox */
.remember-me {
    display: flex;
    align-items: center;
    margin: 15px 0;
    gap: 8px;
}

.remember-me input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #2196F3;
}

.remember-me label {
    color: #666;
    font-size: 0.9rem;
}

/* Forgot Password Link */
.forgot-password {
    text-align: center;
    margin-top: 20px;
}

.forgot-password a {
    color: #2196F3;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.forgot-password a:hover {
    color: #1976D2;
    text-decoration: underline;
}

/* Terms Text */
.terms {
    margin-top: 20px;
    text-align: center;
    font-size: 0.85rem;
    color: #666;
    line-height: 1.5;
}

.terms a {
    color: #2196F3;
    text-decoration: none;
}

.terms a:hover {
    text-decoration: underline;
}

/* Error Messages */
.validation-message,
.error-text {
    color: #ff4444;
    font-size: 0.8rem;
    margin-top: 6px;
    display: block;
    animation: fadeIn 0.2s ease-out;
}

.error-message {
    background-color: #ffebee;
    color: #c62828;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    border-left: 4px solid #c62828;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 480px) {
    .login-container {
        padding: 20px;
        width: 95%;
    }

    .tabs {
        margin: -5px -10px 15px;
    }

    .input-group input {
        padding: 10px 12px;
    }

    .login-btn, .signup-btn {
        padding: 12px;
    }
}

/* Tab Content Animation */
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-out;
}

.tab-content.active {
    display: block;
}

/* Add these new styles */
.image-upload-wrapper {
    display: flex;
    gap: 20px;
    align-items: center;
    margin-top: 8px;
}

.image-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #e0e0e0;
    background: #f8f9fa;
    flex-shrink: 0;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.upload-controls {
    flex: 1;
}

.file-input {
    display: none;
}

.upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #2196F3;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.upload-btn:hover {
    background: #1976D2;
    transform: translateY(-2px);
}

.upload-info {
    margin-top: 8px;
    font-size: 0.8rem;
    color: #666;
}


</style>

    <script>
    function showLoginTab(event) {
        event.preventDefault();
        document.getElementById('loginForm').classList.add('active');
        document.getElementById('signupForm').classList.remove('active');
        document.querySelector('.tab:first-child').classList.add('active');
        document.querySelector('.tab:last-child').classList.remove('active');
    }

    function showSignupTab(event) {
        event.preventDefault();
        document.getElementById('loginForm').classList.remove('active');
        document.getElementById('signupForm').classList.add('active');
        document.querySelector('.tab:first-child').classList.remove('active');
        document.querySelector('.tab:last-child').classList.add('active');
    }

    function showLoginModal() {
        const modal = document.getElementById('loginModal');
        modal.style.display = 'flex';
        showLoginTab(new Event('click'));
    }

    function closeLoginModal() {
        // Only close if there are no error messages
        const errorMessages = document.querySelectorAll('.error-message');
        const validationMessages = document.querySelectorAll('.validation-message');
        
        let hasErrors = false;
        
        // Check for error messages
        errorMessages.forEach(msg => {
            if (msg.textContent.trim() !== '') {
                hasErrors = true;
            }
        });
        
        // Check for validation messages
        validationMessages.forEach(msg => {
            if (msg.textContent.trim() !== '') {
                hasErrors = true;
            }
        });
        
        // Only close if there are no errors
        if (!hasErrors) {
            const modal = document.getElementById('loginModal');
            modal.style.display = 'none';
        }
    }

    // Update the window click handler
    window.onclick = function(event) {
        const modal = document.getElementById('loginModal');
        if (event.target === modal) {
            closeLoginModal(); // Use the modified closeLoginModal function
        }
    }

    // Form validation functions
    function validateUsername(username) {
        // Check if username starts with a letter
        if (!/^[a-zA-Z]/.test(username)) {
            return "Username must start with a letter";
        }
        // Check minimum length
        if (username.length < 3) {
            return "Username must be at least 3 characters long";
        }
        // Check for valid characters (letters, numbers, and underscores only)
        if (!/^[a-zA-Z][a-zA-Z0-9_]*$/.test(username)) {
            return "Username can only contain letters, numbers, and underscores";
        }
        return "";
    }

    function validateEmail(email) {
        // Check if email starts with a letter
        if (!/^[a-zA-Z]/.test(email)) {
            return "Email must start with a letter";
        }
        // More comprehensive email validation
        const emailRegex = /^[a-zA-Z][\w.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z.]*[a-zA-Z]$/;
        if (!emailRegex.test(email)) {
            return "Please enter a valid email address";
        }
        return "";
    }

    function validatePassword(password) {
        if (password.length < 8) {
            return "Password must be at least 8 characters long";
        }
        if (!/[A-Z]/.test(password)) {
            return "Password must contain at least one uppercase letter";
        }
        if (!/[a-z]/.test(password)) {
            return "Password must contain at least one lowercase letter";
        }
        if (!/[0-9]/.test(password)) {
            return "Password must contain at least one number";
        }
        return "";
    }

    // Add live validation when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Login form validation
        const loginForm = document.getElementById('loginForm');
        const loginUsername = document.getElementById('login-username');
        const loginPassword = document.getElementById('login-password');
        
        // Login username validation
        loginUsername.addEventListener('input', function() {
            const error = validateUsername(this.value);
            const validationMessage = document.getElementById('login-username-validation');
            validationMessage.textContent = error;
            this.classList.toggle('error', error !== '');
        });

        // Login password validation
        loginPassword.addEventListener('input', function() {
            const error = validatePassword(this.value);
            const validationMessage = document.getElementById('login-password-validation');
            validationMessage.textContent = error;
            this.classList.toggle('error', error !== '');
        });

        // Login form submission
        loginForm.addEventListener('submit', function(e) {
            const usernameError = validateUsername(loginUsername.value);
            const passwordError = validatePassword(loginPassword.value);

            if (usernameError || passwordError) {
                e.preventDefault();
                document.getElementById('login-username-validation').textContent = usernameError;
                document.getElementById('login-password-validation').textContent = passwordError;
            }
        });

        // Signup form validation
        const signupForm = document.getElementById('signupForm');
        const signupUsername = document.getElementById('signup-username');
        const signupEmail = document.getElementById('signup-email');
        const signupPassword = document.getElementById('signup-password');
        const confirmPassword = document.getElementById('confirm-password');

        // Signup username validation
        signupUsername.addEventListener('input', function() {
            const error = validateUsername(this.value);
            const errorElement = document.getElementById('username-error');
            errorElement.textContent = error;
            this.classList.toggle('error', error !== '');
        });

        // Signup email validation
        signupEmail.addEventListener('input', function() {
            const error = validateEmail(this.value);
            const errorElement = document.getElementById('email-error');
            errorElement.textContent = error;
            this.classList.toggle('error', error !== '');
        });

        // Signup password validation
        signupPassword.addEventListener('input', function() {
            const error = validatePassword(this.value);
            const errorElement = document.getElementById('password-error');
            errorElement.textContent = error;
            this.classList.toggle('error', error !== '');
            
            // Check confirm password match if it has a value
            if (confirmPassword.value) {
                const confirmError = confirmPassword.value !== this.value ? 
                    "Passwords do not match" : "";
                document.getElementById('confirm-password-error').textContent = confirmError;
                confirmPassword.classList.toggle('error', confirmError !== '');
            }
        });

        // Confirm password validation
        confirmPassword.addEventListener('input', function() {
            const error = this.value !== signupPassword.value ? 
                "Passwords do not match" : "";
            const errorElement = document.getElementById('confirm-password-error');
            errorElement.textContent = error;
            this.classList.toggle('error', error !== '');
        });

        // Signup form submission
        signupForm.addEventListener('submit', function(e) {
            const usernameError = validateUsername(signupUsername.value);
            const emailError = validateEmail(signupEmail.value);
            const passwordError = validatePassword(signupPassword.value);
            const confirmError = signupPassword.value !== confirmPassword.value;

            if (usernameError || emailError || passwordError || confirmError) {
                e.preventDefault();
                document.getElementById('username-error').textContent = usernameError;
                document.getElementById('email-error').textContent = emailError;
                document.getElementById('password-error').textContent = passwordError;
                document.getElementById('confirm-password-error').textContent = 
                    confirmError ? "Passwords do not match" : "";
                document.getElementById('signup-error-message').textContent = 
                    "Please fix the errors before submitting";
            }
        });

        const profileImage = document.getElementById('profile-image');
        const previewImage = document.getElementById('previewImage');
        const imageError = document.getElementById('image-error');

        profileImage.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            // Validate file
            if (file) {
                // Check file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    imageError.textContent = "Image size must be less than 5MB";
                    this.value = '';
                    return;
                }

                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    imageError.textContent = "Please upload a JPG or PNG image";
                    this.value = '';
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    imageError.textContent = '';
                };
                reader.readAsDataURL(file);
            }
        });

        // Add image validation to form submission
        signupForm.addEventListener('submit', function(e) {
            // ... existing validation ...

            const file = profileImage.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                imageError.textContent = "Image size must be less than 5MB";
            }
        });

        // Add this to your existing scripts
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.querySelector('.user-profile');
            if (userProfile) {
                userProfile.addEventListener('click', function(e) {
                    const dropdown = this.querySelector('.dropdown-content');
                    if (e.target.closest('.dropdown-content')) {
                        // If clicking a dropdown item, let it process normally
                        return;
                    }
                    // Toggle dropdown visibility
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userProfile.contains(e.target)) {
                        userProfile.querySelector('.dropdown-content').style.display = 'none';
                    }
                });
            }
        });

        function handleGoogleSignIn() {
            // Initialize Google Sign-In
            // You'll need to add your Google OAuth credentials
            // and implement the sign-in flow
            console.log('Google sign-in clicked');
        }

        function handleFacebookSignIn() {
            // Initialize Facebook Sign-In
            // You'll need to add your Facebook OAuth credentials
            // and implement the sign-in flow
            console.log('Facebook sign-in clicked');
        }

        function handleAppleSignIn() {
            // Initialize Apple Sign-In
            // You'll need to add your Apple OAuth credentials
            // and implement the sign-in flow
            console.log('Apple sign-in clicked');
        }
    });
    </script>

    <!-- Initialize Facebook SDK -->
    <script>
    window.fbAsyncInit = function() {
        FB.init({
            appId: 'your-app-id',
            cookie: true,
            xfbml: true,
            version: 'v12.0'
        });
    };
    </script>

    <!-- Initialize Apple Sign-In -->
    <script type="text/javascript" src="https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js"></script>

    <script>
        function onSignIn(googleUser) {
            var profile = googleUser.getBasicProfile();
            console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
            console.log('Name: ' + profile.getName());
            console.log('Image URL: ' + profile.getImageUrl());
            console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.

            // Send the ID token to your server
            var id_token = googleUser.getAuthResponse().id_token;
            // You can send this token to your server for further processing
            // Example: sendTokenToServer(id_token);
        }

        function signOut() {
            var auth2 = gapi.auth2.getAuthInstance();
            auth2.signOut().then(function () {
                console.log('User signed out.');
            });
        }
    </script>
</body>
</html>
