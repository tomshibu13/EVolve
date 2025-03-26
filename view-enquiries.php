<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

// Initialize variables
$enquiries = [];
$error = null;
$success = null;
$unreadCount = 0;

// Process form submission for responding to enquiries
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enquiry_id'], $_POST['response'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE enquiries 
            SET response = ?, 
                response_date = NOW(), 
                status = 'responded' 
            WHERE enquiry_id = ? AND station_id IN (
                SELECT station_id FROM charging_stations WHERE owner_name = ?
            )
        ");
        
        $stmt->execute([
            $_POST['response'],
            $_POST['enquiry_id'],
            $_SESSION['owner_name']
        ]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Response sent successfully!";
        } else {
            $error = "Unable to respond to this enquiry. It may not exist or you don't have permission.";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "An error occurred while processing your response. Please try again.";
    }
}

// Handle marking as read/unread
if (isset($_GET['mark']) && isset($_GET['id'])) {
    try {
        $status = ($_GET['mark'] === 'read') ? 'read' : 'unread';
        
        $stmt = $pdo->prepare("
            UPDATE enquiries 
            SET status = ? 
            WHERE enquiry_id = ? AND station_id IN (
                SELECT station_id FROM charging_stations WHERE owner_name = ?
            )
        ");
        
        $stmt->execute([$status, $_GET['id'], $_SESSION['owner_name']]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Enquiry marked as " . $status;
        } else {
            $error = "Unable to update this enquiry. It may not exist or you don't have permission.";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "An error occurred while updating the enquiry. Please try again.";
    }
}

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
    
    // Get list of stations owned by this owner
    $stationStmt = $pdo->prepare("
        SELECT station_id, name 
        FROM charging_stations 
        WHERE owner_name = ? 
        ORDER BY name
    ");
    $stationStmt->execute([$_SESSION['owner_name']]);
    $stations = $stationStmt->fetchAll();
    
    // Handle station filter
    $stationFilter = '';
    $stationFilterParam = [];
    
    if (isset($_GET['station_id']) && !empty($_GET['station_id'])) {
        $stationFilter = " AND e.station_id = ? ";
        $stationFilterParam = [$_GET['station_id']];
    }
    
    // Fetch enquiries for all stations owned by this station owner
    $stmt = $pdo->prepare("
        SELECT 
            e.enquiry_id,
            e.user_id,
            e.station_id,
            e.message,
            e.enquiry_date,
            e.status,
            e.response,
            e.response_date,
            cs.name as station_name,
            u.name as user_name,
            u.email as user_email,
            u.phone_number as user_phone
        FROM 
            enquiries e
        JOIN 
            charging_stations cs ON e.station_id = cs.station_id
        JOIN 
            tbl_users u ON e.user_id = u.user_id
        WHERE 
            cs.owner_name = ?
            $stationFilter
        ORDER BY 
            CASE 
                WHEN e.status = 'unread' THEN 1
                WHEN e.status = 'read' THEN 2
                WHEN e.status = 'responded' THEN 3
            END,
            e.enquiry_date DESC
    ");
    
    $params = [$_SESSION['owner_name']];
    if (!empty($stationFilterParam)) {
        $params = array_merge($params, $stationFilterParam);
    }
    
    $stmt->execute($params);
    $enquiries = $stmt->fetchAll();
    
    // Count unread enquiries
    $unreadCount = 0;
    foreach ($enquiries as $enquiry) {
        if ($enquiry['status'] === 'unread') {
            $unreadCount++;
        }
    }
    
} catch (PDOException $e) {
    // Enhance error logging with more details
    error_log("Database error in view-enquiries.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    // Display more specific error message (only during development)
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    error_log("General error in view-enquiries.php: " . $e->getMessage());
    $error = "An error occurred while loading enquiries. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Enquiries - Station Owner Dashboard</title>
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
        
        /* Cards and sections */
        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        /* Enquiry styles */
        .enquiry-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .enquiry-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .enquiry-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .enquiry-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .enquiry-body {
            padding: 1.5rem;
        }
        
        .enquiry-message {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .enquiry-response {
            background: #e8f4f8;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .enquiry-unread {
            border-left: 4px solid var(--primary-color);
        }
        
        .enquiry-read {
            border-left: 4px solid var(--secondary-color);
        }
        
        .enquiry-responded {
            border-left: 4px solid var(--info-color);
        }
        
        .enquiry-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Badge styles */
        .badge-unread {
            background-color: var(--primary-color);
        }
        
        .badge-read {
            background-color: var(--secondary-color);
        }
        
        .badge-responded {
            background-color: var(--info-color);
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
            
            .user-info {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .main-header {
                padding: 10px 15px;
            }
            
            .section-card {
                padding: 15px;
            }
            
            .enquiry-header, .enquiry-body, .enquiry-footer {
                padding: 1rem;
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
                <a href="view-enquiries.php" class="sidebar-link active">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class='bx bx-message-detail'></i> 
                        Enquiries 
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-primary"><?php echo $unreadCount; ?> unread</span>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="d-flex align-items-center">
                        <form method="get" class="me-3">
                            <div class="input-group">
                                <select name="station_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Stations</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?php echo $station['station_id']; ?>" 
                                            <?php echo (isset($_GET['station_id']) && $_GET['station_id'] == $station['station_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($station['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class='bx bx-filter-alt'></i>
                                </button>
                            </div>
                        </form>
                        
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" id="filterAll">All</button>
                            <button type="button" class="btn btn-outline-primary" id="filterUnread">Unread</button>
                            <button type="button" class="btn btn-outline-primary" id="filterRead">Read</button>
                            <button type="button" class="btn btn-outline-primary" id="filterResponded">Responded</button>
                        </div>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($enquiries)): ?>
                    <div class="section-card text-center py-5">
                        <i class='bx bx-message-x' style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">No Enquiries Found</h4>
                        <p class="text-muted">You don't have any enquiries for your charging stations yet.</p>
                    </div>
                <?php else: ?>
                    <!-- Enquiries List -->
                    <div class="enquiries-container">
                        <?php foreach ($enquiries as $enquiry): ?>
                            <div class="card enquiry-card enquiry-<?php echo $enquiry['status']; ?>" 
                                 data-status="<?php echo $enquiry['status']; ?>">
                                <div class="enquiry-header">
                                    <div>
                                        <h5 class="mb-0">
                                            <span class="badge badge-<?php echo $enquiry['status']; ?> bg-<?php 
                                                echo $enquiry['status'] === 'unread' ? 'primary' : 
                                                    ($enquiry['status'] === 'read' ? 'success' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($enquiry['status']); ?>
                                            </span>
                                            <?php echo htmlspecialchars($enquiry['station_name']); ?>
                                        </h5>
                                        <div class="enquiry-date">
                                            <small>
                                                <i class='bx bx-calendar'></i> 
                                                <?php echo date('F j, Y, g:i a', strtotime($enquiry['enquiry_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class='bx bx-dots-vertical-rounded'></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if ($enquiry['status'] !== 'unread'): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="?mark=unread&id=<?php echo $enquiry['enquiry_id']; ?>">
                                                            <i class='bx bx-envelope'></i> Mark as Unread
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($enquiry['status'] !== 'read'): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="?mark=read&id=<?php echo $enquiry['enquiry_id']; ?>">
                                                            <i class='bx bx-envelope-open'></i> Mark as Read
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="enquiry-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h6><i class='bx bx-user'></i> From:</h6>
                                            <p class="mb-0"><?php echo htmlspecialchars($enquiry['user_name']); ?></p>
                                            <p class="mb-0">
                                                <i class='bx bx-envelope'></i> 
                                                <?php echo htmlspecialchars($enquiry['user_email']); ?>
                                            </p>
                                            <?php if (!empty($enquiry['user_phone'])): ?>
                                                <p class="mb-0">
                                                    <i class='bx bx-phone'></i> 
                                                    <?php echo htmlspecialchars($enquiry['user_phone']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class='bx bx-map-pin'></i> Station:</h6>
                                            <p><?php echo htmlspecialchars($enquiry['station_name']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="enquiry-message">
                                        <h6><i class='bx bx-message-square-detail'></i> Message:</h6>
                                        <p><?php echo nl2br(htmlspecialchars($enquiry['message'])); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($enquiry['response'])): ?>
                                        <div class="enquiry-response">
                                            <h6><i class='bx bx-message-square-check'></i> Your Response:</h6>
                                            <p><?php echo nl2br(htmlspecialchars($enquiry['response'])); ?></p>
                                            <small class="text-muted">
                                                Sent on <?php echo date('F j, Y, g:i a', strtotime($enquiry['response_date'])); ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <form method="post" action="" class="mt-3 response-form">
                                            <input type="hidden" name="enquiry_id" value="<?php echo $enquiry['enquiry_id']; ?>">
                                            <div class="mb-3">
                                                <label for="response-<?php echo $enquiry['enquiry_id']; ?>" class="form-label">
                                                    <i class='bx bx-reply'></i> Your Response:
                                                </label>
                                                <textarea class="form-control" id="response-<?php echo $enquiry['enquiry_id']; ?>" 
                                                          name="response" rows="3" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class='bx bx-send'></i> Send Response
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="enquiry-footer">
                                    <small class="text-muted">
                                        Enquiry ID: <?php echo $enquiry['enquiry_id']; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
            
            // Filter functionality
            const filterAll = document.getElementById('filterAll');
            const filterUnread = document.getElementById('filterUnread');
            const filterRead = document.getElementById('filterRead');
            const filterResponded = document.getElementById('filterResponded');
            const enquiryCards = document.querySelectorAll('.enquiry-card');
            
            function filterEnquiries(status) {
                enquiryCards.forEach(card => {
                    if (status === 'all' || card.dataset.status === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Update active button
                [filterAll, filterUnread, filterRead, filterResponded].forEach(btn => {
                    btn.classList.remove('active', 'btn-primary', 'text-white');
                    btn.classList.add('btn-outline-primary');
                });
                
                const activeBtn = {
                    'all': filterAll,
                    'unread': filterUnread,
                    'read': filterRead,
                    'responded': filterResponded
                }[status];
                
                activeBtn.classList.remove('btn-outline-primary');
                activeBtn.classList.add('active', 'btn-primary', 'text-white');
                
                // Maintain the station filter in URL when using status filters
                let currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('status');
                if (status !== 'all') {
                    currentUrl.searchParams.set('status', status);
                }
                history.replaceState(null, '', currentUrl);
            }
            
            filterAll.addEventListener('click', () => filterEnquiries('all'));
            filterUnread.addEventListener('click', () => filterEnquiries('unread'));
            filterRead.addEventListener('click', () => filterEnquiries('read'));
            filterResponded.addEventListener('click', () => filterEnquiries('responded'));
            
            // Set 'All' as active by default
            filterAll.classList.remove('btn-outline-primary');
            filterAll.classList.add('active', 'btn-primary', 'text-white');
            
            // Check URL for predefined status filter
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            if (statusParam) {
                filterEnquiries(statusParam);
            }
        });
    </script>
</body>
</html> 