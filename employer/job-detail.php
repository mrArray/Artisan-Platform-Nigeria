<?php
/**
 * Employer Job Detail Page
 * 
 * View detailed information about a job posting and its applications
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole('employer');

$pageTitle = 'Job Details';
$userId = $_SESSION['user_id'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get job ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid job ID';
    header('Location: /employer/my-jobs.php');
    exit;
}

$jobId = (int)$_GET['id'];

// Get employer profile
try {
    $stmt = getDB()->prepare('SELECT id FROM employer_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $employerProfile = $stmt->fetch();

    if (!$employerProfile) {
        $_SESSION['error'] = 'Employer profile not found';
        header('Location: /employer/dashboard.php');
        exit;
    }

    $employerId = $employerProfile['id'];
} catch (PDOException $e) {
    error_log('Error fetching employer profile: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /employer/dashboard.php');
    exit;
}

// Get job details
try {
    $stmt = getDB()->prepare('
        SELECT j.*, 
               COUNT(DISTINCT ja.id) as application_count,
               COUNT(DISTINCT CASE WHEN ja.status = "pending" THEN ja.id END) as pending_count,
               COUNT(DISTINCT CASE WHEN ja.status = "accepted" THEN ja.id END) as accepted_count
        FROM jobs j
        LEFT JOIN job_applications ja ON j.id = ja.job_id
        WHERE j.id = ? AND j.employer_id = ?
        GROUP BY j.id
    ');
    $stmt->execute([$jobId, $employerId]);
    $job = $stmt->fetch();

    if (!$job) {
        $_SESSION['error'] = 'Job not found or access denied';
        header('Location: /employer/my-jobs.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching job: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /employer/my-jobs.php');
    exit;
}

// Get job applications
try {
    $stmt = getDB()->prepare('
        SELECT 
            ja.*,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            ap.bio,
            ap.hourly_rate,
            ap.experience_years,
            ap.location,
            ap.state,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.artisan_id = ap.id) as avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.artisan_id = ap.id) as review_count
        FROM job_applications ja
        INNER JOIN artisan_profiles ap ON ja.artisan_id = ap.id
        INNER JOIN users u ON ap.user_id = u.id
        WHERE ja.job_id = ?
        ORDER BY 
            CASE ja.status
                WHEN "pending" THEN 1
                WHEN "accepted" THEN 2
                WHEN "rejected" THEN 3
                WHEN "withdrawn" THEN 4
            END,
            ja.applied_date DESC
    ');
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching applications: ' . $e->getMessage());
    $applications = [];
}

// Parse required skills if exists
$requiredSkills = [];
if (!empty($job['required_skills'])) {
    $requiredSkills = explode(',', $job['required_skills']);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Job Details</h1>
        <div class="action-buttons">
            <a href="/employer/my-jobs.php" class="btn btn-secondary">Back to My Jobs</a>
            <a href="/employer/post-job.php?edit=<?php echo $job['id']; ?>" class="btn btn-primary">Edit Job</a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo htmlspecialchars($_SESSION['success']);
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo htmlspecialchars($_SESSION['error']);
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Job Information -->
    <div class="profile-section">
        <h2><?php echo htmlspecialchars($job['title']); ?></h2>
        
        <div class="job-meta" style="margin-bottom: 20px;">
            <span class="badge <?php 
                echo match($job['status']) {
                    'open' => 'badge-accepted',
                    'in_progress' => 'badge-pending',
                    'completed' => 'badge-accepted',
                    'closed' => 'badge-withdrawn',
                    default => 'badge-withdrawn'
                };
            ?>">
                <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
            </span>
            <span style="margin-left: 15px; color: #666;">
                Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
            </span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><strong>Category:</strong></label>
                <p><?php echo htmlspecialchars($job['category'] ?? 'N/A'); ?></p>
            </div>

            <div class="form-group">
                <label><strong>Location:</strong></label>
                <p><?php echo htmlspecialchars($job['location']); ?><?php if ($job['state']): ?>, <?php echo htmlspecialchars($job['state']); ?><?php endif; ?></p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><strong>Budget Range:</strong></label>
                <p>
                    <?php if ($job['budget_min'] && $job['budget_max']): ?>
                        ₦<?php echo number_format($job['budget_min'], 2); ?> - ₦<?php echo number_format($job['budget_max'], 2); ?>
                    <?php else: ?>
                        Negotiable
                    <?php endif; ?>
                </p>
            </div>

            <div class="form-group">
                <label><strong>Duration:</strong></label>
                <p><?php echo htmlspecialchars($job['duration'] ?? 'Not specified'); ?></p>
            </div>
        </div>

        <div class="form-group">
            <label><strong>Experience Level:</strong></label>
            <p><?php echo ucfirst($job['experience_level']); ?></p>
        </div>

        <?php if ($job['deadline']): ?>
            <div class="form-group">
                <label><strong>Application Deadline:</strong></label>
                <p><?php echo date('M d, Y', strtotime($job['deadline'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label><strong>Description:</strong></label>
            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        </div>

        <?php if (!empty($requiredSkills)): ?>
            <div class="form-group">
                <label><strong>Required Skills:</strong></label>
                <div class="skill-tags">
                    <?php foreach ($requiredSkills as $skill): ?>
                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Applications Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Applications</h3>
            <div class="stat-number"><?php echo $job['application_count']; ?></div>
        </div>

        <div class="stat-card">
            <h3>Pending Review</h3>
            <div class="stat-number"><?php echo $job['pending_count']; ?></div>
        </div>

        <div class="stat-card">
            <h3>Accepted</h3>
            <div class="stat-number"><?php echo $job['accepted_count']; ?></div>
        </div>
    </div>

    <!-- Applications List -->
    <div class="profile-section">
        <h2>Applications (<?php echo count($applications); ?>)</h2>

        <?php if (count($applications) > 0): ?>
            <div class="jobs-grid">
                <?php foreach ($applications as $app): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <h3><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h3>
                            <span class="badge <?php 
                                echo match($app['status']) {
                                    'pending' => 'badge-pending',
                                    'accepted' => 'badge-accepted',
                                    'rejected' => 'badge-rejected',
                                    'withdrawn' => 'badge-withdrawn',
                                    default => 'badge-withdrawn'
                                };
                            ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </div>

                        <div class="job-details">
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($app['location']); ?><?php if ($app['state']): ?>, <?php echo htmlspecialchars($app['state']); ?><?php endif; ?></p>
                            <p><strong>Experience:</strong> <?php echo htmlspecialchars($app['experience_years']); ?> years</p>
                            <p><strong>Rate:</strong> ₦<?php echo number_format($app['hourly_rate'], 2); ?>/hour</p>
                            <?php if ($app['proposed_rate']): ?>
                                <p><strong>Proposed Rate:</strong> ₦<?php echo number_format($app['proposed_rate'], 2); ?></p>
                            <?php endif; ?>
                            <?php if ($app['avg_rating']): ?>
                                <p><strong>Rating:</strong> <?php echo number_format($app['avg_rating'], 1); ?>/5.0 (<?php echo $app['review_count']; ?> reviews)</p>
                            <?php endif; ?>
                            <p><strong>Applied:</strong> <?php echo date('M d, Y', strtotime($app['applied_date'])); ?></p>
                        </div>

                        <?php if ($app['cover_letter']): ?>
                            <div class="job-description">
                                <strong>Cover Letter:</strong>
                                <p><?php echo nl2br(htmlspecialchars(substr($app['cover_letter'], 0, 200))); ?><?php echo strlen($app['cover_letter']) > 200 ? '...' : ''; ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="job-actions">
                            <a href="/employer/application-detail.php?id=<?php echo $app['id']; ?>" 
                               class="btn btn-primary">View Full Application</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No applications received yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
