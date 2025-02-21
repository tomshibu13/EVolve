<?php
$host = 'localhost';
$dbname = 'evolve1';
$username = 'root';
$password = '';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}else{
    // echo "Connected to database";
}
?> 

