<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../stationlogin.php");
    exit();
}

// Check if station id is provided
if (!isset($_GET['id'])) {
    header("Location: ../station-owner-dashboard.php");
    exit();
}

$station_id = $_GET['id'];
$success_message = null;
$error_message = null;
$station = null;
$bookings = [];

try {
    // First verify if the user is a station owner
    $stmt = $pdo->prepare("
        SELECT sor.owner_name, sor.status 
        FROM station_owner_requests sor 
        WHERE sor.user_id = ? AND sor.status = 'approved'
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    if (!$result) {
        header("Location: ../stationlogin.php");
        exit();
    }
    
    $_SESSION['owner_name'] = $result['owner_name'];
    
    // Check if the station belongs to this owner
    $stmt = $pdo->prepare("
        SELECT * FROM charging_stations
        WHERE station_id = ? AND owner_name = ?
    ");
    
    $stmt->execute([$station_id, $_SESSION['owner_name']]);
    $station = $stmt->fetch();
    
    if (!$station) {
        header("Location: ../station-owner-dashboard.php");
        exit();
    }
    
    // Get active bookings for this station
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_id,
            b.user_id,
            b.station_id,
            b.booking_date,
            b.booking_time,
            b.duration,
            b.status,
            b.created_at,
            u.name as user_name,
            u.email as user_email,
            u.phone_number as user_phone
        FROM bookings b
        JOIN tbl_users u ON b.user_id = u.user_id
        WHERE b.station_id = ? AND b.status IN ('pending', 'confirmed', 'in_progress')
        ORDER BY b.booking_date, b.booking_time
    ");
    
    $stmt->execute([$station_id]);
    $bookings = $stmt->fetchAll();
    
    // Handle form submission for station updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_station'])) {
        $name = $_POST['name'] ?? $station['name'];
        $address = $_POST['address'] ?? $station['address'];
        $price = $_POST['price'] ?? $station['price'];
        $charger_types = $_POST['charger_types'] ?? $station['charger_types'];
        $total_slots = $_POST['total_slots'] ?? $station['total_slots'];
        $available_slots = $_POST['available_slots'] ?? $station['available_slots'];
        $status = $_POST['status'] ?? $station['status'];
        
        // Update station details
        $stmt = $pdo->prepare("
            UPDATE charging_stations
            SET name = ?, address = ?, price = ?, 
                charger_types = ?, total_slots = ?, 
                available_slots = ?, status = ?, updated_at = NOW()
            WHERE station_id = ? AND owner_name = ?
        ");
        
        $stmt->execute([
            $name, $address, $price, $charger_types, 
            $total_slots, $available_slots, $status, 
            $station_id, $_SESSION['owner_name']
        ]);
        
        $success_message = "Station details updated successfully!";
        
        // Refresh station data
        $stmt = $pdo->prepare("SELECT * FROM charging_stations WHERE station_id = ?");
        $stmt->execute([$station_id]);
        $station = $stmt->fetch();
    }
    
    // Handle booking status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
        $booking_id = $_POST['booking_id'];
        $new_status = $_POST['new_status'];
        
        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = ? 
            WHERE booking_id = ? AND station_id = ?
        ");
        
        $stmt->execute([$new_status, $booking_id, $station_id]);
        
        // Check if booking_logs table exists before inserting
        try {
            $check_table = $pdo->query("SHOW TABLES LIKE 'booking_logs'");
            $table_exists = $check_table->rowCount() > 0;
            
            if ($table_exists) {
                // Record in booking logs
                $action_type = ($new_status === 'in_progress') ? 'check_in' : 
                              (($new_status === 'completed') ? 'check_out' : 'status_update');
                
                $stmt = $pdo->prepare("
                    INSERT INTO booking_logs (booking_id, user_id, action_type, status, action_time)
                    SELECT booking_id, user_id, ?, ?, NOW()
                    FROM bookings
                    WHERE booking_id = ?
                ");
                
                $stmt->execute([$action_type, $new_status, $booking_id]);
            }
        } catch (PDOException $logError) {
            // If there's an error with the logs, continue anyway but log the error
            error_log("Error with booking logs: " . $logError->getMessage());
            // We don't want to stop the main process if just the logging fails
        }
        
        // Update available slots if necessary
        if ($new_status === 'in_progress') {
            // Reduce available slots by 1
            $stmt = $pdo->prepare("
                UPDATE charging_stations
                SET available_slots = GREATEST(available_slots - 1, 0)
                WHERE station_id = ?
            ");
            $stmt->execute([$station_id]);
        } else if ($new_status === 'completed' || $new_status === 'cancelled') {
            // Increase available slots by 1, but don't exceed total_slots
            $stmt = $pdo->prepare("
                UPDATE charging_stations
                SET available_slots = LEAST(available_slots + 1, total_slots)
                WHERE station_id = ?
            ");
            $stmt->execute([$station_id]);
        }
        
        $success_message = "Booking status updated successfully!";
        
        // Refresh booking data
        $stmt = $pdo->prepare("
            SELECT 
                b.booking_id,
                b.user_id,
                b.station_id,
                b.booking_date,
                b.booking_time,
                b.duration,
                b.status,
                b.created_at,
                u.name as user_name,
                u.email as user_email,
                u.phone_number as user_phone
            FROM bookings b
            JOIN tbl_users u ON b.user_id = u.user_id
            WHERE b.station_id = ? AND b.status IN ('pending', 'confirmed', 'in_progress')
            ORDER BY b.booking_date, b.booking_time
        ");
        
        $stmt->execute([$station_id]);
        $bookings = $stmt->fetchAll();
        
        // Refresh station data
        $stmt = $pdo->prepare("SELECT * FROM charging_stations WHERE station_id = ?");
        $stmt->execute([$station_id]);
        $station = $stmt->fetch();
    }
    
} catch (PDOException $e) {
    error_log("Database error in update_booking_status.php: " . $e->getMessage() . " SQL: " . (isset($stmt) ? $stmt->queryString : "No query"));
    $error_message = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in update_booking_status.php: " . $e->getMessage());
    $error_message = "An error occurred while processing your request. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Station & Bookings - EV Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --dark-color: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
        }
        
        /* Page Container */
        .page-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar-container {
            width: 250px;
            background: linear-gradient(180deg, #4e73df 0%, #224abe 100%);
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand:hover {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar-link {
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: #f8f9fc;
            min-height: 100vh;
        }
        
        /* Header */
        .main-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: left;
            margin-left: 15px;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
        }

        .user-role {
            font-size: 0.85rem;
            color: #6c757d;
        }

        #sidebarToggle {
            padding: 8px;
            border-radius: 8px;
            color: #4e73df;
            transition: all 0.3s ease;
        }

        #sidebarToggle:hover {
            background-color: #f8f9fa;
        }

        /* Card Styles */
        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .section-card h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e0e6ed;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            border: none;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
            border: none;
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            border: none;
            color: #2e2e2e;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74a3b 0%, #b02a1b 100%);
            border: none;
        }
        
        /* Table Styles */
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e0e6ed;
            color: #2c3e50;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e0e6ed;
        }
        
        /* Badge Styles */
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar-container {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar-container.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
            
            .container-fluid {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Sidebar Container -->
        <div class="sidebar-container" id="sidebar">
            <div class="sidebar-header">
                <a href="../station-owner-dashboard.php" class="sidebar-brand">
                    <i class='bx bx-car'></i>
                    <span>EV Station</span>
                </a>
            </div>
            
            <div class="sidebar-nav">
                <a href="../station-owner-dashboard.php" class="sidebar-link">
                    <i class='bx bx-home'></i>
                    <span>Dashboard</span>
                </a>
                <a href="../so_add_station.php" class="sidebar-link">
                    <i class='bx bx-plus-circle'></i>
                    <span>Add Station</span>
                </a>
                <a href="../manage-bookings.php" class="sidebar-link active">
                    <i class='bx bx-calendar'></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="payment_analytics.php" class="sidebar-link">
                    <i class='bx bx-money'></i>
                    <span>Payment Analytics</span>
                </a>
                <a href="so_profile.php" class="sidebar-link">
                    <i class='bx bx-user'></i>
                    <span>Profile</span>
                </a>
                <a href="../reports.php" class="sidebar-link">
                    <i class='bx bx-line-chart'></i>
                    <span>Reports</span>
                </a>
                <a href="../settings.php" class="sidebar-link">
                    <i class='bx bx-cog'></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="btn btn-link" id="sidebarToggle">
                        <i class='bx bx-menu'></i>
                    </button>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['owner_name']); ?></div>
                            <div class="user-role">Station Owner</div>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-link" type="button" id="userMenuButton" data-bs-toggle="dropdown">
                        <i class='bx bx-user-circle fs-4'></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                        <li><a class="dropdown-item" href="so_profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="../settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </header>

            <!-- Main Container -->
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-edit'></i> Update Station & Bookings</h2>
                    <a href="../station-owner-dashboard.php" class="btn btn-outline-primary">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Station Details Update Form -->
                    <div class="col-lg-6">
                        <div class="section-card">
                            <h4><i class='bx bx-station'></i> Station Details</h4>
                            <?php if ($station): ?>
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Station Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($station['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($station['address']); ?></textarea>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="price" class="form-label">Price (₹/kWh)</label>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   value="<?php echo htmlspecialchars($station['price']); ?>" min="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="charger_types" class="form-label">Charger Types</label>
                                            <input type="text" class="form-control" id="charger_types" name="charger_types" 
                                                   value="<?php echo htmlspecialchars($station['charger_types']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="total_slots" class="form-label">Total Slots</label>
                                            <input type="number" class="form-control" id="total_slots" name="total_slots" 
                                                   value="<?php echo htmlspecialchars($station['total_slots']); ?>" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="available_slots" class="form-label">Available Slots</label>
                                            <input type="number" class="form-control" id="available_slots" name="available_slots" 
                                                   value="<?php echo htmlspecialchars($station['available_slots']); ?>" min="0" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo $station['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $station['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="maintenance" <?php echo $station['status'] === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="update_station" class="btn btn-primary">
                                        <i class='bx bx-save'></i> Update Station
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class='bx bx-error'></i> Station not found or you don't have permission to edit it.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Active Bookings Management -->
                    <div class="col-lg-6">
                        <div class="section-card">
                            <h4><i class='bx bx-calendar-check'></i> Manage Active Bookings</h4>
                            <?php if (!empty($bookings)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Date & Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                                        <small><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                                                        <small><?php echo date('h:i A', strtotime($booking['booking_time'])); ?> (<?php echo $booking['duration']; ?> min)</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $booking['status'] === 'pending' ? 'warning' : 
                                                                ($booking['status'] === 'confirmed' ? 'info' : 
                                                                ($booking['status'] === 'in_progress' ? 'primary' : 'secondary')); 
                                                        ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class='bx bx-info-circle'></i> No active bookings found for this station.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quick QR Scanner Card -->
                        <div class="section-card">
                            <h4><i class='bx bx-qr-scan'></i> Quick QR Scanner</h4>
                            <p>Scan customer QR codes for quick check-in and check-out.</p>
                            <button id="startScanBtn" class="btn btn-primary">
                                <i class='bx bx-qr'></i> Open QR Scanner
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.2.1/dist/html5-qrcode.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', () => {
                    document.getElementById('sidebar').classList.toggle('active');
                });
            }
            
            // Set up QR scanner button
            document.getElementById('startScanBtn').addEventListener('click', function() {
                // Open scan_station.php in a new window with the station ID as a parameter
                window.open('../scan_station.php?station_id=<?php echo $station_id; ?>', 'qrScannerWindow', 'width=800,height=600');
            });
        });
    </script>
</body>
</html>
