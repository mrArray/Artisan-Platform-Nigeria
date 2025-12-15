<?php
/**
 * Artisan Dashboard
 * 
 * Displays artisan profile overview, recent applications, and statistics
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Dashboard - Artisan';

// Require artisan role
requireRole('artisan');

$userId = getCurrentUserId();

// Get artisan profile
$stmt = getDB()->prepare('
    SELECT ap.*, u.email, u.phone, u.first_name, u.last_name
    FROM artisan_profiles ap
    JOIN users u ON ap.user_id = u.id
    WHERE ap.user_id = ?
');
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Get recent applications
$stmt = getDB()->prepare('
    SELECT ja.*, j.title, j.budget_max, j.budget_min, ep.company_name
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN employer_profiles ep ON j.employer_id = ep.id
    WHERE ja.artisan_id = ?
    ORDER BY ja.applied_date DESC
    LIMIT 5
');
$stmt->execute([$profile['id']]);
$applications = $stmt->fetchAll();

// Get statistics
$stmt = getDB()->prepare('
    SELECT COUNT(*) as total FROM job_applications WHERE artisan_id = ?
');
$stmt->execute([$profile['id']]);
$appStats = $stmt->fetch();

$stmt = getDB()->prepare('
    SELECT COUNT(*) as total FROM reviews WHERE reviewed_user_id = ?
');
$stmt->execute([$userId]);
$reviewStats = $stmt->fetch();

$stmt = getDB()->prepare('
    SELECT COUNT(*) as total FROM artisan_skills WHERE artisan_id = ?
');
$stmt->execute([$profile['id']]);
$skillStats = $stmt->fetch();

// Get unread messages and notifications
$unreadMessages = getUnreadMessagesCount($userId);
$unreadNotifications = getUnreadNotificationsCount($userId);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($profile['first_name']); ?>!</h1>
        <p>Your artisan profile dashboard</p>
    </div>

    <!-- Alert Messages -->
    <?php if ($unreadMessages > 0 || $unreadNotifications > 0): ?>
        <div class="alert alert-info">
            <?php if ($unreadMessages > 0): ?>
                <p>You have <strong><?php echo $unreadMessages; ?></strong> unread message(s). <a href="/user/messages.php">View</a></p>
            <?php endif; ?>
            <?php if ($unreadNotifications > 0): ?>
                <p>You have <strong><?php echo $unreadNotifications; ?></strong> new notification(s). <a href="/user/notifications.php">View</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Section -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Applications</h3>
            <p class="stat-number"><?php echo $appStats['total']; ?></p>
            <a href="/artisan/applications.php">View Details</a>
        </div>

        <div class="stat-card">
            <h3>Skills Added</h3>
            <p class="stat-number"><?php echo $skillStats['total']; ?></p>
            <a href="/artisan/profile.php">Manage Skills</a>
        </div>

        <div class="stat-card">
            <h3>Rating</h3>
            <p class="stat-number"><?php echo number_format($profile['rating'], 1); ?>/5.0</p>
            <p class="stat-subtitle"><?php echo $reviewStats['total']; ?> reviews</p>
        </div>

        <div class="stat-card">
            <h3>Status</h3>
            <p class="stat-badge <?php echo 'status-' . $profile['availability_status']; ?>">
                <?php echo ucfirst($profile['availability_status']); ?>
            </p>
            <a href="/artisan/profile.php">Update Status</a>
        </div>
    </div>

    <!-- Profile Verification Status -->
    <div class="info-card">
        <h3>Profile Verification</h3>
        <?php if ($profile['verification_status'] === 'verified'): ?>
            <div class="alert alert-success">
                ✓ Your profile has been verified by the government agency.
            </div>
        <?php elseif ($profile['verification_status'] === 'pending'): ?>
            <div class="alert alert-warning">
                ⏳ Your profile is pending verification. This may take 2-3 business days.
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                ✗ Your profile verification was rejected. Please update your information and resubmit.
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="/artisan/jobs.php" class="btn btn-primary">Browse Jobs</a>
            <a href="/artisan/profile.php" class="btn btn-secondary">Edit Profile</a>
            <a href="/user/messages.php" class="btn btn-secondary">Messages</a>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="recent-section">
        <h3>Recent Applications</h3>
        <?php if (!empty($applications)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['title']); ?></td>
                            <td><?php echo htmlspecialchars($app['company_name'] ?? 'N/A'); ?></td>
                            <td>
                                ₦<?php echo number_format($app['budget_min']); ?> - 
                                ₦<?php echo number_format($app['budget_max']); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                            <td>
                                <a href="/artisan/application-detail.php?id=<?php echo $app['id']; ?>" 
                                   class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No applications yet. <a href="/artisan/jobs.php">Start applying for jobs</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
