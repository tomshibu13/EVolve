<?php
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

    // Fetch only active charging stations with additional columns
    $query = "
        SELECT 
            cs.station_id,
            cs.name as station_name,
            cs.owner_name,
            cs.price,
            cs.address,
            cs.status,
            cs.operator_id,
            cs.created_at,
            cs.charger_types,
            cs.available_slots,
            cs.total_slots,
            u.name as operator_name,
            u.user_id,
            ST_X(cs.location) as longitude,
            ST_Y(cs.location) as latitude
        FROM charging_stations cs 
        LEFT JOIN tbl_users u ON cs.operator_id = u.user_id 
        WHERE cs.status = 'active'";

    // Add search condition if search term is provided
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = mysqli_real_escape_string($conn, $_POST['search']);
        $query .= " AND (
            cs.address LIKE '%$search%' OR 
            u.name LIKE '%$search%' OR 
            cs.charger_types LIKE '%$search%' OR
            cs.name LIKE '%$search%'
        )";
    }
    
    $query .= " ORDER BY cs.created_at DESC";
    
    $stations = mysqli_query($conn, $query);
    
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charging Stations - EVolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --secondary-color: #2C3E50;
            --background-color: #f5f6fa;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 2rem;
            min-height: 100vh;
        }

        .header {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            letter-spacing: -0.025em;
        }

        .stations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            padding: 0.5rem;
        }

        .station-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .station-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            opacity: 0;
            transition: var(--transition);
        }

        .station-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .station-card:hover::before {
            opacity: 1;
        }

        .station-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .station-info {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--secondary-color);
        }

        .station-info i {
            color: var(--primary-color);
            width: 1.25rem;
            font-size: 1rem;
        }

        .book-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            width: 100%;
            margin-top: 1.5rem;
            font-weight: 500;
            font-size: 1rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .book-btn:hover {
            background-color: var(--primary-hover);
        }

        .filters {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .loading {
            animation: pulse 1.5s infinite;
        }

        .price-tag {
            display: inline-flex;
            align-items: center;
            background: #eef2ff;
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        /* .status-full {
            background: #fee2e2;
            color: #991b1b;
        } */

        .status-limited {
            background: #fef3c7;
            color: #92400e;
        }

        .back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--card-bg);
            border: 1px solid rgba(0,0,0,0.1);
            color: var(--text-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .back-btn:hover {
            background: var(--background-color);
            transform: translateX(-2px);
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="page-title">⚡ Available Charging Stations</h1>
        </div>
        <form method="POST" class="filters">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="search" class="search-input" 
                value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>"
                placeholder="Search by location, operator, or power output...">
        </form>
    </div>

    <?php if ($error_message): ?>
        <div class="empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="stations-grid">
        <?php 
        if ($stations && mysqli_num_rows($stations) > 0):
            while($station = mysqli_fetch_assoc($stations)): 
                // Calculate availability status
                $availableSlots = intval($station['available_slots']);
                $totalSlots = intval($station['total_slots']);
                $statusClass = '';
                $statusText = '';
                
                if ($availableSlots <= 0) {
                    $statusClass = 'status-full';
                    $statusText = 'Full';
                } elseif ($availableSlots < ($totalSlots / 2)) {
                    $statusClass = 'status-limited';
                    $statusText = 'Limited';
                } else {
                    $statusClass = 'status-available';
                    $statusText = 'Available';
                }
        ?>
            <div class="station-card">
                <div class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></div>
                <div class="station-name"><?php echo htmlspecialchars($station['station_name'] ?? $station['name'] ?? 'Unnamed Station'); ?></div>
                <div class="station-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($station['address'] ?? 'No address available'); ?></span>
                </div>
                <div class="station-info">
                    <i class="fas fa-user"></i>
                    <span>Operated by <?php echo htmlspecialchars($station['operator_name'] ?? $station['owner_name'] ?? 'Unknown'); ?></span>
                </div>
                <div class="station-info">
                    <i class="fas fa-bolt"></i>
                    <span>
                        <?php 
                        $charger_types = json_decode($station['charger_types'] ?? '[]', true);
                        if (is_array($charger_types) && !empty($charger_types)) {
                            $types = array_map(function($type) {
                                // If it's a string, return it directly
                                if (is_string($type)) {
                                    return $type;
                                }
                                // If it's an array, check for both 'type' and 'name' keys
                                if (is_array($type)) {
                                    return $type['type'] ?? $type['name'] ?? 'Standard';
                                }
                                return 'Standard';
                            }, $charger_types);
                            echo htmlspecialchars(implode(', ', $types));
                        } else {
                            echo 'Standard';
                        }
                        ?>
                    </span>
                </div>
                <div class="station-info">
                    <i class="fas fa-plug"></i>
                    <span><?php echo $availableSlots . '/' . $totalSlots . ' slots available'; ?></span>
                </div>
                <div class="station-info">
                    <i class="fas fa-dollar-sign"></i>
                    <span class="price-tag">₹<?php echo isset($station['price']) ? number_format($station['price'], 2) : '0.00'; ?></span>
                </div>
                <a href="book_station.php?id=<?php echo urlencode($station['station_id']); ?>" class="book-btn">
                    <i class="fas fa-bolt"></i>
                    Book Now
                </a>
            </div>
        <?php 
            endwhile;
        else: 
        ?>
            <div class="empty-state">
                <i class="fas fa-charging-station"></i>
                <p>No charging stations available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add event listener for form submission
        document.querySelector('.filters').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
    </script>
</body>
</html>