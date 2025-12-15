<?php
/**
 * Artisan Job Application Page
 * 
 * Allows artisans to apply for a job posting
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole('artisan');

$pageTitle = 'Apply for Job';
$userId = $_SESSION['user_id'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get job ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid job ID';
    header('Location: /artisan/jobs.php');
    exit;
}

$jobId = (int)$_GET['id'];

// Get artisan profile
try {
    $stmt = getDB()->prepare('SELECT id, hourly_rate FROM artisan_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $artisanProfile = $stmt->fetch();

    if (!$artisanProfile) {
        $_SESSION['error'] = 'Please complete your artisan profile before applying for jobs';
        header('Location: /artisan/profile.php');
        exit;
    }

    $artisanId = $artisanProfile['id'];
} catch (PDOException $e) {
    error_log('Error fetching artisan profile: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /artisan/jobs.php');
    exit;
}

// Get job details
try {
    $stmt = getDB()->prepare('
        SELECT j.*, 
               u.first_name as employer_first_name,
               u.last_name as employer_last_name,
               ep.company_name
        FROM jobs j
        INNER JOIN employer_profiles ep ON j.employer_id = ep.id
        INNER JOIN users u ON ep.user_id = u.id
        WHERE j.id = ? AND j.status = "open"
    ');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) {
        $_SESSION['error'] = 'Job not found or no longer accepting applications';
        header('Location: /artisan/jobs.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching job: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /artisan/jobs.php');
    exit;
}

// Check if already applied
try {
    $stmt = getDB()->prepare('
        SELECT id, status 
        FROM job_applications 
        WHERE job_id = ? AND artisan_id = ?
    ');
    $stmt->execute([$jobId, $artisanId]);
    $existingApplication = $stmt->fetch();

    if ($existingApplication) {
        $_SESSION['error'] = 'You have already applied for this job';
        header('Location: /artisan/applications.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error checking existing application: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: /artisan/apply-job.php?id=' . $jobId);
        exit;
    }

    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $proposedRate = trim($_POST['proposed_rate'] ?? '');

    $errors = [];

    if (empty($coverLetter)) {
        $errors[] = 'Cover letter is required';
    }

    if (!empty($proposedRate) && (!is_numeric($proposedRate) || $proposedRate < 0)) {
        $errors[] = 'Proposed rate must be a valid number';
    }

    if (empty($errors)) {
        try {
            // Insert application
            $stmt = getDB()->prepare('
                INSERT INTO job_applications (job_id, artisan_id, cover_letter, proposed_rate, status)
                VALUES (?, ?, ?, ?, "pending")
            ');
            
            $stmt->execute([
                $jobId,
                $artisanId,
                $coverLetter,
                $proposedRate ?: null
            ]);

            // Create notification for employer
            $employerId = $job['employer_id'];
            $stmt = getDB()->prepare('
                SELECT user_id FROM employer_profiles WHERE id = ?
            ');
            $stmt->execute([$employerId]);
            $employerUserId = $stmt->fetch()['user_id'];

            $stmt = getDB()->prepare('
                INSERT INTO notifications (user_id, title, message, type)
                VALUES (?, ?, ?, "application")
            ');
            $stmt->execute([
                $employerUserId,
                'New Job Application',
                'You received a new application for: ' . $job['title']
            ]);

            $_SESSION['success'] = 'Application submitted successfully!';
            header('Location: /artisan/applications.php');
            exit;

        } catch (PDOException $e) {
            error_log('Error submitting application: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to submit application. Please try again.';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Parse required skills
$requiredSkills = [];
if (!empty($job['required_skills'])) {
    $requiredSkills = explode(',', $job['required_skills']);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Apply for Job</h1>
        <div class="action-buttons">
            <a href="/artisan/jobs.php" class="btn btn-secondary">Back to Jobs</a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Job Information -->
    <div class="profile-section">
        <h2><?php echo htmlspecialchars($job['title']); ?></h2>
        
        <div class="job-meta" style="margin-bottom: 20px;">
            <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name'] ?: $job['employer_first_name'] . ' ' . $job['employer_last_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?><?php if ($job['state']): ?>, <?php echo htmlspecialchars($job['state']); ?><?php endif; ?></p>
            <?php if ($job['budget_min'] && $job['budget_max']): ?>
                <p><strong>Budget:</strong> ₦<?php echo number_format($job['budget_min'], 2); ?> - ₦<?php echo number_format($job['budget_max'], 2); ?></p>
            <?php endif; ?>
            <?php if ($job['duration']): ?>
                <p><strong>Duration:</strong> <?php echo htmlspecialchars($job['duration']); ?></p>
            <?php endif; ?>
            <?php if ($job['deadline']): ?>
                <p><strong>Application Deadline:</strong> <?php echo date('M d, Y', strtotime($job['deadline'])); ?></p>
            <?php endif; ?>
        </div>

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

    <!-- Application Form -->
    <div class="profile-section">
        <h2>Your Application</h2>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label for="cover_letter">Cover Letter *</label>
                <textarea id="cover_letter" name="cover_letter" required 
                          placeholder="Explain why you're a good fit for this job. Highlight your relevant skills and experience."
                          style="min-height: 200px;"><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                <small>Tell the employer why you're the best candidate for this job</small>
            </div>

            <div class="form-group">
                <label for="proposed_rate">Proposed Rate (Optional)</label>
                <input type="number" id="proposed_rate" name="proposed_rate" 
                       step="0.01" min="0"
                       value="<?php echo isset($_POST['proposed_rate']) ? htmlspecialchars($_POST['proposed_rate']) : $artisanProfile['hourly_rate']; ?>"
                       placeholder="Your proposed rate for this job">
                <small>Your profile hourly rate: ₦<?php echo number_format($artisanProfile['hourly_rate'], 2); ?>. You can propose a different rate for this specific job.</small>
            </div>

            <div class="alert alert-info">
                <strong>Note:</strong> Once you submit this application, the employer will be notified and can view your profile. 
                Make sure your profile is complete and up-to-date before applying.
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
                <a href="/artisan/jobs.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
