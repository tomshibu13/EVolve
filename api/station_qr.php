<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

// Check for authentication if needed
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Authentication required']);
//     exit();
// }

// Check if station_id is provided
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid station ID']);
    exit();
}

$stationId = intval($_GET['id']);

// Check if station exists
try {
    $stmt = $pdo->prepare("SELECT station_id, name, address, ST_X(location) as lng, ST_Y(location) as lat FROM charging_stations WHERE station_id = ?");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$station) {
        http_response_code(404);
        echo json_encode(['error' => 'Station not found']);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit();
}

// Generate a URL for this station that points to the scan page
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";

// IMPORTANT: Use the consistent scan page filename
$scanUrl = $baseUrl . "/scan-qr.php?station_id=" . $stationId;

// Return station data including the QR code URL
echo json_encode([
    'success' => true,
    'station' => [
        'id' => $stationId,
        'name' => $station['name'],
        'address' => $station['address'],
        'location' => [
            'lat' => floatval($station['lat']),
            'lng' => floatval($station['lng'])
        ],
        'qr_data' => $scanUrl,
        'qr_image_url' => $baseUrl . "/view-qr.php?station_id=" . $stationId . "&size=300"
    ]
]);
?> 