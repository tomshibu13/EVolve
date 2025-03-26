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
$reviews = [];
$selectedStationId = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 0;
$error = null;
$success = null;

// First, ensure the station_reviews table has owner_response columns
try {
    // Add owner_response and response_date columns if they don't exist
    $pdo->exec("
        ALTER TABLE station_reviews 
        ADD COLUMN IF NOT EXISTS owner_response TEXT NULL,
        ADD COLUMN IF NOT EXISTS response_date DATETIME NULL
    ");
} catch (PDOException $e) {
    error_log("Error modifying reviews table: " . $e->getMessage());
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
    
    // Get all stations owned by this owner
    $stmtStations = $pdo->prepare("
        SELECT station_id, name 
        FROM charging_stations 
        WHERE owner_name = ?
        ORDER BY name
    ");
    $stmtStations->execute([$_SESSION['owner_name']]);
    $stations = $stmtStations->fetchAll();
    
    // Check if this is a response submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['response'])) {
        $reviewId = (int)$_POST['review_id'];
        $response = trim($_POST['response']);
        
        if (empty($response)) {
            $error = "Response cannot be empty.";
        } else {
            // Update the review with the response
            $stmtResponse = $pdo->prepare("
                UPDATE station_reviews 
                SET owner_response = ?, response_date = NOW() 
                WHERE id = ? AND 
                      station_id IN (SELECT station_id FROM charging_stations WHERE owner_name = ?)
            ");
            
            $stmtResponse->execute([$response, $reviewId, $_SESSION['owner_name']]);
            
            if ($stmtResponse->rowCount() > 0) {
                $success = "Your response has been submitted successfully.";
            } else {
                $error = "Failed to submit response. Please try again.";
            }
        }
    }
    
    // Fetch reviews for the selected station or all stations if none selected
    $reviewsQuery = "
        SELECT 
            sr.id,
            sr.station_id,
            cs.name as station_name,
            sr.user_id,
            u.name as user_name,
            u.email as user_email,
            sr.rating,
            sr.review_text,
            sr.owner_response,
            sr.response_date,
            sr.created_at,
            sr.updated_at
        FROM station_reviews sr
        JOIN charging_stations cs ON sr.station_id = cs.station_id
        JOIN tbl_users u ON sr.user_id = u.user_id
        WHERE cs.owner_name = ?
    ";
    
    $queryParams = [$_SESSION['owner_name']];
    
    if ($selectedStationId > 0) {
        $reviewsQuery .= " AND sr.station_id = ?";
        $queryParams[] = $selectedStationId;
    }
    
    $reviewsQuery .= " ORDER BY sr.created_at DESC";
    
    $stmtReviews = $pdo->prepare($reviewsQuery);
    $stmtReviews->execute($queryParams);
    $reviews = $stmtReviews->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while loading the reviews. Please try again later.";
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    $error = "An error occurred while loading the reviews. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Reviews - Owner Dashboard</title>
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

        /* Review Cards */
        .review-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid var(--primary-color);
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.25);
        }
        
        .rating {
            color: #f6c23e;
            font-size: 1.2rem;
        }
        
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: #6c757d;
        }
        
        .response-area {
            border-top: 1px solid #e9ecef;
            margin-top: 15px;
            padding-top: 15px;
        }
        
        .owner-response {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--secondary-color);
            margin-top: 10px;
        }
        
        /* Star Rating Display */
        .stars-container {
            display: inline-block;
            position: relative;
        }
        
        .stars-inactive {
            color: #e9ecef;
        }
        
        .stars-active {
            color: #f6c23e;
            overflow: hidden;
            position: absolute;
            top: 0;
            left: 0;
            white-space: nowrap;
        }
        
        /* Responsive */
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
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .sidebar-overlay.active {
                display: block;
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
                <a href="view-enquiries.php" class="sidebar-link">
                    <i class='bx bx-message-detail'></i>
                    <span>Enquiries</span>
                </a>
                <a href="view-reviews.php" class="sidebar-link active">
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
                <div class="d-flex align-items-center">
                    <button class="btn btn-link" id="sidebarToggle">
                        <i class='bx bx-menu fs-4'></i>
                    </button>
                    <h4 class="mb-0 ms-3">Station Reviews</h4>
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

            <!-- Content Container -->
            <div class="container-fluid py-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Station Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="view-reviews.php" method="get" class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <label for="station_id" class="form-label">Select Station</label>
                                <select name="station_id" id="station_id" class="form-select">
                                    <option value="0">All Stations</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?php echo $station['station_id']; ?>" <?php echo ($selectedStationId == $station['station_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($station['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-filter-alt'></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class='bx bx-star'></i> 
                                    <?php echo $selectedStationId > 0 ? 'Reviews for ' . htmlspecialchars(array_filter($stations, fn($s) => $s['station_id'] == $selectedStationId)[0]['name'] ?? '') : 'All Reviews'; ?>
                                </h5>
                                
                                <?php if (empty($reviews)): ?>
                                    <div class="alert alert-info">
                                        No reviews found for this station.
                                    </div>
                                <?php else: ?>
                                    <div class="reviews-container">
                                        <?php foreach ($reviews as $review): ?>
                                            <div class="review-card">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="station-name mb-0">
                                                        <i class='bx bx-car'></i> <?php echo htmlspecialchars($review['station_name']); ?>
                                                    </h6>
                                                    <div class="review-date">
                                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <i class='bx bx-user'></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                                        <div class="text-muted"><?php echo htmlspecialchars($review['user_email']); ?></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="rating mb-2">
                                                    <div class="stars-container">
                                                        <div class="stars-inactive">★★★★★</div>
                                                        <div class="stars-active" style="width: <?php echo ($review['rating'] / 5) * 100; ?>%">★★★★★</div>
                                                    </div>
                                                    <span class="ms-2"><?php echo $review['rating']; ?>/5</span>
                                                </div>
                                                
                                                <div class="review-content mb-3">
                                                    <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                                </div>
                                                
                                                <?php if (!empty($review['owner_response'])): ?>
                                                    <div class="response-area">
                                                        <div class="fw-bold mb-2">Your Response:</div>
                                                        <div class="owner-response">
                                                            <div class="mb-2 text-muted">
                                                                <?php echo date('M d, Y', strtotime($review['response_date'])); ?>
                                                            </div>
                                                            <?php echo nl2br(htmlspecialchars($review['owner_response'])); ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="response-area">
                                                        <button class="btn btn-sm btn-outline-primary mb-2" type="button" 
                                                                data-bs-toggle="collapse" data-bs-target="#response-form-<?php echo $review['id']; ?>">
                                                            <i class='bx bx-reply'></i> Respond to this review
                                                        </button>
                                                        
                                                        <div class="collapse" id="response-form-<?php echo $review['id']; ?>">
                                                            <form action="view-reviews.php<?php echo $selectedStationId > 0 ? '?station_id=' . $selectedStationId : ''; ?>" method="post">
                                                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="response-<?php echo $review['id']; ?>" class="form-label">Your Response:</label>
                                                                    <textarea class="form-control" id="response-<?php echo $review['id']; ?>" 
                                                                             name="response" rows="3" required></textarea>
                                                                </div>
                                                                <button type="submit" class="btn btn-primary">Submit Response</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
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
        });
    </script>
</body>
</html> 