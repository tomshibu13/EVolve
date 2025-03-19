<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

header('Content-Type: application/json');

// Check if all required parameters are provided
if (!isset($_GET['station_id']) || !isset($_GET['date'])) {
    echo json_encode([]);
    exit();
}

$station_id = $_GET['station_id'];
$date = $_GET['date'];

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Get all bookings for this station and date
    $query = "
        SELECT booking_time, duration 
        FROM bookings 
        WHERE station_id = ? 
        AND booking_date = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $station_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $bookings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    
    mysqli_close($conn);
    echo json_encode($bookings);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 