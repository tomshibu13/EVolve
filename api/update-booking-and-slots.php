<?php
// Include database configuration
require_once '../config.php';
header('Content-Type: application/json');

// Get the raw POST data
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate request data
if (!isset($requestData['bookingId']) || !isset($requestData['stationId']) || 
    !isset($requestData['status']) || !isset($requestData['action']) || 
    !isset($requestData['userId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Extract data
$bookingId = $requestData['bookingId'];
$userId = $requestData['userId'];
$stationId = $requestData['stationId'];
$status = $requestData['status'];
$action = $requestData['action']; // 'decrease_slot' or 'increase_slot'

// Verify the booking belongs to this station (with more detailed error)
$verifyStmt = $pdo->prepare("
    SELECT station_id FROM bookings 
    WHERE booking_id = ? AND user_id = ?
");
$verifyStmt->execute([$bookingId, $userId]);
$actualStationId = $verifyStmt->fetchColumn();

if ($actualStationId === false) {
    echo json_encode([
        'success' => false, 
        'message' => 'Booking not found'
    ]);
    exit;
}

// Cast both to strings for comparison to avoid type issues
if ((string)$actualStationId !== (string)$stationId) {
    echo json_encode([
        'success' => false, 
        'message' => "Station mismatch. This booking is for station #$actualStationId, not #$stationId"
    ]);
    exit;
}

try {
    // First, check if the necessary columns exist in the bookings table
    $columnsExist = true;
    try {
        $checkColumnsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as column_exists 
            FROM information_schema.COLUMNS 
            WHERE 
                TABLE_SCHEMA = DATABASE() AND 
                TABLE_NAME = 'bookings' AND 
                COLUMN_NAME = 'check_in_time'
        ");
        $checkColumnsStmt->execute();
        $columnsExist = ($checkColumnsStmt->fetchColumn() > 0);
    } catch (PDOException $e) {
        // If this fails, assume columns don't exist
        $columnsExist = false;
    }

    // Start a transaction to ensure both operations succeed or fail together
    $pdo->beginTransaction();
    
    // 1. Update the booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = :status, updated_at = NOW() 
        WHERE booking_id = :booking_id AND station_id = :station_id
    ");
    
    $stmt->execute([
        ':status' => $status,
        ':booking_id' => $bookingId,
        ':station_id' => $stationId
    ]);
    
    if ($stmt->rowCount() === 0) {
        // No rows were updated - booking not found
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // 2. Update the available slots in the charging station
    if ($action === 'decrease_slot') {
        // Check if slots are available before decreasing
        $stmtCheck = $pdo->prepare("
            SELECT available_slots 
            FROM charging_stations 
            WHERE station_id = :station_id
        ");
        
        $stmtCheck->execute([':station_id' => $stationId]);
        $availableSlots = $stmtCheck->fetchColumn();
        
        if ($availableSlots <= 0) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'No available slots at this station']);
            exit;
        }
        
        // Decrease available slots by 1
        $stmtUpdate = $pdo->prepare("
            UPDATE charging_stations 
            SET available_slots = available_slots - 1 
            WHERE station_id = :station_id AND available_slots > 0
        ");
        
        // Only update check_in_time if the column exists
        if ($columnsExist) {
            try {
                $stmtCheckIn = $pdo->prepare("
                    UPDATE bookings 
                    SET check_in_time = NOW()
                    WHERE booking_id = :booking_id
                ");
                $stmtCheckIn->execute([':booking_id' => $bookingId]);
            } catch (PDOException $e) {
                // Ignore errors here as it's not critical
                error_log("Could not update check_in_time: " . $e->getMessage());
            }
        }
        
    } else {
        // Increase available slots by 1, but don't exceed total slots
        $stmtUpdate = $pdo->prepare("
            UPDATE charging_stations 
            SET available_slots = LEAST(available_slots + 1, total_slots) 
            WHERE station_id = :station_id
        ");
        
        // Only update check_out_time if the columns exist
        if ($columnsExist) {
            try {
                $stmtCheckOut = $pdo->prepare("
                    UPDATE bookings 
                    SET check_out_time = NOW()
                    WHERE booking_id = :booking_id
                ");
                $stmtCheckOut->execute([':booking_id' => $bookingId]);
            } catch (PDOException $e) {
                // Ignore errors here as it's not critical
                error_log("Could not update check_out_time: " . $e->getMessage());
            }
        }
    }
    
    $stmtUpdate->execute([':station_id' => $stationId]);
    
    if ($stmtUpdate->rowCount() === 0) {
        // No rows were updated - station not found or constraints not met
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Station not found or slot update constraint not met']);
        exit;
    }
    
    // 3. Add an entry to the booking_logs table using the correct schema
    try {
        $actionType = ($status === 'checked_in') ? 'check_in' : 'check_out';
        
        $stmt = $pdo->prepare("
            INSERT INTO booking_logs (
                booking_id, 
                user_id, 
                action_type, 
                action_time, 
                status
            )
            VALUES (
                :booking_id, 
                :user_id, 
                :action_type, 
                NOW(), 
                :status
            )
        ");
        
        $stmt->execute([
            ':booking_id' => $bookingId,
            ':user_id' => $userId,
            ':action_type' => $actionType,
            ':status' => $status
        ]);
    } catch (PDOException $logError) {
        // Log the error but continue with the transaction
        error_log("Warning: Could not log to booking_logs: " . $logError->getMessage());
    }
    
    // 4. Add notification for the user
    try {
        $statusMessages = [
            'checked_in' => 'You have successfully checked in at your charging station.',
            'completed' => 'Your charging session has been completed. Thank you for using our service!'
        ];
        
        if (isset($statusMessages[$status])) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                VALUES (:user_id, :title, :message, 'booking_status')
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':title' => "Booking Update",
                ':message' => $statusMessages[$status]
            ]);
        }
    } catch (PDOException $notifyError) {
        // Log the error but continue with the transaction
        error_log("Warning: Could not create notification: " . $notifyError->getMessage());
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Return a success response
    echo json_encode([
        'success' => true, 
        'message' => $status === 'checked_in' ? 'Check-in successful' : 'Check-out successful',
        'action' => $action
    ]);
    
} catch (PDOException $e) {
    // Roll back the transaction if anything went wrong
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the detailed error and return a more specific message
    error_log("Database error in update-booking-and-slots.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 