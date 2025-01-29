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