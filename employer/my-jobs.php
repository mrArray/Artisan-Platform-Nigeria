<?php
/**
 * Employer My Jobs Page
 * 
 * Allows employers to view and manage their job postings
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole('employer');

$pageTitle = 'My Job Postings';
$userId = $_SESSION['user_id'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get employer profile
try {
    $stmt = getDB()->prepare('SELECT id FROM employer_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $employerProfile = $stmt->fetch();

    if (!$employerProfile) {
        $_SESSION['error'] = 'Employer profile not found. Please complete your profile.';
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

// Handle job deletion
if (isset($_GET['delete']) && isset($_GET['job_id']) && isset($_GET['csrf_token'])) {
    if ($_GET['csrf_token'] === $_SESSION['csrf_token']) {
        $jobId = (int)$_GET['job_id'];

        try {
            // Verify job belongs to this employer
            $stmt = getDB()->prepare('SELECT id FROM jobs WHERE id = ? AND employer_id = ?');
            $stmt->execute([$jobId, $employerId]);
            
            if ($stmt->fetch()) {
                // Delete the job
                $stmt = getDB()->prepare('DELETE FROM jobs WHERE id = ?');
                $stmt->execute([$jobId]);
                
                $_SESSION['success'] = 'Job deleted successfully';
            } else {
                $_SESSION['error'] = 'Job not found or access denied';
            }
        } catch (PDOException $e) {
            error_log('Error deleting job: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete job';
        }
        
        header('Location: /employer/my-jobs.php');
        exit;
    }
}

// Handle job status update
if (isset($_GET['update_status']) && isset($_GET['job_id']) && isset($_GET['status']) && isset($_GET['csrf_token'])) {
    if ($_GET['csrf_token'] === $_SESSION['csrf_token']) {
        $jobId = (int)$_GET['job_id'];
        $newStatus = $_GET['status'];
        
        $validStatuses = ['open', 'in_progress', 'completed', 'closed'];
        
        if (in_array($newStatus, $validStatuses)) {
            try {
                // Verify job belongs to this employer
                $stmt = getDB()->prepare('SELECT id FROM jobs WHERE id = ? AND employer_id = ?');
                $stmt->execute([$jobId, $employerId]);
                
                if ($stmt->fetch()) {
                    // Update status
                    $stmt = getDB()->prepare('UPDATE jobs SET status = ? WHERE id = ?');
                    $stmt->execute([$newStatus, $jobId]);
                    
                    $_SESSION['success'] = 'Job status updated successfully';
                } else {
                    $_SESSION['error'] = 'Job not found or access denied';
                }
            } catch (PDOException $e) {
                error_log('Error updating job status: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update job status';
            }
        } else {
            $_SESSION['error'] = 'Invalid status';
        }
        
        header('Location: /employer/my-jobs.php');
        exit;
    }
}

// Filter parameters
$statusFilter = $_GET['status'] ?? 'all';

// Get jobs
try {
    $query = '
        SELECT 
            j.*,
            COUNT(DISTINCT ja.id) as application_count,
            COUNT(DISTINCT CASE WHEN ja.status = "accepted" THEN ja.id END) as accepted_count
        FROM jobs j
        LEFT JOIN job_applications ja ON j.id = ja.job_id
        WHERE j.employer_id = ?
    ';

    $params = [$employerId];

    if ($statusFilter !== 'all') {
        $query .= ' AND j.status = ?';
        $params[] = $statusFilter;
    }

    $query .= '
        GROUP BY j.id
        ORDER BY j.posted_date DESC
    ';

    $stmt = getDB()->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    // Get total counts
    $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM jobs WHERE employer_id = ?');
    $stmt->execute([$employerId]);
    $totalJobs = $stmt->fetch()['count'];

    $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM jobs WHERE employer_id = ? AND status = "open"');
    $stmt->execute([$employerId]);
    $openJobs = $stmt->fetch()['count'];

    $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM jobs WHERE employer_id = ? AND status = "closed"');
    $stmt->execute([$employerId]);
    $closedJobs = $stmt->fetch()['count'];

} catch (PDOException $e) {
    error_log('Error fetching jobs: ' . $e->getMessage());
    $jobs = [];
    $totalJobs = 0;
    $openJobs = 0;
    $closedJobs = 0;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>My Job Postings</h1>
        <p>Manage your job vacancies and applications</p>
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

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Jobs</h3>
            <div class="stat-number"><?php echo $totalJobs; ?></div>
            <a href="/employer/my-jobs.php">View All</a>
        </div>

        <div class="stat-card">
            <h3>Open Jobs</h3>
            <div class="stat-number"><?php echo $openJobs; ?></div>
            <a href="/employer/my-jobs.php?status=open">View Open</a>
        </div>

        <div class="stat-card">
            <h3>Closed Jobs</h3>
            <div class="stat-number"><?php echo $closedJobs; ?></div>
            <a href="/employer/my-jobs.php?status=closed">View Closed</a>
        </div>
    </div>

    <!-- Actions -->
    <div class="card" style="margin-bottom: 20px;">
        <a href="/employer/post-job.php" class="btn btn-primary">Post New Job</a>
        <a href="/employer/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Filter Tabs -->
    <div class="card" style="margin-bottom: 20px;">
        <a href="/employer/my-jobs.php" 
           class="btn <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
            All Jobs
        </a>
        <a href="/employer/my-jobs.php?status=open" 
           class="btn <?php echo $statusFilter === 'open' ? 'btn-primary' : 'btn-secondary'; ?>">
            Open
        </a>
        <a href="/employer/my-jobs.php?status=in_progress" 
           class="btn <?php echo $statusFilter === 'in_progress' ? 'btn-primary' : 'btn-secondary'; ?>">
            In Progress
        </a>
        <a href="/employer/my-jobs.php?status=completed" 
           class="btn <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">
            Completed
        </a>
        <a href="/employer/my-jobs.php?status=closed" 
           class="btn <?php echo $statusFilter === 'closed' ? 'btn-primary' : 'btn-secondary'; ?>">
            Closed
        </a>
    </div>

    <!-- Jobs List -->
    <div class="profile-section">
        <h2>Job Listings</h2>

        <?php if (count($jobs) > 0): ?>
            <div class="table">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Applications</th>
                            <th>Posted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                    <?php if ($job['category']): ?>
                                        <br><small><?php echo htmlspecialchars($job['category']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($job['location']); ?>
                                    <?php if ($job['state']): ?>
                                        <br><small><?php echo htmlspecialchars($job['state']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($job['status']) {
                                        'open' => 'badge-accepted',
                                        'in_progress' => 'badge-pending',
                                        'completed' => 'badge-accepted',
                                        'closed' => 'badge-withdrawn',
                                        default => 'badge-withdrawn'
                                    };
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo $job['application_count']; ?></strong> applications
                                    <?php if ($job['accepted_count'] > 0): ?>
                                        <br><small><?php echo $job['accepted_count']; ?> accepted</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($job['posted_date'])); ?></td>
                                <td>
                                    <div class="action-buttons" style="display: flex; flex-direction: column; gap: 5px;">
                                        <a href="/employer/job-detail.php?id=<?php echo $job['id']; ?>" 
                                           class="btn btn-sm btn-primary">View Details</a>
                                        
                                        <?php if ($job['status'] === 'open'): ?>
                                            <a href="/employer/my-jobs.php?update_status=1&job_id=<?php echo $job['id']; ?>&status=closed&csrf_token=<?php echo urlencode($csrfToken); ?>" 
                                               class="btn btn-sm btn-warning"
                                               data-confirm="Are you sure you want to close this job posting?"
                                               data-confirm-title="Close Job">Close Job</a>
                                        <?php elseif ($job['status'] === 'closed'): ?>
                                            <a href="/employer/my-jobs.php?update_status=1&job_id=<?php echo $job['id']; ?>&status=open&csrf_token=<?php echo urlencode($csrfToken); ?>" 
                                               class="btn btn-sm btn-success">Reopen Job</a>
                                        <?php endif; ?>
                                        
                                        <a href="/employer/my-jobs.php?delete=1&job_id=<?php echo $job['id']; ?>&csrf_token=<?php echo urlencode($csrfToken); ?>" 
                                           class="btn btn-sm btn-danger"
                                           data-confirm="Are you sure you want to delete this job? This action cannot be undone."
                                           data-confirm-title="Delete Job">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No jobs found. <a href="/employer/post-job.php">Post your first job</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
