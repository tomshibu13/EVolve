<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a station owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['owner_name'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate input
if (!isset($_POST['booking_id']) || !isset($_POST['status']) || !isset($_POST['station_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$booking_id = (int)$_POST['booking_id'];
$status = $_POST['status'];
$station_id = (int)$_POST['station_id'];

// Validate status
$allowed_statuses = ['completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // First verify this station belongs to the logged-in owner
    $query = "SELECT * FROM charging_stations WHERE station_id = ? AND owner_name = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("is", $station_id, $_SESSION['owner_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Unauthorized access to this station");
    }
    
    $stmt->close();
    
    // Update booking status
    $query = "UPDATE bookings SET status = ? WHERE booking_id = ? AND station_id = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("sii", $status, $booking_id, $station_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update booking status");
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No booking found with the provided ID");
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 