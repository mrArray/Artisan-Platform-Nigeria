<?php
/**
 * Employer Dashboard
 * 
 * Displays employer profile overview, job postings, and applications
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Dashboard - Employer';

// Require employer role
requireRole('employer');

$userId = getCurrentUserId();

// Get employer profile
$stmt = getDB()->prepare('
    SELECT ep.*, u.email, u.phone, u.first_name, u.last_name
    FROM employer_profiles ep
    JOIN users u ON ep.user_id = u.id
    WHERE ep.user_id = ?
');
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Get recent job postings
$stmt = getDB()->prepare('
    SELECT * FROM jobs
    WHERE employer_id = ?
    ORDER BY posted_date DESC
    LIMIT 5
');
$stmt->execute([$profile['id']]);
$jobs = $stmt->fetchAll();

// Get recent applications
$stmt = getDB()->prepare('
    SELECT ja.*, j.title, ap.user_id, u.first_name, u.last_name
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN artisan_profiles ap ON ja.artisan_id = ap.id
    JOIN users u ON ap.user_id = u.id
    WHERE j.employer_id = ?
    ORDER BY ja.applied_date DESC
    LIMIT 10
');
$stmt->execute([$profile['id']]);
$applications = $stmt->fetchAll();

// Get statistics
$stmt = getDB()->prepare('
    SELECT COUNT(*) as total FROM jobs WHERE employer_id = ?
');
$stmt->execute([$profile['id']]);
$jobStats = $stmt->fetch();

$stmt = getDB()->prepare('
    SELECT COUNT(*) as total FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    WHERE j.employer_id = ?
');
$stmt->execute([$profile['id']]);
$appStats = $stmt->fetch();

$stmt = getDB()->prepare('
    SELECT COUNT(*) as total FROM reviews WHERE reviewed_user_id = ?
');
$stmt->execute([$userId]);
$reviewStats = $stmt->fetch();

// Get unread messages and notifications
$unreadMessages = getUnreadMessagesCount($userId);
$unreadNotifications = getUnreadNotificationsCount($userId);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($profile['first_name']); ?>!</h1>
        <p>Your employer dashboard</p>
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
            <h3>Total Jobs Posted</h3>
            <p class="stat-number"><?php echo $jobStats['total']; ?></p>
            <a href="/employer/my-jobs.php">View All</a>
        </div>

        <div class="stat-card">
            <h3>Total Applications</h3>
            <p class="stat-number"><?php echo $appStats['total']; ?></p>
            <a href="/employer/applications.php">View All</a>
        </div>

        <div class="stat-card">
            <h3>Rating</h3>
            <p class="stat-number"><?php echo number_format($profile['rating'], 1); ?>/5.0</p>
            <p class="stat-subtitle"><?php echo $reviewStats['total']; ?> reviews</p>
        </div>

        <div class="stat-card">
            <h3>Profile Status</h3>
            <p class="stat-badge <?php echo 'status-' . $profile['verification_status']; ?>">
                <?php echo ucfirst($profile['verification_status']); ?>
            </p>
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
            <a href="/employer/post-job.php" class="btn btn-primary">Post New Job</a>
            <a href="/employer/artisans.php" class="btn btn-secondary">Find Artisans</a>
            <a href="/user/messages.php" class="btn btn-secondary">Messages</a>
        </div>
    </div>

    <!-- Recent Job Postings -->
    <div class="recent-section">
        <h3>Recent Job Postings</h3>
        <?php if (!empty($jobs)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th>Budget</th>
                        <th>Posted Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $job['status']; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td>
                                ₦<?php echo number_format($job['budget_min']); ?> - 
                                ₦<?php echo number_format($job['budget_max']); ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($job['posted_date'])); ?></td>
                            <td>
                                <a href="/employer/job-detail.php?id=<?php echo $job['id']; ?>" 
                                   class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No jobs posted yet. <a href="/employer/post-job.php">Post your first job</a></p>
        <?php endif; ?>
    </div>

    <!-- Recent Applications -->
    <div class="recent-section">
        <h3>Recent Applications</h3>
        <?php if (!empty($applications)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Artisan Name</th>
                        <th>Job Title</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($app['title']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                            <td>
                                <a href="/employer/application-detail.php?id=<?php echo $app['id']; ?>" 
                                   class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No applications received yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
