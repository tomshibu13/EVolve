<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Default response
$response = [
    'success' => false,
    'error' => 'An unknown error occurred'
];

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Get station_id and message
        $stationId = filter_input(INPUT_POST, 'station_id', FILTER_VALIDATE_INT);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        
        // Validate required fields
        if (!$stationId || !$message) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        } else {
            // For non-logged in users, create a temporary record or require login
            throw new Exception('Please log in to submit an enquiry');
        }
        
        // Format message with subject
        $fullMessage = "Subject: " . $subject . "\n\n" . $message;
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO enquiries (user_id, station_id, message) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$userId, $stationId, $fullMessage])) {
            // Get the enquiry ID
            $enquiryId = $pdo->lastInsertId();
            
            // Get station details
            $stmtStation = $pdo->prepare("
                SELECT name, operator_id FROM charging_stations WHERE station_id = ?
            ");
            $stmtStation->execute([$stationId]);
            $station = $stmtStation->fetch(PDO::FETCH_ASSOC);
            
            if ($station && isset($station['operator_id'])) {
                // Create notification for station owner/operator
                $notifyOwner = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, is_read)
                    VALUES (?, ?, ?, 'system', FALSE)
                ");
                $notifyOwner->execute([
                    $station['operator_id'], 
                    "New Enquiry Received",
                    "You have received a new enquiry about your station: " . $station['name']
                ]);
            }
            
            $response = [
                'success' => true,
                'message' => 'Your enquiry has been sent successfully!'
            ];
        } else {
            throw new Exception('Failed to save your enquiry');
        }
    }
    
    // Handle response to an enquiry
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_to_enquiry'])) {
        // Validate inputs
        $enquiryId = filter_input(INPUT_POST, 'enquiry_id', FILTER_VALIDATE_INT);
        $responseText = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
        
        if (!$enquiryId || !$responseText) {
            throw new Exception('Invalid response data');
        }
        
        // Check if user is authorized to respond (station owner check)
        // ...
        
        // Update the enquiry with response
        $updateStmt = $pdo->prepare("
            UPDATE enquiries 
            SET response = ?, 
                response_date = NOW(), 
                status = 'responded'
            WHERE enquiry_id = ?
        ");
        
        if ($updateStmt->execute([$responseText, $enquiryId])) {
            // Get the user who submitted the enquiry and station info
            $userStmt = $pdo->prepare("
                SELECT e.user_id, s.name as station_name 
                FROM enquiries e
                JOIN charging_stations s ON e.station_id = s.station_id
                WHERE e.enquiry_id = ?
            ");
            $userStmt->execute([$enquiryId]);
            $enquiryData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($enquiryData) {
                // Create notification for the user that includes the response
                $notifyUser = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type, is_read)
                    VALUES (?, ?, ?, 'system', FALSE)
                ");
                
                // Include the response content in the notification message
                $notificationMessage = "Your enquiry about " . $enquiryData['station_name'] . " has received a response:\n\n";
                $notificationMessage .= $responseText;
                
                $notifyUser->execute([
                    $enquiryData['user_id'], 
                    "Response to Your Enquiry",
                    $notificationMessage
                ]);
            }
            
            $response = [
                'success' => true,
                'message' => 'Response submitted successfully'
            ];
        } else {
            throw new Exception('Failed to save your response');
        }
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response); 