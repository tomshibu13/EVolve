<?php
session_start();
require_once 'config.php';

// Check if the request is POST and contains JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_SERVER['CONTENT_TYPE']) && 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    
    // Get the JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate required fields
    if (!isset($data['station_id']) || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }
    
    $station_id = intval($data['station_id']);
    $action = $data['action']; // 'increment' or 'decrement'
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // First, get the current available slots
        $stmt = $pdo->prepare("SELECT available_slots, total_slots FROM charging_stations WHERE station_id = ?");
        $stmt->execute([$station_id]);
        $station = $stmt->fetch();
        
        if (!$station) {
            throw new Exception("Station not found");
        }
        
        $available_slots = $station['available_slots'];
        $total_slots = $station['total_slots'];
        
        // Update the available slots
        if ($action === 'increment') {
            // Make sure we don't exceed total slots
            if ($available_slots < $total_slots) {
                $available_slots++;
            }
        } else if ($action === 'decrement') {
            // Make sure we don't go below zero
            if ($available_slots > 0) {
                $available_slots--;
            }
        }
        
        // Update the database
        $stmt = $pdo->prepare("UPDATE charging_stations SET available_slots = ? WHERE station_id = ?");
        $stmt->execute([$available_slots, $station_id]);
        
        // Commit the transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Available slots updated successfully',
            'available_slots' => $available_slots
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or content type']);
}
?> 