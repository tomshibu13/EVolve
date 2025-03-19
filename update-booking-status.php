<?php
// update-booking-status.php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['bookingId']) || !isset($data['userId']) || !isset($data['status'])) {
        throw new Exception('Missing required parameters');
    }
    
    $bookingId = $data['bookingId'];
    $userId = $data['userId'];
    $status = $data['status'];
    
    // Validate status
    $allowedStatuses = ['confirmed', 'checked_in', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception('Invalid status value');
    }
    
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ?, updated_at = NOW()
        WHERE booking_id = ? AND user_id = ?
    ");
    
    $result = $stmt->execute([$status, $bookingId, $userId]);
    
    if (!$result) {
        throw new Exception('Failed to update booking status');
    }
    
    // If check-in, record start time
    if ($status === 'checked_in') {
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET check_in_time = NOW()
            WHERE booking_id = ?
        ");
        $stmt->execute([$bookingId]);
    }
    
    // If check-out, record end time and calculate actual duration
    if ($status === 'completed') {
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET check_out_time = NOW(),
                actual_duration = TIMESTAMPDIFF(MINUTE, check_in_time, NOW())
            WHERE booking_id = ?
        ");
        $stmt->execute([$bookingId]);
    }
    
    // Add notification for the user
    $statusMessages = [
        'checked_in' => 'You have successfully checked in at your charging station.',
        'completed' => 'Your charging session has been completed. Thank you for using EVolve!',
        'cancelled' => 'Your booking has been cancelled.'
    ];
    
    if (isset($statusMessages[$status])) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, 'booking_status')
        ");
        $stmt->execute([
            $userId,
            "Booking Update",
            $statusMessages[$status]
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully',
        'status' => $status
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>