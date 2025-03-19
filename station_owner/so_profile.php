<?php
session_start();
require_once '../config.php';  // Adjust path as needed

// Check if station owner is logged in
if (!isset($_SESSION['owner_name'])) {
    header("Location: ../stationlogin.php");
    exit();
}

$owner_name = $_SESSION['owner_name'];
$error = null;
$success = null;
$owner = null;

try {
    // Fetch station owner details
    $stmt = $pdo->prepare("
        SELECT 
            r.*
        FROM station_owner_requests r
        WHERE r.owner_name = ? AND r.status = 'approved'
        LIMIT 1
    ");
    $stmt->execute([$owner_name]);
    $owner = $stmt->fetch();

    if (!$owner) {
        header("Location: ../stationlogin.php");
        exit();
    }

    // Handle profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $business_name = trim($_POST['business_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $postal_code = trim($_POST['postal_code']);

        // Update profile
        $updateStmt = $pdo->prepare("
            UPDATE station_owner_requests 
            SET 
                business_name = ?,
                email = ?,
                phone = ?,
                address = ?,
                city = ?,
                state = ?,
                postal_code = ?
            WHERE owner_name = ?
        ");

        $updateStmt->execute([
            $business_name,
            $email,
            $phone,
            $address,
            $city,
            $state,
            $postal_code,
            $owner_name
        ]);

        $success = "Profile updated successfully!";
        
        // Refresh owner data
        $stmt->execute([$owner_name]);
        $owner = $stmt->fetch();
    }

} catch (PDOException $e) {
    $error = "An error occurred: " . $e->getMessage();
    error_log($error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Owner Profile - EVolve</title>
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

        /* Header and user info styles */
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
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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
                <a href="../manage-booking.php" class="sidebar-link">
                    <i class='bx bx-calendar'></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="payment_analytics.php" class="sidebar-link">
                    <i class='bx bx-money'></i>
                    <span>Payment Analytics</span>
                </a>
                <a href="so_profile.php" class="sidebar-link active">
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
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['owner_name'] ?? ''); ?></div>
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
            
            <!-- Profile Content -->
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class='bx bx-user-circle'></i> Profile Settings</h2>
                    <a href="../station-owner-dashboard.php" class="btn btn-primary">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="profile-card">
                    <form id="profileForm" method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($owner['owner_name']); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" class="form-control" name="business_name" value="<?php echo htmlspecialchars($owner['business_name']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($owner['email']); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($owner['phone']); ?>">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($owner['address']); ?></textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($owner['city']); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($owner['state']); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="postal_code" value="<?php echo htmlspecialchars($owner['postal_code']); ?>">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary" id="saveButton" disabled>
                                <i class='bx bx-save'></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.history.back();">
                                <i class='bx bx-arrow-back'></i> Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            const saveButton = document.getElementById('saveButton');
            const initialData = new FormData(form);

            // Form change detection
            form.addEventListener('input', function() {
                const currentData = new FormData(form);
                let isChanged = false;

                for (let [key, value] of currentData.entries()) {
                    if (value !== initialData.get(key)) {
                        isChanged = true;
                        break;
                    }
                }

                saveButton.disabled = !isChanged;
            });
            
            // Toggle sidebar on mobile
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('active');
            });
        });
    </script>
</body>
</html>