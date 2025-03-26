<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

$success_message = null;
$error_message = null;
$stations = [];
$bookings = [];
$filter_status = $_GET['status'] ?? 'all';
$filter_station = $_GET['station'] ?? 'all';
$search_term = $_GET['search'] ?? '';

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
        header("Location: stationlogin.php");
        exit();
    }
    
    $_SESSION['owner_name'] = $result['owner_name'];
    
    // Get all stations owned by this owner
    $stmt = $pdo->prepare("
        SELECT * FROM charging_stations
        WHERE owner_name = ?
    ");
    
    $stmt->execute([$_SESSION['owner_name']]);
    $stations = $stmt->fetchAll();
    
    // Build query for bookings based on filters
    $query = "
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
            u.phone_number as user_phone,
            cs.name as station_name
        FROM bookings b
        JOIN tbl_users u ON b.user_id = u.user_id
        JOIN charging_stations cs ON b.station_id = cs.station_id
        WHERE cs.owner_name = ?
    ";
    
    $params = [$_SESSION['owner_name']];
    
    // Add status filter if not 'all'
    if ($filter_status !== 'all') {
        $query .= " AND b.status = ?";
        $params[] = $filter_status;
    }
    
    // Add station filter if not 'all'
    if ($filter_station !== 'all') {
        $query .= " AND b.station_id = ?";
        $params[] = $filter_station;
    }
    
    // Add search filter if provided
    if (!empty($search_term)) {
        $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Order by date and time
    $query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Handle booking status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
        $booking_id = $_POST['booking_id'];
        $new_status = $_POST['new_status'];
        $station_id = $_POST['station_id'];
        
        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = ? 
            WHERE booking_id = ? AND station_id IN (
                SELECT station_id FROM charging_stations WHERE owner_name = ?
            )
        ");
        
        $stmt->execute([$new_status, $booking_id, $_SESSION['owner_name']]);
        
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
        
        // Redirect to refresh page and avoid form resubmission
        header("Location: manage-bookings.php?status=" . urlencode($filter_status) . "&station=" . urlencode($filter_station) . "&search=" . urlencode($search_term) . "&success=1");
        exit();
    }
    
    // Check for success message from redirect
    if (isset($_GET['success']) && $_GET['success'] == 1) {
        $success_message = "Booking status updated successfully!";
    }
    
} catch (PDOException $e) {
    error_log("Database error in manage-bookings.php: " . $e->getMessage() . " SQL: " . (isset($stmt) ? $stmt->queryString : "No query"));
    $error_message = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in manage-bookings.php: " . $e->getMessage());
    $error_message = "An error occurred while processing your request. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - EV Station</title>
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
        
        /* Filter Form */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-form .form-group {
            flex: 1;
            min-width: 200px;
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
            
            .filter-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Sidebar Container -->
        <div class="sidebar-container" id="sidebar">
            <div class="sidebar-header">
                <a href="station-owner-dashboard.php" class="sidebar-brand">
                    <i class='bx bx-car'></i>
                    <span>EV Station</span>
                </a>
            </div>
            
            <div class="sidebar-nav">
                <a href="station-owner-dashboard.php" class="sidebar-link">
                    <i class='bx bx-home'></i>
                    <span>Dashboard</span>
                </a>
                <a href="so_add_station.php" class="sidebar-link">
                    <i class='bx bx-plus-circle'></i>
                    <span>Add Station</span>
                </a>
                <a href="manage-bookings.php" class="sidebar-link active">
                    <i class='bx bx-calendar'></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="station_owner/payment_analytics.php" class="sidebar-link">
                    <i class='bx bx-money'></i>
                    <span>Payment Analytics</span>
                </a>
                <a href="view-enquiries.php" class="sidebar-link">
                    <i class='bx bx-message-detail'></i>
                    <span>Enquiries</span>
                </a>
                <a href="view-reviews.php" class="sidebar-link">
                    <i class='bx bx-star'></i>
                    <span>Reviews</span>
                </a>
                <a href="station_owner/so_profile.php" class="sidebar-link">
                    <i class='bx bx-user'></i>
                    <span>Profile</span>
                </a>
                <a href="reports.php" class="sidebar-link">
                    <i class='bx bx-line-chart'></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="sidebar-link">
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
                        <li><a class="dropdown-item" href="station_owner/so_profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </header>

            <!-- Main Container -->
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-calendar-check'></i> Manage Bookings</h2>
                    <a href="station-owner-dashboard.php" class="btn btn-outline-primary">
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

                <div class="section-card">
                    <h4><i class='bx bx-filter-alt'></i> Filter Bookings</h4>
                    <form action="" method="GET" class="filter-form">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="in_progress" <?php echo $filter_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="station" class="form-label">Station</label>
                            <select name="station" id="station" class="form-select">
                                <option value="all">All Stations</option>
                                <?php foreach ($stations as $station): ?>
                                    <option value="<?php echo $station['station_id']; ?>" <?php echo $filter_station == $station['station_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($station['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="search" class="form-label">Search User</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Name, Email or Phone" value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                        
                        <div class="form-group d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-search'></i> Apply Filters
                            </button>
                            
                            <a href="manage-bookings.php" class="btn btn-outline-secondary ms-2">
                                <i class='bx bx-reset'></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <div class="section-card">
                    <h4><i class='bx bx-list-ul'></i> Booking List</h4>
                    
                    <?php if (!empty($bookings)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Station</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($booking['user_email']); ?></small><br>
                                                <small><?php echo htmlspecialchars($booking['user_phone']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['station_name']); ?></td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                                                <small><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></small><br>
                                                <small>Duration: <?php echo $booking['duration']; ?> min</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $booking['status'] === 'pending' ? 'warning' : 
                                                        ($booking['status'] === 'confirmed' ? 'info' : 
                                                        ($booking['status'] === 'in_progress' ? 'primary' : 
                                                        ($booking['status'] === 'completed' ? 'success' : 'secondary'))); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateModal<?php echo $booking['booking_id']; ?>">
                                                    <i class='bx bx-edit'></i> Update
                                                </button>
                                                
                                                <a href="station_owner/update_booking_status.php?id=<?php echo $booking['station_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class='bx bx-detail'></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal for Status Update -->
                                        <div class="modal fade" id="updateModal<?php echo $booking['booking_id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Booking Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                            <input type="hidden" name="station_id" value="<?php echo $booking['station_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="current_status" class="form-label">Current Status</label>
                                                                <input type="text" class="form-control" id="current_status" 
                                                                       value="<?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>" readonly>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="new_status" class="form-label">New Status</label>
                                                                <select class="form-select" id="new_status" name="new_status" required>
                                                                    <option value="">Select Status</option>
                                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                                        <option value="confirmed">Confirm</option>
                                                                        <option value="cancelled">Cancel</option>
                                                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                                                        <option value="in_progress">Check In</option>
                                                                        <option value="cancelled">Cancel</option>
                                                                    <?php elseif ($booking['status'] === 'in_progress'): ?>
                                                                        <option value="completed">Complete</option>
                                                                    <?php endif; ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="alert alert-info">
                                                                <i class='bx bx-info-circle'></i> 
                                                                <small>
                                                                    Updating the status will automatically adjust the available slots at your station.
                                                                    Check-in will decrease slots, while completion or cancellation will increase slots.
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="update_booking" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class='bx bx-info-circle'></i> No bookings found matching your criteria.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <h4><i class='bx bx-qr-scan'></i> Quick QR Scanner</h4>
                    <p>Scan customer QR codes for quick check-in and check-out at any of your stations.</p>
                    <button id="startScanBtn" class="btn btn-primary">
                        <i class='bx bx-qr'></i> Open QR Scanner
                    </button>
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
                // Open scan station page in a new window
                window.open('scan_station.php', 'qrScannerWindow', 'width=800,height=600');
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>