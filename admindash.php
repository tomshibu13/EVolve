<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) { // Assuming 'user_id' is set upon login
    header("Location: index.php#LoginForm"); // Redirect to the login page
    exit();
}

// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

// Initialize variables with default values
$total_stations = 0;
$active_users = 0;
$bookings = 0;
$total_revenue = 0;
$recent_stations = null;
$admin_user = null;
$station_owner_requests_count = 0; // New variable for station owner requests count

// Create a connection with error handling using try-catch
try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Check if tables exist
    $tables_exist = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = '$dbname' 
        AND table_name IN ('charging_stations', 'tbl_users')
    ");
    
    if ($tables_exist && $row = mysqli_fetch_assoc($tables_exist)) {
        if ($row['count'] == 2) {
            // Fetch dashboard statistics
            // Total Stations
            $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM charging_stations");
            if ($result) {
                $total_stations = mysqli_fetch_assoc($result)['total'];
            }

            // Active Users
            $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_users");
            if ($result) {
                $active_users = mysqli_fetch_assoc($result)['total'];
            }

            // Total Bookings
            $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM bookings");
            if ($result) {
                $bookings = mysqli_fetch_assoc($result)['total'];
            }

            // Debug: Check table structure
            $debug_table = mysqli_query($conn, "DESCRIBE charging_stations");
            if ($debug_table) {
                echo "<!-- Table columns: -->";
                while ($column = mysqli_fetch_assoc($debug_table)) {
                    echo "<!-- Column: " . $column['Field'] . " -->";
                }
            } else {
                echo "<!-- Error checking table structure: " . mysqli_error($conn) . " -->";
            }

            // Fetch recent stations
            $recent_stations = mysqli_query($conn, "
                SELECT 
                    cs.station_id,
                    cs.name,
                    cs.owner_name as owner_name,
                    cs.price as station_price,
                    cs.address,
                    cs.status,
                    cs.operator_id,
                    cs.created_at,
                    u.name as operator_name,
                    u.user_id
                FROM charging_stations cs 
                LEFT JOIN tbl_users u ON cs.operator_id = u.user_id 
                ORDER BY cs.created_at DESC 
                LIMIT 5
            ");
            
            if (!$recent_stations) {
                throw new Exception("Error fetching recent stations: " . mysqli_error($conn));
            } else {
                echo "<!-- Debug: Query successful -->";
                while ($row = mysqli_fetch_assoc($recent_stations)) {
                    echo "<!-- Debug: Operator name: " . ($row['operator_name'] ?? 'null') . " -->";
                }
                // Reset the result pointer
                mysqli_data_seek($recent_stations, 0);
            }

            // Debug query to see column names
            $debug_columns = mysqli_query($conn, "
                SHOW COLUMNS FROM charging_stations
            ");
            if ($debug_columns) {
                echo "<!-- Database columns: -->";
                while ($column = mysqli_fetch_assoc($debug_columns)) {
                    echo "<!-- " . $column['Field'] . " -->";
                }
            }

            // Update the admin query to use is_admin instead of role
            $admin_query = mysqli_query($conn, "
                SELECT name, profile_picture 
                FROM tbl_users 
                WHERE is_admin = TRUE 
                LIMIT 1
            ");
            if ($admin_query && mysqli_num_rows($admin_query) > 0) {
                $admin_user = mysqli_fetch_assoc($admin_query);
            }

            // Fetch station owner requests count
            $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM station_owner_requests");
            if ($result) {
                $station_owner_requests_count = mysqli_fetch_assoc($result)['total'];
            }
        } else {
            throw new Exception("Required tables do not exist. Please run create_database.php first.");
        }
    } else {
        throw new Exception("Error checking tables: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}



// Note: Don't close the connection here as we need it for the HTML section
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVolve Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #2C3E50;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #2d3436;
            --border-radius: 10px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            transition: all 0.3s ease;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .logo i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .logo-text {
            font-size: 20px;
            font-weight: bold;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--primary-color);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: var(--card-bg);
            padding: 10px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .search-bar input {
            border: none;
            outline: none;
            padding: 5px 10px;
            font-size: 16px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-title {
            font-size: 14px;
            color: #666;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .card-change {
            font-size: 14px;
            color: var(--primary-color);
        }

        /* Tables */
        .table-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 600;
            color: #666;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .status.active {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .status.inactive {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: #2196f3;
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .profile-dropdown {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--card-bg);
            min-width: 160px;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            z-index: 1;
            margin-top: 10px;
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content a {
            color: var(--text-color);
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dropdown-content a:hover {
            background-color: var(--background-color);
        }

        .dropdown-content i {
            width: 20px;
        }

        .home-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: background-color 0.3s ease;
        }

        .home-btn:hover {
            background-color: #3d8b40;
        }
    </style>
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
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="charging_stations.php" class="nav-link">
                        <i class="fas fa-charging-station"></i>
                        <span>Charging Stations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="user_list.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="station_owner_details.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Station Owner Details</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="bookings.php" class="nav-link">
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
                <div style="display: flex; gap: 20px; align-items: center;">
                    <a href="index.php" class="home-btn">
                        <i class="fas fa-home"></i>
                        Home
                    </a>
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                </div>
                <div class="user-profile">
                    <div class="profile-dropdown" onclick="toggleDropdown()">
                        <span><?php echo $admin_user ? htmlspecialchars($admin_user['name']) : '$username'; ?></span>
                        <img src="<?php echo $admin_user && $admin_user['profile_picture'] ? htmlspecialchars($admin_user['profile_picture']) : 'https://via.placeholder.com/40'; ?>" 
                             alt="Admin Profile">
                        <div class="dropdown-content" id="profileDropdown">
                            <a href="editprofile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                            <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
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
                    <div class="card-value"><?php echo $total_stations; ?></div>
                    <!-- <div class="card-change">+12% from last month</div> -->
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-title">Active Users</div>
                    <div class="card-value"><?php echo $active_users; ?></div>
                    <!-- <div class="card-change">+8% from last month</div> -->
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                    </div>
                    <div class="card-title">Total Bookings</div>
                    <div class="card-value"><?php echo $bookings; ?></div>
                    <!-- <div class="card-change">+15% from last month</div> -->
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-user-plus"></i> <!-- New icon for station owner requests -->
                        </div>
                    </div>
                    <div class="card-title">Station Owner Requests</div> <!-- New title -->
                    <div class="card-value"><?php echo $station_owner_requests_count; ?></div> <!-- Displaying the count -->
                </div>
            </div>

            <!-- Recent Stations Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Recent Stations</h2>
                    <a href="add_station_page.php" class="action-btn edit-btn">Add New Station</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Station Name</th>
                            <th>Location</th>
                            <th>Operator</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($recent_stations && mysqli_num_rows($recent_stations) > 0):
                            while($station = mysqli_fetch_assoc($recent_stations)): 
                                // Add debug output
                                echo "<!-- Debug: Station data: " . print_r($station, true) . " -->";
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($station['name']); ?></td>
                                <td><?php echo htmlspecialchars($station['address']); ?></td>
                                <td><?php echo htmlspecialchars($station['owner_name']); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower($station['status']); ?>">
                                        <?php echo ucfirst($station['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No stations found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    function toggleDropdown() {
        document.getElementById("profileDropdown").classList.toggle("show");
    }

    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        if (!event.target.matches('.profile-dropdown, .profile-dropdown *')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }

    function toggleStatus(stationId, currentStatus) {
        const newStatus = currentStatus.toLowerCase() === 'active' ? 'Inactive' : 'Active';
        if (confirm(`Are you sure you want to change the status to ${newStatus}?`)) {
            // Create a form and submit it with POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'toggle_station_status.php';
            
            // Add station ID input
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'station_id';
            idInput.value = stationId;
            form.appendChild(idInput);
            
            // Add status input
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = newStatus;
            form.appendChild(statusInput);
            
            // Append form to body and submit
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>