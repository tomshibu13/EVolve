<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a station owner
if (!isset($_SESSION['user_id'])) {
    header("Location: stationlogin.php");
    exit();
}

// Initialize stations array
$stations = [];

// Verify if the user is a station owner
try {
    $stmt = $mysqli->prepare("
        SELECT sor.owner_name, sor.status 
        FROM station_owner_requests sor 
        WHERE sor.user_id = ? AND sor.status = 'approved'
    ");
    
    if ($stmt === false) {
        throw new Exception($mysqli->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: stationlogin.php");
        exit();
    }
    
    $owner_data = $result->fetch_assoc();
    $_SESSION['owner_name'] = $owner_data['owner_name'];
    
    // Now fetch the stations with simplified query
    $stmt = $mysqli->prepare("
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
            GROUP_CONCAT(DISTINCT CONCAT(u.name, '|', u.email, '|', 
                (SELECT COUNT(*) FROM bookings b3 WHERE b3.user_id = u.user_id AND b3.station_id = s.station_id)
            ) SEPARATOR ';;') as user_details
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

    if ($stmt === false) {
        throw new Exception($mysqli->error);
    }
    
    $stmt->bind_param("s", $_SESSION['owner_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }
} catch (Exception $e) {
    $error = "Error fetching stations: " . $e->getMessage();
}

// Calculate dashboard metrics
$total_stations = count($stations);
$active_stations = array_filter($stations, fn($s) => $s['status'] === 'active');
$total_active_stations = count($active_stations);
$total_slots = array_sum(array_column($stations, 'total_slots'));
$available_slots = array_sum(array_column($stations, 'available_slots'));
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
        
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
        
        .sidebar {
            background: linear-gradient(180deg, #4e73df 0%, #224abe 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            border-radius: 8px;
            margin: 0 15px;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            border: none;
        }
        
        .btn-info {
            background-color: var(--info-color);
            border: none;
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border: none;
            color: #2e2e2e;
        }
        
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
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .btn-group .btn {
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 76px;
            background-color: #343a40;
            width: 250px;
            transition: 0.3s;
            z-index: 999;
        }
        .sidebar-link {
            color: #fff;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            background-color: #495057;
            color: #fff;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            transition: 0.3s;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            padding: 0 15px;
        }
        .row {
            margin-right: 0;
            margin-left: 0;
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .container {
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
<?php
include 'header.php';
?>

    <div class="sidebar" id="sidebar">
        <a href="station-owner-dashboard.php" class="sidebar-link active">
            <i class='bx bx-home'></i> Dashboard
        </a>
        <a href="so_add_station.php" class="sidebar-link">
            <i class='bx bx-plus-circle'></i> Add Station
        </a>
        <a href="manage-bookings.php" class="sidebar-link">
            <i class='bx bx-calendar'></i> Manage Bookings
        </a>
        <a href="station_owner/so_profile.php" class="sidebar-link">
            <i class='bx bx-calendar'></i> Profile
        </a>
        <a href="reports.php" class="sidebar-link">
            <i class='bx bx-line-chart'></i> Reports
        </a>
        <a href="settings.php" class="sidebar-link">
            <i class='bx bx-cog'></i> Settings
        </a>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Dashboard Summary -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card dashboard-card bg-primary text-white">
                        <div class="card-body">
                            <h6><i class='bx bx-station'></i> Total Stations</h6>
                            <h2><?php echo $total_stations; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body">
                            <h6><i class='bx bx-check-circle'></i> Active Stations</h6>
                            <h2><?php echo $total_active_stations; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card bg-info text-white">
                        <div class="card-body">
                            <h6><i class='bx bx-plug'></i> Total Slots</h6>
                            <h2><?php echo $total_slots; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card bg-warning text-white">
                        <div class="card-body">
                            <h6><i class='bx bx-battery'></i> Available Slots</h6>
                            <h2><?php echo $available_slots; ?></h2>
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

            <div class="row g-4">
                <?php foreach ($stations as $station): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card station-card">
                            <div class="status-badge">
                                <span class="badge <?php echo $station['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo ucfirst($station['status']); ?>
                                </span>
                            </div>
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
                                            <i class='bx bx-dollar'></i> <strong>Price:</strong> 
                                            $<?php echo number_format($station['price'], 2); ?>/kWh<br>
                                            <i class='bx bx-plug'></i> <strong>Slots:</strong> 
                                            <?php echo $station['available_slots']; ?>/<?php echo $station['total_slots']; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            <i class='bx bx-calendar'></i> <strong>Today's Bookings:</strong> 
                                            <?php echo $station['today_bookings']; ?><br>
                                            <i class='bx bx-check-double'></i> <strong>Total Bookings:</strong> 
                                            <?php echo $station['total_bookings']; ?><br>
                                            <i class='bx bx-time'></i> <strong>Completion Rate:</strong>
                                            <?php echo $station['total_bookings'] ? 
                                                round(($station['completed_bookings'] / $station['total_bookings']) * 100) : 0; ?>%
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h6><i class='bx bx-user-circle'></i> Recent Users:</h6>
                                    <?php 
                                    if ($station['user_details']) {
                                        $users = explode(';;', $station['user_details']);
                                        echo '<div class="table-responsive"><table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Total Bookings</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                        foreach ($users as $user) {
                                            list($name, $email, $bookings) = explode('|', $user);
                                            if ($name && $email) {
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($name) . "</td>
                                                        <td>" . htmlspecialchars($email) . "</td>
                                                        <td>$bookings</td>
                                                    </tr>";
                                            }
                                        }
                                        echo '</tbody></table></div>';
                                    } else {
                                        echo '<p class="text-muted">No users have booked this station yet.</p>';
                                    }
                                    ?>
                                </div>
                                <div class="btn-group w-100">
                                    <a href="edit-station.php?id=<?php echo $station['station_id']; ?>" 
                                       class="btn btn-primary">
                                        <i class='bx bx-edit'></i> Edit
                                    </a>
                                    <a href="view-bookings.php?station_id=<?php echo $station['station_id']; ?>" 
                                       class="btn btn-info">
                                        <i class='bx bx-calendar'></i> Bookings
                                    </a>
                                    <button class="btn btn-warning toggle-status" 
                                            data-station-id="<?php echo $station['station_id']; ?>">
                                        <i class='bx bx-power-off'></i>
                                        <?php echo $station['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('active');
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
    </script>
</body>
</html>
