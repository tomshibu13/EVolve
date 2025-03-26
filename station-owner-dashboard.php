<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

// Initialize variables
$stations = [];
$error = null;

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
    
    // Now fetch the stations with user booking details
    $stmt = $pdo->prepare("
        SELECT 
            s.station_id,
            s.name,
            s.address,
            s.owner_name,
            s.price,
            s.charger_types,
            s.total_slots,
            s.available_slots,
            s.status,
            s.image, 
            s.created_at,
            s.updated_at,
            COUNT(DISTINCT b.booking_id) as total_bookings,
            SUM(CASE WHEN DATE(b.booking_date) = CURDATE() THEN 1 ELSE 0 END) as today_bookings,
            SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            (
                SELECT COUNT(*) FROM booking_logs bl1
                JOIN bookings b1 ON bl1.booking_id = b1.booking_id
                WHERE bl1.action_type = 'check_in' 
                AND b1.station_id = s.station_id
                AND NOT EXISTS (
                    SELECT 1 FROM booking_logs bl2 
                    WHERE bl2.booking_id = bl1.booking_id 
                    AND bl2.action_type = 'check_out'
                    AND bl2.action_time > bl1.action_time
                )
            ) as active_sessions,
            GROUP_CONCAT(
                DISTINCT 
                CONCAT(u.name, '|', u.email, '|', 
                    (SELECT COUNT(*) FROM bookings b2 WHERE b2.user_id = u.user_id AND b2.station_id = s.station_id)
                ) 
                SEPARATOR ';;'
            ) as user_details
        FROM charging_stations s
        LEFT JOIN bookings b ON s.station_id = b.station_id
        LEFT JOIN tbl_users u ON b.user_id = u.user_id
        WHERE s.owner_name = ?
        GROUP BY 
            s.station_id,
            s.name,
            s.address,
            s.owner_name,
            s.price,
            s.charger_types,
            s.total_slots,
            s.available_slots,
            s.status,
            s.image,
            s.created_at,
            s.updated_at
    ");
    
    $stmt->execute([$_SESSION['owner_name']]);
    $stations = $stmt->fetchAll();

    // Calculate dashboard metrics
    $total_stations = count($stations);
    $active_stations = array_filter($stations, fn($s) => $s['status'] === 'active');
    $total_active_stations = count($active_stations);
    $total_slots = array_sum(array_column($stations, 'total_slots'));
    $available_slots = array_sum(array_column($stations, 'available_slots'));

    // Fetch recent booking logs for all stations
    $stmt = $pdo->prepare("
        SELECT 
            bl.log_id,
            bl.booking_id,
            bl.action_type,
            bl.action_time,
            bl.status,
            b.station_id,
            s.name as station_name,
            u.name as user_name,
            u.email as user_email
        FROM booking_logs bl
        JOIN bookings b ON bl.booking_id = b.booking_id
        JOIN charging_stations s ON b.station_id = s.station_id
        JOIN tbl_users u ON bl.user_id = u.user_id
        WHERE s.owner_name = ?
        ORDER BY bl.action_time DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['owner_name']]);
    $recentLogs = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard. Please try again later.";
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard. Please try again later.";
}

// Rest of your HTML remains the same...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Owner Dashboard</title>
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
        
        /* Dashboard Cards */
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            margin-bottom: 20px;
            padding: 25px;
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
        .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
        .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
        .dashboard-card:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .station-card {
            border-radius: 15px;
            transition: all 0.3s;
            height: 100%;
            margin-bottom: 0;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .station-card:hover {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.25);
        }
        
        .card-body h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .card-body h6 {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Background Gradients */
        .bg-primary {
            background: linear-gradient(45deg, #4e73df, #224abe) !important;
        }
        
        .bg-success {
            background: linear-gradient(45deg, #1cc88a, #13855c) !important;
        }
        
        .bg-info {
            background: linear-gradient(45deg, #36b9cc, #258391) !important;
        }
        
        .bg-warning {
            background: linear-gradient(45deg, #f6c23e, #dda20a) !important;
        }
        
        /* Tables */
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
        
        /* Buttons */
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
        
        .btn-group .btn {
            border-radius: 8px;
            margin: 0 2px;
        }
        
        /* Badges */
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        /* Cards and sections */
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
        
        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .sidebar-container {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 250px;
                position: fixed;
                z-index: 1010;
            }

            .sidebar-container.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .container-fluid {
                padding: 15px;
            }
            
            .dashboard-card {
                padding: 15px;
            }
            
            .card-body h2 {
                font-size: 2rem;
            }
            
            .user-info {
                display: none;
            }
            
            /* Create an overlay when sidebar is active */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1005;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-card {
                margin-bottom: 15px;
            }
            
            .main-header {
                padding: 10px 15px;
            }
            
            .section-card {
                padding: 15px;
            }
            
            .table {
                font-size: 0.85rem;
            }
            
            .btn-group .btn {
                padding: 0.375rem 0.5rem;
                font-size: 0.85rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            h4 {
                font-size: 1.2rem;
            }
        }
        
        /* Ensure table responsiveness */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
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
                <a href="station-owner-dashboard.php" class="sidebar-link active">
                    <i class='bx bx-home'></i>
                    <span>Dashboard</span>
                </a>
                <a href="so_add_station.php" class="sidebar-link">
                    <i class='bx bx-plus-circle'></i>
                    <span>Add Station</span>
                </a>
                <a href="manage-booking.php" class="sidebar-link">
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
        
        <!-- Add sidebar overlay for mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

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

            <!-- Dashboard Content -->
            <div class="container-fluid py-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Dashboard Summary -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="dashboard-card bg-primary text-white">
                            <h6><i class='bx bx-station'></i> Total Stations</h6>
                            <h2><?php echo $total_stations; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card bg-success text-white">
                            <h6><i class='bx bx-check-circle'></i> Active Stations</h6>
                            <h2><?php echo $total_active_stations; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card bg-info text-white">
                            <h6><i class='bx bx-plug'></i> Total Slots</h6>
                            <h2><?php echo $total_slots; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card bg-warning text-white">
                            <h6><i class='bx bx-battery'></i> Available Slots</h6>
                            <h2><?php echo $available_slots; ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="section-card mb-4">
                    <h4 class="mb-0"><i class='bx bx-history'></i> Recent Check-in/Check-out Activity</h4>
                    <div class="card-body px-0">
                        <?php if (!empty($recentLogs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Station</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentLogs as $log): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($log['action_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($log['station_name']); ?></td>
                                                <td><?php echo htmlspecialchars($log['user_name']); ?> (<?php echo htmlspecialchars($log['user_email']); ?>)</td>
                                                <td>
                                                    <span class="badge bg-<?php echo $log['action_type'] == 'check_in' ? 'success' : 'primary'; ?>">
                                                        <?php echo $log['action_type'] == 'check_in' ? 'Check-in' : 'Check-out'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $log['status'] == 'completed' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($log['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent check-in/check-out activity.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- QR Scanner and Active Sessions -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="section-card h-100">
                            <h4 class="mb-3"><i class='bx bx-qr-scan'></i> QR Scanner</h4>
                            <p>Scan customer QR codes for quick check-in and check-out.</p>
                            <button id="startScanBtn" class="btn btn-primary mb-3">
                                <i class='bx bx-qr'></i> Open QR Scanner
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="section-card h-100">
                            <h4 class="mb-3"><i class='bx bx-time'></i> Active Sessions</h4>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Station</th>
                                            <th>Active Sessions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stations as $station): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($station['name']); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo (int)($station['active_sessions'] ?? 0); ?> Active
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="so_bookings_view.php?id=<?php echo $station['station_id']; ?>&status=in_progress" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-list-ul'></i> My Charging Stations</h2>
                    <a href="so_add_station.php" class="btn btn-primary">
                        <i class='bx bx-plus'></i> Add New Station
                    </a>
                </div>

                <div class="row">
                    <?php foreach ($stations as $station): ?>
                        <div class="col-md-6 col-xl-4 mb-4">
                            <div class="station-card card">
                                <?php if ($station['image']): ?>
                                    <img src="<?php echo htmlspecialchars($station['image']); ?>" class="card-img-top" alt="Station Image" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($station['name']); ?></h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="card-text">
                                                <i class='bx bx-map'></i> <strong>Address:</strong><br>
                                                <?php echo htmlspecialchars($station['address']); ?><br>
                                                <i class='bx bx-money'></i> <strong>Price:</strong> 
                                                ₹<?php echo number_format($station['price'], 2); ?>/kWh<br>
                                                <i class='bx bx-plug'></i> <strong>Slots:</strong> 
                                                <?php echo $station['available_slots']; ?>/<?php echo $station['total_slots']; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="card-text">
                                                <i class='bx bx-calendar'></i> <strong>Today's Bookings:</strong> 
                                                <?php echo $station['today_bookings'] ?? 0; ?><br>
                                                <i class='bx bx-check-double'></i> <strong>Total Bookings:</strong> 
                                                <?php echo $station['total_bookings'] ?? 0; ?><br>
                                                <i class='bx bx-time'></i> <strong>Completion Rate:</strong>
                                                <?php 
                                                    $total = $station['total_bookings'] ?? 0;
                                                    $completed = $station['completed_bookings'] ?? 0;
                                                    echo $total > 0 ? round(($completed / $total) * 100) : 0;
                                                ?>%
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Recent Users Section -->
                                    <div class="mt-3">
                                        <h6><i class='bx bx-user-circle'></i> Recent Users:</h6>
                                        <?php if (!empty($station['user_details'])): ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Email</th>
                                                            <th>Bookings</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $users = explode(';;', $station['user_details']);
                                                        foreach ($users as $user) {
                                                            $userInfo = explode('|', $user);
                                                            if (count($userInfo) === 3) {
                                                                echo "<tr>
                                                                    <td>" . htmlspecialchars($userInfo[0]) . "</td>
                                                                    <td>" . htmlspecialchars($userInfo[1]) . "</td>
                                                                    <td>" . htmlspecialchars($userInfo[2]) . "</td>
                                                                </tr>";
                                                            }
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No users have booked this station yet.</p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="btn-group w-100 mt-3">
                                        <a href="station_owner/update_booking_status.php?id=<?php echo $station['station_id']; ?>" 
                                           class="btn btn-primary">
                                            <i class='bx bx-edit'></i> Edit
                                        </a>
                                        <a href="so_bookings_view.php?id=<?php echo $station['station_id']; ?>" 
                                           class="btn btn-info">
                                            <i class='bx bx-calendar'></i> Bookings
                                        </a>
                                        <a href="view-qr.php?station_id=<?php echo $station['station_id']; ?>" 
                                           class="btn btn-info">
                                            <i class='bx bx-qr'></i> View QR Code
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.2.1/dist/html5-qrcode.min.js"></script>
    <script>
        // Enhanced sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
            }
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', toggleSidebar);
            }
            
            // Close sidebar when clicking a menu item on mobile
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            if (window.innerWidth <= 768) {
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.remove('active');
                        }
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
        });

        document.querySelectorAll('.toggle-status').forEach(button => {
            button.addEventListener('click', async (e) => {
                if (!confirm('Are you sure you want to change this station\'s status?')) {
                    return;
                }
                
                const stationId = e.target.dataset.stationId;
                try {
                    const response = await fetch('toggle-station-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ station_id: stationId })
                    });
                    
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Error updating station status');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error updating station status');
                }
            });
        });

        // Modify QR Scanner button to open a dedicated scanner window
        document.getElementById('startScanBtn').addEventListener('click', function() {
            // Open scan-qr.php (corrected name with dash) in a new window
            window.open('scan-qr.php', 'qrScannerWindow', 'width=800,height=600');
        });
    </script>
</body>
</html>
