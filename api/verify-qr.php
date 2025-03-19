<?php
// Include database configuration
require_once '../config.php';
session_start();

// Set header to return JSON
header('Content-Type: application/json');

// Get the raw POST data
$requestData = json_decode(file_get_contents('php://input'), true);

if (!isset($requestData['qrData'])) {
    echo json_encode(['success' => false, 'message' => 'No QR data provided']);
    exit;
}

// Get the current station ID if provided
$currentStationId = isset($requestData['stationId']) ? intval($requestData['stationId']) : null;

$qrData = $requestData['qrData'];
$response = ['success' => false, 'message' => 'Invalid QR code'];

try {
    // Try to decode as JSON if it's a booking QR code
    $decodedData = json_decode($qrData, true);
    
    if (json_last_error() === JSON_ERROR_NONE && isset($decodedData['booking_id'])) {
        // This is a booking QR code
        $bookingId = $decodedData['booking_id'];
        $userId = $decodedData['user_id'];
        $stationId = $decodedData['station_id'];
        
        // If we know the current station, verify this booking is for this station
        if ($currentStationId !== null && $currentStationId != $stationId) {
            echo json_encode([
                'success' => false,
                'message' => 'This booking is for a different station'
            ]);
            exit;
        }
        
        // Query the database to verify the booking
        $stmt = $pdo->prepare("
            SELECT 
                b.booking_id, 
                b.user_id, 
                b.station_id, 
                b.booking_date, 
                b.booking_time, 
                b.status,
                cs.name as station_name
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            WHERE b.booking_id = ? AND b.user_id = ? AND b.station_id = ?
        ");
        
        $stmt->execute([$bookingId, $userId, $stationId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($booking) {
            // Return the booking details
            $response = [
                'success' => true,
                'booking' => $booking,
                'message' => 'Valid booking found'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No matching booking found'
            ];
        }
    } else {
        // Check if it's a station QR code (URL format)
        if (strpos($qrData, 'scan_station.php') !== false) {
            // Extract station_id from the URL
            $parts = parse_url($qrData);
            parse_str($parts['query'], $query);
            
            if (isset($query['station_id'])) {
                $stationId = $query['station_id'];
                
                // Query the database to verify the station
                $stmt = $pdo->prepare("
                    SELECT station_id, name, address, status
                    FROM charging_stations
                    WHERE station_id = ? AND status = 'active'
                ");
                
                $stmt->execute([$stationId]);
                $station = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($station) {
                    // Return the station details
                    $response = [
                        'success' => true,
                        'station' => $station,
                        'message' => 'Valid station found'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Station not found or not active'
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    // Log the error and return a generic message
    error_log("QR verification error: " . $e->getMessage());
    $response = [
        'success' => false,
        'message' => 'Error processing QR code'
    ];
}

echo json_encode($response);
exit;
?> 