<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is a station owner
if (!isset($_SESSION['user_id']) || !isset($_SESSION['owner_name'])) {
    header("Location: ../stationlogin.php");
    exit();
}

try {
    // Get total earnings and payment statistics
    $query = "SELECT 
                SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END) as total_earnings,
                COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_payments,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments,
                COUNT(*) as total_bookings
            FROM bookings b
            JOIN charging_stations cs ON b.station_id = cs.station_id
            WHERE cs.owner_name = ?";
            
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['owner_name']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get detailed payment information
    $query = "SELECT 
                pd.*,
                b.booking_date,
                b.booking_time,
                b.duration,
                cs.name as station_name,
                u.name as user_name,
                u.email as user_email,
                u.phone_number as user_phone
            FROM payment_details pd
            JOIN bookings b ON pd.booking_id = b.booking_id
            JOIN charging_stations cs ON pd.station_id = cs.station_id
            JOIN tbl_users u ON pd.user_id = u.user_id
            WHERE cs.owner_name = ?
            ORDER BY pd.payment_date DESC";
            
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['owner_name']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Analytics - EVolve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .analytics-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .stat-card p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .payment-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .payment-filters {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .payment-filters h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .payment-filters .form-control {
            border-radius: 8px;
            border: 1px solid #e0e6ed;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .payment-filters .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
        }
        
        .payment-details-table {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .payment-details-table h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .table {
            margin-bottom: 0;
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
        
        .export-btn {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
            color: white;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
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
        
        .stat-card {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        /* Sidebar Styles */
        .page-container {
            display: flex;
            min-height: 100vh;
        }

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

        /* Main Content adjustment */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: #f8f9fc;
            min-height: 100vh;
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
        }

        /* Updated header and user info styles */
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
            text-align: left;  /* Changed from right to left */
            margin-left: 15px; /* Added margin for spacing */
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
                <a href="../station-owner-dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'station-owner-dashboard.php' ? 'active' : ''; ?>">
                    <i class='bx bx-home'></i>
                    <span>Dashboard</span>
                </a>
                <a href="../so_add_station.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'so_add_station.php' ? 'active' : ''; ?>">
                    <i class='bx bx-plus-circle'></i>
                    <span>Add Station</span>
                </a>
                <a href="../manage-booking.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage-bookings.php' ? 'active' : ''; ?>">
                    <i class='bx bx-calendar'></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="payment_analytics.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment_analytics.php' ? 'active' : ''; ?>">
                    <i class='bx bx-money'></i>
                    <span>Payment Analytics</span>
                </a>
                <a href="so_profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'so_profile.php' ? 'active' : ''; ?>">
                    <i class='bx bx-user'></i>
                    <span>Profile</span>
                </a>
                <a href="reports.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class='bx bx-line-chart'></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
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
                        <!-- <i class='bx bx-menu'></i> -->
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

            <!-- Your existing page content goes here -->
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-money'></i> Payment Analytics</h2>
                    <a href="../station-owner-dashboard.php" class="btn btn-primary">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #4e73df 0%, #224abe 100%)">
                            <h3>₹<?php echo number_format($stats['total_earnings'], 2); ?></h3>
                            <p><i class='bx bx-money-withdraw'></i> Total Earnings</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%)">
                            <h3><?php echo $stats['total_bookings']; ?></h3>
                            <p><i class='bx bx-calendar-check'></i> Total Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #36b9cc 0%, #258391 100%)">
                            <h3><?php echo $stats['completed_payments']; ?></h3>
                            <p><i class='bx bx-check-circle'></i> Completed Payments</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%)">
                            <h3><?php echo $stats['pending_payments']; ?></h3>
                            <p><i class='bx bx-time'></i> Pending Payments</p>
                        </div>
                    </div>
                </div>

                <div class="payment-filters">
                    <h4>Payment Filters</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <label>Date Range:</label>
                            <input type="date" class="form-control" id="start-date">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <input type="date" class="form-control" id="end-date">
                        </div>
                        <div class="col-md-3">
                            <label>Status:</label>
                            <select class="form-control" id="payment-status">
                                <option value="">All</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label><br>
                            <button class="btn btn-primary" onclick="filterPayments()">Apply Filters</button>
                            <button class="export-btn" onclick="exportToExcel()">
                                <i class='bx bx-download'></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <div class="payment-details-table">
                    <h3>Payment Details</h3>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Customer</th>
                                <th>Station</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <?php echo date('d M Y', strtotime($payment['booking_date'])); ?><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($payment['booking_time'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($payment['user_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($payment['user_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['station_name']); ?></td>
                                    <td><?php echo $payment['duration']; ?> mins</td>
                                    <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td>
                                        <span class="text-muted"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $payment['status'] === 'completed' ? 'bg-success' : 
                                                ($payment['status'] === 'failed' ? 'bg-danger' : 'bg-warning'); 
                                        ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterPayments() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const status = document.getElementById('payment-status').value;
            
            // Implement filtering logic here
            // You can either use AJAX to fetch filtered data or
            // implement client-side filtering using JavaScript
        }
        
        function exportToExcel() {
            // Implement export logic here
            // You can use libraries like SheetJS or create a CSV file
            alert('Export feature will be implemented soon!');
        }

        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar-container').classList.toggle('active');
        });
    </script>
</body>
</html> 