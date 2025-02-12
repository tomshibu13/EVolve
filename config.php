<<<<<<< HEAD
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
=======
<?php
$host = 'localhost';
$dbname = 'evolves';
$username = 'root';  // Change this to your database username
$password = '';      // Change this to your database password

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}else{
    echo "Connected to database";
}
?> 
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
