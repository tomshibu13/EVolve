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
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $new_status = isset($_GET['status']) ? $_GET['status'] : '';

    // Validate status
    if (!in_array($new_status, ['Active', 'Inactive'])) {
        throw new Exception("Invalid status value");
    }

    // Update user status
    $stmt = mysqli_prepare($conn, "UPDATE tbl_users SET status = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating status: " . mysqli_error($conn));
    }

    // Redirect back to user list
    header("Location: user_list.php");
    exit();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 