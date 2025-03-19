<?php
session_start();
require_once 'config.php';

// Assume payment is completed and you have the following variables
$bookingId = 123; // Example booking ID
$userId = $_SESSION['user_id']; // Get the user ID from the session
$stationId = 456; // Example station ID

// Generate a unique token
$secretKey = 'your_secret_key'; // Use a secure secret key
$timestamp = time();
$token = hash('sha256', $bookingId . $userId . $stationId . $timestamp . $secretKey);

// JSON data for QR code
$qrData = json_encode([
    'booking_id' => $bookingId,
    'user_id' => $userId,
    'station_id' => $stationId,
    'token' => $token
]);

// Store the QR code data in the database
$stmt = $pdo->prepare("INSERT INTO qr_codes (booking_id, user_id, station_id, token, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$bookingId, $userId, $stationId, $token]);

// Redirect to the page where the QR code will be displayed
header("Location: view-qr.php?id=$bookingId&user=$userId");
exit();
?> 