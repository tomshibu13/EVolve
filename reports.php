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
        
        // Initialize empty arrays for popular time slots in case the query returns no results
        $popularTimeSlots = [];
        
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
                IFNULL(DATE_FORMAT(b.booking_date, '%H:00'), 'No Data') as hour_slot,
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

        // If no popular time slots were found, provide default data to prevent JavaScript errors
        if (empty($popularTimeSlots)) {
            $popularTimeSlots = [
                ['hour_slot' => 'No Data', 'booking_count' => 0]
            ];
        }

        // Initialize station totals
        $stationTotals = [];

        // Calculate totals from booking data
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
            --primary-color: #4361ee;
            --secondary-color: #3bc14a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --card-border-radius: 12px;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Page Container */
        .page-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar-container {
            width: 250px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: transform var(--transition-speed) ease;
            box-shadow: var(--box-shadow);
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
            transition: all var(--transition-speed) ease;
            text-decoration: none;
            position: relative;
            border-left: 4px solid transparent;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
            text-decoration: none;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: var(--light-color);
            min-height: 100vh;
            transition: margin-left var(--transition-speed) ease;
        }
        
        /* Header */
        .main-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            border-radius: 50%;
            color: var(--primary-color);
            transition: all var(--transition-speed) ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #sidebarToggle:hover {
            background-color: #f0f4ff;
        }
        
        /* Report Cards */
        .report-card {
            background-color: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 24px;
            border: none;
            transition: transform var(--transition-speed) ease;
            overflow: hidden;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
        }
        
        .report-card .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .report-card .card-body {
            padding: 1.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }
        
        .filter-container {
            background-color: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .period-btn {
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
        }
        
        .period-btn.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        .stat-card {
            border-radius: var(--card-border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            height: 100%;
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
            transition: transform var(--transition-speed) ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-primary { border-left-color: var(--primary-color); }
        .stat-success { border-left-color: var(--secondary-color); }
        .stat-info { border-left-color: var(--info-color); }
        .stat-warning { border-left-color: var(--warning-color); }
        
        .stat-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .stat-card p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 3rem;
            opacity: 0.1;
            color: #000;
        }
        
        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            color: #4e73df;
            font-weight: 600;
            border: none;
            padding: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .table tbody td {
            padding: 1rem;
            border-top: 1px solid #f1f1f1;
            vertical-align: middle;
        }
        
        .table-hover tbody tr {
            transition: background-color var(--transition-speed) ease;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        /* Enhanced Responsive Design */
        @media (max-width: 992px) {
            .sidebar-container {
                transform: translateX(-100%);
                transition: transform var(--transition-speed) ease;
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
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }
            
            .report-card .card-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .btn-group {
                display: flex;
                width: 100%;
            }
            
            .period-btn {
                flex: 1;
                text-align: center;
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
            
            .main-header {
                padding: 0.75rem 1rem;
            }
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(67, 97, 238, 0.2);
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Export Button */
        #exportReportBtn {
            background: var(--primary-color);
            border: none;
            border-radius: 30px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-speed) ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        #exportReportBtn:hover {
            background: #2e43b8;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
        }
        
        /* Empty State Style */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e3e6f0;
        }
        
        .empty-state h5 {
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p class="mt-3">Loading reports...</p>
    </div>

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
                    <button class="btn" id="sidebarToggle">
                        <i class='bx bx-menu fs-4'></i>
                    </button>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['owner_name']); ?></div>
                            <div class="user-role">Station Owner</div>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown">
                    <button class="btn" type="button" id="userMenuButton" data-bs-toggle="dropdown">
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
                    <button class="d-none d-sm-inline-block btn" id="exportReportBtn">
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
                    <div class="col-md-3 mb-4">
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
                    <div class="col-md-3 mb-4">
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
                    <div class="col-md-3 mb-4">
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
                    <div class="col-md-3 mb-4">
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
                    <div class="col-lg-8 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Revenue Over Time</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" id="viewAllRevenue">All</button>
                                    <?php 
                                    if (!empty($stations)) {
                                        foreach ($stations as $index => $station): 
                                            if (isset($station['station_id']) && isset($station['name'])) {
                                                $colorClass = ($index % 4 == 0) ? 'primary' : 
                                                            (($index % 4 == 1) ? 'success' : 
                                                            (($index % 4 == 2) ? 'info' : 'warning'));
                                                $displayName = !empty($station['name']) ? substr($station['name'], 0, 10) : 'Station';
                                    ?>
                                        <button class="btn btn-sm btn-outline-<?php echo $colorClass; ?>" 
                                                data-station-id="<?php echo $station['station_id']; ?>">
                                            <?php echo htmlspecialchars($displayName); ?>
                                        </button>
                                    <?php 
                                            }
                                        endforeach; 
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($revenueData)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-bar-chart-alt-2'></i>
                                    <h5>No Revenue Data Available</h5>
                                    <p>There is no revenue data for the selected time period.</p>
                                </div>
                                <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Popular Time Slots</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($popularTimeSlots) <= 1 && $popularTimeSlots[0]['hour_slot'] == 'No Data'): ?>
                                <div class="empty-state">
                                    <i class='bx bx-time'></i>
                                    <h5>No Time Slot Data</h5>
                                    <p>There is no booking time data for the selected period.</p>
                                </div>
                                <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="timeSlotChart"></canvas>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Bookings by Station</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($bookingsData)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-calendar'></i>
                                    <h5>No Booking Data Available</h5>
                                    <p>There are no bookings for the selected time period.</p>
                                </div>
                                <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="bookingsChart"></canvas>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="report-card">
                            <div class="card-header">
                                <h5 class="m-0">Usage Hours by Station</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($usageData)): ?>
                                <div class="empty-state">
                                    <i class='bx bx-time-five'></i>
                                    <h5>No Usage Data Available</h5>
                                    <p>There is no usage data for the selected time period.</p>
                                </div>
                                <?php else: ?>
                                <div class="chart-container">
                                    <canvas id="usageChart"></canvas>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
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
                    <div class="col-lg-6 mb-4">
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
            // Hide loading overlay
            setTimeout(function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 500);
            
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
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                const revenueData = <?php echo json_encode($revenueData); ?>;
                const stationNames = <?php echo json_encode($stationNames); ?>;
                const stationIds = Object.keys(stationNames);
                
                // Generate colors for each station
                const colors = [
                    'rgba(67, 97, 238, 0.8)',    // Primary
                    'rgba(59, 193, 74, 0.8)',    // Success
                    'rgba(54, 185, 204, 0.8)',   // Info
                    'rgba(246, 194, 62, 0.8)',   // Warning
                    'rgba(231, 74, 59, 0.8)',    // Danger
                    'rgba(90, 92, 105, 0.8)'     // Dark
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
                            return dateStr; // Already in "YYYY-MM" format
                        default:
                            return dateStr;
                    }
                };
                
                // Prepare datasets for revenue chart
                const labels = Object.keys(revenueData).sort();
                const formattedLabels = labels.map(formatDate);
                
                const datasets = stationIds.map((stationId, index) => {
                    return {
                        label: stationNames[stationId],
                        data: labels.map(date => revenueData[date] && revenueData[date][stationId] ? revenueData[date][stationId] : 0),
                        backgroundColor: colors[index % colors.length],
                        borderColor: colors[index % colors.length].replace('0.8', '1'),
                        borderWidth: 1,
                        hidden: false
                    };
                });
                
                const revenueChart = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: formattedLabels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '₹' + context.parsed.y.toFixed(2);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₹' + value;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Add event listeners for station filtering
                document.querySelectorAll('[data-station-id]').forEach(button => {
                    button.addEventListener('click', function() {
                        const stationId = this.getAttribute('data-station-id');
                        
                        // Hide all datasets first
                        revenueChart.data.datasets.forEach(dataset => {
                            dataset.hidden = true;
                        });
                        
                        // Show only the clicked station
                        const datasetIndex = stationIds.indexOf(stationId);
                        if (datasetIndex !== -1) {
                            revenueChart.data.datasets[datasetIndex].hidden = false;
                        }
                        
                        revenueChart.update();
                    });
                });
                
                // View all button
                document.getElementById('viewAllRevenue').addEventListener('click', function() {
                    revenueChart.data.datasets.forEach(dataset => {
                        dataset.hidden = false;
                    });
                    revenueChart.update();
                });
            }
            
            // Bookings Chart
            const bookingsCtx = document.getElementById('bookingsChart');
            if (bookingsCtx) {
                const bookingsData = <?php echo json_encode($bookingsData); ?>;
                const stationNames = <?php echo json_encode($stationNames); ?>;
                
                // Prepare datasets for bookings chart
                const stationIds = Object.keys(stationNames);
                const labels = Object.keys(bookingsData).sort();
                const formattedLabels = labels.map(date => {
                    const period = '<?php echo $period; ?>';
                    if (period === 'day') return date;
                    if (period === 'year') return date;
                    return new Date(date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
                });
                
                const totalsByStation = {};
                stationIds.forEach(stationId => {
                    totalsByStation[stationId] = labels.reduce((sum, date) => {
                        return sum + (bookingsData[date] && bookingsData[date][stationId] ? bookingsData[date][stationId] : 0);
                    }, 0);
                });
                
                const sortedStationIds = Object.keys(totalsByStation).sort((a, b) => totalsByStation[b] - totalsByStation[a]);
                
                const bookingsChart = new Chart(bookingsCtx, {
                    type: 'pie',
                    data: {
                        labels: sortedStationIds.map(id => stationNames[id]),
                        datasets: [{
                            data: sortedStationIds.map(id => totalsByStation[id]),
                            backgroundColor: [
                                'rgba(67, 97, 238, 0.8)',
                                'rgba(59, 193, 74, 0.8)',
                                'rgba(54, 185, 204, 0.8)',
                                'rgba(246, 194, 62, 0.8)',
                                'rgba(231, 74, 59, 0.8)',
                                'rgba(90, 92, 105, 0.8)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        const value = context.raw;
                                        const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        label += value + ' bookings (' + percentage + '%)';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Usage Chart
            const usageCtx = document.getElementById('usageChart');
            if (usageCtx) {
                const usageData = <?php echo json_encode($usageData); ?>;
                const stationNames = <?php echo json_encode($stationNames); ?>;
                
                // Calculate total usage hours by station
                const stationIds = Object.keys(stationNames);
                const totalUsageByStation = {};
                
                stationIds.forEach(stationId => {
                    totalUsageByStation[stationId] = 0;
                    Object.keys(usageData).forEach(date => {
                        if (usageData[date] && usageData[date][stationId]) {
                            totalUsageByStation[stationId] += usageData[date][stationId];
                        }
                    });
                });
                
                // Sort stations by total usage
                const sortedStationIds = Object.keys(totalUsageByStation)
                    .sort((a, b) => totalUsageByStation[b] - totalUsageByStation[a]);
                
                const usageChart = new Chart(usageCtx, {
                    type: 'bar',
                    data: {
                        labels: sortedStationIds.map(id => stationNames[id]),
                        datasets: [{
                            label: 'Usage Hours',
                            data: sortedStationIds.map(id => totalUsageByStation[id]),
                            backgroundColor: 'rgba(54, 185, 204, 0.8)',
                            borderColor: 'rgba(54, 185, 204, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            }
                        }
                    }
                });
            }
            
            // Time Slot Chart
            const timeSlotCtx = document.getElementById('timeSlotChart');
            if (timeSlotCtx) {
                const timeSlotData = <?php echo json_encode($popularTimeSlots); ?>;
                
                // Don't create chart if there's only "No Data" entry
                if (!(timeSlotData.length === 1 && timeSlotData[0].hour_slot === 'No Data')) {
                    const timeSlotChart = new Chart(timeSlotCtx, {
                        type: 'doughnut',
                        data: {
                            labels: timeSlotData.map(slot => slot.hour_slot),
                            datasets: [{
                                data: timeSlotData.map(slot => slot.booking_count),
                                backgroundColor: [
                                    'rgba(67, 97, 238, 0.8)',
                                    'rgba(59, 193, 74, 0.8)',
                                    'rgba(54, 185, 204, 0.8)',
                                    'rgba(246, 194, 62, 0.8)',
                                    'rgba(231, 74, 59, 0.8)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        boxWidth: 12
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // Export Report Button
            document.getElementById('exportReportBtn').addEventListener('click', function() {
                // Create a print-friendly version
                const period = '<?php echo $period; ?>';
                const printWindow = window.open('', '_blank');
                
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Station Reports - ${period.charAt(0).toUpperCase() + period.slice(1)}</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            h1, h2 { color: #4361ee; }
                            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                            th { background-color: #f8f9fc; }
                            .report-section { margin-bottom: 30px; }
                            .page-break { page-break-after: always; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="no-print" style="text-align: right; margin-bottom: 20px;">
                            <button onclick="window.print()">Print Report</button>
                        </div>
                        
                        <h1>Station Reports & Analytics</h1>
                        <p>Period: ${period.charAt(0).toUpperCase() + period.slice(1)}</p>
                        <p>Generated on: ${new Date().toLocaleString()}</p>
                        
                        <div class="report-section">
                            <h2>Summary</h2>
                            <table>
                                <tr>
                                    <th>Total Revenue</th>
                                    <td>₹<?php echo number_format($totalRevenue, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Bookings</th>
                                    <td><?php echo number_format($totalBookings); ?></td>
                                </tr>
                                <tr>
                                    <th>Usage Hours</th>
                                    <td><?php echo number_format($totalHours, 1); ?> hours</td>
                                </tr>
                                <tr>
                                    <th>Average Revenue per Booking</th>
                                    <td>₹<?php echo $totalBookings > 0 ? number_format($totalRevenue / $totalBookings, 2) : '0.00'; ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="report-section">
                            <h2>Station Performance</h2>
                            <table>
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
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="page-break"></div>
                        
                        <div class="report-section">
                            <h2>Top 5 Users</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Bookings</th>
                                        <th>Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topUsers as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo number_format($user['total_bookings']); ?></td>
                                            <td>₹<?php echo number_format($user['total_spent'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="report-section">
                            <h2>Popular Time Slots</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Time Slot</th>
                                        <th>Number of Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popularTimeSlots as $slot): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($slot['hour_slot']); ?></td>
                                            <td><?php echo number_format($slot['booking_count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="report-section">
                            <p style="text-align: center; color: #6c757d; margin-top: 40px;">
                                Generated by EV Station Management System
                            </p>
                        </div>
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
            });
        });
    </script>
</body>
</html>
                            