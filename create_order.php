<?php
session_start();
require 'vendor/autoload.php'; // Ensure Composer is installed
require 'config.php'; // This will give us the $pdo connection

use Razorpay\Api\Api;

// Razorpay API Keys
$key_id = "rzp_test_R6h0atxxQ4WsUU";
$key_secret = "5CyNCDCaDKmrRqPWX2K6uLGV";
$api = new Api($key_id, $key_secret);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            throw new Exception("Please log in to continue.");
        }

        // Validate inputs
        $station_id = filter_var($_POST['station_id'], FILTER_VALIDATE_INT);
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $duration = filter_var($_POST['duration'], FILTER_VALIDATE_INT);

        if (!$station_id || !$booking_date || !$booking_time || !$duration) {
            throw new Exception("Invalid input parameters");
        }

        // Get station price
        $stmt = $pdo->prepare("SELECT price FROM charging_stations WHERE station_id = ?");
        $stmt->execute([$station_id]);
        $station = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$station) {
            throw new Exception("Station not found");
        }

        // Calculate amount based on duration and station price
        // Convert duration to hours (as price is per hour)
        $hours = $duration / 60;
        $amount = $hours * $station['price'];
        $amount_paise = (int)($amount * 100); // Convert to paise

        // Create Razorpay Order
        $orderData = [
            'receipt'         => 'rcpt_' . time() . '_' . $user_id,
            'amount'          => $amount_paise,
            'currency'        => 'INR',
            'payment_capture' => 1,
            'notes'          => [
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'duration' => $duration,
                'user_id' => $user_id,
                'station_id' => $station_id
            ]
        ];

        $razorpayOrder = $api->order->create($orderData);

        // Store booking in database
        $stmt = $pdo->prepare("
            INSERT INTO bookings 
            (user_id, station_id, booking_date, booking_time, duration, amount, razorpay_order_id, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $user_id,
            $station_id,
            $booking_date,
            $booking_time,
            $duration,
            $amount,
            $razorpayOrder->id
        ]);

        // Redirect to payment page with amount in rupees
        header("Location: payment_page.php?order_id=" . urlencode($razorpayOrder->id) . "&amount=" . urlencode($amount));
        exit();

    } catch (Exception $e) {
        error_log('Razorpay Error: ' . $e->getMessage());
        die("Error: " . $e->getMessage());
    }
}
?>
