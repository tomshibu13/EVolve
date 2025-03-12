<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    if (!isset($_POST['booking_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $bookingId = (int)$_POST['booking_id'];
    $status = $_POST['status'];

    // Verify that this booking belongs to one of the owner's stations
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM bookings b
        JOIN charging_stations cs ON b.station_id = cs.station_id
        JOIN station_owner_requests sor ON cs.owner_request_id = sor.request_id
        WHERE b.booking_id = ? AND sor.user_id = ?
    ");
    
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Unauthorized access to this booking');
    }

    // Update the booking status
    $updateStmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ? 
        WHERE booking_id = ?
    ");
    
    $updateStmt->execute([$status, $bookingId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error updating booking status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 