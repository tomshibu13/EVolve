<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'error' => 'An unknown error occurred'
];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to manage notifications');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Mark single notification as read
        if (isset($_POST['notification_id'])) {
            $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
            
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE notification_id = ? AND user_id = ?
            ");
            
            if ($stmt->execute([$notificationId, $_SESSION['user_id']])) {
                $response = [
                    'success' => true
                ];
            }
        }
        
        // Mark all notifications as read
        if (isset($_POST['mark_all_read'])) {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ?
            ");
            
            if ($stmt->execute([$_SESSION['user_id']])) {
                $response = [
                    'success' => true
                ];
            }
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response); 