<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Get parameters
    $station_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $new_status = isset($_GET['status']) ? $_GET['status'] : '';

    // Validate status
    if (!in_array($new_status, ['Active', 'Inactive'])) {
        throw new Exception("Invalid status value");
    }

    // Update station status
    $stmt = mysqli_prepare($conn, "UPDATE charging_stations SET status = ? WHERE station_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $station_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating status: " . mysqli_error($conn));
    }

    // Redirect back to admin dashboard
    header("Location: admindash.php");
    exit();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 