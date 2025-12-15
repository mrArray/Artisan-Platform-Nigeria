<?php
/**
 * Artisan Application Detail Page
 * 
 * View detailed information about a job application
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole('artisan');

$pageTitle = 'Application Details';
$userId = $_SESSION['user_id'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get application ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid application ID';
    header('Location: /artisan/applications.php');
    exit;
}

$applicationId = (int)$_GET['id'];

// Get artisan profile
try {
    $stmt = getDB()->prepare('SELECT id FROM artisan_profiles WHERE user_id = ?');
    $stmt->execute([$userId]);
    $artisanProfile = $stmt->fetch();

    if (!$artisanProfile) {
        $_SESSION['error'] = 'Artisan profile not found';
        header('Location: /artisan/dashboard.php');
        exit;
    }

    $artisanId = $artisanProfile['id'];
} catch (PDOException $e) {
    error_log('Error fetching artisan profile: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /artisan/dashboard.php');
    exit;
}

// Handle withdrawal
if (isset($_GET['withdraw']) && isset($_GET['csrf_token'])) {
    if ($_GET['csrf_token'] === $_SESSION['csrf_token']) {
        try {
            $stmt = getDB()->prepare('
                UPDATE job_applications 
                SET status = "withdrawn" 
                WHERE id = ? AND artisan_id = ? AND status = "pending"
            ');
            $stmt->execute([$applicationId, $artisanId]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = 'Application withdrawn successfully';
            } else {
                $_SESSION['error'] = 'Could not withdraw application';
            }
        } catch (PDOException $e) {
            error_log('Error withdrawing application: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to withdraw application';
        }
        
        header('Location: /artisan/application-detail.php?id=' . $applicationId);
        exit;
    }
}

// Get application details
try {
    $stmt = getDB()->prepare('
        SELECT 
            ja.*,
            j.title as job_title,
            j.description as job_description,
            j.location as job_location,
            j.state as job_state,
            j.budget_min,
            j.budget_max,
            j.duration,
            j.required_skills,
            j.status as job_status,
            u.first_name as employer_first_name,
            u.last_name as employer_last_name,
            u.email as employer_email,
            u.phone as employer_phone,
            ep.company_name,
            ep.company_type
        FROM job_applications ja
        INNER JOIN jobs j ON ja.job_id = j.id
        INNER JOIN employer_profiles ep ON j.employer_id = ep.id
        INNER JOIN users u ON ep.user_id = u.id
        WHERE ja.id = ? AND ja.artisan_id = ?
    ');
    $stmt->execute([$applicationId, $artisanId]);
    $application = $stmt->fetch();

    if (!$application) {
        $_SESSION['error'] = 'Application not found or access denied';
        header('Location: /artisan/applications.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching application: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /artisan/applications.php');
    exit;
}

// Parse required skills
$requiredSkills = [];
if (!empty($application['required_skills'])) {
    $requiredSkills = explode(',', $application['required_skills']);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Application Details</h1>
        <div class="action-buttons">
            <a href="/artisan/applications.php" class="btn btn-secondary">Back to Applications</a>
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

    <!-- Application Status -->
    <div class="card" style="margin-bottom: 20px;">
        <h3>Application Status</h3>
        <span class="badge <?php 
            echo match($application['status']) {
                'pending' => 'badge-pending',
                'accepted' => 'badge-accepted',
                'rejected' => 'badge-rejected',
                'withdrawn' => 'badge-withdrawn',
                default => 'badge-withdrawn'
            };
        ?>" style="font-size: 1.2rem;">
            <?php echo ucfirst($application['status']); ?>
        </span>
        
        <p style="margin-top: 15px; color: #666;">
            Applied on: <?php echo date('F d, Y \a\t g:i A', strtotime($application['applied_date'])); ?>
        </p>

        <?php if ($application['status'] === 'pending'): ?>
            <div style="margin-top: 15px;">
                <a href="/artisan/application-detail.php?id=<?php echo $application['id']; ?>&withdraw=1&csrf_token=<?php echo urlencode($csrfToken); ?>" 
                   class="btn btn-warning"
                   data-confirm="Are you sure you want to withdraw this application?"
                   data-confirm-title="Withdraw Application">
                    Withdraw Application
                </a>
            </div>
        <?php elseif ($application['status'] === 'accepted'): ?>
            <div class="alert alert-success" style="margin-top: 15px;">
                <strong>Congratulations!</strong> Your application has been accepted. The employer may contact you soon.
            </div>
        <?php elseif ($application['status'] === 'rejected'): ?>
            <div class="alert alert-error" style="margin-top: 15px;">
                Unfortunately, your application was not accepted for this position.
            </div>
        <?php endif; ?>
    </div>

    <!-- Job Information -->
    <div class="profile-section">
        <h2>Job Details</h2>
        
        <div class="form-group">
            <label><strong>Job Title:</strong></label>
            <h3><?php echo htmlspecialchars($application['job_title']); ?></h3>
        </div>

        <div class="form-group">
            <label><strong>Company:</strong></label>
            <p><?php echo htmlspecialchars($application['company_name'] ?: $application['employer_first_name'] . ' ' . $application['employer_last_name']); ?></p>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><strong>Location:</strong></label>
                <p><?php echo htmlspecialchars($application['job_location']); ?><?php if ($application['job_state']): ?>, <?php echo htmlspecialchars($application['job_state']); ?><?php endif; ?></p>
            </div>

            <div class="form-group">
                <label><strong>Job Status:</strong></label>
                <p><?php echo ucfirst(str_replace('_', ' ', $application['job_status'])); ?></p>
            </div>
        </div>

        <?php if ($application['budget_min'] && $application['budget_max']): ?>
            <div class="form-group">
                <label><strong>Budget Range:</strong></label>
                <p>₦<?php echo number_format($application['budget_min'], 2); ?> - ₦<?php echo number_format($application['budget_max'], 2); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($application['duration']): ?>
            <div class="form-group">
                <label><strong>Duration:</strong></label>
                <p><?php echo htmlspecialchars($application['duration']); ?></p>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label><strong>Job Description:</strong></label>
            <p><?php echo nl2br(htmlspecialchars($application['job_description'])); ?></p>
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

    <!-- Your Application -->
    <div class="profile-section">
        <h2>Your Application</h2>

        <?php if ($application['proposed_rate']): ?>
            <div class="form-group">
                <label><strong>Proposed Rate:</strong></label>
                <p>₦<?php echo number_format($application['proposed_rate'], 2); ?></p>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label><strong>Cover Letter:</strong></label>
            <p><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
        </div>
    </div>

    <!-- Employer Contact (only show if accepted) -->
    <?php if ($application['status'] === 'accepted'): ?>
        <div class="profile-section">
            <h2>Employer Contact Information</h2>
            
            <div class="form-group">
                <label><strong>Contact Person:</strong></label>
                <p><?php echo htmlspecialchars($application['employer_first_name'] . ' ' . $application['employer_last_name']); ?></p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><strong>Email:</strong></label>
                    <p><a href="mailto:<?php echo htmlspecialchars($application['employer_email']); ?>">
                        <?php echo htmlspecialchars($application['employer_email']); ?>
                    </a></p>
                </div>

                <div class="form-group">
                    <label><strong>Phone:</strong></label>
                    <p><a href="tel:<?php echo htmlspecialchars($application['employer_phone']); ?>">
                        <?php echo htmlspecialchars($application['employer_phone']); ?>
                    </a></p>
                </div>
            </div>

            <div class="form-group">
                <a href="/user/messages.php?action=compose&recipient=<?php echo $application['employer_id']; ?>" 
                   class="btn btn-primary">Send Message</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
