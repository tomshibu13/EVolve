<?php
$host = 'localhost';
$dbname = 'evolve1';
$username = 'root';  // Change this if needed
$password = '';      // Change this if needed

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Uncomment if you want a success message
echo "Connected to database";  
?>
