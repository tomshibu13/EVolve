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
            cs.image,
            u.name as operator_name,
            u.user_id,
            ST_X(cs.location) as longitude,
            ST_Y(cs.location) as latitude,
            (SELECT AVG(rating) FROM station_reviews WHERE station_id = cs.station_id) as avg_rating,
            (SELECT COUNT(*) FROM station_reviews WHERE station_id = cs.station_id) as review_count
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

// Modify the query to return JSON when it's an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    $stations_array = [];
    while ($station = mysqli_fetch_assoc($stations)) {
        $stations_array[] = $station;
    }
    echo json_encode($stations_array);
    exit;
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
            --primary-color: #0066FF;
            --primary-hover: #0052cc;  /* Darker shade for hover */
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

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: var(--card-bg);
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            padding: 2rem;
            padding-top: calc(70px + 2rem); /* Adjust this value based on your header height */
            min-height: 100vh;
        }

        .ev-header {
            position: sticky;
            top: 70px; /* Adjust this value based on your header height */
            z-index: 900;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .ev-page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            letter-spacing: -0.025em;
        }

        .ev-stations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            padding: 0.5rem;
        }

        .ev-station-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .ev-station-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            background-color: #f3f4f6;
        }
        
        .ev-station-image-placeholder {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            color: #94a3b8;
        }

        .ev-station-card::before {
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

        .ev-station-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .ev-station-card:hover::before {
            opacity: 1;
        }

        .ev-station-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .ev-station-info {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--secondary-color);
        }

        .ev-station-info i {
            color: var(--primary-color);
            width: 1.25rem;
            font-size: 1rem;
        }

        .ev-book-btn {
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
            text-decoration: none;
        }

        .ev-book-btn:hover {
            background-color: var(--primary-hover);
        }

        .ev-filters {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .ev-search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .ev-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .ev-search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }

        .ev-empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .ev-empty-state i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .ev-empty-state p {
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

        .ev-price-tag {
            display: inline-flex;
            align-items: center;
            background: #eef2ff;
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }

        .ev-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .ev-status-available {
            background: #dcfce7;
            color: #166534;
        }

        /* .status-full {
            background: #fee2e2;
            color: #991b1b;
        } */

        .ev-status-limited {
            background: #fef3c7;
            color: #92400e;
        }

        .ev-back-btn {
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

        .ev-back-btn:hover {
            background: var(--background-color);
            transform: translateX(-2px);
        }
        
        /* Star Rating Styles */
        .ev-rating {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .ev-stars {
            display: flex;
            margin-right: 0.5rem;
            color: #FFD700;
        }
        
        .ev-rating-count {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header>
        <?php include 'header.php'; ?>
    </header>
    <div class="ev-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="index.php" class="ev-back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="ev-page-title">⚡ Available Charging Stations</h1>
        </div>
        <form method="POST" class="ev-filters">
            <i class="fas fa-search ev-search-icon"></i>
            <input type="text" name="search" class="ev-search-input" 
                value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['search']) : ''; ?>"
                placeholder="Search by location, operator, or power output...">
        </form>
    </div>

    <?php if ($error_message): ?>
        <div class="ev-empty-state">
            <i class="fas fa-exclamation-circle"></i>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>

    <div class="ev-stations-grid">
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
            <div class="ev-station-card">
                <div class="ev-status-badge <?php echo str_replace('status-', 'ev-status-', $statusClass); ?>"><?php echo $statusText; ?></div>
                
                <?php if (!empty($station['image'])): ?>
                    <img src="<?php echo htmlspecialchars($station['image']); ?>" alt="<?php echo htmlspecialchars($station['station_name']); ?>" class="ev-station-image">
                <?php else: ?>
                    <div class="ev-station-image-placeholder">
                        <i class="fas fa-charging-station fa-2x"></i>
                    </div>
                <?php endif; ?>
                
                <div class="ev-station-name"><?php echo htmlspecialchars($station['station_name'] ?? $station['name'] ?? 'Unnamed Station'); ?></div>
                <div class="ev-station-info">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($station['address'] ?? 'No address available'); ?></span>
                </div>
                <div class="ev-station-info">
                    <i class="fas fa-user"></i>
                    <span>Operated by <?php echo htmlspecialchars($station['operator_name'] ?? $station['owner_name'] ?? 'Unknown'); ?></span>
                </div>
                <div class="ev-station-info">
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
                <div class="ev-station-info">
                    <i class="fas fa-plug"></i>
                    <span><?php echo $availableSlots . '/' . $totalSlots . ' slots available'; ?></span>
                </div>
                <div class="ev-station-info">
                    <i class="fas fa-dollar-sign"></i>
                    <span class="ev-price-tag">₹<?php echo isset($station['price']) ? number_format($station['price'], 2) : '0.00'; ?></span>
                </div>
                
                <!-- Display Rating -->
                <div class="ev-rating">
                    <div class="ev-stars">
                        <?php
                        $avgRating = round(floatval($station['avg_rating'] ?? 0), 1);
                        $fullStars = floor($avgRating);
                        $halfStar = $avgRating - $fullStars >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                        
                        // Display full stars
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        // Display half star if applicable
                        if ($halfStar) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        // Display empty stars
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span class="ev-rating-count">
                        <?php 
                        echo $avgRating . ' (' . ($station['review_count'] ?? 0) . ' reviews)'; 
                        ?>
                    </span>
                </div>
                
                <a href="book_station.php?id=<?php echo urlencode($station['station_id']); ?>" class="ev-book-btn">
                    <i class="fas fa-bolt" style="line-height: 1;"></i>
                    <span style="line-height: 1;">Book Now</span>
                </a>
            </div>
        <?php 
            endwhile;
        else: 
        ?>
            <div class="ev-empty-state">
                <i class="fas fa-charging-station"></i>
                <p>No charging stations available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Replace the existing script with this new version
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.ev-search-input');
        const stationsGrid = document.querySelector('.ev-stations-grid');
        let searchTimeout;

        // Function to update stations
        async function updateStations(searchTerm = '') {
            try {
                const formData = new FormData();
                formData.append('search', searchTerm);

                const response = await fetch('user_stations.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const stations = await response.json();
                
                if (stations.length === 0) {
                    stationsGrid.innerHTML = `
                        <div class="ev-empty-state">
                            <i class="fas fa-charging-station"></i>
                            <p>No charging stations found matching your search.</p>
                        </div>`;
                    return;
                }

                stationsGrid.innerHTML = stations.map(station => {
                    const availableSlots = parseInt(station.available_slots);
                    const totalSlots = parseInt(station.total_slots);
                    let statusClass = '';
                    let statusText = '';
                    
                    if (availableSlots <= 0) {
                        statusClass = 'ev-status-full';
                        statusText = 'Full';
                    } else if (availableSlots < (totalSlots / 2)) {
                        statusClass = 'ev-status-limited';
                        statusText = 'Limited';
                    } else {
                        statusClass = 'ev-status-available';
                        statusText = 'Available';
                    }

                    const chargerTypes = JSON.parse(station.charger_types || '[]');
                    const types = Array.isArray(chargerTypes) ? chargerTypes.map(type => 
                        typeof type === 'string' ? type : (type.type || type.name || 'Standard')
                    ).join(', ') : 'Standard';

                    // Calculate star rating display
                    const avgRating = Math.round((parseFloat(station.avg_rating || 0) * 10)) / 10;
                    const fullStars = Math.floor(avgRating);
                    const halfStar = avgRating - fullStars >= 0.5;
                    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
                    
                    let starsHtml = '';
                    
                    // Full stars
                    for (let i = 0; i < fullStars; i++) {
                        starsHtml += '<i class="fas fa-star"></i>';
                    }
                    
                    // Half star
                    if (halfStar) {
                        starsHtml += '<i class="fas fa-star-half-alt"></i>';
                    }
                    
                    // Empty stars
                    for (let i = 0; i < emptyStars; i++) {
                        starsHtml += '<i class="far fa-star"></i>';
                    }

                    return `
                        <div class="ev-station-card">
                            <div class="ev-status-badge ${statusClass}">${statusText}</div>
                            
                            ${station.image 
                                ? `<img src="${station.image}" alt="${station.station_name || 'Charging Station'}" class="ev-station-image">` 
                                : `<div class="ev-station-image-placeholder"><i class="fas fa-charging-station fa-2x"></i></div>`
                            }
                            
                            <div class="ev-station-name">${station.station_name || station.name || 'Unnamed Station'}</div>
                            <div class="ev-station-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${station.address || 'No address available'}</span>
                            </div>
                            <div class="ev-station-info">
                                <i class="fas fa-user"></i>
                                <span>Operated by ${station.operator_name || station.owner_name || 'Unknown'}</span>
                            </div>
                            <div class="ev-station-info">
                                <i class="fas fa-bolt"></i>
                                <span>${types}</span>
                            </div>
                            <div class="ev-station-info">
                                <i class="fas fa-plug"></i>
                                <span>${availableSlots}/${totalSlots} slots available</span>
                            </div>
                            <div class="ev-station-info">
                                <i class="fas fa-dollar-sign"></i>
                                <span class="ev-price-tag">₹${parseFloat(station.price || 0).toFixed(2)}</span>
                            </div>
                            <div class="ev-rating">
                                <div class="ev-stars">${starsHtml}</div>
                                <span class="ev-rating-count">${avgRating} (${station.review_count || 0} reviews)</span>
                            </div>
                            <a href="book_station.php?id=${encodeURIComponent(station.station_id)}" class="ev-book-btn">
                                <i class="fas fa-bolt" style="line-height: 1;"></i>
                                <span style="line-height: 1;">Book Now</span>
                            </a>
                        </div>
                    `;
                }).join('');
            } catch (error) {
                console.error('Error fetching stations:', error);
                stationsGrid.innerHTML = `
                    <div class="ev-empty-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Error loading stations. Please try again later.</p>
                    </div>`;
            }
        }

        // Real-time search with debouncing
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                updateStations(e.target.value);
            }, 300);
        });

        // Initial load
        updateStations(searchInput.value);

        // Auto-refresh every 30 seconds
        setInterval(() => {
            updateStations(searchInput.value);
        }, 30000);
    });
    </script>
</body>
</html>