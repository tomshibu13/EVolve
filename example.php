<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: index.php#LoginForm");
    exit();
}

// Check if user is admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    // Admin user, redirect to admin dashboard
    header("Location: admindash.php");
    exit();
} else {
    // Regular user, redirect to profile page
    header("Location: profile.php");
    exit();
}
?>