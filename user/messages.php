<?php
/**
 * Messaging System
 * 
 * Allows users to send and receive messages
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Messages';

// Require login
requireLogin();

$userId = getCurrentUserId();
$action = sanitizeInput($_GET['action'] ?? 'inbox');
$conversationId = (int)($_GET['conversation_id'] ?? 0);

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $recipientId = (int)($_POST['recipient_id'] ?? 0);
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $messageBody = sanitizeInput($_POST['message'] ?? '');
        
        if ($recipientId <= 0) {
            $error = 'Invalid recipient.';
        } elseif (empty($messageBody)) {
            $error = 'Message cannot be empty.';
        } else {
            try {
                $stmt = getDB()->prepare('
                    INSERT INTO messages (sender_id, recipient_id, subject, message_body)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$userId, $recipientId, $subject, $messageBody]);
                
                // Create notification for recipient
                createNotification(
                    $recipientId,
                    'new_message',
                    'New Message: ' . $subject,
                    'You have a new message from a user.',
                    $userId
                );
                
                $success = 'Message sent successfully!';
            } catch (Exception $e) {
                $error = 'Failed to send message.';
            }
        }
    }
}

// Handle marking as read
if (isset($_GET['mark_read']) && isset($_GET['message_id'])) {
    $messageId = (int)$_GET['message_id'];
    $stmt = getDB()->prepare('
        UPDATE messages SET is_read = TRUE, read_at = NOW()
        WHERE id = ? AND recipient_id = ?
    ');
    $stmt->execute([$messageId, $userId]);
}

// Get inbox messages
$stmt = getDB()->prepare('
    SELECT m.*, u.first_name, u.last_name, u.email
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ?
    ORDER BY m.sent_at DESC
    LIMIT 50
');
$stmt->execute([$userId]);
$inboxMessages = $stmt->fetchAll();

// Get sent messages
$stmt = getDB()->prepare('
    SELECT m.*, u.first_name, u.last_name, u.email
    FROM messages m
    JOIN users u ON m.recipient_id = u.id
    WHERE m.sender_id = ?
    ORDER BY m.sent_at DESC
    LIMIT 50
');
$stmt->execute([$userId]);
$sentMessages = $stmt->fetchAll();

// Get unread count
$stmt = getDB()->prepare('
    SELECT COUNT(*) as count FROM messages
    WHERE recipient_id = ? AND is_read = FALSE
');
$stmt->execute([$userId]);
$unreadCount = $stmt->fetch()['count'];

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="messages-container">
    <h1>Messages</h1>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Message Tabs -->
    <div class="message-tabs">
        <a href="/user/messages.php?action=inbox" 
           class="tab <?php echo $action === 'inbox' ? 'active' : ''; ?>">
            Inbox <?php if ($unreadCount > 0): ?><span class="badge"><?php echo $unreadCount; ?></span><?php endif; ?>
        </a>
        <a href="/user/messages.php?action=sent" 
           class="tab <?php echo $action === 'sent' ? 'active' : ''; ?>">
            Sent
        </a>
        <a href="/user/messages.php?action=compose" 
           class="tab <?php echo $action === 'compose' ? 'active' : ''; ?>">
            Compose
        </a>
    </div>

    <!-- Inbox View -->
    <?php if ($action === 'inbox'): ?>
        <div class="messages-list">
            <h2>Inbox</h2>
            <?php if (!empty($inboxMessages)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inboxMessages as $msg): ?>
                            <tr class="<?php echo !$msg['is_read'] ? 'unread' : ''; ?>">
                                <td><?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['subject'] ?? '(No Subject)'); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <?php if (!$msg['is_read']): ?>
                                        <span class="badge badge-info">Unread</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/user/message-detail.php?id=<?php echo $msg['id']; ?>" 
                                       class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No messages in inbox.</p>
            <?php endif; ?>
        </div>

    <!-- Sent View -->
    <?php elseif ($action === 'sent'): ?>
        <div class="messages-list">
            <h2>Sent Messages</h2>
            <?php if (!empty($sentMessages)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sentMessages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($msg['subject'] ?? '(No Subject)'); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <a href="/user/message-detail.php?id=<?php echo $msg['id']; ?>" 
                                       class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No sent messages.</p>
            <?php endif; ?>
        </div>

    <!-- Compose View -->
    <?php elseif ($action === 'compose'): ?>
        <div class="compose-form">
            <h2>Compose Message</h2>
            
            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="send">

                <div class="form-group">
                    <label for="recipient_id">Recipient *</label>
                    <select id="recipient_id" name="recipient_id" required>
                        <option value="">-- Select Recipient --</option>
                        <?php
                        // Get list of users to message
                        $stmt = getDB()->prepare('
                            SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
                            FROM users u
                            WHERE u.id != ?
                            ORDER BY u.first_name, u.last_name
                            LIMIT 100
                        ');
                        $stmt->execute([$userId]);
                        $users = $stmt->fetchAll();
                        
                        foreach ($users as $user):
                        ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> 
                                (<?php echo htmlspecialchars($user['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" 
                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                           placeholder="Message subject (optional)">
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required rows="8"
                              placeholder="Type your message here..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Message</button>
                    <a href="/user/messages.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
    .messages-container {
        max-width: 1000px;
        margin: 40px auto;
    }

    .message-tabs {
        display: flex;
        gap: 10px;
        margin: 20px 0;
        border-bottom: 2px solid #ddd;
    }

    .tab {
        padding: 12px 20px;
        text-decoration: none;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tab:hover {
        color: #007bff;
    }

    .tab.active {
        color: #007bff;
        border-bottom-color: #007bff;
    }

    .tab .badge {
        background-color: #dc3545;
        color: white;
        padding: 2px 6px;
        border-radius: 12px;
        font-size: 0.75rem;
    }

    .messages-list {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .messages-list h2 {
        margin-bottom: 20px;
    }

    .table tbody tr.unread {
        background-color: #f0f8ff;
        font-weight: 500;
    }

    .compose-form {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .compose-form h2 {
        margin-bottom: 20px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .form-actions .btn {
        flex: 1;
    }

    @media (max-width: 768px) {
        .message-tabs {
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 15px;
            font-size: 0.95rem;
        }

        .compose-form {
            padding: 20px;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
