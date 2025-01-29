<?php
// Database connection credentials
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = "";    // Replace with your database password
$dbname = "evolve1"; // Name of the database to create

// Create a connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully\n";
} else {
    echo "Error creating database: " . $conn->error;
}

// Select the created database
$conn->select_db($dbname);

// SQL to create tbl_users table
$tableSql = "CREATE TABLE IF NOT EXISTS tbl_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    passwordhash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($tableSql) === TRUE) {
    echo "Table tbl_users created successfully\n";
} else {
    echo "Error creating table: " . $conn->error;
}



// Close connection
$conn->close();
?>
