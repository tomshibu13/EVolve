<?php
session_start();
require_once 'config.php';

// Function to get user notifications
function getUserNotifications($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                notification_id,
                title,
                message,
                type,
                created_at,
                is_read
            FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

// Mark notification as read
if (isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        $stmt->execute([$_POST['notification_id'], $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get user notifications if logged in
$notifications = [];
if (isset($_SESSION['user_id'])) {
    $notifications = getUserNotifications($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - EVolve</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .notification-card.unread {
            border-left: 4px solid #4361ee;
            background: linear-gradient(to right, rgba(67,97,238,0.03), white);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .notification-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2d3748;
            margin: 10px 0;
        }

        .notification-time {
            font-size: 0.9em;
            color: #718096;
            font-weight: 500;
        }

        .notification-message {
            color: #4a5568;
            line-height: 1.6;
            margin: 0;
        }

        .notification-type {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-booking {
            background: #c6f6d5;
            color: #2f855a;
        }

        .type-system {
            background: #bee3f8;
            color: #2c5282;
        }

        .type-alert {
            background: #feebc8;
            color: #c05621;
        }

        .mark-read {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #edf2f7;
            border: none;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .mark-read:hover {
            background: #4361ee;
            color: white;
            transform: scale(1.1);
        }

        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .no-notifications i {
            font-size: 3.5em;
            margin-bottom: 20px;
            color: #a0aec0;
        }

        .no-notifications p {
            font-size: 1.1em;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: #4361ee;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #3651d4;
            transform: translateY(-2px);
        }

        h1 {
            color: #2d3748;
            font-size: 2em;
            margin-bottom: 30px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="notifications-container">
        <h1>Notifications</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                         data-id="<?php echo $notification['notification_id']; ?>">
                        <div class="notification-header">
                            <span class="notification-type type-<?php echo strtolower($notification['type']); ?>">
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                            <span class="notification-time">
                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                        <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <?php if (!$notification['is_read']): ?>
                            <button class="mark-read" onclick="markAsRead(<?php echo $notification['notification_id']; ?>)">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <i class="far fa-bell-slash"></i>
                    <p>No notifications to display</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fas fa-user-lock"></i>
                <p>Please log in to view your notifications</p>
                <button onclick="window.location.href='login.php'" class="btn btn-primary">Log In</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function markAsRead(notificationId) {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.querySelector(`[data-id="${notificationId}"]`);
                    notification.classList.remove('unread');
                    notification.querySelector('.mark-read').remove();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html> 