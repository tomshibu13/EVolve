<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user's bookings with station details
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            cs.name as station_name,
            cs.address as station_address,
            cs.image as station_image
        FROM bookings b
        JOIN charging_stations cs ON b.station_id = cs.station_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - EVolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="booking-styles.css">
    <style>
        /* Container Layout */
        .bookings-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Page Title */
        .page-title-container {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-title i {
            color: #007bff;
        }

        /* Grid Layout */
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        /* Booking Card Styles */
        .booking-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .booking-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .booking-content {
            padding: 20px;
        }

        /* Station Information */
        .station-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin: 0 0 8px 0;
        }

        .station-address {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: #666;
            font-size: 0.9rem;
        }

        /* Booking Details */
        .booking-details {
            display: grid;
            gap: 8px;
            margin: 12px 0;
        }

        .booking-details p {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: #555;
            font-size: 0.9rem;
        }

        .booking-details i {
            color: #0066FF;
            width: 20px;
        }

        /* Status Indicators */
        .booking-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin: 8px 0;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .confirmed {
            background: #d4edda;
            color: #155724;
        }

        .completed {
            background: #e3f2fd;
            color: #1976d2;
        }

        .cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Action Buttons */
        .booking-actions {
            margin-top: 15px;
        }

        .cancel-btn {
            width: 100%;
            padding: 8px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .cancel-btn:hover {
            background: #c82333;
        }

        /* Empty State */
        .no-bookings {
            text-align: center;
            padding: 50px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }

        .no-bookings i {
            font-size: 3em;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .no-bookings h2 {
            color: #343a40;
            margin-bottom: 10px;
        }

        .no-bookings p {
            color: #6c757d;
            margin-bottom: 20px;
        }

        .no-bookings .action-button {
            display: inline-block;
            padding: 10px 20px;
            background: #0066FF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .no-bookings .action-button:hover {
            background: #0052cc;
        }

        /* Messages */
        .success-message, .error-message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }

            .bookings-container {
                margin: 20px auto;
                padding: 0 15px;
            }
        }

        /* Navigation Styles */
        .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0066FF;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            text-decoration: none;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link.active {
            background: #0066FF;
            color: white;
        }

        .nav-link:hover:not(.active) {
            background: #f1f5f9;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notification-icon {
            position: relative;
            color: #1e293b;
            text-decoration: none;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #0066FF;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .user-menu {
            position: relative;
            cursor: pointer;
        }

        .username {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .username::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            min-width: 200px;
            padding: 0.5rem;
            display: none;
        }

        .user-menu:hover .dropdown-menu {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0.25rem;
        }

        .dropdown-menu a:hover {
            background: #f1f5f9;
        }

        .logout-btn {
            color: #dc2626 !important;
            border-top: 1px solid #e5e7eb;
            margin-top: 0.5rem;
            padding-top: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="bookings-container">
        <div class="page-title-container">
            <h1 class="page-title">
                <i class="fas fa-calendar-check"></i>
                My Bookings
            </h1>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <?php if (empty($bookings)): ?>
                <div class="no-bookings">
                    <i class="fas fa-calendar-times"></i>
                    <h2>No Bookings Found</h2>
                    <p>You haven't made any bookings yet.</p>
                    <a href="user_stations.php" class="action-button">Find Charging Stations</a>
                </div>
            <?php else: ?>
                <div class="booking-grid">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <?php if (!empty($booking['station_image'])): ?>
                                <img src="<?php echo htmlspecialchars($booking['station_image']); ?>" alt="<?php echo htmlspecialchars($booking['station_name']); ?>" class="booking-image">
                            <?php else: ?>
                                <img src="images/default-station.jpg" alt="Default station image" class="booking-image">
                            <?php endif; ?>
                            
                            <div class="booking-content">
                                <div class="booking-station">
                                    <h3 class="station-name"><?php echo htmlspecialchars($booking['station_name']); ?></h3>
                                    <div class="station-address">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($booking['station_address']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="booking-details">
                                    <p>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?>
                                    </p>
                                    <p>
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                    </p>
                                    <p>
                                        <i class="fas fa-hourglass-half"></i>
                                        Duration: <?php echo htmlspecialchars($booking['duration']); ?> minutes
                                    </p>
                                </div>

                                <div class="booking-status <?php echo strtolower($booking['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                </div>

                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                    <div class="booking-actions">
                                        <button onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)" class="cancel-btn">
                                            Cancel Booking
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    function cancelBooking(bookingId) {
        if (confirm('Are you sure you want to cancel this booking?')) {
            fetch('cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ booking_id: bookingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to cancel booking');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to cancel booking');
            });
        }
    }
    </script>
</body>
</html> 