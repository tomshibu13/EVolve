<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? null;
$status = $data['status'] ?? null;

if (!$booking_id || !$status) {
    http_response_code(400);
    exit('Missing required data');
}

try {
    // Verify the booking belongs to a station owned by this user
    $stmt = $mysqli->prepare("
        UPDATE bookings b
        JOIN charging_stations s ON b.station_id = s.station_id
        SET b.status = ?
        WHERE b.booking_id = ? AND s.owner_name = ?
    ");
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    if (!$stmt->bind_param("sis", $status, $booking_id, $_SESSION['owner_name'])) {
        throw new Exception("Binding parameters failed: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    if ($stmt->affected_rows > 0) {
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found or not authorized']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 