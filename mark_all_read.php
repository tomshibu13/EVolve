<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = TRUE 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

// Redirect back to the referring page or homepage
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit; 