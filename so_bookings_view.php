<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

// Check if station ID is provided
if (!isset($_GET['id'])) {
    header("Location: station-owner-dashboard.php");
    exit();
}

$station_id = $_GET['id'];
$error = null;
$station = null;
$bookings = [];

try {
    // Add this for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // First verify if the station belongs to this owner
    $stmt = $pdo->prepare("
        SELECT * FROM charging_stations 
        WHERE station_id = ? AND owner_name = ?
    ");
    $stmt->execute([$station_id, $_SESSION['owner_name']]);
    $station = $stmt->fetch();

    if (!$station) {
        header("Location: station-owner-dashboard.php");
        exit();
    }

    // Fetch bookings for this station with user details from tbl_users
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            u.name as user_name,
            u.email as user_email,
            u.phone_number as user_phone
        FROM bookings b
        JOIN tbl_users u ON b.user_id = u.user_id
        WHERE b.station_id = ?
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");
    $stmt->execute([$station_id]);
    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    // Make the error message more specific for debugging
    $error = "Database error: " . $e->getMessage();
    error_log($error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Bookings - <?php echo htmlspecialchars($station['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* Reuse the same styles from station-owner-dashboard.php */
        .booking-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            border: none;
            margin-bottom: 20px;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class='bx bx-calendar'></i> 
                    Bookings for <?php echo htmlspecialchars($station['name']); ?>
                </h2>
                <a href="station-owner-dashboard.php" class="btn btn-primary">
                    <i class='bx bx-arrow-back'></i> Back to Dashboard
                </a>
            </div>

            <!-- Station Details Card -->
            <div class="card booking-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Station Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($station['address']); ?></p>
                            <p><strong>Price:</strong> ₹<?php echo number_format($station['price'], 2); ?>/kWh</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Available Slots:</strong> <?php echo $station['available_slots']; ?>/<?php echo $station['total_slots']; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $station['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($station['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings List -->
            <?php if (empty($bookings)): ?>
                <div class="alert alert-info">No bookings found for this station.</div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="card booking-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="card-title">Booking #<?php echo $booking['booking_id']; ?></h5>
                                    <p class="mb-1">
                                        <i class='bx bx-user'></i> 
                                        <strong>User:</strong> <?php echo htmlspecialchars($booking['user_name']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class='bx bx-envelope'></i> 
                                        <strong>Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1">
                                        <i class='bx bx-calendar'></i> 
                                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class='bx bx-time'></i> 
                                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class='bx bx-battery'></i> 
                                        <strong>Duration:</strong> <?php echo $booking['duration']; ?> hours
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <span class="status-badge badge bg-<?php 
                                        echo match($booking['status']) {
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'pending' => 'warning',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <div class="mt-3">
                                            <button class="btn btn-success btn-sm me-2" 
                                                    onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'completed')">
                                                <i class='bx bx-check'></i> Complete
                                            </button>
                                            <button class="btn btn-danger btn-sm"
                                                    onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'cancelled')">
                                                <i class='bx bx-x'></i> Cancel
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function updateBookingStatus(bookingId, status) {
            if (!confirm(`Are you sure you want to mark this booking as ${status}?`)) {
                return;
            }

            try {
                const response = await fetch('update_booking_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        status: status
                    })
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Error updating booking status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating booking status');
            }
        }
    </script>
</body>
</html>