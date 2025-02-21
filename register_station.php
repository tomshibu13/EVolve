<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please login to register a charging station.";
    header("Location: index.php#loginForm");
    exit();
}

// Make the POST check more explicit and secure
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error_message'] = "Invalid request method. Please use the registration form.";
    header("Location: add_station_page.php");
    exit();
}

try {
    // Get basic station information
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $price = floatval($_POST['price']);
    $total_slots = intval($_POST['total_slots']);
    
    // Create JSON object for charger types
    $charger_types = [];
    if (isset($_POST['charger_types']) && is_array($_POST['charger_types'])) {
        foreach ($_POST['charger_types'] as $type) {
            $slots = intval($_POST["slots_" . str_replace(' ', '_', $type)]);
            $charger_types[] = [
                "type" => $type,
                "slots" => $slots
            ];
        }
    }
    $charger_types_json = json_encode(["types" => $charger_types]);
    
    // Get operator_id from session (assuming you store user_id in session after login)
    $operator_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Insert station data using POINT for location
    $query = "INSERT INTO charging_stations 
              (owner_name, name, location, address, operator_id, price, 
               charger_types, total_slots, available_slots) 
              VALUES 
              (?, ?, POINT(?, ?), ?, ?, ?, ?, ?, ?)";
              
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Error preparing statement: " . mysqli_error($conn));
    }
    
    // Set available_slots equal to total_slots initially
    $available_slots = $total_slots;
    
    mysqli_stmt_bind_param($stmt, "ssddidsiii", 
        $owner_name, 
        $name, 
        $latitude, 
        $longitude, 
        $address, 
        $operator_id, 
        $price, 
        $charger_types_json, 
        $total_slots, 
        $available_slots
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error executing statement: " . mysqli_stmt_error($stmt));
    }
    
    $_SESSION['success_message'] = "Charging station registered successfully!";
    header("Location: view_stations.php");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error registering charging station: " . $e->getMessage();
    header("Location: add_station_page.php");
    exit();
}

mysqli_close($conn);
?> 