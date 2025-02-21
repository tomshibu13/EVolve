<?php
// Add this near the top of the file, after database connection
if (!isset($_SESSION['user_id'])) {
    // Redirect to index.php with a parameter to show login modal
    header("Location: index.php?showLogin=true");
    exit();
}

// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

// Initialize variables
$bookings = null;
$admin_user = null;

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Fetch bookings with user and station details
    $bookings = mysqli_query($conn, "
        SELECT 
            b.booking_id,
            b.user_id,
            b.station_id,
            b.booking_date,
            b.status,
            b.created_at,
            u.name as user_name,
            cs.name as station_name,
            cs.address as station_address
        FROM bookings b
        LEFT JOIN tbl_users u ON b.user_id = u.user_id
        LEFT JOIN charging_stations cs ON b.station_id = cs.station_id
        ORDER BY b.created_at DESC
    ");

    if (!$bookings) {
        throw new Exception("Error fetching bookings: " . mysqli_error($conn));
    }

    // Fetch admin user details
    $admin_query = mysqli_query($conn, "
        SELECT name, profile_picture 
        FROM tbl_users 
        WHERE is_admin = TRUE 
        LIMIT 1
    ");
    
    if ($admin_query && mysqli_num_rows($admin_query) > 0) {
        $admin_user = mysqli_fetch_assoc($admin_query);
    }

    // Add this PHP code near the top, after the existing try-catch block
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
        try {
            $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
            $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
            
            $update_query = "UPDATE bookings SET status = '$new_status' WHERE booking_id = '$booking_id'";
            if (!mysqli_query($conn, $update_query)) {
                throw new Exception("Error updating booking status: " . mysqli_error($conn));
            }
            
            // Redirect to refresh the page
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - EVolve Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Use the same CSS as admindash.php -->
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
            color: white;
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
            text-decoration: none;
            color: white;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: var(--primary-color);
            color: white;
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
            padding: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .search-bar {
            display: flex;
            align-items: center;
            background-color: var(--card-bg);
            padding: 8px 15px;
            border-radius: 20px;
            width: 300px;
            box-shadow: var(--shadow);
        }

        .search-bar input {
            border: none;
            background: none;
            outline: none;
            margin-left: 10px;
            width: 100%;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-dropdown {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .profile-dropdown img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--card-bg);
            min-width: 160px;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            z-index: 1;
        }

        .dropdown-content.show {
            display: block;
        }

        .dropdown-content a {
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            color: var(--text-color);
        }

        .dropdown-content a:hover {
            background-color: var(--background-color);
        }

        /* Table Styles */
        .table-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .table-header {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        /* Button Styles */
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .edit-btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }

        .status-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 14px;
            cursor: pointer;
            min-width: 150px;
        }

        .status-select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
        }

        .status-select option {
            padding: 8px;
        }

        .status-select option:disabled {
            color: #999;
            background-color: #f5f5f5;
        }

        .fas.fa-eye {
            margin-right: 4px;
        }

        .home-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .home-btn:hover {
            opacity: 0.9;
        }

        /* Status Badge Styles */
        .booking-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            display: inline-block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                margin-bottom: 20px;
            }

            .search-bar {
                width: 100%;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Additional styles for bookings page */
        .booking-status.pending {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }
        
        .booking-status.completed {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4caf50;
        }
        
        .booking-status.cancelled {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-select {
            padding: 8px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            outline: none;
        }

        .status-select {
            padding: 6px;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            margin-left: 5px;
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
                    <a href="admindash.php" class="nav-link">
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
                    <a href="bookings.php" class="nav-link active">
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
                        <input type="text" placeholder="Search bookings...">
                    </div>
                </div>
                <div class="user-profile">
                    <div class="profile-dropdown" onclick="toggleDropdown()">
                        <span><?php echo $admin_user ? htmlspecialchars($admin_user['name']) : 'Admin'; ?></span>
                        <img src="<?php echo $admin_user && $admin_user['profile_picture'] ? htmlspecialchars($admin_user['profile_picture']) : 'https://via.placeholder.com/40'; ?>" 
                             alt="Admin Profile">
                        <div class="dropdown-content" id="profileDropdown">
                            <a href="editprofile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>Booking Management</h2>
                </div>
                <div class="filters">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select class="filter-select" id="dateFilter">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Station</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($bookings && mysqli_num_rows($bookings) > 0):
                            while($booking = mysqli_fetch_assoc($bookings)): 
                        ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['station_name']); ?><br>
                                    <small><?php echo htmlspecialchars($booking['station_address']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d', strtotime($booking['booking_date'])); ?>
                                </td>
                                <td>
                                    <?php echo date('H:i', strtotime($booking['booking_date'])); ?>
                                </td>
                                <td>
                                    <span class="status booking-status <?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <select class="status-select" onchange="updateStatus(<?php echo $booking['booking_id']; ?>, this.value)">
                                        <option value="">Change Status</option>
                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'disabled' : ''; ?>>Pending</option>
                                        <option value="completed" <?php echo $booking['status'] === 'completed' ? 'disabled' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'disabled' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No bookings found</td>
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

    function viewBookingDetails(bookingId) {
        // Implement viewing booking details
        alert('View booking details for ID: ' + bookingId);
    }

    function updateStatus(bookingId, newStatus) {
        if (!newStatus) return; // Don't proceed if no status is selected
        
        if (confirm('Are you sure you want to update this booking status to ' + newStatus + '?')) {
            // Create a form element
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo $_SERVER["PHP_SELF"]; ?>'; // Add explicit form action
            form.style.display = 'none';

            // Create booking ID input
            const bookingInput = document.createElement('input');
            bookingInput.type = 'hidden'; // Make input hidden
            bookingInput.name = 'booking_id';
            bookingInput.value = bookingId;

            // Create status input
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden'; // Make input hidden
            statusInput.name = 'new_status';
            statusInput.value = newStatus;

            // Append inputs to form
            form.appendChild(bookingInput);
            form.appendChild(statusInput);
            
            // Append form to document and submit
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Add filter functionality
    document.getElementById('statusFilter').addEventListener('change', function() {
        // Implement status filtering
    });

    document.getElementById('dateFilter').addEventListener('change', function() {
        // Implement date filtering
    });
    </script>
</body>
</html> 