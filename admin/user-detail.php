<?php
/**
 * Admin User Detail
 * 
 * View and manage an individual user's account and profile
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'User Detail - Admin';

// Require admin role
requireRole('admin');

$adminId = getCurrentUserId();
$errors = [];
$success = false;

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: /admin/users.php');
    exit;
}

// Handle account actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $action = sanitizeInput($_POST['action']);
        try {
            if ($action === 'activate') {
                $stmt = getDB()->prepare('UPDATE users SET status = "active" WHERE id = ?');
                $stmt->execute([$userId]);
                $success = 'User activated successfully.';
            } elseif ($action === 'suspend') {
                $stmt = getDB()->prepare('UPDATE users SET status = "suspended" WHERE id = ?');
                $stmt->execute([$userId]);
                $success = 'User suspended successfully.';
            } elseif ($action === 'delete') {
                $stmt = getDB()->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                header('Location: /admin/users.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to update user: ' . $e->getMessage();
        }
    }
}

// Get base user
$stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: /admin/users.php');
    exit;
}

// Get role-specific profile
$profile = null;
$documents = [];
if ($user['role'] === 'artisan') {
    $stmt = getDB()->prepare('
        SELECT ap.*, GROUP_CONCAT(DISTINCT s.name SEPARATOR ", ") as skills
        FROM artisan_profiles ap
        LEFT JOIN artisan_skills ask ON ap.id = ask.artisan_id
        LEFT JOIN skills s ON ask.skill_id = s.id
        WHERE ap.user_id = ?
        GROUP BY ap.id
    ');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    $stmt = getDB()->prepare('SELECT * FROM documents WHERE artisan_id = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$profile['id'] ?? 0]);
    $documents = $stmt->fetchAll();
} elseif ($user['role'] === 'employer') {
    $stmt = getDB()->prepare('SELECT * FROM employer_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
}

// Recent applications/jobs
$recentItems = [];
if ($user['role'] === 'artisan') {
    $stmt = getDB()->prepare('
        SELECT ja.*, j.title as job_title, j.state as job_state
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        WHERE ja.artisan_id = ?
        ORDER BY ja.applied_date DESC
        LIMIT 10
    ');
    $stmt->execute([$profile['id'] ?? 0]);
    $recentItems = $stmt->fetchAll();
} elseif ($user['role'] === 'employer') {
    $stmt = getDB()->prepare('
        SELECT j.*
        FROM jobs j
        WHERE j.employer_id = ?
        ORDER BY j.posted_date DESC
        LIMIT 10
    ');
    $stmt->execute([$profile['id'] ?? 0]);
    $recentItems = $stmt->fetchAll();
}

// Verification logs
$stmt = getDB()->prepare('
    SELECT vl.*, admin.first_name as admin_first_name, admin.last_name as admin_last_name
    FROM verification_logs vl
    LEFT JOIN users admin ON vl.admin_id = admin.id
    WHERE vl.user_id = ?
    ORDER BY vl.created_at DESC
');
$stmt->execute([$userId]);
$verificationHistory = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container main-content">
    <div class="page-header">
        <h1>User Detail</h1>
        <a href="/admin/users.php" class="btn btn-secondary">← Back to Users</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>Account</h2>
            <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Name:</label>
                    <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <label>Phone:</label>
                    <span><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <label>Status:</label>
                    <span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span>
                </div>
                <div class="info-item">
                    <label>Email Verified:</label>
                    <span><?php echo $user['email_verified'] ? '✓ Yes' : '✗ No'; ?></span>
                </div>
                <div class="info-item">
                    <label>Profile Verified:</label>
                    <span><?php echo $user['profile_verified'] ? '✓ Yes' : '✗ No'; ?></span>
                </div>
                <div class="info-item">
                    <label>Member Since:</label>
                    <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>

            <form method="POST" class="form-inline">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <button class="btn btn-success" name="action" value="activate">Activate</button>
                <button class="btn btn-warning" name="action" value="suspend">Suspend</button>
                <button class="btn btn-danger" name="action" value="delete" onclick="return confirm('Delete this user? This cannot be undone.')">Delete</button>
            </form>
        </div>
    </div>

    <?php if ($profile): ?>
        <div class="card">
            <div class="card-header">
                <h2><?php echo $user['role'] === 'artisan' ? 'Artisan Profile' : 'Employer Profile'; ?></h2>
            </div>
            <div class="card-body">
                <?php if ($user['role'] === 'artisan'): ?>
                    <div class="info-grid">
                        <div class="info-item"><label>Location:</label><span><?php echo htmlspecialchars($profile['location'] ?? 'N/A'); ?></span></div>
                        <div class="info-item"><label>State:</label><span><?php echo htmlspecialchars($profile['state'] ?? 'N/A'); ?></span></div>
                        <div class="info-item"><label>Experience:</label><span><?php echo (int)$profile['years_of_experience']; ?> years</span></div>
                        <div class="info-item"><label>Hourly Rate:</label><span>₦<?php echo number_format($profile['hourly_rate'] ?? 0, 2); ?></span></div>
                        <div class="info-item"><label>Availability:</label><span class="badge badge-<?php echo $profile['availability_status']; ?>"><?php echo ucfirst($profile['availability_status']); ?></span></div>
                        <div class="info-item"><label>Rating:</label><span>⭐ <?php echo number_format($profile['rating'], 2); ?> (<?php echo $profile['total_reviews']; ?> reviews)</span></div>
                        <div class="info-item full-width"><label>Skills:</label><span><?php echo htmlspecialchars($profile['skills'] ?? 'No skills listed'); ?></span></div>
                        <?php if ($profile['bio']): ?><div class="info-item full-width"><label>Bio:</label><p><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></p></div><?php endif; ?>
                    </div>

                    <?php if (!empty($documents)): ?>
                    <h3>Documents</h3>
                    <div class="documents-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item">
                                <div class="document-info">
                                    <span class="document-type"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                                    <span class="document-name"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                                    <span class="document-size"><?php echo round($doc['file_size'] / 1024, 2); ?> KB</span>
                                    <span class="document-date"><?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?></span>
                                </div>
                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-secondary">View</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="info-grid">
                        <div class="info-item"><label>Company Name:</label><span><?php echo htmlspecialchars($profile['company_name'] ?? 'N/A'); ?></span></div>
                        <div class="info-item"><label>Type:</label><span><?php echo ucfirst($profile['company_type']); ?></span></div>
                        <div class="info-item"><label>Phone:</label><span><?php echo htmlspecialchars($profile['company_phone'] ?? 'N/A'); ?></span></div>
                        <div class="info-item"><label>Website:</label><span><?php echo htmlspecialchars($profile['company_website'] ?? 'N/A'); ?></span></div>
                        <div class="info-item"><label>Rating:</label><span>⭐ <?php echo number_format($profile['rating'], 2); ?> (<?php echo $profile['total_reviews']; ?> reviews)</span></div>
                        <?php if ($profile['company_address']): ?><div class="info-item full-width"><label>Address:</label><span><?php echo htmlspecialchars($profile['company_address']); ?></span></div><?php endif; ?>
                        <?php if ($profile['company_description']): ?><div class="info-item full-width"><label>Description:</label><p><?php echo nl2br(htmlspecialchars($profile['company_description'])); ?></p></div><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($verificationHistory)): ?>
        <div class="card">
            <div class="card-header"><h2>Verification History</h2></div>
            <div class="card-body">
                <ul class="list">
                    <?php foreach ($verificationHistory as $v): ?>
                        <li>
                            <span class="badge badge-<?php echo $v['status']; ?>"><?php echo ucfirst($v['status']); ?></span>
                            <span><?php echo date('M j, Y, g:i a', strtotime($v['created_at'])); ?></span>
                            <?php if ($v['admin_first_name']): ?>
                                <span> by <?php echo htmlspecialchars($v['admin_first_name'] . ' ' . $v['admin_last_name']); ?></span>
                            <?php endif; ?>
                            <?php if ($v['comments']): ?>
                                <div><?php echo nl2br(htmlspecialchars($v['comments'])); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($recentItems)): ?>
        <div class="card">
            <div class="card-header">
                <h2><?php echo $user['role'] === 'artisan' ? 'Recent Applications' : 'Recent Jobs'; ?></h2>
            </div>
            <div class="card-body">
                <ul class="list">
                    <?php if ($user['role'] === 'artisan'): ?>
                        <?php foreach ($recentItems as $app): ?>
                            <li>
                                <span><?php echo htmlspecialchars($app['job_title']); ?></span>
                                <span class="badge badge-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span>
                                <span>Applied: <?php echo date('M j, Y', strtotime($app['applied_date'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($recentItems as $job): ?>
                            <li>
                                <span><?php echo htmlspecialchars($job['title']); ?></span>
                                <span class="badge badge-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                                <span>Posted: <?php echo date('M j, Y', strtotime($job['posted_date'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
