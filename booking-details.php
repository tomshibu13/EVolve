<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get booking ID from URL
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookingId <= 0) {
    header('Location: my-bookings.php');
    exit();
}

// Get booking details
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        cs.name as station_name,
        cs.address as station_address
    FROM bookings b
    JOIN charging_stations cs ON b.station_id = cs.station_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch();

// If booking not found or doesn't belong to current user
if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

// Generate QR code data
$qrCodeData = json_encode([
    'booking_id' => $booking['booking_id'],
    'user_id' => $_SESSION['user_id']
]);

// Create QR code URL
$qrCodeUrl = 'qr-code.php?data=' . urlencode($qrCodeData) . '&size=300';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - EVolve</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fc;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .booking-details {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .booking-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .booking-status {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 10px;
        }
        .status-pending { background-color: #f6c23e; color: #fff; }
        .status-confirmed { background-color: #4e73df; color: #fff; }
        .status-in_progress { background-color: #36b9cc; color: #fff; }
        .status-completed { background-color: #1cc88a; color: #fff; }
        .status-cancelled { background-color: #e74a3b; color: #fff; }
        .qr-container {
            text-align: center;
            margin: 30px 0;
        }
        .qr-code {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            display: inline-block;
            background-color: white;
        }
        .action-buttons {
            margin-top: 20px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            font-weight: bold;
        }
        .btn-secondary {
            background-color: #858796;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="booking-details">
            <div class="booking-header">
                <h1>Booking #<?php echo $booking['booking_id']; ?></h1>
                <div class="booking-status status-<?php echo $booking['status']; ?>">
                    <?php 
                    $statusLabels = [
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled'
                    ];
                    echo $statusLabels[$booking['status']] ?? ucfirst($booking['status']);
                    ?>
                </div>
            </div>
            
            <h2>Booking Details</h2>
            <p><strong>Station:</strong> <?php echo htmlspecialchars($booking['station_name']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($booking['station_address']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['booking_time'])); ?></p>
            <p><strong>Duration:</strong> <?php echo $booking['duration']; ?> hour(s)</p>
            <p><strong>Amount Paid:</strong> ₹<?php echo number_format($booking['amount'], 2); ?></p>
            <p><strong>Payment Status:</strong> <?php echo ucfirst($booking['payment_status']); ?></p>
            <p><strong>Booking Created:</strong> <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></p>
            
            <?php if ($booking['status'] == 'confirmed' || $booking['status'] == 'in_progress'): ?>
                <div class="qr-container">
                    <h2>Check-In/Check-Out QR Code</h2>
                    <p>Scan this QR code at the station to <?php echo $booking['status'] == 'confirmed' ? 'start' : 'end'; ?> your charging session</p>
                    <div class="qr-code">
                        <img src="<?php echo $qrCodeUrl; ?>" alt="Booking QR Code" width="300" height="300">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="my-bookings.php" class="btn btn-secondary">Back to My Bookings</a>
                <?php if ($booking['status'] == 'confirmed'): ?>
                    <a href="scan_station.php" class="btn">Scan at Station</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 