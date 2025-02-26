<?php
session_start(); // Start the session

// Check if the user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php#LoginForm");
    exit();
}

// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

// Create a connection with error handling using try-catch
try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Fetch station owner requests with user details
    $query = "SELECT r.*, u.email as user_email, u.username 
              FROM station_owner_requests r 
              JOIN tbl_users u ON r.user_id = u.user_id 
              ORDER BY r.created_at DESC";
    
    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Error fetching station owner details: " . mysqli_error($conn));
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
    <title>Station Owner Details</title>
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

        .approve-btn {
            background-color: #4CAF50; /* Green */
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .approve-btn:hover {
            background-color: #45a049; /* Darker green on hover */
        }

        .reject-btn {
            background-color: #f44336; /* Red */
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .reject-btn:hover {
            background-color: #d32f2f; /* Darker red on hover */
        }

        /* Action Buttons */
        .action-buttons button {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .enable-btn {
            background-color: #4CAF50;
            color: white;
        }

        .enable-btn:hover {
            background-color: #45a049;
        }

        .disable-btn {
            background-color: #f44336;
            color: white;
        }

        .disable-btn:hover {
            background-color: #d32f2f;
        }

        .view-btn {
            background-color: #2196F3;
            color: white;
        }

        .view-btn:hover {
            background-color: #1976D2;
        }

        .action-buttons i {
            font-size: 14px;
        }

        .button-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .approve-btn {
            background-color: #4CAF50;
            color: white;
        }

        .approve-btn:hover {
            background-color: #45a049;
        }

        .reject-btn {
            background-color: #f44336;
            color: white;
        }

        .reject-btn:hover {
            background-color: #d32f2f;
        }

        .edit-btn {
            background-color: #2196F3;
            color: white;
        }

        .edit-btn:hover {
            background-color: #1976D2;
        }

        .disable-btn {
            background-color: #f44336;
            color: white;
        }

        .disable-btn:hover {
            background-color: #d32f2f;
        }

        .view-btn {
            background-color: #2196F3;
            color: white;
        }

        .view-btn:hover {
            background-color: #1976D2;
        }

        .no-actions {
            color: #666;
            font-style: italic;
        }

        .action-btn i {
            font-size: 14px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #1a2634;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-inactive {
            background-color: #e2e3e5;
            color: #383d41;
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
                    <a href="station_owner_details.php" class="nav-link active">
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
            <div style="margin-bottom: 20px;">
                <a href="admindash.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <h1>Station Owner Requests</h1>
            <table>
                <thead>
                    <tr>
                        <th>Owner Name</th>
                        <th>Business Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Business Registration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while ($request = mysqli_fetch_assoc($result)): 
                        $statusClass = 'status-' . $request['status'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['business_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo htmlspecialchars($request['phone']); ?></td>
                            <td>
                                <?php 
                                echo htmlspecialchars($request['address']) . '<br>';
                                echo htmlspecialchars($request['city']) . ', ';
                                echo htmlspecialchars($request['state']) . ' ';
                                echo htmlspecialchars($request['postal_code']);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($request['business_registration']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(htmlspecialchars($request['status'])); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div class="button-group">
                                        <button class="action-btn approve-btn" onclick="updateStatus(<?php echo htmlspecialchars($request['request_id']); ?>, 'approved')" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn reject-btn" onclick="updateStatus(<?php echo htmlspecialchars($request['request_id']); ?>, 'rejected')" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php elseif ($request['status'] === 'approved'): ?>
                                    <div class="button-group">
                                        <a href="views/view_station_owner.php?request_id=<?php echo htmlspecialchars($request['request_id']); ?>" class="action-btn view-btn" title="View Details">
                                            <i class="fas fa-eye"></i> 
                                        </a>
                                        <button class="action-btn disable-btn" onclick="updateStatus(<?php echo htmlspecialchars($request['request_id']); ?>, 'inactive')" title="Disable">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </div>
                                <?php elseif ($request['status'] === 'inactive'): ?>
                                    <div class="button-group">
                                        <a href="views/view_station_owner.php?request_id=<?php echo htmlspecialchars($request['request_id']); ?>" class="action-btn view-btn" title="View Details">
                                            <i class="fas fa-eye"></i> 
                                        </a>
                                        <button class="action-btn enable-btn" onclick="updateStatus(<?php echo htmlspecialchars($request['request_id']); ?>, 'active')" title="Enable">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </div>
                                <?php elseif ($request['status'] === 'active'): ?>
                                    <div class="button-group">
                                        <a href="views/view_station_owner.php?request_id=<?php echo htmlspecialchars($request['request_id']); ?>" class="action-btn view-btn" title="View Details">
                                            <i class="fas fa-eye"></i> 
                                        </a>
                                        <button class="action-btn disable-btn" onclick="updateStatus(<?php echo htmlspecialchars($request['request_id']); ?>, 'inactive')" title="Disable">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="button-group">
                                        <button class="action-btn approve-btn" onclick="updateStatus(<?php echo htmlspecialchars($request['request_id']); ?>, 'pending')" title="Activate">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <a href="views/view_station_owner.php?request_id=<?php echo htmlspecialchars($request['request_id']); ?>" class="action-btn view-btn" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php 
                    endwhile; 
                    mysqli_close($conn);
                    ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
    function updateStatus(requestId, status) {
        if (confirm('Are you sure you want to ' + (status === 'active' ? 'enable' : status) + ' this request?')) {
            // Send AJAX request to update status
            fetch('update_owner_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'request_id=' + encodeURIComponent(requestId) + '&status=' + encodeURIComponent(status)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully');
                    location.reload();
                } else {
                    alert('Error updating status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the status');
            });
        }
    }

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

    function viewDetails(button, requestId) {
        // Change the icon to indicate it's been clicked
        const icon = button.querySelector('i');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-spinner', 'fa-spin'); // Change to spinner icon

        // Simulate a delay for demonstration (you can remove this in production)
        setTimeout(() => {
            // Your existing logic to view details
            alert('Viewing details for request ID: ' + requestId);
            // Reset the icon back to original after viewing
            icon.classList.remove('fa-spinner', 'fa-spin');
            icon.classList.add('fa-eye');
        }, 1000); // Simulate a delay of 1 second
    }
    </script>
</body>
</html>