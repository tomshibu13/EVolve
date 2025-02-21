<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user's charging history
    $stmt = $pdo->prepare("
        SELECT 
            ch.session_id,
            ch.start_time,
            ch.end_time,
            ch.energy_consumed,
            ch.total_cost,
            cs.name as station_name,
            cs.address as station_address
        FROM charging_history ch
        JOIN charging_stations cs ON ch.station_id = cs.station_id
        WHERE ch.user_id = ?
        ORDER BY ch.start_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $chargingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "An error occurred while fetching your charging history.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usage History - EVolve</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .history-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .history-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .history-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .history-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .history-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 40px;
        }

        .history-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .history-card:hover {
            transform: translateY(-5px);
        }

        .station-info {
            margin-bottom: 15px;
        }

        .station-name {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .station-address {
            color: #666;
            font-size: 0.9rem;
        }

        .session-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-item i {
            color: #3498db;
            font-size: 1.2rem;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
        }

        .detail-value {
            font-weight: 500;
            color: #2c3e50;
        }

        .no-history {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .no-history i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .no-history p {
            color: #666;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .history-header h1 {
                font-size: 2rem;
            }

            .session-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="history-container">
        <div class="history-header">
            <h1>Charging History</h1>
            <p>View your past charging sessions and usage details</p>
        </div>

        <div class="history-grid">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php elseif (empty($chargingHistory)): ?>
                <div class="no-history">
                    <i class="fas fa-history"></i>
                    <p>No charging history found. Start charging to see your usage details here!</p>
                </div>
            <?php else: ?>
                <?php foreach ($chargingHistory as $session): ?>
                    <div class="history-card">
                        <div class="station-info">
                            <h3 class="station-name"><?php echo htmlspecialchars($session['station_name']); ?></h3>
                            <p class="station-address"><?php echo htmlspecialchars($session['station_address']); ?></p>
                        </div>
                        <div class="session-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <div class="detail-label">Start Time</div>
                                    <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($session['start_time'])); ?></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-hourglass-end"></i>
                                <div>
                                    <div class="detail-label">End Time</div>
                                    <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($session['end_time'])); ?></div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-bolt"></i>
                                <div>
                                    <div class="detail-label">Energy Consumed</div>
                                    <div class="detail-value"><?php echo number_format($session['energy_consumed'], 2); ?> kWh</div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-rupee-sign"></i>
                                <div>
                                    <div class="detail-label">Total Cost</div>
                                    <div class="detail-value">₹<?php echo number_format($session['total_cost'], 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 