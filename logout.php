<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// Log the logout event
$logFile = 'logout.log'; // Specify the log file
$logMessage = date('Y-m-d H:i:s') . " - User logged out\n"; // Create a log message
file_put_contents($logFile, $logMessage, FILE_APPEND); // Append the log message to the file

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php#Login");
exit();

