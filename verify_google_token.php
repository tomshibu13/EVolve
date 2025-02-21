<?php
require_once 'vendor/autoload.php';

// Get token from POST request
$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

try {
    $client = new Google_Client(['client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com']);
    $payload = $client->verifyIdToken($token);
    
    if ($payload) {
        // Token is valid, create session
        session_start();
        $_SESSION['user_id'] = $payload['sub'];
        $_SESSION['email'] = $payload['email'];
        $_SESSION['username'] = $payload['name'];
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 