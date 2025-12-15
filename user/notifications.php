<?php
/**
 * Notifications Page
 * 
 * Displays user notifications
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Notifications';

// Require login
requireLogin();

$userId = getCurrentUserId();

// Handle marking notification as read
if (isset($_GET['mark_read']) && isset($_GET['notification_id'])) {
    $notificationId = (int)$_GET['notification_id'];
    $stmt = getDB()->prepare('
        UPDATE notifications SET is_read = TRUE, read_at = NOW()
        WHERE id = ? AND user_id = ?
    ');
    $stmt->execute([$notificationId, $userId]);
}

// Handle deleting notification
if (isset($_GET['delete']) && isset($_GET['notification_id'])) {
    if (verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        $notificationId = (int)$_GET['notification_id'];
        $stmt = getDB()->prepare('
            DELETE FROM notifications WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$notificationId, $userId]);
    }
}

// Handle marking all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $stmt = getDB()->prepare('
            UPDATE notifications SET is_read = TRUE, read_at = NOW()
            WHERE user_id = ? AND is_read = FALSE
        ');
        $stmt->execute([$userId]);
    }
}

// Get all notifications
$stmt = getDB()->prepare('
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 100
');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Get unread count
$unreadCount = 0;
foreach ($notifications as $notif) {
    if (!$notif['is_read']) {
        $unreadCount++;
    }
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="notifications-container">
    <div class="notifications-header">
        <h1>Notifications</h1>
        <?php if ($unreadCount > 0): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-secondary">Mark All as Read</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="notifications-list">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                    <div class="notification-content">
                        <h3><?php echo htmlspecialchars($notif['title']); ?></h3>
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <small><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                    </div>
                    <div class="notification-actions">
                        <?php if (!$notif['is_read']): ?>
                            <a href="/user/notifications.php?mark_read=1&notification_id=<?php echo $notif['id']; ?>" 
                               class="btn btn-sm btn-secondary">Mark as Read</a>
                        <?php endif; ?>
                        <a href="/user/notifications.php?delete=1&notification_id=<?php echo $notif['id']; ?>&csrf_token=<?php echo htmlspecialchars($csrfToken); ?>" 
                           class="btn btn-sm btn-danger" 
                           data-confirm="Are you sure you want to delete this notification?"
                           data-confirm-title="Delete Notification">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <p>You have no notifications.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .notifications-container {
        max-width: 800px;
        margin: 40px auto;
    }

    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .notifications-header h1 {
        margin: 0;
    }

    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .notification-item {
        background-color: white;
        border-left: 4px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .notification-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .notification-item.unread {
        background-color: #f0f8ff;
        border-left-color: #007bff;
    }

    .notification-content {
        flex: 1;
    }

    .notification-content h3 {
        margin: 0 0 8px 0;
        color: #333;
        font-size: 1.1rem;
    }

    .notification-content p {
        margin: 0 0 8px 0;
        color: #666;
        line-height: 1.5;
    }

    .notification-content small {
        color: #999;
        font-size: 0.9rem;
    }

    .notification-actions {
        display: flex;
        gap: 10px;
        margin-left: 20px;
    }

    .notification-actions .btn {
        white-space: nowrap;
    }

    .no-notifications {
        text-align: center;
        padding: 40px 20px;
        background-color: white;
        border-radius: 8px;
        color: #999;
    }

    @media (max-width: 768px) {
        .notifications-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .notification-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .notification-actions {
            margin-left: 0;
            margin-top: 15px;
            width: 100%;
        }

        .notification-actions .btn {
            flex: 1;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
