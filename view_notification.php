<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$notificationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$notificationId) {
    header('Location: index.php');
    exit;
}

// Mark notification as read
$markRead = $pdo->prepare("
    UPDATE notifications 
    SET is_read = TRUE 
    WHERE notification_id = ? AND user_id = ?
");
$markRead->execute([$notificationId, $_SESSION['user_id']]);

// Fetch notification details
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE notification_id = ? AND user_id = ?
");
$stmt->execute([$notificationId, $_SESSION['user_id']]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    header('Location: index.php');
    exit;
}

// Page title
$pageTitle = "Notification - " . $notification['title'];
include 'header.php';
?>

<div class="container">
    <div class="notification-detail-card">
        <h1><?php echo htmlspecialchars($notification['title']); ?></h1>
        <div class="notification-meta">
            <span class="notification-type"><?php echo ucfirst($notification['type']); ?></span>
            <span class="notification-date"><?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?></span>
        </div>
        <div class="notification-content">
            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
        </div>
        <div class="notification-actions">
            <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? 'index.php'; ?>" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 