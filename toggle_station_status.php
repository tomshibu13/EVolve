<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['station_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $station_id = intval($_POST['station_id']);
    $status = $_POST['status'];

    // Validate status
    if (!in_array($status, ['Active', 'Inactive'])) {
        throw new Exception('Invalid status value');
    }

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Update the status
    $query = "UPDATE charging_stations SET status = ? WHERE station_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $station_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating status: " . mysqli_error($conn));
    }

    // Close connection
    mysqli_close($conn);

    // Return success response
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit; // Ensure no further output is sent
}
?> 