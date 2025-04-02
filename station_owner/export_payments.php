<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a station owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['owner_name'])) {
    die('Unauthorized access');
}

// Set headers for Excel download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="payment_details_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Date',
    'Time',
    'Customer Name',
    'Customer Email',
    'Station Name',
    'Duration (mins)',
    'Amount (₹)',
    'Payment Method',
    'Transaction ID',
    'Status'
]);

try {
    // Get payment details
    $query = "SELECT 
                pd.*,
                b.booking_date,
                b.booking_time,
                b.duration,
                cs.name as station_name,
                u.name as user_name,
                u.email as user_email
            FROM payment_details pd
            JOIN bookings b ON pd.booking_id = b.booking_id
            JOIN charging_stations cs ON pd.station_id = cs.station_id
            JOIN tbl_users u ON pd.user_id = u.user_id
            WHERE cs.owner_name = ?
            ORDER BY pd.payment_date DESC";
            
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['owner_name']]);
    
    // Add data rows
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            date('Y-m-d', strtotime($row['booking_date'])),
            date('H:i', strtotime($row['booking_time'])),
            $row['user_name'],
            $row['user_email'],
            $row['station_name'],
            $row['duration'],
            number_format($row['amount'], 2),
            $row['payment_method'],
            $row['transaction_id'],
            ucfirst($row['status'])
        ]);
    }
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

fclose($output);
exit(); 