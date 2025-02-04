<<<<<<< HEAD
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
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
?> 