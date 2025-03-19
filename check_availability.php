<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

header('Content-Type: application/json');
$response = ['available' => true];

// Check if all required parameters are provided
if (!isset($_POST['station_id']) || !isset($_POST['booking_date']) || 
    !isset($_POST['booking_time']) || !isset($_POST['duration'])) {
    $response['available'] = false;
    $response['error'] = 'Missing required parameters';
    echo json_encode($response);
    exit();
}

$station_id = $_POST['station_id'];
$booking_date = $_POST['booking_date'];
$booking_time = $_POST['booking_time'];
$duration = $_POST['duration'];

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Check if the slot is already booked
    $end_time = date('H:i', strtotime($booking_time . ' + ' . $duration . ' minutes'));
    $check_query = "
        SELECT * FROM bookings 
        WHERE station_id = ? 
        AND booking_date = ? 
        AND (
            (booking_time <= ? AND DATE_ADD(booking_time, INTERVAL duration MINUTE) > ?) OR
            (booking_time < ? AND DATE_ADD(booking_time, INTERVAL duration MINUTE) >= ?)
        )
    ";
    
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "isssss", $station_id, $booking_date, $end_time, $booking_time, $booking_time, $booking_time);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $response['available'] = false;
    }
    
    mysqli_close($conn);
    
} catch (Exception $e) {
    $response['available'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response); 