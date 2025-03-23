<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get station ID from URL parameter
    $station_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($station_id === 0) {
        throw new Exception("Invalid station ID");
    }

    // Prepare and execute the query
    $sql = "SELECT 
            station_id,
            name,
            ST_X(location) as lng,
            ST_Y(location) as lat,
            status,
            address,
            price,
            total_slots,
            available_slots,
            charger_types,
            image
            FROM charging_stations 
            WHERE station_id = ?";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Query failed: " . $stmt->error);
    }

    $station = $result->fetch_assoc();
    
    if (!$station) {
        throw new Exception("Station not found");
    }

    // Convert charger_types from JSON string to array
    $station['charger_types'] = json_decode($station['charger_types'], true);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $error_message = $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Details - EVolve</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="navigation-buttons">
            <a href="javascript:history.back()" class="nav-button back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="index.php" class="nav-button home-button">
                <i class="fas fa-home"></i> Home
            </a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <h1><?php echo htmlspecialchars($station['name']); ?></h1>
            
            <div class="station-layout">
                <?php if (!empty($station['image'])): ?>
                    <img src="<?php echo htmlspecialchars($station['image']); ?>" alt="<?php echo htmlspecialchars($station['name']); ?>" class="station-image">
                <?php else: ?>
                    <img src="images/default_station.jpg" alt="Default station image" class="station-image">
                <?php endif; ?>
                
                <div class="station-info">
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($station['status']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($station['address']); ?></p>
                    <p><strong>Price:</strong> ₹<?php echo htmlspecialchars($station['price']); ?>/kWh</p>
                    <p><strong>Total Slots:</strong> <?php echo htmlspecialchars($station['total_slots']); ?></p>
                    <p><strong>Available Slots:</strong> <?php echo htmlspecialchars($station['available_slots']); ?></p>
                    
                    <?php if (!empty($station['charger_types'])): ?>
                        <div class="charger-types">
                            <h3>Available Charger Types:</h3>
                            <ul>
                                <?php 
                                $charger_types = is_string($station['charger_types']) 
                                    ? json_decode($station['charger_types'], true) 
                                    : $station['charger_types'];
                                
                                foreach ($charger_types as $type): 
                                    // Handle both string and array values
                                    $display_value = is_array($type) ? implode(', ', $type) : $type;
                                ?>
                                    <li><?php echo htmlspecialchars($display_value); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="booking-section">
                    <h2>Book a Charging Slot</h2>
                    <a href="book_station.php?station_id=<?php echo $station['station_id']; ?>" class="book-button">
                        Book Now
                    </a>
                </div>
            <?php else: ?>
                <div class="login-prompt">
                    <p>Please <a href="index.php#loginForm" onclick="showLoginModal(); return false;">login</a> to book a charging slot.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <style>
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .station-layout {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
        }

        .station-image {
            width: 40%;
            height: auto;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .station-info {
            flex: 1;
        }

        h1 {
            color: #2196F3;
            margin-bottom: 20px;
            font-size: 2em;
        }

        .station-info p {
            margin: 15px 0;
            font-size: 1.1em;
            color: #333;
        }

        .station-info strong {
            color: #666;
            width: 150px;
            display: inline-block;
        }

        .charger-types {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .charger-types h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .charger-types ul {
            list-style: none;
            padding: 0;
        }

        .charger-types li {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 8px 15px;
            background: #f5f5f5;
            border-radius: 20px;
            color: #666;
            font-size: 0.9em;
        }

        .booking-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            text-align: center;
        }

        .book-button {
            display: inline-block;
            padding: 12px 30px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .book-button:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .login-prompt {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .login-prompt a {
            color: #2196F3;
            text-decoration: none;
            font-weight: 500;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .navigation-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .nav-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1.1em;
        }

        .back-button {
            background-color: #f0f4f8;
            color: #1a73e8;
            border: 1px solid #e0e0e0;
        }

        .back-button:hover {
            background-color: #e4e9f2;
            transform: translateX(-2px);
        }

        .home-button {
            background-color: #1a73e8;
            color: white;
            border: 1px solid #1557b0;
        }

        .home-button:hover {
            background-color: #1557b0;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.2);
        }

        @media (max-width: 650px) {
            .station-layout {
                flex-direction: column;
            }
            
            .station-image {
                width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
</body>
</html> 