<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

// Initialize variables
$error = null;
$chartData = [];
$revenueData = [];
$usageData = [];
$bookingsData = [];
$period = isset($_GET['period']) ? $_GET['period'] : 'week';

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
    
    // Fetch station IDs for this owner
    $stmt = $pdo->prepare("
        SELECT station_id, name
        FROM charging_stations
        WHERE owner_name = ?
    ");
    $stmt->execute([$_SESSION['owner_name']]);
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stationIds = array_column($stations, 'station_id');
    $stationNames = array_column($stations, 'name', 'station_id');
    
    if (empty($stationIds)) {
        $error = "No stations found. Please add a station first.";
    } else {
        // Determine date range based on period
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d');
                $groupBy = "DATE_FORMAT(b.booking_date, '%H:00')";
                $dateFormat = "FORMAT_DATE_HOUR";
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-6 days'));
                $groupBy = "DATE(b.booking_date)";
                $dateFormat = "FORMAT_DATE_DAY";
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-29 days'));
                $groupBy = "DATE(b.booking_date)";
                $dateFormat = "FORMAT_DATE_DAY";
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-11 months'));
                $groupBy = "DATE_FORMAT(b.booking_date, '%Y-%m')";
                $dateFormat = "FORMAT_DATE_MONTH";
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-6 days'));
                $groupBy = "DATE(b.booking_date)";
                $dateFormat = "FORMAT_DATE_DAY";
        }
        $endDate = date('Y-m-d');

        // Format for display
        $placeholders = implode(',', array_fill(0, count($stationIds), '?'));
        
        // Fetch revenue data
        $stmt = $pdo->prepare("
            SELECT 
                s.station_id,
                $groupBy as date_group,
                SUM(b.amount) as revenue
            FROM bookings b
            JOIN charging_stations s ON b.station_id = s.station_id
            WHERE s.station_id IN ($placeholders)
            AND b.booking_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            AND b.payment_status = 'completed'
            GROUP BY s.station_id, date_group
            ORDER BY date_group
        ");
        
        $params = array_merge($stationIds, [$startDate, $endDate]);
        $stmt->execute($params);
        $revenueResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format revenue data for charts
        foreach ($revenueResults as $row) {
            $stationId = $row['station_id'];
            $date = $row['date_group'];
            $revenue = floatval($row['revenue']);
            
            if (!isset($revenueData[$date])) {
                $revenueData[$date] = [];
            }
            $revenueData[$date][$stationId] = $revenue;
        }
        
        // Fetch booking count data
        $stmt = $pdo->prepare("
            SELECT 
                s.station_id,
                $groupBy as date_group,
                COUNT(b.booking_id) as booking_count
            FROM bookings b
            JOIN charging_stations s ON b.station_id = s.station_id
            WHERE s.station_id IN ($placeholders)
            AND b.booking_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            GROUP BY s.station_id, date_group
            ORDER BY date_group
        ");
        
        $stmt->execute($params);
        $bookingResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format booking data for charts
        foreach ($bookingResults as $row) {
            $stationId = $row['station_id'];
            $date = $row['date_group'];
            $count = intval($row['booking_count']);
            
            if (!isset($bookingsData[$date])) {
                $bookingsData[$date] = [];
            }
            $bookingsData[$date][$stationId] = $count;
        }
        
        // Fetch usage duration data
        $stmt = $pdo->prepare("
            SELECT 
                s.station_id,
                $groupBy as date_group,
                SUM(TIMESTAMPDIFF(MINUTE, 
                    (SELECT MIN(action_time) FROM booking_logs WHERE booking_id = b.booking_id AND action_type = 'check_in'),
                    (SELECT MAX(action_time) FROM booking_logs WHERE booking_id = b.booking_id AND action_type = 'check_out')
                )) as total_minutes
            FROM bookings b
            JOIN charging_stations s ON b.station_id = s.station_id
            WHERE s.station_id IN ($placeholders)
            AND b.booking_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            AND b.status = 'completed'
            AND EXISTS (SELECT 1 FROM booking_logs WHERE booking_id = b.booking_id AND action_type = 'check_in')
            AND EXISTS (SELECT 1 FROM booking_logs WHERE booking_id = b.booking_id AND action_type = 'check_out')
            GROUP BY s.station_id, date_group
            ORDER BY date_group
        ");
        
        $stmt->execute($params);
        $usageResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format usage data for charts
        foreach ($usageResults as $row) {
            $stationId = $row['station_id'];
            $date = $row['date_group'];
            $minutes = $row['total_minutes'] ? floatval($row['total_minutes']) : 0;
            
            if (!isset($usageData[$date])) {
                $usageData[$date] = [];
            }
            $usageData[$date][$stationId] = round($minutes / 60, 1); // Convert to hours
        }
        
        // Get top 5 users
        $stmt = $pdo->prepare("
            SELECT 
                u.name,
                u.email,
                COUNT(b.booking_id) as total_bookings,
                SUM(b.amount) as total_spent
            FROM bookings b
            JOIN tbl_users u ON b.user_id = u.user_id
            JOIN charging_stations s ON b.station_id = s.station_id
            WHERE s.owner_name = ?
            AND b.booking_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            AND b.payment_status = 'completed'
            GROUP BY u.user_id
            ORDER BY total_bookings DESC
            LIMIT 5
        ");
        
        $stmt->execute([$_SESSION['owner_name'], $startDate, $endDate]);
        $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Initialize $topUsers if the query returned no results
        if (!is_array($topUsers)) {
            $topUsers = [];
        }
        
        // Get most popular time slots
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(b.booking_date, '%H:00') as hour_slot,
                COUNT(b.booking_id) as booking_count
            FROM bookings b
            JOIN charging_stations s ON b.station_id = s.station_id
            WHERE s.owner_name = ?
            AND b.booking_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
            GROUP BY hour_slot
            ORDER BY booking_count DESC
            LIMIT 5
        ");
        
        $stmt->execute([$_SESSION['owner_name'], $startDate, $endDate]);
        $popularTimeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while loading the reports. Please try again later.";
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $error = "An error occurred while loading the reports. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Reports & Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Report Cards */
        .report-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 24px;
            border: none;
        }
        
        .report-card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
            color: #4e73df;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report-card .card-body {
            padding: 1.25rem;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        
        .filter-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 15px;
            margin-bottom: 24px;
        }
        
        .period-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }
        
        .period-btn.active {
            background-color: #4e73df;
            color: white;
        }
        
        .stat-card {
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            height: 100%;
            border-left: 4px solid;
        }
        
        .stat-primary { border-left-color: #4e73df; }
        .stat-success { border-left-color: #1cc88a; }
        .stat-info { border-left-color: #36b9cc; }
        .stat-warning { border-left-color: #f6c23e; }
        
        .stat-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        /* Table Styles */
        .table thead th {
            background-color: #f8f9fc;
            color: #6e707e;
            font-weight: 600;
            border-bottom: 2px solid #e3e6f0;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fc;
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
            
            .chart-container {
                height: 300px;
            }
        }
        
        @media (max-width: 576px) {
            .chart-container {
                height: 250px;
            }
            
            .period-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.85rem;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
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
                <a href="manage-booking.php" class="sidebar-link">
                    <i class='bx bx-calendar'></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="station_owner/payment_analytics.php" class="sidebar-link">
                    <i class='bx bx-money'></i>
                    <span>Payment Analytics</span>
                </a>
                <a href="station_owner/so_profile.php" class="sidebar-link">
                    <i class='bx bx-user'></i>
                    <span>Profile</span>
                </a>
                <a href="reports.php" class="sidebar-link active">
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

            <!-- Reports Content -->
            <div class="container-fluid py-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><i class='bx bx-line-chart'></i> Reports & Analytics</h1>
                    <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" id="exportReportBtn">
                        <i class='bx bx-download'></i> Export Report
                    </button>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="filter-container mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h5 class="mb-0 me-3">Time Period:</h5>
                        <div class="btn-group">
                            <a href="?period=day" class="btn btn-outline-primary period-btn <?php echo $period == 'day' ? 'active' : ''; ?>">
                                Day
                            </a>
                            <a href="?period=week" class="btn btn-outline-primary period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">
                                Week
                            </a>
                            <a href="?period=month" class="btn btn-outline-primary period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">
                                Month
                            </a>
                            <a href="?period=year" class="btn btn-outline-primary period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">
                                Year
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card stat-primary bg-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p>TOTAL REVENUE</p>
                                    <h3>₹<?php 
                                        $totalRevenue = 0;
                                        foreach ($revenueData as $date => $stations) {
                                            foreach ($stations as $stationId => $revenue) {
                                                $totalRevenue += $revenue;
                                            }
                                        }
                                        echo number_format($totalRevenue, 2);
                                    ?></h3>
                                </div>
                                <div class="stat-icon">
                                    <i class='bx bx-rupee'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-success bg-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p>TOTAL BOOKINGS</p>
                                    <h3><?php 
                                        $totalBookings = 0;
                                        foreach ($bookingsData as $date => $stations) {
                                            foreach ($stations as $stationId => $count) {
                                                $totalBookings += $count;
                                            }
                                        }
                                        echo number_format($totalBookings);
                                    ?></h3>
                                </div>
                                <div class="stat-icon">
                                    <i class='bx bx-calendar-check'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-info bg-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p>USAGE HOURS</p>
                                    <h3><?php 
                                        $totalHours = 0;
                                        foreach ($usageData as $date => $stations) {
                                            foreach ($stations as $stationId => $hours) {
                                                $totalHours += $hours;
                                            }
                                        }
                                        echo number_format($totalHours, 1);
                                    ?></h3>
                                </div>
                                <div class="stat-icon">
                                    <i class='bx bx-time'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card stat-warning bg-white">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p>AVG REVENUE/BOOKING</p>
                                    <h3>₹<?php 
                                        echo $totalBookings > 0 ? number_format($totalRevenue / $totalBookings, 2) : '0.00';
                                    ?></h3>
                                </div>
                                <div class="stat-icon">
                                    <i class='bx bx-trending-up'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Revenue Over Time</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" id="viewAllRevenue">All</button>
                                    <?php 
                                    foreach ($stations as $index => $station): 
                                        $colorClass = ($index % 4 == 0) ? 'primary' : 
                                                    (($index % 4 == 1) ? 'success' : 
                                                    (($index % 4 == 2) ? 'info' : 'warning'));
                                    ?>
                                        <button class="btn btn-sm btn-outline-<?php echo $colorClass; ?>" 
                                                data-station-id="<?php echo $station['station_id']; ?>">
                                            <?php echo htmlspecialchars(substr($station['name'], 0, 10)); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Popular Time Slots</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="timeSlotChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Bookings by Station</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="bookingsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Usage Hours by Station</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="usageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Top 5 Users</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Email</th>
                                                <th>Bookings</th>
                                                <th>Total Spent</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (is_array($topUsers) && !empty($topUsers)): ?>
                                                <?php foreach ($topUsers as $user): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo number_format($user['total_bookings']); ?></td>
                                                        <td>₹<?php echo number_format($user['total_spent'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No user data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Station Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Station</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                                <th>Avg. Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $stationTotals = [];
                                            
                                            // Initialize station totals
                                            foreach ($stations as $station) {
                                                $stationId = $station['station_id'];
                                                $stationTotals[$stationId] = [
                                                    'name' => $station['name'],
                                                    'bookings' => 0,
                                                    'revenue' => 0
                                                ];
                                            }
                                            
                                            // Calculate totals from booking data
                                            foreach ($bookingsData as $date => $stationBookings) {
                                                foreach ($stationBookings as $stationId => $count) {
                                                    if (isset($stationTotals[$stationId])) {
                                                        $stationTotals[$stationId]['bookings'] += $count;
                                                    }
                                                }
                                            }
                                            
                                            // Calculate totals from revenue data
                                            foreach ($revenueData as $date => $stationRevenues) {
                                                foreach ($stationRevenues as $stationId => $revenue) {
                                                    if (isset($stationTotals[$stationId])) {
                                                        $stationTotals[$stationId]['revenue'] += $revenue;
                                                    }
                                                }
                                            }
                                            
                                            // Sort by revenue (highest first)
                                            usort($stationTotals, function($a, $b) {
                                                return $b['revenue'] <=> $a['revenue'];
                                            });
                                            
                                            foreach ($stationTotals as $stationId => $data):
                                                $avgRevenue = $data['bookings'] > 0 ? $data['revenue'] / $data['bookings'] : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                                                    <td><?php echo number_format($data['bookings']); ?></td>
                                                    <td>₹<?php echo number_format($data['revenue'], 2); ?></td>
                                                    <td>₹<?php echo number_format($avgRevenue, 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($stationTotals)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No station data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
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

            // Chart setup for Revenue
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = <?php echo json_encode($revenueData); ?>;
            const stationNames = <?php echo json_encode($stationNames); ?>;
            const stationIds = Object.keys(stationNames);
            
            // Generate colors for each station
            const colors = [
                'rgba(78, 115, 223, 0.8)',
                'rgba(28, 200, 138, 0.8)',
                'rgba(54, 185, 204, 0.8)',
                'rgba(246, 194, 62, 0.8)',
                'rgba(231, 74, 59, 0.8)',
                'rgba(133, 135, 150, 0.8)'
            ];
            
            // Format dates based on the selected period
            const period = '<?php echo $period; ?>';
            const formatDate = (dateStr) => {
                const date = new Date(dateStr);
                switch(period) {
                    case 'day':
                        return dateStr; // Already in hour format "HH:00"
                    case 'week':
                    case 'month':
                        return new Date(dateStr).toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
                    case 'year':
                        return new Date(dateStr + '-01').toLocaleDateString('en-US', {year: 'numeric', month: 'short'});
                    default:
                        return dateStr;
                }
            };
            
            // Prepare datasets for revenue chart
            const revenueDatasets = stationIds.map((stationId, index) => {
                const colorIndex = index % colors.length;
                return {
                    label: stationNames[stationId],
                    data: Object.keys(revenueData).map(date => {
                        return revenueData[date] && revenueData[date][stationId] ? revenueData[date][stationId] : 0;
                    }),
                    backgroundColor: colors[colorIndex],
                    borderColor: colors[colorIndex].replace('0.8', '1'),
                    borderWidth: 2,
                    pointBackgroundColor: colors[colorIndex].replace('0.8', '1'),
                    pointRadius: 3,
                    tension: 0.1
                };
            });
            
            // Create revenue chart
            const revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(revenueData).map(date => formatDate(date)),
                    datasets: revenueDatasets
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        },
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₹' + context.raw;
                                }
                            }
                        }
                    }
                }
            });
            
            // Time Slot Chart
            const timeSlotCtx = document.getElementById('timeSlotChart').getContext('2d');
            const timeSlotData = <?php echo json_encode($popularTimeSlots); ?>;
            
            new Chart(timeSlotCtx, {
                type: 'bar',
                data: {
                    labels: timeSlotData.map(slot => slot.hour_slot),
                    datasets: [{
                        label: 'Bookings',
                        data: timeSlotData.map(slot => slot.booking_count),
                        backgroundColor: 'rgba(78, 115, 223, 0.8)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Bookings Chart
            const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
            const bookingsData = <?php echo json_encode($bookingsData); ?>;
            
            // Calculate total bookings per station
            const stationBookings = {};
            Object.keys(bookingsData).forEach(date => {
                Object.keys(bookingsData[date]).forEach(stationId => {
                    if (!stationBookings[stationId]) {
                        stationBookings[stationId] = 0;
                    }
                    stationBookings[stationId] += bookingsData[date][stationId];
                });
            });
            
            new Chart(bookingsCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(stationBookings).map(id => stationNames[id]),
                    datasets: [{
                        data: Object.values(stationBookings),
                        backgroundColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Usage Chart
            const usageCtx = document.getElementById('usageChart').getContext('2d');
            const usageData = <?php echo json_encode($usageData); ?>;
            
            // Calculate total usage hours per station
            const stationUsage = {};
            Object.keys(usageData).forEach(date => {
                Object.keys(usageData[date]).forEach(stationId => {
                    if (!stationUsage[stationId]) {
                        stationUsage[stationId] = 0;
                    }
                    stationUsage[stationId] += usageData[date][stationId];
                });
            });
            
            new Chart(usageCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(stationUsage).map(id => stationNames[id]),
                    datasets: [{
                        data: Object.values(stationUsage),
                        backgroundColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Station filter buttons for revenue chart
            document.querySelectorAll('[data-station-id]').forEach(button => {
                button.addEventListener('click', function() {
                    const stationId = this.getAttribute('data-station-id');
                    revenueChart.data.datasets.forEach(dataset => {
                        dataset.hidden = dataset.label !== stationNames[stationId];
                    });
                    revenueChart.update();
                });
            });
            
            // View all stations button
            document.getElementById('viewAllRevenue').addEventListener('click', function() {
                revenueChart.data.datasets.forEach(dataset => {
                    dataset.hidden = false;
                });
                revenueChart.update();
            });
            
            // Export report functionality
            document.getElementById('exportReportBtn').addEventListener('click', function() {
                // Create a simple PDF or CSV export functionality
                alert('Report export functionality will be implemented here.');
                // In a real implementation, this would generate a PDF/CSV with the report data
            });
        });
    </script>
</body>
</html>