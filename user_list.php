<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) { // Assuming 'user_id' is set upon login
    header("Location: index.php#LoginForm"); // Redirect to the login page
    exit();
}

// Database connection credentials
$serverusername = "localhost";
$userusername = "root"; 
$password = "";    
$dbusername = "evolve1";

try {
    // Create connection
    $conn = mysqli_connect($serverusername, $userusername, $password, $dbusername);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Handle block/unblock action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
        $user_id = mysqli_real_escape_string($conn, $_POST['id']);
        
        if ($_POST['action'] === 'toggle_block') {
            $query = "UPDATE tbl_users SET status = NOT status WHERE user_id = '$user_id'";
            mysqli_query($conn, $query);
            header("Location: user_list.php");
            exit();
        }
    }

    // Fetch all users with status
    $users = mysqli_query($conn, "
        SELECT 
            user_id,
            username,
            email,
            created_at,
            status
        FROM tbl_users 
        ORDER BY created_at DESC
    ");
    
    if (!$users) {
        throw new Exception("Error fetching users: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta username="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List - EVolve Admin</title>
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
        <div class="main-content">
            <div class="header">
                <a href="admindash.php" class="home-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($users && mysqli_num_rows($users) > 0):
                            while($user = mysqli_fetch_assoc($users)): 
                                $created_date = date('M d, Y', strtotime($user['created_at']));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($created_date); ?></td>
                                <td class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                        <input type="hidden" name="action" value="toggle_block">
                                        <button type="button" class="action-btn edit-btn" onclick="window.location.href='edit_user.php?id=<?php echo htmlspecialchars($user['user_id']); ?>'">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="submit" class="action-btn <?php echo $user['status'] ? 'delete-btn' : 'edit-btn'; ?>" 
                                            onclick="return confirmToggleBlock(<?php echo htmlspecialchars($user['user_id']); ?>, '<?php echo $user['status'] ? 'block' : 'unblock'; ?>')">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function confirmToggleBlock(userId, action) {
            const message = action === 'block' ? 
                'Are you sure you want to block this user?' : 
                'Are you sure you want to unblock this user?';
            
            return confirm(message);
        }
    </script>
</body>
</html> 