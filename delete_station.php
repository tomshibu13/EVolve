<?php
// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Check if ID is provided and is numeric
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception("Invalid or missing station ID");
    }

    $station_id = intval($_POST['id']);

    // Check if station exists and is not in use before deleting
    $check_query = "SELECT cs.station_id 
                    FROM charging_stations cs
                    LEFT JOIN charging_sessions css ON cs.station_id = css.station_id 
                    WHERE cs.station_id = ? AND css.session_id IS NULL";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $station_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) === 0) {
        throw new Exception("Station not found or is currently in use");
    }
    mysqli_stmt_close($check_stmt);

    // Delete the station
    $query = "DELETE FROM charging_stations WHERE station_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $station_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error deleting station: " . mysqli_error($conn));
    }

    // Add affected rows check
    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception("No station was deleted");
    }

    // Set a more specific success message
    header("Location: admindash.php?success=station_deleted");
    exit();

} catch (Exception $e) {
    header("Location: admindash.php?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 