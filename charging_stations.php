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

// Initialize variables
$stations = null;
$error_message = '';

try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Fetch all charging stations with operator information
    $query = "
        SELECT 
            cs.*,
            u.username as operator_name,
            ST_X(cs.location) as latitude,
            ST_Y(cs.location) as longitude
        FROM charging_stations cs 
        LEFT JOIN tbl_users u ON cs.operator_id = u.user_id 
        ORDER BY cs.created_at DESC
    ";
    
    $stations = mysqli_query($conn, $query);
    
    if (!$stations) {
        throw new Exception("Error fetching stations: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charging Stations Management - EVolve Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Updated Root Variables */
        :root {
            --primary-color: #4CAF50;    /* Green */
            --secondary-color: #2C3E50;  /* Dark Blue */
            --background-color: #f5f6fa; /* Light Gray */
            --card-bg: #ffffff;          /* White */
            --text-color: #2d3436;       /* Dark Gray */
            --border-radius: 10px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Increased shadow for depth */
        }

        /* Reset Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Base Layout */
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

        /* Navigation Menu */
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

        .nav-link:hover, 
        .nav-link.active {
            background-color: var(--primary-color);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        /* Updated Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 10px 0; /* Added padding for better spacing */
            border-bottom: 2px solid var(--primary-color); /* Added bottom border */
        }

        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-color); /* Text color */
        }

        .add-station-btn {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px; /* Added padding */
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .add-station-btn:hover {
            background-color: #3d8b40; /* Darker green on hover */
        }

        /* Updated Error Message Styles */
        .error-message {
            background-color: #f8d7da; /* Light red background */
            color: #721c24; /* Dark red text */
            padding: 10px; /* Added padding */
            border: 1px solid #f5c6cb; /* Border color */
            border-radius: var(--border-radius);
            margin-bottom: 20px; /* Space below the error message */
        }

        /* Updated Filters Styles */
        .filters {
            display: flex;
            align-items: center;
            gap: 10px; /* Space between inputs */
            margin-bottom: 20px; /* Space below filters */
        }

        .search-input {
            border: 1px solid #ccc; /* Border for search input */
            border-radius: var(--border-radius);
            padding: 8px; /* Padding for search input */
            font-size: 16px;
            width: 200px; /* Fixed width for search input */
        }

        .filter-select {
            padding: 8px; /* Padding for select */
            border-radius: var(--border-radius);
            border: 1px solid #ccc; /* Border for select */
            font-size: 16px; /* Font size for select */
        }

        /* Updated Search Bar */
        .search-bar {
            display: flex;
            align-items: center;
            background-color: var(--card-bg);
            padding: 12px; /* Increased padding */
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px; /* Space below the search bar */
        }

        .search-bar input {
            border: 1px solid #ccc; /* Added border */
            border-radius: var(--border-radius);
            padding: 8px; /* Increased padding */
            font-size: 16px;
            width: 100%; /* Full width */
            margin-right: 10px; /* Space between input and select */
        }

        .search-bar select {
            padding: 8px; /* Padding for select */
            border-radius: var(--border-radius);
            border: 1px solid #ccc; /* Added border */
        }

        /* User Profile */
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

        /* Updated Table Styles */
        .table-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: var(--border-radius);
            overflow: hidden; /* Rounded corners for the table */
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s; /* Smooth background transition */
        }

        tr:hover {
            background-color: rgba(76, 175, 80, 0.1); /* Highlight row on hover */
        }

        /* Updated Status Indicators */
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold; /* Bold text for status */
        }

        .status.active {
            background-color: rgba(76, 175, 80, 0.3); /* Lighter green */
            color: var(--primary-color);
        }

        .status.inactive {
            background-color: rgba(244, 67, 54, 0.3); /* Lighter red */
            color: #f44336;
        }

        /* Updated Action Buttons */
        .action-btn {
            padding: 8px 12px; /* Increased padding for better touch targets */
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold; /* Bold text for better visibility */
        }

        .edit-btn {
            background-color: #2196f3;
            color: white;
        }

        .edit-btn:hover {
            background-color: #1e88e5; /* Darker blue on hover */
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background-color: #e53935; /* Darker red on hover */
        }

        /* Dropdown Menu */
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

        /* Home Button */
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
        <!-- Sidebar (same as admindash.php) -->
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
                    <a href="charging_stations.php" class="nav-link active">
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
                <!-- Added Back Button -->
                <a href="admindash.php" class="home-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
                <h1 class="page-title">Charging Stations Management</h1>
                <a href="add_station_page.php" class="add-station-btn">
                    <i class="fas fa-plus"></i>
                    Add New Station
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="filters">
                <input type="text" class="search-input" placeholder="Search stations...">
                <select class="filter-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="stations-table">
                <table>
                    <thead>
                        <tr>
                            <th>Station Name</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Operator</th>
                            <th>Status</th>
                            <th>Charger Types</th>
                            <th>Slots (Available/Total)</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($stations && mysqli_num_rows($stations) > 0):
                            while($station = mysqli_fetch_assoc($stations)): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($station['name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($station['owner_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($station['address'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($station['operator_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($station['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($station['status'])); ?>
                                    </span>
                                </td>
                                <td><?php 
                                    $charger_types = $station['charger_types'];
                                    echo !empty($charger_types) ? htmlspecialchars($charger_types) : 'N/A';
                                ?></td>
                                <td><?php echo $station['available_slots'] . '/' . $station['total_slots']; ?></td>
                                <td>₹<?php echo number_format($station['price'], 2); ?></td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="window.location.href='edit_station.php?id=<?php echo htmlspecialchars($station['station_id']); ?>'">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn <?php echo strtolower($station['status']) === 'active' ? 'delete-btn' : 'edit-btn'; ?>" 
                                            onclick="toggleStatus(<?php echo htmlspecialchars($station['station_id']); ?>, '<?php echo $station['status']; ?>')">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No charging stations found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function confirmDelete(stationId) {
            if (confirm('Are you sure you want to delete this charging station?')) {
                window.location.href = 'delete_station.php?id=' + stationId;
            }
        }

        // Updated status filter functionality
        document.querySelector('.filter-select').addEventListener('change', function(e) {
            const filterValue = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (!filterValue) {
                    row.style.display = '';
                    return;
                }
                
                const statusBadge = row.querySelector('.status-badge');
                const status = statusBadge ? statusBadge.textContent.trim().toLowerCase() : '';
                row.style.display = status === filterValue ? '' : 'none';
            });
        });

        // Enhanced search functionality
        document.querySelector('.search-input').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let found = false;
                
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchText)) {
                        found = true;
                    }
                });
                
                row.style.display = found ? '' : 'none';
            });
        });

        // Updated status toggle function to use POST method
        function toggleStatus(stationId, currentStatus) {
            const newStatus = currentStatus.toLowerCase() === 'active' ? 'Inactive' : 'Active';
            if (confirm(`Are you sure you want to change the status to ${newStatus}?`)) {
                const button = event.target.closest('.action-btn');
                const originalContent = button.innerHTML;
                
                // Show loading indicator
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;
                
                // Create form data
                const formData = new FormData();
                formData.append('station_id', stationId);
                formData.append('status', newStatus);
                
                // Make POST request
                fetch('toggle_station_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Refresh the page to show updated status
                        location.reload();
                    } else {
                        throw new Error(data.error || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error: ' + error.message);
                    // Restore button state
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
            }
        }
    </script>
</body>
</html> 