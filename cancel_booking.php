<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if booking_id is provided
if (!isset($_POST['booking_id'])) {
    header('Location: my-bookings.php');
    exit();
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify the booking belongs to the logged-in user
    $stmt = $pdo->prepare("
        SELECT user_id 
        FROM bookings 
        WHERE booking_id = ? AND user_id = ?
    ");
    $stmt->execute([$_POST['booking_id'], $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        // Update booking status to cancelled
        $updateStmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'cancelled' 
            WHERE booking_id = ?
        ");
        $updateStmt->execute([$_POST['booking_id']]);
        
        // Redirect back to bookings page with success message
        $_SESSION['message'] = "Booking cancelled successfully";
    } else {
        $_SESSION['error'] = "Invalid booking or unauthorized access";
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error cancelling booking: " . $e->getMessage();
}

header('Location: my-bookings.php');
exit();
?> 