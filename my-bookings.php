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
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Container Layout */
        .bookings-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2em;
            color: #333;
        }

        /* Grid Layout */
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* Booking Card Styles */
        .booking-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
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
            margin-bottom: 10px;
        }

        .booking-info {
            margin: 15px 0;
            color: #666;
        }

        .booking-info p {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
        }

        .booking-info i {
            color: #2196F3;
            width: 20px;
        }

        /* Status Indicators */
        .booking-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            margin-top: 10px;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-completed {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        /* Action Buttons */
        .booking-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .cancel-btn {
            background: #ff5252;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .cancel-btn:hover {
            background: #d32f2f;
            transform: scale(1.05);
        }

        .view-btn {
            background: #2196F3;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .view-btn:hover {
            background: #1976D2;
            transform: scale(1.05);
        }

        /* Empty State */
        .no-bookings {
            text-align: center;
            padding: 50px;
            background: #f5f5f5;
            border-radius: 12px;
            color: #666;
            border: 1px solid #ddd;
        }

        .no-bookings i {
            font-size: 4em;
            color: #ccc;
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Back to Home Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #2196F3; /* Primary color */
            color: white;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .back-btn:hover {
            background-color: #1976D2; /* Darker shade on hover */
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="bookings-container">
        <div class="page-header">
            <h1 class="page-title">My Bookings</h1>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
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
                </div>
            <?php else: ?>
                <!-- Add debugging section -->
                <?php if (isset($_GET['debug'])): ?>
                    <div style="margin-bottom: 20px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                        <h3>Debug Information:</h3>
                        <pre><?php 
                            foreach ($bookings as $booking) {
                                echo "Booking ID: " . $booking['booking_id'] . "\n";
                                echo "Raw Date: " . $booking['booking_date'] . "\n";
                                echo "Raw Time: " . $booking['booking_time'] . "\n";
                                echo "Combined: " . $booking['booking_date'] . " " . $booking['booking_time'] . "\n";
                                echo "------------------------\n";
                            }
                        ?></pre>
                    </div>
                <?php endif; ?>
                <div class="booking-grid">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                           
                            
                            <div class="booking-content">
                                <h2 class="station-name"><?php echo htmlspecialchars($booking['station_name']); ?></h2>
                                
                                <div class="booking-info">
                                    <p>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($booking['station_address']); ?>
                                    </p>
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

                                <div class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                </div>

                                <div class="booking-actions">
                                    <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                        <form method="POST" action="cancel_booking.php" style="flex: 1;" 
                                              onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" class="action-button cancel-btn" style="width: 100%;">
                                                Cancel Booking
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                  
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 