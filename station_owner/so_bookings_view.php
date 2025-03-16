<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a station owner
if (!isset($_SESSION['user_id'])) {
    header("Location: ../stationlogin.php");
    exit();
}

// Validate station_id parameter
if (!isset($_GET['station_id']) || !is_numeric($_GET['station_id'])) {
    header("Location: ../station-owner-dashboard.php");
    exit();
}

$station_id = (int)$_GET['station_id'];
$bookings = [];
$station_info = null;

try {
    // First verify this station belongs to the logged-in owner
    $query = "SELECT * FROM charging_stations WHERE station_id = ? AND owner_name = ?";
    $stmt = $mysqli->prepare($query);
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    if (!$stmt->bind_param("is", $station_id, $_SESSION['owner_name'])) {
        throw new Exception("Binding parameters failed: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../station-owner-dashboard.php");
        exit();
    }
    
    $station_info = $result->fetch_assoc();
    $stmt->close();
    
    // Fetch bookings for this station
    $query = "SELECT 
                b.*,
                u.name as user_name,
                u.email as user_email,
                u.phone_number as user_phone,
                COALESCE(b.amount, 0) as payment_amount,
                COALESCE(b.payment_status, 'pending') as payment_status,
                b.razorpay_payment_id,
                b.razorpay_order_id
            FROM bookings b
            JOIN tbl_users u ON b.user_id = u.user_id
            WHERE b.station_id = ?
            ORDER BY b.booking_date DESC, b.booking_time DESC";
            
    $stmt = $mysqli->prepare($query);
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    if (!$stmt->bind_param("i", $station_id)) {
        throw new Exception("Binding parameters failed: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    $stmt->close();

} catch (Exception $e) {
    $error = "Error fetching bookings: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings - <?php echo htmlspecialchars($station_info['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .booking-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5em 1em;
        }
        .booking-details {
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-details">
            <i class='bx bx-bolt-circle'></i>
            <span class="logo_name">EVolve</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="station-owner-dashboard.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="link_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="payment_analytics.php">
                    <i class='bx bx-money'></i>
                    <span class="link_name">Payment Analytics</span>
                </a>
            </li>
            <!-- ... other sidebar items ... -->
        </ul>
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class='bx bx-calendar'></i> 
                Bookings for <?php echo htmlspecialchars($station_info['name']); ?>
            </h2>
            <a href="station-owner-dashboard.php" class="btn btn-primary">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Station Summary</h5>
                        <p class="card-text">
                            <strong>Address:</strong> <?php echo htmlspecialchars($station_info['address']); ?><br>
                            <strong>Price:</strong> $<?php echo number_format($station_info['price'], 2); ?>/kWh<br>
                            <strong>Available Slots:</strong> <?php echo $station_info['available_slots']; ?>/<?php echo $station_info['total_slots']; ?><br>
                            <strong>Status:</strong> 
                            <span class="badge <?php echo $station_info['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ucfirst($station_info['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Summary</h5>
                        <?php
                        $total_earnings = 0;
                        $pending_payments = 0;
                        $completed_payments = 0;
                        
                        foreach ($bookings as $booking) {
                            if ($booking['payment_status'] === 'completed') {
                                $total_earnings += $booking['payment_amount'];
                                $completed_payments++;
                            } elseif ($booking['payment_status'] === 'pending') {
                                $pending_payments++;
                            }
                        }
                        ?>
                        <p class="card-text">
                            <strong>Total Earnings:</strong> ₹<?php echo number_format($total_earnings, 2); ?><br>
                            <strong>Completed Payments:</strong> <?php echo $completed_payments; ?><br>
                            <strong>Pending Payments:</strong> <?php echo $pending_payments; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <?php if (empty($bookings)): ?>
                    <div class="alert alert-info">No bookings found for this station.</div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="card booking-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title">
                                            <i class='bx bx-user'></i> 
                                            <?php echo htmlspecialchars($booking['user_name']); ?>
                                        </h5>
                                        <div class="booking-details">
                                            <p class="mb-2">
                                                <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($booking['user_email']); ?><br>
                                                <i class='bx bx-phone'></i> <?php echo htmlspecialchars($booking['user_phone']); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class='bx bx-calendar'></i> 
                                                <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?><br>
                                                <i class='bx bx-time'></i> 
                                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                            </p>
                                            <?php if (isset($booking['notes']) && !empty($booking['notes'])): ?>
                                                <p class="mb-2">
                                                    <i class='bx bx-note'></i> 
                                                    <?php echo htmlspecialchars($booking['notes']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge status-badge <?php 
                                        switch($booking['status']) {
                                            case 'pending': echo 'bg-warning'; break;
                                            case 'completed': echo 'bg-success'; break;
                                            case 'cancelled': echo 'bg-danger'; break;
                                            default: echo 'bg-secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="mt-3 booking-details">
                                    <hr>
                                    <h6>Payment Details</h6>
                                    <p class="mb-2">
                                        <strong>Amount:</strong> ₹<?php echo number_format($booking['payment_amount'], 2); ?><br>
                                        <strong>Status:</strong> 
                                        <span class="badge <?php 
                                            echo $booking['payment_status'] === 'completed' ? 'bg-success' : 
                                                ($booking['payment_status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                        ?>">
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </span><br>
                                        <?php if ($booking['razorpay_payment_id']): ?>
                                            <strong>Payment ID:</strong> <?php echo htmlspecialchars($booking['razorpay_payment_id']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($booking['razorpay_order_id']): ?>
                                            <strong>Order ID:</strong> <?php echo htmlspecialchars($booking['razorpay_order_id']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-success btn-sm me-2" 
                                                onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'completed', <?php echo $station_id; ?>)">
                                            <i class='bx bx-check'></i> Mark Complete
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'cancelled', <?php echo $station_id; ?>)">
                                            <i class='bx bx-x'></i> Cancel Booking
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateBookingStatus(bookingId, status, stationId) {
            if (!confirm(`Are you sure you want to mark this booking as ${status}?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('status', status);
            formData.append('station_id', stationId);
            
            // Add loading state to buttons
            const buttons = document.querySelectorAll(`button[onclick*="${bookingId}"]`);
            buttons.forEach(button => {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            });

            fetch('./update_booking_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating booking status');
                    // Re-enable buttons on error
                    buttons.forEach(button => {
                        button.disabled = false;
                        button.innerHTML = status === 'completed' ? 
                            '<i class="bx bx-check"></i> Mark Complete' : 
                            '<i class="bx bx-x"></i> Cancel Booking';
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the booking status');
                // Re-enable buttons on error
                buttons.forEach(button => {
                    button.disabled = false;
                    button.innerHTML = status === 'completed' ? 
                        '<i class="bx bx-check"></i> Mark Complete' : 
                        '<i class="bx bx-x"></i> Cancel Booking';
                });
            });
        }
    </script>
</body>
</html>