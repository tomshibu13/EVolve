<?php
session_start();
require_once 'config.php';

// Function to get user notifications
function getUserNotifications($userId) {
    global $pdo;
    try {
        // First get standard notifications
        $stmt = $pdo->prepare("
            SELECT 
                notification_id,
                title,
                message,
                type,
                created_at,
                is_read,
                NULL as station_id,
                NULL as enquiry_id,
                NULL as owner_name,
                NULL as station_name
            FROM notifications 
            WHERE user_id = ?
            
            UNION
            
            -- Get station owner responses to enquiries
            SELECT 
                e.enquiry_id as notification_id,
                CONCAT('Response from ', cs.owner_name) as title,
                e.response as message,
                'response' as type,
                e.response_date as created_at,
                (CASE WHEN e.status = 'read' THEN 1 ELSE 0 END) as is_read,
                e.station_id,
                e.enquiry_id,
                cs.owner_name,
                cs.name as station_name
            FROM enquiries e
            JOIN charging_stations cs ON e.station_id = cs.station_id
            WHERE e.user_id = ? 
            AND e.status = 'responded'
            AND e.response IS NOT NULL
            
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
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

// Mark all notifications as read
if (isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Mark enquiry notification as read
if (isset($_POST['action']) && $_POST['action'] === 'mark_enquiry_read') {
    try {
        $stmt = $pdo->prepare("
            UPDATE enquiries 
            SET status = 'read' 
            WHERE enquiry_id = ? AND user_id = ?
        ");
        $stmt->execute([$_POST['enquiry_id'], $_SESSION['user_id']]);
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
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: linear-gradient(145deg, #f0f4f8, #f8f9fa);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(67,97,238,0.1);
        }

        .notifications-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #4361ee;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .notification-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-btn {
            background: white;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 20px;
            padding: 6px 15px;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #4361ee;
            color: white;
            border-color: #4361ee;
        }

        .notification-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            position: relative;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .notification-card.unread {
            border-left: 5px solid #4361ee;
            background: linear-gradient(to right, rgba(67,97,238,0.05), white 15%);
        }

        .notification-card.unread::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 10px;
            height: 10px;
            background: #4361ee;
            border-radius: 50%;
            margin: 15px;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .notification-title {
            font-size: 1.3em;
            font-weight: 700;
            color: #1a202c;
            margin: 10px 0;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 0.9em;
            color: #718096;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notification-time i {
            color: #a0aec0;
        }

        .notification-message {
            color: #4a5568;
            line-height: 1.7;
            margin: 0;
            font-size: 1.05em;
        }

        .notification-type {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-booking {
            background: linear-gradient(to right, #c6f6d5, #9ae6b4);
            color: #2f855a;
        }

        .type-system {
            background: linear-gradient(to right, #bee3f8, #90cdf4);
            color: #2b6cb0;
        }

        .type-alert {
            background: linear-gradient(to right, #feebc8, #fbd38d);
            color: #c05621;
        }

        .type-response {
            background: linear-gradient(to right, #d1e9ff, #a3d0ff);
            color: #1a56db;
        }

        .mark-read {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #edf2f7;
            border: none;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .mark-read:hover {
            background: #4361ee;
            color: white;
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 5px 15px rgba(67,97,238,0.3);
        }

        .no-notifications {
            text-align: center;
            padding: 70px 30px;
            color: #718096;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .no-notifications i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #a0aec0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .no-notifications p {
            font-size: 1.2em;
            margin-bottom: 25px;
            color: #4a5568;
        }

        .btn-primary {
            background: linear-gradient(145deg, #4361ee, #3651d4);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67,97,238,0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(145deg, #3651d4, #2b46c8);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67,97,238,0.3);
        }

        h1 {
            color: #1a202c;
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 800;
        }

        @media (max-width: 768px) {
            .notifications-container {
                padding: 20px;
                margin: 20px;
                border-radius: 15px;
            }
            
            .notification-card {
                padding: 20px;
            }
            
            .notification-title {
                font-size: 1.1em;
            }
            
            .notification-filters {
                overflow-x: auto;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }
        }

        .btn-mark-all-read {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f0f4f8;
            color: #4361ee;
            border: 1px solid rgba(67, 97, 238, 0.2);
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 0.9em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-mark-all-read:hover {
            background: #4361ee;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        .btn-mark-all-read i {
            font-size: 0.9em;
        }

        .station-info {
            margin-top: 10px;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
        }
        
        .station-info i {
            color: #4361ee;
        }
        
        .view-station-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 6px 14px;
            background: #f0f4f8;
            color: #4361ee;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .view-station-btn:hover {
            background: #4361ee;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="notifications-container">
        <div class="notifications-header">
            <div class="notifications-title">
                <h1>Notifications</h1>
                <?php if (isset($_SESSION['user_id']) && !empty($notifications)): ?>
                    <?php 
                        $unreadCount = array_reduce($notifications, function($carry, $item) {
                            return $carry + ($item['is_read'] ? 0 : 1);
                        }, 0);
                        if ($unreadCount > 0):
                    ?>
                    <span class="notification-count"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['user_id']) && !empty($notifications) && $unreadCount > 0): ?>
            <button id="mark-all-read" class="btn-mark-all-read">
                <i class="fas fa-check-double"></i> Mark All Read
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (!empty($notifications)): ?>
                <div class="notification-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="booking">Booking</button>
                    <button class="filter-btn" data-filter="system">System</button>
                    <button class="filter-btn" data-filter="alert">Alert</button>
                    <button class="filter-btn" data-filter="response">Responses</button>
                </div>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                         data-id="<?php echo $notification['notification_id']; ?>"
                         data-type="<?php echo strtolower($notification['type']); ?>"
                         <?php if (strtolower($notification['type']) === 'response'): ?>
                         data-enquiry-id="<?php echo $notification['enquiry_id']; ?>"
                         <?php endif; ?>>
                        <div class="notification-header">
                            <span class="notification-type type-<?php echo strtolower($notification['type']); ?>">
                                <i class="fas <?php 
                                    echo strtolower($notification['type']) === 'booking' ? 'fa-calendar-check' : 
                                        (strtolower($notification['type']) === 'system' ? 'fa-cog' : 
                                         (strtolower($notification['type']) === 'response' ? 'fa-reply' : 'fa-exclamation-circle')); 
                                ?>"></i>
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                            <span class="notification-time">
                                <i class="far fa-clock"></i>
                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                        </div>
                        
                        <?php if (strtolower($notification['type']) === 'response' && isset($notification['station_name'])): ?>
                        <div class="station-info">
                            <i class="fas fa-charging-station"></i>
                            <span><?php echo htmlspecialchars($notification['station_name']); ?></span>
                        </div>
                        <a href="station_details.php?id=<?php echo $notification['station_id']; ?>" class="view-station-btn">
                            <i class="fas fa-eye"></i> View Station
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!$notification['is_read']): ?>
                            <?php if (strtolower($notification['type']) === 'response'): ?>
                                <button class="mark-read" onclick="markEnquiryAsRead(<?php echo $notification['enquiry_id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php else: ?>
                                <button class="mark-read" onclick="markAsRead(<?php echo $notification['notification_id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <i class="far fa-bell-slash"></i>
                    <p>You don't have any notifications yet</p>
                    <p>We'll notify you of important updates and events</p>
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
                    
                    // Update notification count
                    const countElement = document.querySelector('.notification-count');
                    if (countElement) {
                        const currentCount = parseInt(countElement.textContent);
                        if (currentCount > 1) {
                            countElement.textContent = currentCount - 1;
                        } else {
                            countElement.remove();
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function markEnquiryAsRead(enquiryId) {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_enquiry_read&enquiry_id=${enquiryId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.querySelector(`[data-enquiry-id="${enquiryId}"]`);
                    notification.classList.remove('unread');
                    notification.querySelector('.mark-read').remove();
                    
                    // Update notification count
                    const countElement = document.querySelector('.notification-count');
                    if (countElement) {
                        const currentCount = parseInt(countElement.textContent);
                        if (currentCount > 1) {
                            countElement.textContent = currentCount - 1;
                        } else {
                            countElement.remove();
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Mark all notifications as read
        document.getElementById('mark-all-read')?.addEventListener('click', function() {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI to reflect all notifications being read
                    document.querySelectorAll('.notification-card.unread').forEach(card => {
                        card.classList.remove('unread');
                        const markReadBtn = card.querySelector('.mark-read');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    });
                    
                    // Remove notification count
                    const countElement = document.querySelector('.notification-count');
                    if (countElement) {
                        countElement.remove();
                    }
                    
                    // Hide the "Mark All Read" button
                    this.style.display = 'none';
                }
            })
            .catch(error => console.error('Error:', error));
        });
        
        // Filter notifications
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.notification-card').forEach(card => {
                    if (filter === 'all' || card.dataset.type === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html> 