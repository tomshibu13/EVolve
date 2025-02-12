<<<<<<< HEAD

<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to the home page
header("Location: index.php");
exit();
=======
<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to the home page
header("Location: index.php");
exit();
?> 
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
