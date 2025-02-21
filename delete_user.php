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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
        $user_id = mysqli_real_escape_string($conn, $_POST['id']);
        
        // Delete the user
        $query = "DELETE FROM tbl_users WHERE user_id = '$user_id'";
        if (mysqli_query($conn, $query)) {
            header("Location: user_list.php?message=User+deleted+successfully");
            exit();
        } else {
            throw new Exception("Error deleting user: " . mysqli_error($conn));
        }
    } else {
        throw new Exception("Invalid request method or User ID not provided");
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 