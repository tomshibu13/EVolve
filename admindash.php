<?php
require_once 'dashboard_functions.php';

// Get dashboard data
$stats = getDashboardStats();
$recent_stations = getRecentStations();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVolve Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php include 'admindash.html'; // Include the CSS styles ?>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-charging-station"></i>
                <span class="logo-text">EVolve Admin</span>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admindash.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-charging-station"></i>
                        <span>Charging Stations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-bookmark"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search stations...">
                </div>
                <div class="user-profile">
                    <span>Admin User</span>
                    <img src="https://via.placeholder.com/40" alt="Admin Profile">
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-charging-station"></i>
                        </div>
                    </div>
                    <div class="card-title">Total Stations</div>
                    <div class="card-value"><?php echo $stats['total_stations']; ?></div>
                    <div class="card-change"><?php echo $stats['active_stations']; ?> Active Stations</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div class="card-title">Available Slots</div>
                    <div class="card-value"><?php 
                        $total_available = 0;
                        foreach ($recent_stations as $station) {
                            $total_available += $station['available_slots'];
                        }
                        echo $total_available;
                    ?></div>
                    <div class="card-change">Across All Stations</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                    </div>
                    <div class="card-title">Total Bookings</div>
                    <div class="card-value"><?php echo $stats['total_bookings']; ?></div>
                    <div class="card-change">Active Reservations</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="card-title">Revenue</div>
                    <div class="card-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                    <div class="card-change">Total Earnings</div>
                </div>
            </div>

            <!-- Recent Stations Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Recent Stations</h2>
                    <button class="action-btn edit-btn" onclick="location.href='add_station.php'">Add New Station</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Station Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Utilization</th>
                            <th>Wait Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_stations as $station): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($station['name']); ?></td>
                            <td><?php echo htmlspecialchars($station['address']); ?></td>
                            <td>
                                <span class="status <?php echo $station['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                    <?php echo ucfirst($station['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $utilization = calculateUtilization($station['total_slots'], $station['available_slots']);
                                    echo $utilization . '%';
                                ?>
                            </td>
                            <td><?php echo $station['estimated_wait_time'] . ' mins'; ?></td>
                            <td>
                                <button onclick="editStation(<?php echo $station['station_id']; ?>)" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteStation(<?php echo $station['station_id']; ?>)" class="action-btn delete-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    function editStation(stationId) {
        window.location.href = 'edit_station.php?id=' + stationId;
    }

    function deleteStation(stationId) {
        if (confirm('Are you sure you want to delete this station?')) {
            fetch('delete_station.php?id=' + stationId, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting station: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the station');
            });
        }
    }
    </script>
</body>
</html>
