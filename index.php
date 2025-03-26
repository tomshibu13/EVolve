<?php
// Add this at the very beginning of your file
session_start();

// Add this code after session_start() and before the HTML
require_once 'config.php'; // Make sure this points to your database configuration file

// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";  // Use your actual database name from create_database.php

// If you need to create a connection, use this:
if (!isset($conn)) {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Add this near the top of your PHP section after session_start()
$showVerificationModal = false;
if (isset($_GET['verify']) && $_GET['verify'] === 'true' && isset($_SESSION['pending_verification']) && $_SESSION['pending_verification'] === true) {
    $showVerificationModal = true;
}

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

// Add this after your existing PDO connection setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'nearby') {
                $userLat = floatval($_POST['lat']);
                $userLng = floatval($_POST['lng']);
                $radius = floatval($_POST['radius']);

                // Log the received parameters
                error_log("Nearby search parameters - Lat: $userLat, Lng: $userLng, Radius: $radius");

                // Use MySQL's ST_Distance_Sphere to calculate distance
                $stmt = $pdo->prepare("
                    SELECT 
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
                        image,
                        ST_Distance_Sphere(
                            point(?, ?),
                            location
                        ) * 0.001 as distance
                    FROM charging_stations
                    WHERE status != 'inactive'
                    HAVING distance <= ?
                    ORDER BY distance
                ");

                $stmt->execute([$userLng, $userLat, $radius]);
                $nearbyStations = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Log the number of stations found
                error_log("Found " . count($nearbyStations) . " nearby stations");

                // Process charger_types JSON
                foreach ($nearbyStations as &$station) {
                    if (isset($station['charger_types'])) {
                        $station['charger_types'] = json_decode($station['charger_types'], true);
                    }
                    // Ensure coordinates are numeric
                    $station['lat'] = floatval($station['lat']);
                    $station['lng'] = floatval($station['lng']);
                    // Round distance to 1 decimal place
                    $station['distance'] = round($station['distance'], 1);
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'stations' => $nearbyStations,
                    'searchLocation' => [
                        'lat' => $userLat,
                        'lng' => $userLng
                    ]
                ]);
                exit;
            }
        } catch(PDOException $e) {
            error_log("Database Error in nearby search: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Add this function near the top of the file after session_start()
function isApprovedStationOwner($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT status 
            FROM station_owner_requests 
            WHERE user_id = ? AND status = 'approved'
        ");
        $stmt->execute([$userId]);
        
        // Add debug logging
        error_log("Checking station owner status for user_id: " . $userId);
        error_log("Query result count: " . $stmt->rowCount());
        
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        error_log("Error checking station owner status: " . $e->getMessage());
        return false;
    }
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
            position: relative;
            padding-right: 30px; /* Make room for close button */
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: border-color 0.3s ease;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Optional: Add a subtle border and hover effect */
        .profile-photo {
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: border-color 0.3s ease;
        }

        .user-profile:hover .profile-photo {
            border-color: rgba(255, 255, 255, 0.4);
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

        /* Add these styles to your existing CSS */
        .error-close {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #c62828;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .error-close:hover {
            opacity: 1;
        }

        /* Support Container Styles */
        .support-container {
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
        }

        /* Header Styles */
        .support-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .support-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .support-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Support Grid Styles */
        .support-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .support-card {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .support-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .support-card i {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 20px;
        }

        .support-card h3 {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .support-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Contact Section Styles */
        .contact-section {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-top: 25px;
            position: relative;
            overflow: hidden;
        }

        .contact-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: #3498db;
            border-radius: 4px;
        }

        .contact-section h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 12px;
        }

        .contact-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: #3498db;
        }

        .contact-form .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .contact-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .contact-form input,
        .contact-form textarea,
        .contact-form select {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
            font-size: 1rem;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .contact-form input:focus,
        .contact-form textarea:focus,
        .contact-form select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .contact-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-actions {
            margin-top: 30px;
        }

        .submit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.25);
        }

        .submit-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.3);
        }

        .submit-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }

        .submit-button i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .submit-button:hover i {
            transform: translateX(4px);
        }

        .success-message {
            display: none;
            background-color: #e8f7ed;
            color: #1d6f42;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 500;
            border-left: 4px solid #4CAF50;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Input placeholder styling */
        .contact-form input::placeholder,
        .contact-form textarea::placeholder {
            color: #a0aec0;
            font-size: 0.95rem;
        }

        /* Show icon in select field */
        .contact-form select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233498db' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .support-header h1 {
                font-size: 2rem;
            }

            .support-grid {
                grid-template-columns: 1fr;
            }

            .contact-section {
                padding: 30px 20px;
            }

            .contact-buttons {
                flex-direction: column;
            }

            .contact-button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation for form submission */
        @keyframes submitPulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(0.95);
            }
            100% {
                transform: scale(1);
            }
        }

        .submit-button:active {
            animation: submitPulse 0.2s ease-in-out;
        }

        /* Placeholder styles */
        ::placeholder {
            color: #999;
            opacity: 1;
        }

        :-ms-input-placeholder {
            color: #999;
        }

        ::-ms-input-placeholder {
            color: #999;
        }

        /* Focus styles for accessibility */
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        /* Error state styles */
        .form-group.error input,
        .form-group.error textarea {
            border-color: #e74c3c;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        /* Success message styles */
        .success-message {
            text-align: center;
            padding: 20px;
            background: #2ecc71;
            color: white;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .success-message.show {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .station-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .station-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .station-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .station-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.2rem;
        }

        .station-image {
            width: 100%;
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .station-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .station-info {
            color: #666;
            font-size: 0.95rem;
        }

        .station-info p {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .station-info i {
            color: #3498db;
            width: 16px;
        }

        .station-actions {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
        }

        .view-details-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .view-details-btn:hover {
            background: #2980b9;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .no-results i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        .results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .results-container h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .validation-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            min-height: 20px;
        }

        .input-group input.error {
            border-color: #dc3545;
        }

        .input-group input.error:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .nav-link {
            text-decoration: none;
        }

        .owner-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .owner-button:hover:not(:disabled) {
            background-color: #45a049;
            transform: translateY(-1px);
        }

        .owner-button.pending {
            background-color: #ffa726;
            cursor: not-allowed;
        }

        .owner-button i {
            font-size: 16px;
        }

        .google-signin-container {
            margin-top: 20px;
            text-align: center;
        }

        .google-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 24px;
            background: #fff;
            color: #757575;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .google-login-btn:hover {
            background: #f8f8f8;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .google-login-btn i {
            color: #4285f4;
            font-size: 18px;
        }

        /* Add a divider between regular login and Google login */
        .google-signin-container::before {
            content: "or";
            display: block;
            text-align: center;
            color: #757575;
            margin: 15px 0;
            position: relative;
        }

        .google-signin-container::before::after {
            content: "";
            display: block;
            height: 1px;
            background: #ddd;
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            z-index: -1;
        }

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

        /* Add these styles for recent bookings */
        .recent-booking {
            position: relative;
            border-left: 4px solid #4CAF50;
            animation: fadeIn 0.5s ease-out;
        }

        .recent-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #4CAF50;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Update existing booking card styles */
        .booking-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* Add styles for see more link */
        .see-more-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            background-color: #f8f9fa;
            color: #2196F3;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .see-more-link:hover {
            background-color: #e9ecef;
            color: #1976D2;
        }

        .see-more-link i {
            font-size: 0.9em;
            transition: transform 0.3s ease;
        }

        .see-more-link:hover i {
            transform: translateX(4px);
        }

        .validation-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }

        .input-group {
            margin-bottom: 1rem;
        }

        /* Responsive Header Styles */
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: relative;
            width: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            z-index: 1001; /* Ensure logo stays above mobile menu */
        }

        .mobile-menu-toggle {
            display: none; /* Hidden by default on desktop */
            cursor: pointer;
            font-size: 24px;
            color: #333;
            z-index: 1001; /* Ensure it stays above mobile menu */
        }

        .mobile-menu-close {
            display: none; /* Hidden by default */
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            z-index: 1002;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Media Queries for Responsive Design */
        @media (max-width: 992px) {
            .mobile-menu-toggle {
                display: block; /* Show only on mobile */
            }
            
            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background-color: white;
                flex-direction: column;
                align-items: flex-start;
                padding: 80px 20px 20px;
                transition: right 0.3s ease;
                z-index: 1000;
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            }
            
            .nav-links.active {
                right: 0;
            }
            
            .mobile-menu-close {
                display: none; /* Initially hidden */
            }
            
            .nav-links.active .mobile-menu-close {
                display: block; /* Show only when menu is active */
            }
            
            .nav-link {
                padding: 15px 0;
                width: 100%;
                text-align: left;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            }
            
            .user-profile {
                margin: 15px 0 0 0;
                width: 100%;
                padding: 15px 0;
            }
        }

        .ev-features-container {
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .ev-features-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .ev-features-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .ev-features-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .ev-features-content {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .ev-features-image {
            flex: 1;
        }

        .ev-features-image img {
            width: 100%;
            height: auto;
            border-radius: 12px;
            object-fit: cover;
        }

        .ev-features-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .ev-feature-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .ev-feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .ev-feature-icon-box {
            width: 60px;
            height: 60px;
            background: #f0f4ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .ev-feature-icon {
            width: 30px;
            height: 30px;
            fill: #3b82f6;
        }

        .ev-feature-label {
            font-weight: 500;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        /* Responsive styles for EV features section */
        @media (max-width: 992px) {
            .ev-features-content {
                flex-direction: column;
            }
            
            .ev-features-image, 
            .ev-features-grid {
                flex: none;
                width: 100%;
            }
            
            .ev-features-image {
                margin-bottom: 30px;
            }
            
            .ev-features-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .ev-features-header h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .ev-features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .ev-features-header h1 {
                font-size: 1.8rem;
            }
            
            .ev-feature-card {
                padding: 15px;
            }
            
            .ev-feature-icon-box {
                width: 50px;
                height: 50px;
                margin-bottom: 10px;
            }
            
            .ev-feature-icon {
                width: 25px;
                height: 25px;
            }
        }

        @media (max-width: 480px) {
            .ev-features-container {
                padding: 40px 15px;
            }
            
            .ev-features-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .ev-features-header h1 {
                font-size: 1.5rem;
            }
            
            .ev-features-header p {
                font-size: 0.95rem;
            }
            
            .ev-feature-card {
                display: flex;
                align-items: center;
                text-align: left;
                padding: 12px;
            }
            
            .ev-feature-icon-box {
                margin: 0 15px 0 0;
                width: 40px;
                height: 40px;
            }
            
            .ev-feature-icon {
                width: 20px;
                height: 20px;
            }
        }

        /* How to Charge - Responsive Styles */
        @media (max-width: 992px) {
            .how-steps-container {
                flex-direction: column;
                gap: 25px;
            }
            
            .how-step-card {
                width: 100%;
                max-width: 450px;
                margin: 0 auto;
            }
        }

        @media (max-width: 480px) {
            .how-title {
                font-size: 1.8rem;
            }
            
            .how-step-card {
                padding: 15px;
            }
            
            .how-step-number {
                width: 35px;
                height: 35px;
                top: -15px;
            }
            
            .how-step-number span {
                font-size: 1.2rem;
            }
            
            .how-step-icon {
                font-size: 1.8rem;
            }
            
            .how-step-title {
                font-size: 1.2rem;
            }
            
            .how-icon-name {
                font-size: 0.8rem;
            }
        }

        /* About Us - Responsive Styles */
        @media (max-width: 768px) {
            .about-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .about-card {
                padding: 20px;
            }
            
            .about-header h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .about-container {
                padding: 40px 15px;
            }
            
            .about-header h2 {
                font-size: 1.5rem;
            }
            
            .about-badge {
                font-size: 0.8rem;
                padding: 5px 10px;
            }
            
            .card-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            
            .about-card h3 {
                font-size: 1.2rem;
            }
        }

        /* Support Section - Responsive Styles */
        @media (max-width: 992px) {
            .support-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .support-grid {
                grid-template-columns: 1fr;
            }
            
            .support-header h1 {
                font-size: 1.8rem;
            }
            
            .support-card {
                padding: 20px;
            }
            
            .contact-section {
                padding: 25px;
            }
        }

        @media (max-width: 480px) {
            .support-container {
                padding: 40px 15px;
            }
            
            .support-header h1 {
                font-size: 1.5rem;
            }
            
            .support-card i {
                font-size: 2rem;
            }
            
            .support-card h3 {
                font-size: 1.2rem;
            }
            
            .contact-section h2 {
                font-size: 1.5rem;
            }
            
            .contact-button {
                width: 100%;
                justify-content: center;
            }
        }

        .nav-link {
            position: relative;
            text-decoration: none;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff5252;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
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
            <div class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="nav-links" id="navLinks">
                <div class="mobile-menu-close" id="mobileMenuClose">
                    <i class="fas fa-times"></i>
                </div>
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
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <?php 
                    if (isset($_SESSION['user_id'])) {
                        // Count unread notifications
                        $notifStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                        $notifStmt->execute([$_SESSION['user_id']]);
                        $unreadNotifications = $notifStmt->fetchColumn();
                        
                        // Count unread enquiry responses
                        $responseStmt = $pdo->prepare("SELECT COUNT(*) FROM enquiries WHERE user_id = ? AND status = 'responded' AND response IS NOT NULL");
                        $responseStmt->execute([$_SESSION['user_id']]);
                        $unreadResponses = $responseStmt->fetchColumn();
                        
                        // Calculate total unread count
                        $totalUnread = $unreadNotifications + $unreadResponses;
                        
                        // Display badge if there are unread items
                        if ($totalUnread > 0) {
                            echo '<span class="notification-badge">' . $totalUnread . '</span>';
                        }
                    }
                    ?>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Debug information -->
                <?php
                echo "<!-- Debug: \n";
                echo "Session user_id: " . $_SESSION['user_id'] . "\n";
                echo "Session profile_picture: " . (isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'not set') . "\n";
                echo "-->";
                ?>
                <?php
                // Add this code block to fetch user data if name is not set
                if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
                    try {
                        $stmt = $pdo->prepare("SELECT name FROM tbl_users WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($userData) {
                            $_SESSION['name'] = $userData['name'];
                        }
                    } catch(PDOException $e) {
                        error_log("Error fetching user data: " . $e->getMessage());
                    }
                }
                ?>
                <div class="user-profile">
                    <span class="username">
                        <?php 
                        if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
                            echo htmlspecialchars($_SESSION['name']);
                        } else {
                            echo 'User';
                        }
                        ?>
                    </span>
                    <i class="fas fa-chevron-down"></i>
                    <div class="dropdown-content">
                        <a href="example.php">
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
                <a href="#" class="nav-link" id="loginSignupBtn">
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
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            // Add debug output
                            error_log("User ID from session: " . $_SESSION['user_id']);
                            $isOwner = isApprovedStationOwner($_SESSION['user_id']);
                            error_log("Is approved owner: " . ($isOwner ? "yes" : "no"));
                            ?>
                            
                            <?php if ($isOwner): ?>
                                <a href="station-owner-dashboard.php">
                                    <button class="owner-button">
                                        <i class="fas fa-charging-station"></i>
                                        Station Owner Dashboard
                                    </button>
                                </a>
                            <?php else: ?>
                                <?php
                                // Check if user has a pending request
                                $stmt = $pdo->prepare("
                                    SELECT status 
                                    FROM station_owner_requests 
                                    WHERE user_id = ? AND status = 'pending'
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $hasPendingRequest = $stmt->rowCount() > 0;
                                ?>
                                
                                <?php if ($hasPendingRequest): ?>
                                    <button class="owner-button pending" disabled>
                                        <i class="fas fa-clock"></i>
                                        Request Pending Approval
                                    </button>
                                <?php else: ?>
                                    <a href="stationlogin.php">
                                        <button class="owner-button">
                                            <i class="fas fa-plus-circle"></i>
                                            Become a Station Owner
                                        </button>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="stationlogin.php">
                                <button class="owner-button">
                                    <i class="fas fa-plus-circle"></i>
                                    Become a Station Owner
                                </button>
                            </a>
                        <?php endif; ?>
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
            <div class="ev-feature-card">
                <a href="#" onclick="showSignupModal(); return false;" style="text-decoration: none; color: inherit; display: block;">
                    <div class="ev-feature-icon-box">
                        <svg class="ev-feature-icon" viewBox="0 0 24 24">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                    </div>
                    <div class="ev-feature-label">Registration</div>
                </a>
            </div>

            
            <div class="ev-feature-card">
                <a href="#" onclick="showLoginModal(); return false;" style="text-decoration: none; color: inherit; display: block;">
                    <div class="ev-feature-icon-box">
                        <svg class="ev-feature-icon" viewBox="0 0 24 24">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                    </div>
                    <div class="ev-feature-label">Log In</div>
                </a>
            </div>

            <div class="ev-feature-card" onclick="document.getElementById('searchInput').focus();">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">Search Option</div>
            </div>

            <div class="ev-feature-card">
            <a href="cs/geolocation.php" style="text-decoration: none; color: inherit;">
                <div class="ev-feature-icon-box">
                    <svg class="ev-feature-icon" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                    </svg>
                </div>
                <div class="ev-feature-label">
                   Geolocation</a>
                </div>
            </div>

            <div class="ev-feature-card">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Show slot booking link for logged in tbl_users -->
                    <a href="user_stations.php" style="text-decoration: none; color: inherit; display: block;">
                        <div class="ev-feature-icon-box">
                            <svg class="ev-feature-icon" viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.11 0-1.99.89-1.99 2L3 19c0 1.11.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                        </div>
                        <div class="ev-feature-label">Slot Booking</div>
                    </a>
                <?php else: ?>
                    <!-- Show login prompt for non-logged in tbl_users -->
                    <a href="#" onclick="showLoginModal(); return false;" style="text-decoration: none; color: inherit; display: block;">
                        <div class="ev-feature-icon-box">
                            <svg class="ev-feature-icon" viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.11 0-1.99.89-1.99 2L3 19c0 1.11.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                        </div>
                        <div class="ev-feature-label">Book Slots</div>
                    </a>
                <?php endif; ?>
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
                <a href="notifications.php" style="text-decoration: none; color: inherit;">
                    <div class="ev-feature-icon-box">
                        <svg class="ev-feature-icon" viewBox="0 0 24 24">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                    </div>
                    <div class="ev-feature-label">Notifications</div>
                </a>
            </div>

            <div class="ev-feature-card">
                <a href="#support" onclick="scrollToSupport(event)" style="text-decoration: none; color: inherit; display: block;">
                    <div class="ev-feature-icon-box">
                        <svg class="ev-feature-icon" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                    </div>
                    <div class="ev-feature-label">24/7 Support</div>
                </a>
            </div>


            <div class="ev-feature-card">
                <a href="reviews.php" style="text-decoration: none; color: inherit; display: block;">
                    <div class="ev-feature-icon-box">
                        <svg class="ev-feature-icon" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    </div>
                    <div class="ev-feature-label">Review</div>
                </a>
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


<div id="support" class="support-container">
        <div class="support-header">
            <h1>24/7 Customer Support</h1>
            <p>We're here to help you with any questions or concerns you may have</p>
        </div>

        <div class="support-grid">
            <div class="support-card">
                <i class="fas fa-question-circle"></i>
                <h3>FAQ</h3>
                <p>Find answers to commonly asked questions about our services, booking process, and payment options.</p>
            </div>

            <div class="support-card">
                <i class="fas fa-book"></i>
                <h3>User Guide</h3>
                <p>Detailed instructions on how to use our platform, make bookings, and manage your account.</p>
            </div>

            <div class="support-card">
                <i class="fas fa-tools"></i>
                <h3>Technical Support</h3>
                <p>Get help with technical issues related to charging stations, app functionality, or account access.</p>
            </div>
        </div>

        <div class="contact-section">
            <h2>Get in Touch</h2>
            <form id="enquiryForm" class="contact-form">
                <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="form-group">
                    <label for="enquiryName">Your Name</label>
                    <input type="text" id="enquiryName" name="name" required placeholder="Enter your name">
                </div>
                <div class="form-group">
                    <label for="enquiryEmail">Email Address</label>
                    <input type="email" id="enquiryEmail" name="email" required placeholder="Enter your email">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="stationSelect">Select Station</label>
                    <select id="stationSelect" name="station_id" required>
                        <option value="">-- Select a station --</option>
                        <?php
                        // Fetch all active stations
                        $stationQuery = $pdo->prepare("SELECT station_id, name FROM charging_stations WHERE status = 'active'");
                        $stationQuery->execute();
                        $stationList = $stationQuery->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($stationList as $station) {
                            echo '<option value="' . $station['station_id'] . '">' . htmlspecialchars($station['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="enquirySubject">Subject</label>
                    <input type="text" id="enquirySubject" name="subject" required placeholder="Enter subject">
                </div>
                <div class="form-group">
                    <label for="enquiryMessage">Message</label>
                    <textarea id="enquiryMessage" name="message" rows="4" required placeholder="Type your message here..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-button">
                        <i class="fas fa-paper-plane"></i> Send Enquiry
                    </button>
                </div>
                <div class="success-message" id="enquirySuccess">
                    Your enquiry has been sent successfully! We'll get back to you soon.
                </div>
            </form>
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
                                            <i class="fas fa-rupee-sign"></i>
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

            // Show loading indicator
            showLoadingIndicator();

            // Use the Nominatim API to geocode the search input
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchInput)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        throw new Error('Location not found');
                    }

                    const location = data[0];
                    const lat = parseFloat(location.lat);
                    const lng = parseFloat(location.lon);

                    // Create form data for nearby search
                    const formData = new FormData();
                    formData.append('action', 'nearby');
                    formData.append('lat', lat);
                    formData.append('lng', lng);
                    formData.append('radius', searchRadius);

                    // Search for stations near the geocoded location
                    return fetch('index.php', {
                        method: 'POST',
                        body: formData
                    });
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

                        // Add search location marker
                        const searchIcon = L.divIcon({
                            className: 'search-location-marker',
                            html: '<i class="fas fa-search-location"></i>',
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        });

                        const searchLat = parseFloat(data.searchLocation?.lat || data.stations[0]?.lat);
                        const searchLng = parseFloat(data.searchLocation?.lng || data.stations[0]?.lng);

                        L.marker([searchLat, searchLng], {
                            icon: searchIcon
                        }).addTo(map);

                        // Add radius circle
                        L.circle([searchLat, searchLng], {
                            radius: searchRadius * 1000, // Convert km to meters
                            color: '#4CAF50',
                            fillColor: '#4CAF50',
                            fillOpacity: 0.1,
                            weight: 1
                        }).addTo(map);

                        // Update map view and markers
                        updateMapWithResults(data.stations);
                        updateSearchResults(data.stations);

                        // Show the results container
                        document.getElementById('appContent').style.display = 'block';

                    } else {
                        throw new Error(data.error || 'No stations found in the area');
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    showErrorMessage(error.message);
                })
                .finally(() => {
                    hideLoadingIndicator();
                });
        }

        // Add these helper styles for the search marker
        const styles = `
            .search-location-marker {
                color: #2196F3;
                font-size: 24px;
                text-align: center;
                line-height: 30px;
            }

            .loading-indicator {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: #666;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .loading-indicator i {
                font-size: 18px;
            }
        `;

        // Add the styles to the document
        const styleSheet = document.createElement("style");
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);

        // Add event listener for Enter key in search input
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSearch();
            }
        });

        function updateMapWithResults(stations) {
            // Clear existing markers
            map.eachLayer(layer => {
                if (layer instanceof L.Marker) {
                    map.removeLayer(layer);
                }
            });

            if (!stations || stations.length === 0) {
                showNoResultsMessage();
                return;
            }

            // Create a bounds object
            let bounds = L.latLngBounds([]);

            stations.forEach(station => {
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
                                            <i class="fas fa-rupee-sign"></i>
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

                    // Extend bounds with this marker's position
                    bounds.extend([station.lat, station.lng]);
                }
            });

            // Only fit bounds if we have markers
            if (bounds.isValid()) {
                map.fitBounds(bounds, {
                    padding: [50, 50],
                    maxZoom: 15
                });
            }
        }

        function updateSearchResults(stations) {
            const resultsContainer = document.getElementById('searchResults');
            const appContent = document.getElementById('appContent');
            
            // Show the results container
            appContent.style.display = 'block';
            
            if (!stations || stations.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No charging stations found in this area</p>
                    </div>
                `;
                return;
            }

            // Create HTML for stations
            const stationsHTML = stations.map(station => `
                <div class="station-card">
                    <div class="station-header">
                        <h3>${station.name}</h3>
                        <span class="status-badge ${station.status.toLowerCase()}">${station.status}</span>
                    </div>
                   
                    <div class="station-info">
                        <p><i class="fas fa-map-marker-alt"></i> ${station.address}</p>
                        ${station.distance ? `
                            <p><i class="fas fa-road"></i> ${station.distance} km away</p>
                        ` : ''}
                        <p><i class="fas fa-plug"></i> ${station.availableSlots}/${station.totalSlots} slots available</p>
                        <p><i class="fas fa-rupee-sign"></i> ${station.price}/kWh</p>
                    </div>
                    <div class="station-actions">
                        <a href="station_details.php?id=${station.id}" class="view-details-btn">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            `).join('');

            resultsContainer.innerHTML = stationsHTML;
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
                    <button class="error-close">&times;</button>
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

                            L.marker([userLat, userLng], {
                                icon: userIcon
                            }).addTo(map);

                            // Add radius circle
                            L.circle([userLat, userLng], {
                                radius: radius * 1000, // Convert km to meters
                                color: '#4CAF50',
                                fillColor: '#4CAF50',
                                fillOpacity: 0.1,
                                weight: 1
                            }).addTo(map);

                            // Update map view and markers
                            updateMapWithResults(data.stations);
                            updateSearchResults(data.stations);

                            // Center map on user location with appropriate zoom
                            map.setView([userLat, userLng], getZoomForRadius(radius));
                        } else {
                            throw new Error(data.error || 'No stations found in the area');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        showErrorMessage(`Failed to find nearby stations: ${error.message}`);
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
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function handleGeolocationError(error) {
            let errorMessage;
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = "Location access denied. Please enable location services in your browser settings to find nearby stations.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = "Location information is unavailable. Please try again later.";
                    break;
                case error.TIMEOUT:
                    errorMessage = "Location request timed out. Please check your internet connection and try again.";
                    break;
                default:
                    errorMessage = "An unknown error occurred while getting your location. Please try again.";
                    break;
            }
            showErrorMessage(errorMessage);
            console.error('Geolocation error:', error); // Debug log
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

        // Add this function to your JavaScript code
        function displayError(message) {
            const errorContainer = document.getElementById('error-message') || 
                                  document.querySelector('.error-message');
            
            if (errorContainer) {
                errorContainer.textContent = message;
                errorContainer.style.display = 'block';
            } else {
                // Fallback if no error container exists
                alert(message);
            }
        }

        // Update your form submission handler to use this function
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            const errorContainer = document.querySelector('.error-message');
            if (errorContainer) {
                errorContainer.style.display = 'none';
            }
            
            // Get form data
            const formData = new FormData(this);
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = 'Processing...';
            
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If not JSON, get text and log it
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned invalid format');
                    });
                }
            })
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    displayError(data.error || 'An unexpected error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayError('An unexpected error occurred. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });

        function displayError(message) {
            const errorContainer = document.querySelector('.error-message');
            if (!errorContainer) {
                // Create error container if it doesn't exist
                const form = document.getElementById('registerForm');
                const newErrorContainer = document.createElement('div');
                newErrorContainer.className = 'error-message';
                newErrorContainer.style.display = 'block';
                newErrorContainer.style.color = '#dc3545';
                newErrorContainer.style.backgroundColor = '#f8d7da';
                newErrorContainer.style.padding = '10px';
                newErrorContainer.style.borderRadius = '5px';
                newErrorContainer.style.marginBottom = '15px';
                form.insertBefore(newErrorContainer, form.firstChild);
                newErrorContainer.textContent = message;
            } else {
                errorContainer.style.display = 'block';
                errorContainer.textContent = message;
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
            <?php 
            // Database connection check
            $dbConnected = false;
            try {
                $conn = new mysqli($servername, $username, $password, $dbname);
                if ($conn->connect_error) {
                    throw new Exception("Connection failed: " . $conn->connect_error);
                }
                $dbConnected = true;
            } catch (Exception $e) {
                error_log("Database connection error: " . $e->getMessage());
            ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Unable to connect to the server. Please try again later.</p>
                    <button class="retry-btn" onclick="location.reload()">Retry</button>
                </div>
            <?php
            }
            ?>

            <?php if (isset($_SESSION['user_id']) && $dbConnected): ?>
                <?php 
                try {
                    // Fetch bookings with error handling
                    $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ?");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $result = $stmt->get_result();
                    $userBookings = $result->fetch_all(MYSQLI_ASSOC);
                    
                    if (!empty($userBookings)): 
                ?>
                    <div id="bookingsList" class="bookings-list">
                        <?php 
                        // Sort bookings by date and time in descending order
                        usort($userBookings, function($a, $b) {
                            $dateTimeA = strtotime($a['booking_date'] . ' ' . $a['booking_time']);
                            $dateTimeB = strtotime($b['booking_date'] . ' ' . $b['booking_time']);
                            return $dateTimeB - $dateTimeA;
                        });

                        // Get only first 2 bookings
                        $recentBookings = array_slice($userBookings, 0, 2);
                        
                        foreach ($recentBookings as $booking): 
                            $bookingDateTime = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
                            $isRecent = (time() - $bookingDateTime) < (24 * 60 * 60);
                        ?>
                            <div class="booking-card <?php echo $isRecent ? 'recent-booking' : ''; ?>">
                                <?php if ($isRecent): ?>
                                    <div class="recent-badge">Recent</div>
                                <?php endif; ?>
                                <div class="booking-station">
                                    <?php
                                    // Add this database query before line 2684 to get the booking with station details
                                    $booking_id = $booking['booking_id'] ?? 0;

                                    // Query to get booking with station details
                                    $stmtDetails = $conn->prepare("
                                        SELECT b.*, cs.name AS station_name, cs.address AS station_address 
                                        FROM bookings b
                                        JOIN charging_stations cs ON b.station_id = cs.station_id
                                        WHERE b.booking_id = ?
                                    ");

                                    if ($stmtDetails) {
                                        $stmtDetails->bind_param("i", $booking_id);
                                        $stmtDetails->execute();
                                        $result = $stmtDetails->get_result();
                                        $booking_with_details = $result->fetch_assoc();
                                        
                                        // Properly close the statement here
                                        $stmtDetails->close();
                                        
                                        // Update $booking with the joined data
                                        if ($booking_with_details) {
                                            $booking = array_merge($booking, $booking_with_details);
                                        }
                                    }
                                    ?>

                                    <h3 class="station-name"><?php echo htmlspecialchars($booking['station_name'] ?? 'Unknown Station'); ?></h3>
                                    <div class="station-address">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($booking['station_address'] ?? 'No Address'); ?></span>
                                    </div>
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

                        <?php if (count($userBookings) > 2): ?>
                            <a href="my-bookings.php" class="see-more-link">
                                See All Bookings (<?php echo count($userBookings); ?>)
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-bookings">
                        <i class="fas fa-calendar-times"></i>
                        <p>No bookings found</p>
                    </div>
                <?php 
                    endif;
                } catch (Exception $e) {
                    error_log("Error fetching bookings: " . $e->getMessage());
                ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Unable to load bookings. Please try again later.</p>
                        <button class="retry-btn" onclick="location.reload()">Retry</button>
                    </div>
                <?php
                } finally {
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                    if (isset($conn)) {
                        $conn->close();
                    }
                }
                ?>
            <?php elseif (!$dbConnected): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Unable to connect to the server. Please try again later.</p>
                    <button class="retry-btn" onclick="location.reload()">Retry</button>
                </div>
            <?php else: ?>
                <div class="login-prompt">
                    <i class="fas fa-user-lock"></i>
                    <p>Please log in to view your bookings</p>
                    <button class="login-btn" onclick="showLoginModal()">Log In</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        /* Previous styles remain unchanged */

        /* Add styles for error message */
        .error-message {
            text-align: center;
            padding: 20px;
            background-color: #fff3f3;
            border-radius: 8px;
            margin: 10px 0;
        }

        .error-message i {
            color: #ff3a57;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .error-message p {
            color: #666;
            margin-bottom: 15px;
        }

        .retry-btn {
            padding: 8px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .retry-btn:hover {
            background-color: #2980b9;
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
            <span class="close-modal" onclick="closeLoginModal()"><i class="fas fa-times"></i></span>
            <div class="tabs">
                <a href="#" class="tab active" onclick="showLoginTab(event)">Log In</a>
                <a href="#" class="tab" onclick="showSignupTab(event)">Register</a>
            </div>
    
            <!-- Login Form -->
                <form id="loginForm" action="login_process.php" method="post" class="tab-content active">
                <div class="input-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" required>
                    <div class="validation-message" id="login-email-validation"></div>
                </div>

                <div class="input-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                    <div class="validation-message" id="login-password-validation"></div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="submit-button">
                    <span class="button-text">Log In</span>
                    <span class="spinner"></span>
                </button>

                <p class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </p>

                
            </form>

            <!-- Signup Form -->
            <form id="signupForm" action="register_process.php" method="post" class="tab-content">
                <div class="input-group">
                    <label for="signup-username">Username</label>
                    <input type="text" id="signup-username" name="username" required>
                    <div class="validation-message" id="signup-username-validation"></div>
                </div>

                <div class="input-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email" required>
                    <div class="validation-message" id="signup-email-validation"></div>
                </div>

                <div class="input-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="password" required>
                    <div class="validation-message" id="signup-password-validation"></div>
                </div>

                <div class="input-group">
                    <label for="signup-confirm-password">Confirm Password</label>
                    <input type="password" id="signup-confirm-password" name="confirm_password" required>
                    <div class="validation-message" id="signup-confirm-password-validation"></div>
                </div>

                <div id="signup-error" class="error-message" style="display: none; color: red;"></div>

                <button type="submit" class="submit-button">
                    <span class="button-text">Sign Up</span>
                    <span class="spinner"></span>
                </button>

               
            </form>
        </div>
    </div>

<style>
    
    /* Modal Base Styles */
.login-modal {
    display: none; /* This is the default state */
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

.google-signin-container {
    margin-top: 20px;
    text-align: center;
}

.google-login-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 24px;
    background: #fff;
    color: #757575;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.google-login-btn:hover {
    background: #f8f8f8;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.google-login-btn i {
    color: #4285f4;
    font-size: 18px;
}

/* Add a divider between regular login and Google login */
.google-signin-container::before {
    content: "or";
    display: block;
    text-align: center;
    color: #757575;
    margin: 15px 0;
    position: relative;
}

.google-signin-container::before::after {
    content: "";
    display: block;
    height: 1px;
    background: #ddd;
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    z-index: -1;
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
        if (modal) {
            modal.style.display = 'flex';
            showLoginTab(new Event('click'));
            setupLiveValidation();
        } else {
            console.error('Login modal element not found');
        }
    }

    // Add event listener when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handler to login/signup button
        const loginButton = document.querySelector('a[onclick="showLoginModal(); return false;"]');
        if (loginButton) {
            loginButton.addEventListener('click', function(e) {
                e.preventDefault();
                showLoginModal();
            });
        }
    });

    // Add this function to handle closing the modal and clearing errors
    function closeLoginModal() {
        const modal = document.getElementById('loginModal');
        modal.style.display = 'none';
        
        // Clear all forms and error messages
        document.getElementById('loginForm').reset();
        document.getElementById('signupForm').reset();
        document.querySelectorAll('.validation-message').forEach(msg => msg.textContent = '');
    }

    // Initialize event listeners when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('loginModal');
        const modalContent = document.querySelector('.login-container');
        
        // Add click handler for the close button
        const closeBtn = document.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event from bubbling up
                closeLoginModal();
            });
        }

        // Prevent modal from closing when clicking inside the modal content
        if (modalContent) {
            modalContent.addEventListener('click', function(e) {
                e.stopPropagation(); // Stop the click from reaching the modal backdrop
            });
        }

        // Remove the click outside listener by not adding it at all
        // The modal will now only close via the close button
        
        // Add form submission handlers
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Add your login form submission logic here
                console.log('Login form submitted');
                // You can add AJAX call to login_process.php here
            });
        }

        const signupForm = document.getElementById('signupForm');
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Add your signup form submission logic here
                console.log('Signup form submitted');
                // You can add AJAX call to register_process.php here
            });
        }

        // Add tab switching handlers
        const tabs = document.querySelectorAll('.tabs .tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.textContent.includes('Log In')) {
                    showLoginTab(e);
                } else if (this.textContent.includes('Register')) {
                    showSignupTab(e);
                }
            });
        });
    });

    // Add these CSS styles to ensure proper display
    document.head.insertAdjacentHTML('beforeend', `
        <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        </style>
    `);

    // Add this function to handle live validation
    function setupLiveValidation() {
        console.log('Setting up live validation'); // Debug log

        // Login form validation
        const loginEmail = document.getElementById('login-email');
        const loginPassword = document.getElementById('login-password');

        if (loginEmail) {
            loginEmail.addEventListener('input', function() {
                const error = validateEmail(this.value);
                const validationElement = document.getElementById('login-email-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        if (loginPassword) {
            loginPassword.addEventListener('input', function() {
                const error = validatePassword(this.value);
                const validationElement = document.getElementById('login-password-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        // Signup form validation
        const signupUsername = document.getElementById('signup-username');
        const signupEmail = document.getElementById('signup-email');
        const signupPassword = document.getElementById('signup-password');
        const signupConfirmPassword = document.getElementById('signup-confirm-password');

        if (signupUsername) {
            signupUsername.addEventListener('input', function() {
                const error = validateUsername(this.value);
                const validationElement = document.getElementById('signup-username-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        if (signupEmail) {
            signupEmail.addEventListener('input', function() {
                const error = validateEmail(this.value);
                const validationElement = document.getElementById('signup-email-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        if (signupPassword) {
            signupPassword.addEventListener('input', function() {
                const error = validatePassword(this.value);
                const validationElement = document.getElementById('signup-password-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }

                // Check confirm password match if it has a value
                if (signupConfirmPassword && signupConfirmPassword.value) {
                    const confirmError = signupConfirmPassword.value !== this.value ? 
                        "Passwords do not match" : "";
                    const confirmValidationElement = document.getElementById('signup-confirm-password-validation');
                    if (confirmValidationElement) {
                        confirmValidationElement.textContent = confirmError;
                        signupConfirmPassword.classList.toggle('error', confirmError !== '');
                    }
                }
            });
        }

        if (signupConfirmPassword) {
            signupConfirmPassword.addEventListener('input', function() {
                const signupPassword = document.getElementById('signup-password');
                const error = this.value !== signupPassword.value ? 
                    "Passwords do not match" : "";
                const validationElement = document.getElementById('signup-confirm-password-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }
    }

    // Move validation functions to the top so they're defined first
    function validateUsername(username) {
        if (!username) return "Username is required";
        if (!/^[a-zA-Z]/.test(username)) return "Username must start with a letter";
        if (username.length < 3) return "Username must be at least 3 characters long";
        if (!/^[a-zA-Z][a-zA-Z0-9_]*$/.test(username)) return "Username can only contain letters, numbers, and underscores";
        return "";
    }

    function validateEmail(email) {
        if (!email) return "Email is required";
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return "Please enter a valid email address";
        return "";
    }

    function validatePassword(password) {
        if (!password) return "Password is required";
        if (password.length < 8) return "Password must be at least 8 characters long";
        if (!/[A-Z]/.test(password)) return "Password must contain at least one uppercase letter";
        if (!/[a-z]/.test(password)) return "Password must contain at least one lowercase letter";
        if (!/[0-9]/.test(password)) return "Password must contain at least one number";
        return "";
    }

    // Function to setup live validation
    function setupLiveValidation() {
        // Login form validation
        const loginEmail = document.getElementById('login-email');
        const loginPassword = document.getElementById('login-password');

        if (loginEmail) {
            loginEmail.addEventListener('input', function() {
                const error = validateEmail(this.value);
                const validationElement = document.getElementById('login-email-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        if (loginPassword) {
            loginPassword.addEventListener('input', function() {
                const error = validatePassword(this.value);
                const validationElement = document.getElementById('login-password-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        // Signup form validation
        const signupUsername = document.getElementById('signup-username');
        const signupEmail = document.getElementById('signup-email');
        const signupPassword = document.getElementById('signup-password');
        const signupConfirmPassword = document.getElementById('signup-confirm-password');

        if (signupUsername) {
            signupUsername.addEventListener('input', function() {
                const error = validateUsername(this.value);
                const validationElement = document.getElementById('signup-username-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        if (signupEmail) {
            signupEmail.addEventListener('input', function() {
                const error = validateEmail(this.value);
                const validationElement = document.getElementById('signup-email-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }

        if (signupPassword) {
            signupPassword.addEventListener('input', function() {
                const error = validatePassword(this.value);
                const validationElement = document.getElementById('signup-password-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }

                // Check confirm password match if it has a value
                if (signupConfirmPassword && signupConfirmPassword.value) {
                    const confirmError = signupConfirmPassword.value !== this.value ? 
                        "Passwords do not match" : "";
                    const confirmValidationElement = document.getElementById('signup-confirm-password-validation');
                    if (confirmValidationElement) {
                        confirmValidationElement.textContent = confirmError;
                        signupConfirmPassword.classList.toggle('error', confirmError !== '');
                    }
                }
            });
        }

        if (signupConfirmPassword) {
            signupConfirmPassword.addEventListener('input', function() {
                const signupPassword = document.getElementById('signup-password');
                const error = this.value !== signupPassword.value ? 
                    "Passwords do not match" : "";
                const validationElement = document.getElementById('signup-confirm-password-validation');
                if (validationElement) {
                    validationElement.textContent = error;
                    this.classList.toggle('error', error !== '');
                }
            });
        }
    }

    // Update showLoginModal function to include validation setup
    function showLoginModal() {
        const modal = document.getElementById('loginModal');
        if (modal) {
            modal.style.display = 'flex';
            showLoginTab(new Event('click'));
            setupLiveValidation(); // Set up validation when modal is shown
        }
    }

    // Add event listeners when the document is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Set up initial validation
        setupLiveValidation();

        // Add click handler for login/signup button
        const loginButton = document.getElementById('loginSignupBtn');
        if (loginButton) {
            loginButton.addEventListener('click', function(e) {
                e.preventDefault();
                showLoginModal();
            });
        }

        // Set up tab switching with validation
        const tabs = document.querySelectorAll('.tabs .tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                if (this.textContent.includes('Log In')) {
                    showLoginTab(e);
                } else if (this.textContent.includes('Register')) {
                    showSignupTab(e);
                }
                setupLiveValidation(); // Reset validation when switching tabs
            });
        });
    });
    </script>

    <script>
    // Update the login form submission handler
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear any existing error messages
        const existingErrors = document.querySelectorAll('.error-message');
        existingErrors.forEach(error => error.remove());
        
        // Validate form inputs
        const username = document.getElementById('login-username').value;
        const password = document.getElementById('login-password').value;
        
        const usernameError = validateUsername(username);
        const passwordError = validatePassword(password);

        // Show validation errors if any
        document.getElementById('login-username-validation').textContent = usernameError;
        document.getElementById('login-password-validation').textContent = passwordError;

        if (usernameError || passwordError) {
            return;
        }

        // Create FormData object
        const formData = new FormData(this);

        // Send AJAX request
        fetch('login_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect on success
                window.location.href = data.redirect || 'index.php';
            } else {
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = data.error || 'Login failed. Please try again.';
                this.insertBefore(errorDiv, this.firstChild);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = 'An error occurred. Please try again.';
            this.insertBefore(errorDiv, this.firstChild);
        });
    });

    // Update the signup form submission handler
    document.getElementById('signupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear any existing error messages
        const existingErrors = document.querySelectorAll('.error-message');
        existingErrors.forEach(error => error.remove());
        
        // Validate form inputs
        const username = document.getElementById('signup-username').value;
        const email = document.getElementById('signup-email').value;
        const password = document.getElementById('signup-password').value;
        const confirmPassword = document.getElementById('signup-confirm-password').value;
        
        const usernameError = validateUsername(username);
        const emailError = validateEmail(email);
        const passwordError = validatePassword(password);
        const confirmError = password !== confirmPassword ? "Passwords do not match" : "";

        // Show validation errors if any
        document.getElementById('signup-username-validation').textContent = usernameError;
        document.getElementById('signup-email-validation').textContent = emailError;
        document.getElementById('signup-password-validation').textContent = passwordError;
        document.getElementById('signup-confirm-password-validation').textContent = confirmError;

        if (usernameError || emailError || passwordError || confirmError) {
            return;
        }

        // Create FormData object
        const formData = new FormData(this);

        // Send AJAX request
        fetch('register_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json().then(data => ({status: response.status, data: data})))
        .then(({status, data}) => {
            if (!data.success) {
                displayError(data.error);
                return;
            }
            
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayError('An unexpected error occurred. Please try again.');
        });
    });
    </script>

    <!-- Replace the existing login form submission script with this updated version: -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to initialize form handlers
        function initializeFormHandlers() {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');

            if (loginForm) {
                loginForm.removeEventListener('submit', handleLoginSubmit); // Remove any existing handlers
                loginForm.addEventListener('submit', handleLoginSubmit);
            }

            if (signupForm) {
                signupForm.removeEventListener('submit', handleSignupSubmit); // Remove any existing handlers
                signupForm.addEventListener('submit', handleSignupSubmit);
            }
        }

        // Login form submission handler
        function handleLoginSubmit(e) {
            e.preventDefault();
            
            // Clear any existing error messages
            const existingErrors = this.querySelectorAll('.error-message');
            existingErrors.forEach(error => error.remove());

            // Create FormData object
            const formData = new FormData(this);

            // Show loading state
            const submitButton = this.querySelector('.submit-button');
            const originalButtonText = submitButton.textContent;
            submitButton.textContent = 'Logging in...';
            submitButton.disabled = true;

            // Send AJAX request
            fetch('login_process.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.textContent = data.message;
                    this.insertBefore(successDiv, this.firstChild);
                    
                    // Redirect after brief delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = data.message;
                    this.insertBefore(errorDiv, this.firstChild);
                    
                    // Reset button
                    submitButton.textContent = originalButtonText;
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'An error occurred. Please try again.';
                
                const closeBtn = document.createElement('span');
                closeBtn.className = 'error-close';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = function() {
                    errorDiv.remove();
                };
                errorDiv.appendChild(closeBtn);
                
                this.insertBefore(errorDiv, this.firstChild);
                
                // Reset button
                submitButton.textContent = originalButtonText;
                submitButton.disabled = false;
            });
        }

        // Signup form submission handler
        function handleSignupSubmit(e) {
            e.preventDefault();
            
            // Clear any existing error messages
            const existingErrors = this.querySelectorAll('.error-message');
            existingErrors.forEach(error => error.remove());

            // Create FormData object
            const formData = new FormData(this);

            // Send AJAX request
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().then(data => ({status: response.status, data: data})))
            .then(({status, data}) => {
                if (!data.success) {
                    displayError(data.error);
                    return;
                }
                
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayError('An unexpected error occurred. Please try again.');
            });
        }

        // Initialize form handlers when the modal is shown
        function showLoginModal() {
            const modal = document.getElementById('loginModal');
            if (modal) {
                modal.style.display = 'flex';
                showLoginTab(new Event('click'));
                initializeFormHandlers(); // Initialize form handlers when modal is shown
            }
        }

        // Add click handler for the login/signup button
        const loginSignupBtn = document.getElementById('loginSignupBtn');
        if (loginSignupBtn) {
            loginSignupBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showLoginModal();
            });
        }

        // Initialize form handlers on page load
        initializeFormHandlers();
    });
    </script>

    <!-- After user registration logic -->
    <?php
    if (isset($_POST['signup-username']) && isset($_POST['signup-email']) && isset($_POST['signup-password'])) {
        try {
            $username = $_POST['signup-username'];
            $email = $_POST['signup-email'];
            $password = $_POST['signup-password'];
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $otpExpiration = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert user data
            $stmt = $pdo->prepare("INSERT INTO tbl_users (username, email, password, otp, otp_expiration, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$username, $email, $hashedPassword, $otp, $otpExpiration]);
            
            // Get the user ID
            $userId = $pdo->lastInsertId();
            
            // Send OTP email
            if (sendOtpEmail($email, $otp)) {
                $pdo->commit();
                
                // Start session and store necessary data
                $_SESSION['temp_user_id'] = $userId;
                $_SESSION['temp_email'] = $email;
                
                // Return success response for AJAX
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email for OTP verification.',
                    'redirect' => 'verify_otp.php'
                ]);
                exit;
            } else {
                throw new Exception("Failed to send OTP email");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => 'Registration failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    function sendOtpEmail($email, $otp) {
        require 'vendor/autoload.php'; // Make sure you have PHPMailer installed
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'evolve1829@gmail.com';
            $mail->Password = 'qgmg ijoz obaw wvth';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('evolve1829@gmail.com', 'EVolve');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your EVolve Verification Code';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Welcome to EVolve!</h2>
                    <p>Your verification code is: <strong style='font-size: 24px;'>{$otp}</strong></p>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                </div>";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            return false;
        }
    }
    ?>

    <!-- Add this right after your existing JavaScript code -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        
        // Check if OTP modal exists
        const otpModal = document.getElementById('otpVerificationModal');
        console.log('OTP Modal exists in DOM:', !!otpModal);
        
        // Add the OTP modal to the page if it doesn't exist
        if (!otpModal) {
            console.log('Creating OTP modal dynamically');
            const modalHTML = `
                <div id="otpVerificationModal" class="login-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 1000; align-items: center; justify-content: center;">
                    <div class="login-container" style="background-color: white; border-radius: 8px; padding: 30px; max-width: 400px; width: 90%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative;">
                        <span class="close-modal" onclick="hideOtpModal()" style="position: absolute; right: 15px; top: 15px; font-size: 24px; cursor: pointer;">&times;</span>
                        <h2 style="text-align: center; margin-bottom: 20px;"><i class="fas fa-envelope"></i> Email Verification</h2>
                        <form id="otpVerificationForm">
                            <p class="verification-instructions" style="text-align: center; color: #666; margin-bottom: 20px; font-size: 0.95rem; line-height: 1.5;">
                                We've sent a verification code to your email. Please enter it below to complete your registration.
                            </p>
                            
                            <div class="input-group" style="margin-bottom: 20px;">
                                <div class="otp-input-container" style="display: flex; justify-content: center; margin-bottom: 10px;">
                                    <input type="text" name="otp" id="otpInput" maxlength="6" required placeholder="Enter 6-digit code" 
                                           style="font-size: 1.5rem; letter-spacing: 0.5rem; text-align: center; width: 100%; max-width: 250px; padding: 10px; border: 2px solid #ddd; border-radius: 8px;">
                                </div>
                                <div class="validation-message" id="otpError" style="color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem;"></div>
                            </div>
                            
                            <div class="error-message" style="display: none; color: #dc3545; background-color: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>
                            
                            <button type="submit" class="login-button" style="width: 100%; padding: 12px; background-color: #3498db; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                <i class="fas fa-check-circle"></i> Verify & Complete Registration
                            </button>
                            
                            <div class="resend-code" style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: #666;">
                                <p>Didn't receive the code? <a href="#" id="resendOtpLink" style="color: #3498db; text-decoration: none; font-weight: 500;">Resend</a></p>
                                <div id="resendTimer" style="display: none; color: #666; font-size: 0.85rem; margin-top: 5px;">Resend in <span id="timerCount">60</span> seconds</div>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            // Append the modal to the body
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Now try to get the modal again
            const newOtpModal = document.getElementById('otpVerificationModal');
            console.log('OTP Modal created and exists:', !!newOtpModal);
            
            // Attach event listeners to the new form
            const otpForm = document.getElementById('otpVerificationForm');
            if (otpForm) {
                otpForm.addEventListener('submit', handleOtpFormSubmit);
                console.log('Attached submit event to OTP form');
            }
            
            const resendLink = document.getElementById('resendOtpLink');
            if (resendLink) {
                resendLink.addEventListener('click', handleResendOtp);
                console.log('Attached click event to resend link');
            }
        }
    });

    // Updated showOtpModal function
    function showOtpModal() {
        console.log('Showing OTP modal');
        const modal = document.getElementById('otpVerificationModal');
        if (!modal) {
            console.error('OTP Modal not found in DOM!');
            return;
        }
        
        modal.style.display = 'flex';
        document.getElementById('otpInput').focus();
        startResendTimer();
        console.log('OTP modal should now be visible');
    }

    // Add these standalone handlers for the OTP form
    function handleOtpFormSubmit(e) {
        e.preventDefault();
        console.log('OTP form submitted');
        
        // Clear previous errors
        const errorContainer = this.querySelector('.error-message');
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        
        // Change verification_process.php to verify_otp.php
        fetch('verify_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // If not JSON, get text and log it
                return response.text().then(text => {
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned invalid format');
                });
            }
        })
        .then(data => {
            console.log('Verification response:', data);
            if (data.success) {
                // Show success message
                const successMessage = document.createElement('div');
                successMessage.className = 'success-message';
                successMessage.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                successMessage.style.color = '#28a745';
                successMessage.style.backgroundColor = '#d4edda';
                successMessage.style.padding = '10px';
                successMessage.style.borderRadius = '5px';
                successMessage.style.marginBottom = '15px';
                
                this.insertBefore(successMessage, this.firstChild);
                
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            } else {
                // Show error message
                if (errorContainer) {
                    errorContainer.textContent = data.error;
                    errorContainer.style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (errorContainer) {
                errorContainer.textContent = 'An unexpected error occurred. Please try again.';
                errorContainer.style.display = 'block';
            }
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    }

    function handleResendOtp(e) {
        e.preventDefault();
        console.log('Resend OTP link clicked');
        
        const resendButton = this;
        resendButton.textContent = 'Sending...';
        
        fetch('resend_otp.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Resend response:', data);
            if (data.success) {
                alert('A new verification code has been sent to your email.');
                startResendTimer();
            } else {
                alert(data.error || 'Failed to resend verification code. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred. Please try again.');
        })
        .finally(() => {
            resendButton.textContent = 'Resend';
        });
    }

    // Modify the registration form handler to explicitly call the showOtpModal function
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        
        if (registerForm) {
            console.log('Register form found, attaching submit handler');
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Register form submitted');
                
                // Clear previous errors
                const errorContainer = this.querySelector('.error-message');
                if (errorContainer) {
                    errorContainer.style.display = 'none';
                }
                
                const formData = new FormData(this);
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = 'Processing...';
                
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, get text and log it
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned invalid format');
                        });
                    }
                })
                .then(data => {
                    console.log('Register response:', data);
                    if (data.success) {
                        console.log('Registration successful, showing OTP modal');
                        
                        // Hide registration modal if it exists
                        const loginModal = document.getElementById('loginModal');
                        if (loginModal) {
                            loginModal.style.display = 'none';
                            console.log('Hidden login modal');
                        }
                        
                        // Explicitly call the function to show OTP modal
                        showOtpModal();
                        
                        // Double check if modal is visible
                        setTimeout(() => {
                            const otpModal = document.getElementById('otpVerificationModal');
                            console.log('OTP modal display style:', otpModal.style.display);
                            if (otpModal.style.display !== 'flex') {
                                console.log('Forcing OTP modal display');
                                otpModal.style.display = 'flex';
                            }
                        }, 500);
                    } else {
                        displayError(data.error || 'Registration failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayError('An unexpected error occurred. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
            });
        } else {
            console.log('Register form not found');
        }
    });
    </script>

    <!-- Update your registration form handler to redirect to the verify_otp.php page -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        
        if (registerForm) {
            console.log('Register form found, attaching submit handler');
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Register form submitted');
                
                // Clear previous errors
                const errorContainer = this.querySelector('.error-message');
                if (errorContainer) {
                    errorContainer.style.display = 'none';
                }
                
                const formData = new FormData(this);
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = 'Processing...';
                
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, get text and log it
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned invalid format');
                        });
                    }
                })
                .then(data => {
                    console.log('Register response:', data);
                    if (data.success) {
                        console.log('Registration successful, redirecting to OTP verification page');
                        
                        // Hide registration modal if it exists
                        const loginModal = document.getElementById('loginModal');
                        if (loginModal) {
                            loginModal.style.display = 'none';
                        }
                        
                        // Redirect to the OTP verification page
                        window.location.href = 'verify_otp.php';
                    } else {
                        displayError(data.error || 'Registration failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayError('An unexpected error occurred. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
            });
        } else {
            console.log('Register form not found');
        }
    });
    </script>

    <!-- Update the signup form submission handler - reducing duplicate OTP sending
    document.addEventListener('DOMContentLoaded', function() {
        // Remove any existing event listeners if the form was previously initialized
        const signupForm = document.getElementById('signupForm');
        if (signupForm) {
            const clonedForm = signupForm.cloneNode(true);
            signupForm.parentNode.replaceChild(clonedForm, signupForm);
            
            // Add event listener to the fresh form element
            clonedForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Signup form submitted - single handler');
                
                // Clear any existing error messages
                const existingErrors = this.querySelectorAll('.error-message');
                existingErrors.forEach(error => error.remove());
                
                // Validate form inputs
                const username = document.getElementById('signup-username').value;
                const email = document.getElementById('signup-email').value;
                const password = document.getElementById('signup-password').value;
                const confirmPassword = document.getElementById('signup-confirm-password').value;
                
                const usernameError = validateUsername(username);
                const emailError = validateEmail(email);
                const passwordError = validatePassword(password);
                const confirmError = password !== confirmPassword ? "Passwords do not match" : "";

                // Show validation errors if any
                document.getElementById('signup-username-validation').textContent = usernameError;
                document.getElementById('signup-email-validation').textContent = emailError;
                document.getElementById('signup-password-validation').textContent = passwordError;
                document.getElementById('signup-confirm-password-validation').textContent = confirmError;

                if (usernameError || emailError || passwordError || confirmError) {
                    return;
                }

                // Create FormData object
                const formData = new FormData(this);
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Send AJAX request
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, get text and log it
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned invalid format');
                        });
                    }
                })
                .then(data => {
                    console.log('Register response:', data);
                    if (data.success) {
                        // Hide login modal
                        const loginModal = document.getElementById('loginModal');
                        if (loginModal) {
                            loginModal.style.display = 'none';
                        }
                        
                        // Show OTP verification modal instead of redirecting
                        showOtpModal();
                    } else {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        errorDiv.textContent = data.error || 'Registration failed. Please try again.';
                        this.insertBefore(errorDiv, this.firstChild);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = 'An unexpected error occurred. Please try again.';
                    this.insertBefore(errorDiv, this.firstChild);
                })
                .finally(() => {
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
            });
        }
    });

    // Function to start the resend timer (add this if not already present)
    function startResendTimer() {
        const resendLink = document.getElementById('resendOtpLink');
        const timerDiv = document.getElementById('resendTimer');
        const timerCount = document.getElementById('timerCount');
        
        if (!resendLink || !timerDiv || !timerCount) return;
        
        resendLink.style.display = 'none';
        timerDiv.style.display = 'block';
        
        let seconds = 60;
        timerCount.textContent = seconds;
        
        const countdownInterval = setInterval(() => {
            seconds--;
            timerCount.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                resendLink.style.display = 'inline';
                timerDiv.style.display = 'none';
            }
        }, 1000);
    }

    // Function to hide OTP modal
    function hideOtpModal() {
        const modal = document.getElementById('otpVerificationModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    </script>

    <!-- Create OTP modal dynamically if needed -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Remove any existing event listeners if the form was previously initialized
        const signupForm = document.getElementById('signupForm');
        if (signupForm) {
            const clonedForm = signupForm.cloneNode(true);
            signupForm.parentNode.replaceChild(clonedForm, signupForm);
            
            // Add event listener to the fresh form element
            clonedForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Signup form submitted - single handler');
                
                // Clear any existing error messages
                const existingErrors = this.querySelectorAll('.error-message');
                existingErrors.forEach(error => error.remove());
                
                // Validate form inputs
                const username = document.getElementById('signup-username').value;
                const email = document.getElementById('signup-email').value;
                const password = document.getElementById('signup-password').value;
                const confirmPassword = document.getElementById('signup-confirm-password').value;
                
                const usernameError = validateUsername(username);
                const emailError = validateEmail(email);
                const passwordError = validatePassword(password);
                const confirmError = password !== confirmPassword ? "Passwords do not match" : "";

                // Show validation errors if any
                document.getElementById('signup-username-validation').textContent = usernameError;
                document.getElementById('signup-email-validation').textContent = emailError;
                document.getElementById('signup-password-validation').textContent = passwordError;
                document.getElementById('signup-confirm-password-validation').textContent = confirmError;

                if (usernameError || emailError || passwordError || confirmError) {
                    return;
                }

                // Create FormData object
                const formData = new FormData(this);
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Send AJAX request
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, get text and log it
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned invalid format');
                        });
                    }
                })
                .then(data => {
                    console.log('Register response:', data);
                    if (data.success) {
                        // Hide login modal
                        const loginModal = document.getElementById('loginModal');
                        if (loginModal) {
                            loginModal.style.display = 'none';
                        }
                        
                        // Ensure OTP modal exists
                        createOtpModalIfNeeded();
                        
                        // Show OTP verification modal
                        showOtpModal();
                    } else {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        errorDiv.textContent = data.error || 'Registration failed. Please try again.';
                        this.insertBefore(errorDiv, this.firstChild);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = 'An unexpected error occurred. Please try again.';
                    this.insertBefore(errorDiv, this.firstChild);
                })
                .finally(() => {
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
            });
        }
    });

    // Create OTP modal dynamically if needed
    function createOtpModalIfNeeded() {
        let otpModal = document.getElementById('otpVerificationModal');
        
        if (!otpModal) {
            console.log('Creating OTP modal');
            const modalHTML = `
                <div id="otpVerificationModal" class="login-modal" style="display: none;">
                    <div class="login-container">
                        <span class="close-modal" onclick="hideOtpModal()"><i class="fas fa-times"></i></span>
                        <h2 style="text-align: center; margin-bottom: 20px;"><i class="fas fa-envelope"></i> Email Verification</h2>
                        <p class="verification-instructions" style="text-align: center; color: #666; margin-bottom: 20px;">
                            We've sent a verification code to your email. Please enter it below to complete your registration.
                        </p>
                        
                        <div class="input-group">
                            <div class="otp-input-container" style="display: flex; justify-content: center; margin-bottom: 15px;">
                                <input type="text" name="otp" id="otpInput" maxlength="6" required placeholder="Enter 6-digit code" 
                                       style="font-size: 1.5rem; letter-spacing: 0.5rem; text-align: center; width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px;">
                            </div>
                            <div id="otpError" style="color: #dc3545; text-align: center; margin-top: 5px;"></div>
                        </div>
                        
                        <button type="button" onclick="verifyOtp()" class="submit-button" style="width: 100%; margin-top: 10px;">
                            <span class="button-text">Verify & Complete Registration</span>
                        </button>
                        
                        <div class="resend-code" style="text-align: center; margin-top: 20px;">
                            <p style="color: #666; font-size: 0.9rem;">Didn't receive the code? 
                               <a href="#" id="resendOtpLink" onclick="resendOtp(); return false;" style="color: #2196F3; text-decoration: none;">Resend</a>
                            </p>
                            <div id="resendTimer" style="display: none; color: #666; font-size: 0.85rem;">
                                Resend in <span id="timerCount">30</span> seconds
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            otpModal = document.getElementById('otpVerificationModal');
        }
        
        return otpModal;
    }

    // Function to show OTP modal
    function showOtpModal() {
        const otpModal = createOtpModalIfNeeded();
        if (otpModal) {
            otpModal.style.display = 'flex';
            const otpInput = document.getElementById('otpInput');
            if (otpInput) {
                otpInput.value = '';
                otpInput.focus();
            }
            startResendTimer();
        } else {
            console.error('OTP modal could not be created or found');
        }
    }

    // Verify OTP function - Updated to ensure redirection only after successful verification
    function verifyOtp() {
        const otpInput = document.getElementById('otpInput');
        const otp = otpInput.value.trim();
        const errorDiv = document.getElementById('otpError');
        
        // Clear previous error messages
        errorDiv.textContent = '';
        
        if (!otp) {
            errorDiv.textContent = 'Please enter the verification code';
            return;
        }
        
        if (otp.length !== 6 || !/^\d+$/.test(otp)) {
            errorDiv.textContent = 'Please enter a valid 6-digit code';
            return;
        }
        
        // Show loading state
        const verifyButton = document.querySelector('#otpVerificationModal .submit-button');
        const originalButtonText = verifyButton.innerHTML;
        verifyButton.disabled = true;
        verifyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        
        // Create FormData object
        const formData = new FormData();
        formData.append('otp', otp);
        
        // Add debugging information
        console.log('Sending OTP:', otp);
        
        // Send AJAX request to the correct file
        fetch('verify_otp.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Important: send cookies/session data
        })
        .then(response => {
            console.log('Response status:', response.status);
            // First try to parse as JSON
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    // Try to parse the response as JSON
                    return JSON.parse(text);
                } catch (e) {
                    // If parsing fails, throw an error with the raw text
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Server returned invalid response: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            
            if (data.success) {
                // Show success message
                errorDiv.style.color = '#28a745';
                errorDiv.textContent = 'Verification successful! Redirecting...';
                
                // Redirect after a delay
                setTimeout(() => {
                    window.location.href = data.redirect || 'index.php';
                }, 2000);
            } else {
                // Show error message
                errorDiv.style.color = '#dc3545';
                errorDiv.textContent = data.error || 'Verification failed. Please try again.';
                
                // Re-enable button
                verifyButton.disabled = false;
                verifyButton.innerHTML = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Show error message
            errorDiv.style.color = '#dc3545';
            errorDiv.textContent = 'An unexpected error occurred. Please try again.';
            
            // Re-enable button
            verifyButton.disabled = false;
            verifyButton.innerHTML = originalButtonText;
        });
    }

    // Function to resend OTP
    function resendOtp() {
        const resendLink = document.getElementById('resendOtpLink');
        const errorDiv = document.getElementById('otpError');
        
        // Show loading state
        const originalLinkText = resendLink.textContent;
        resendLink.textContent = 'Sending...';
        resendLink.style.pointerEvents = 'none';
        
        // Clear error message
        if (errorDiv) {
            errorDiv.textContent = '';
        }
        
        // Add any stored temp user info
        const formData = new FormData();
        const tempUserId = sessionStorage.getItem('temp_user_id');
        const tempEmail = sessionStorage.getItem('temp_email');
        if (tempUserId) {
            formData.append('user_id', tempUserId);
        }
        if (tempEmail) {
            formData.append('email', tempEmail);
        }
        
        fetch('resend_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset and start timer
                startResendTimer();
                
                // Show success message
                alert('Verification code resent to your email');
            } else {
                if (errorDiv) {
                    errorDiv.textContent = data.error || 'Failed to resend verification code';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (errorDiv) {
                errorDiv.textContent = 'An error occurred. Please try again.';
            }
        })
        .finally(() => {
            // Reset link state (will be hidden by timer but reset anyway)
            resendLink.textContent = originalLinkText;
            resendLink.style.pointerEvents = '';
        });
    }

    // Function to start the resend timer - REDUCED to 30 seconds
    function startResendTimer() {
        const resendLink = document.getElementById('resendOtpLink');
        const timerDiv = document.getElementById('resendTimer');
        const timerCount = document.getElementById('timerCount');
        
        if (!resendLink || !timerDiv || !timerCount) return;
        
        resendLink.style.display = 'none';
        timerDiv.style.display = 'block';
        
        // Reduced from 60 to 30 seconds
        let seconds = 30;
        timerCount.textContent = seconds;
        
        const countdownInterval = setInterval(() => {
            seconds--;
            timerCount.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                resendLink.style.display = 'inline';
                timerDiv.style.display = 'none';
            }
        }, 1000);
    }

    // Function to hide OTP modal
    function hideOtpModal() {
        const modal = document.getElementById('otpVerificationModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    </script>

    <!-- Add this code before the closing </body> tag -->
    <script>
    // Function to prevent OTP modal from auto-closing
    document.addEventListener('DOMContentLoaded', function() {
        // Check if verification modal should be shown
        <?php if (isset($_GET['verify']) && $_GET['verify'] === 'true'): ?>
            console.log('Verification page loaded, showing OTP modal');
            
            // Make sure OTP modal exists
            createOtpModalIfNeeded();
            
            // Add event listeners to prevent accidental closing
            const otpModal = document.getElementById('otpVerificationModal');
            if (otpModal) {
                // Show the modal
                setTimeout(function() {
                    otpModal.style.display = 'flex';
                    
                    // Focus on the input field
                    const otpInput = document.getElementById('otpInput');
                    if (otpInput) {
                        otpInput.focus();
                    }
                    
                    // Start the resend timer
                    startResendTimer();
                    
                    console.log('OTP modal should now be visible');
                }, 500); // Small delay to ensure DOM is ready
                
                // Prevent the modal from closing when clicking outside
                otpModal.addEventListener('click', function(e) {
                    if (e.target === otpModal) {
                        e.stopPropagation(); // Prevent default closing behavior
                        return false;
                    }
                });
                
                // Only allow closing with the X button
                const closeBtn = otpModal.querySelector('.close-modal');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        if (confirm('Are you sure you want to close? You will need to verify your email to complete registration.')) {
                            hideOtpModal();
                        }
                    });
                }
            } else {
                console.error('Could not find or create OTP modal');
            }
        <?php else: ?>
            // If we're not on a verification page, clear the flag
            sessionStorage.removeItem('otp_modal_shown');
        <?php endif; ?>
        
        // Update the successful verification handler to clear the flag
        const originalVerifyOtp = window.verifyOtp;
        window.verifyOtp = function() {
            const otpInput = document.getElementById('otpInput');
            const otp = otpInput.value.trim();
            const errorDiv = document.getElementById('otpError');
            
            // Clear previous error messages
            errorDiv.textContent = '';
            
            if (!otp) {
                errorDiv.textContent = 'Please enter the verification code';
                return;
            }
            
            if (otp.length !== 6 || !/^\d+$/.test(otp)) {
                errorDiv.textContent = 'Please enter a valid 6-digit code';
                return;
            }
            
            // Show loading state
            const verifyButton = document.querySelector('#otpVerificationModal .submit-button');
            const originalButtonText = verifyButton.innerHTML;
            verifyButton.disabled = true;
            verifyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            
            // Create FormData object
            const formData = new FormData();
            formData.append('otp', otp);
            
            // Send AJAX request to the correct file
            fetch('verify_otp.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        throw new Error('Server returned invalid response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Parsed response:', data);
                
                if (data.success) {
                    // Clear the modal shown flag on successful verification
                    sessionStorage.removeItem('otp_modal_shown');
                    
                    // Show success message
                    errorDiv.style.color = '#28a745';
                    errorDiv.textContent = 'Verification successful! Redirecting...';
                    
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = data.redirect || 'index.php';
                    }, 2000);
                } else {
                    // Show error message
                    errorDiv.style.color = '#dc3545';
                    errorDiv.textContent = data.error || 'Verification failed. Please try again.';
                    
                    // Re-enable button
                    verifyButton.disabled = false;
                    verifyButton.innerHTML = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Show error message
                errorDiv.style.color = '#dc3545';
                errorDiv.textContent = 'An unexpected error occurred. Please try again.';
                
                // Re-enable button
                verifyButton.disabled = false;
                verifyButton.innerHTML = originalButtonText;
            });
        };
    });

    // Update hideOtpModal function to also clear the flag
    function hideOtpModal() {
        const modal = document.getElementById('otpVerificationModal');
        if (modal) {
            modal.style.display = 'none';
            // Clear the flag
            sessionStorage.removeItem('otp_modal_shown');
        }
    }
    </script>

    <!-- Replace all existing signup/registration form handlers with this single unified version -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing form handlers...');
        
        // Clear any existing event listeners by cloning and replacing forms
        const formIds = ['registerForm', 'signupForm'];
        
        formIds.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                console.log(`Found ${formId}, attaching clean event handler`);
                const clonedForm = form.cloneNode(true);
                form.parentNode.replaceChild(clonedForm, form);
                
                // Add fresh event listener
                clonedForm.addEventListener('submit', handleRegistrationSubmit);
            }
        });
        
        // Single unified registration form handler
        function handleRegistrationSubmit(e) {
            e.preventDefault();
            console.log('Registration form submitted');
            
            // Clear previous errors
            const errorContainer = this.querySelector('.error-message');
            if (errorContainer) {
                errorContainer.style.display = 'none';
            }
            
            // Get form data
            const formData = new FormData(this);
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Add a flag to indicate this is coming from the unified handler
            formData.append('unified_handler', 'true');
            
            // Log the actual data being sent
            console.log('Form ID:', this.id);
            
            // Send AJAX request
            fetch('register_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If not JSON, get text and log it
                    return response.text().then(text => {
                        console.error('Non-JSON response:', text);
                        throw new Error('Server returned invalid format');
                    });
                }
            })
            .then(data => {
                console.log('Register response:', data);
                if (data.success) {
                    console.log('Registration successful, preparing for verification');
                    
                    // Hide login/registration modal if it exists
                    const loginModal = document.getElementById('loginModal');
                    if (loginModal) {
                        loginModal.style.display = 'none';
                    }
                    
                    // We'll let the redirect in the response handle showing the OTP modal
                    // This avoids duplicate modal creation issues
                    window.location.href = data.redirect;
                } else {
                    // Show error message
                    if (errorContainer) {
                        errorContainer.textContent = data.error || 'Registration failed. Please try again.';
                        errorContainer.style.display = 'block';
                    } else {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'error-message';
                        errorDiv.textContent = data.error || 'Registration failed. Please try again.';
                        this.insertBefore(errorDiv, this.firstChild);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = 'An unexpected error occurred. Please try again.';
                
                if (errorContainer) {
                    errorContainer.textContent = errorMessage;
                    errorContainer.style.display = 'block';
                } else {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = errorMessage;
                    this.insertBefore(errorDiv, this.firstChild);
                }
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        }
    });
    </script>

    <!-- Mobile menu functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        const navLinks = document.getElementById('navLinks');
        
        if (mobileMenuToggle && mobileMenuClose && navLinks) {
            mobileMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                navLinks.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            
            mobileMenuClose.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event bubbling
                navLinks.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (navLinks.classList.contains('active') && 
                    !navLinks.contains(e.target) && 
                    !mobileMenuToggle.contains(e.target)) {
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }
    });
    </script>

    <!-- Handle enquiry form submission -->
    <script>
    document.getElementById('enquiryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(this);
        
        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        // Send to backend
        fetch('send_enquiry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                document.getElementById('enquirySuccess').style.display = 'block';
                // Reset form
                this.reset();
                
                // Hide success message after 5 seconds
                setTimeout(function() {
                    document.getElementById('enquirySuccess').style.display = 'none';
                }, 5000);
            } else {
                alert(data.error || 'Failed to send enquiry. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });
    </script>
</body>
</html>
