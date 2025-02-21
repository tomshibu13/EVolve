<?php
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

    // Add status column
    $sql = "ALTER TABLE tbl_users 
            ADD COLUMN status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'";
    
    if (mysqli_query($conn, $sql)) {
        echo "Status column added successfully";
    } else {
        throw new Exception("Error adding status column: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 