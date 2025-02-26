<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];

    // Validate status
    if (!in_array($status, ['active', 'disabled'])) {
        throw new Exception('Invalid status value');
    }

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Update the status
    $query = "UPDATE station_owner_requests SET status = ? WHERE request_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "si", $status, $request_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating status: " . mysqli_stmt_error($stmt));
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
}
?> 