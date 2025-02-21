<?php
session_start();
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

// Initialize variables
$station = null;
$error_message = '';
$success_message = '';

// Check if station ID is provided
if (!isset($_POST['id']) && !isset($_GET['id'])) {
    header('Location: user_stations.php');
    exit();
}

$station_id = $_POST['id'] ?? $_GET['id'];

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Fetch station details
    $query = "
        SELECT 
            cs.*,
            u.name as operator_name,
            ST_X(cs.location) as longitude,
            ST_Y(cs.location) as latitude
        FROM charging_stations cs 
        LEFT JOIN tbl_users u ON cs.operator_id = u.user_id 
        WHERE cs.station_id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $station_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $station = mysqli_fetch_assoc($result);

    // Handle booking submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Add your booking logic here
        $user_id = $_SESSION['user_id'] ?? null; // Make sure user is logged in
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $duration = $_POST['duration'];
        
        if (!$user_id) {
            throw new Exception("Please log in to book a station");
        }

        // Insert booking into database
        $booking_query = "
            INSERT INTO bookings (user_id, station_id, booking_date, booking_time, duration, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ";
        
        $booking_stmt = mysqli_prepare($conn, $booking_query);
        mysqli_stmt_bind_param($booking_stmt, "iissi", $user_id, $station_id, $booking_date, $booking_time, $duration);
        
        if (mysqli_stmt_execute($booking_stmt)) {
            $success_message = "Booking successful! We'll notify you once it's confirmed.";
        } else {
            throw new Exception("Failed to create booking");
        }
    }

} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Station - EVolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse your existing CSS variables and basic styles */
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --secondary-color: #2C3E50;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .booking-form {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .station-details {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Book Charging Station</h1>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($station): ?>
            <div class="station-details">
                <h2><?php echo htmlspecialchars($station['name']); ?></h2>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($station['address']); ?></p>
                <p><i class="fas fa-user"></i> Operated by <?php echo htmlspecialchars($station['operator_name']); ?></p>
                <p><i class="fas fa-dollar-sign"></i> ₹<?php echo number_format($station['price'], 2); ?> per kWh</p>
            </div>

            <form class="booking-form" method="POST">
                <div class="form-group">
                    <label for="booking_date">Date</label>
                    <input type="date" id="booking_date" name="booking_date" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="booking_time">Time</label>
                    <input type="time" id="booking_time" name="booking_time" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (minutes)</label>
                    <select id="duration" name="duration" class="form-control" required>
                        <option value="30">30 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-bolt"></i> Confirm Booking
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Add any necessary JavaScript for form validation or dynamic updates
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum time based on current time if date is today
            const dateInput = document.getElementById('booking_date');
            const timeInput = document.getElementById('booking_time');

            dateInput.addEventListener('change', function() {
                if (this.value === new Date().toISOString().split('T')[0]) {
                    const now = new Date();
                    const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
                    timeInput.min = currentTime;
                } else {
                    timeInput.min = '';
                }
            });
        });
    </script>
</body>
</html> 