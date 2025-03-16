<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['booking_id']) || !isset($data['user_id'])) {
        throw new Exception('Invalid QR code data');
    }
    
    $bookingId = $data['booking_id'];
    $userId = $data['user_id'];
    
    // Get booking status
    $stmt = $pdo->prepare("
        SELECT status FROM bookings 
        WHERE booking_id = ? AND user_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Invalid booking');
    }
    
    // Update booking status based on current status
    switch ($booking['status']) {
        case 'confirmed':
            // User is entering
            $newStatus = 'in_progress';
            $message = 'Welcome! You can now start charging.';
            break;
            
        case 'in_progress':
            // User is exiting
            $newStatus = 'completed';
            $message = 'Thank you for using our service!';
            break;
            
        default:
            throw new Exception('Invalid booking status');
    }
    
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ?, 
            updated_at = CURRENT_TIMESTAMP
        WHERE booking_id = ?
    ");
    $stmt->execute([$newStatus, $bookingId]);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'redirect' => $newStatus === 'completed' ? 'my-bookings.php' : null
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 