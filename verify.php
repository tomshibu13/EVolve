<?php
require_once 'config.php'; // Database connection

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists in the database
    $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Token is valid, update the user's status to verified
        $stmt = $pdo->prepare("UPDATE tbl_users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $stmt->execute([$token]);

        echo "Email verified successfully!";
    } else {
        echo "Invalid verification token.";
    }
} else {
    echo "No verification token provided.";
} 