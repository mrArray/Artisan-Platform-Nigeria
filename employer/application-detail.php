<?php
/**
 * Employer Application Detail Page
 * 
 * View detailed information about a job application and manage it
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole('employer');

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
    header('Location: /employer/dashboard.php');
    exit;
}

$applicationId = (int)$_GET['id'];

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

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $newStatus = $_POST['status'];
        
        if (in_array($newStatus, ['accepted', 'rejected'])) {
            try {
                $stmt = getDB()->prepare('
                    UPDATE job_applications ja
                    INNER JOIN jobs j ON ja.job_id = j.id
                    SET ja.status = ?
                    WHERE ja.id = ? AND j.employer_id = ?
                ');
                $stmt->execute([$newStatus, $applicationId, $employerId]);
                
                if ($stmt->rowCount() > 0) {
                    // Create notification for artisan
                    $stmt = getDB()->prepare('
                        SELECT ap.user_id, j.title 
                        FROM job_applications ja
                        INNER JOIN artisan_profiles ap ON ja.artisan_id = ap.id
                        INNER JOIN jobs j ON ja.job_id = j.id
                        WHERE ja.id = ?
                    ');
                    $stmt->execute([$applicationId]);
                    $appData = $stmt->fetch();
                    
                    if ($appData) {
                        $stmt = getDB()->prepare('
                            INSERT INTO notifications (user_id, title, message, type)
                            VALUES (?, ?, ?, "application")
                        ');
                        $statusText = $newStatus === 'accepted' ? 'accepted' : 'not accepted';
                        $stmt->execute([
                            $appData['user_id'],
                            'Application ' . ucfirst($newStatus),
                            'Your application for "' . $appData['title'] . '" has been ' . $statusText
                        ]);
                    }
                    
                    $_SESSION['success'] = 'Application status updated successfully';
                } else {
                    $_SESSION['error'] = 'Could not update application status';
                }
            } catch (PDOException $e) {
                error_log('Error updating application status: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update application status';
            }
        } else {
            $_SESSION['error'] = 'Invalid status';
        }
        
        header('Location: /employer/application-detail.php?id=' . $applicationId);
        exit;
    }
}

// Get application details with artisan profile
try {
    $stmt = getDB()->prepare('
        SELECT 
            ja.*,
            j.id as job_id,
            j.title as job_title,
            j.description as job_description,
            j.location as job_location,
            j.state as job_state,
            j.budget_min,
            j.budget_max,
            u.first_name as artisan_first_name,
            u.last_name as artisan_last_name,
            u.email as artisan_email,
            u.phone as artisan_phone,
            u.profile_verified,
            ap.bio,
            ap.location as artisan_location,
            ap.state as artisan_state,
            ap.experience_years,
            ap.hourly_rate,
            ap.availability_status,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.artisan_id = ap.id) as avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.artisan_id = ap.id) as review_count,
            GROUP_CONCAT(DISTINCT s.skill_name) as skills
        FROM job_applications ja
        INNER JOIN jobs j ON ja.job_id = j.id
        INNER JOIN artisan_profiles ap ON ja.artisan_id = ap.id
        INNER JOIN users u ON ap.user_id = u.id
        LEFT JOIN artisan_skills ask ON ap.id = ask.artisan_id
        LEFT JOIN skills s ON ask.skill_id = s.id
        WHERE ja.id = ? AND j.employer_id = ?
        GROUP BY ja.id
    ');
    $stmt->execute([$applicationId, $employerId]);
    $application = $stmt->fetch();

    if (!$application) {
        $_SESSION['error'] = 'Application not found or access denied';
        header('Location: /employer/dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching application: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /employer/dashboard.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Application Details</h1>
        <div class="action-buttons">
            <a href="/employer/job-detail.php?id=<?php echo $application['job_id']; ?>" class="btn btn-secondary">Back to Job</a>
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

    <!-- Application Status and Actions -->
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
            <div style="margin-top: 20px;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="status" value="accepted">
                    <button type="submit" name="update_status" class="btn btn-success">
                        Accept Application
                    </button>
                </form>
                
                <form method="POST" style="display: inline; margin-left: 10px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit" name="update_status" class="btn btn-danger"
                            data-confirm="Are you sure you want to reject this application?"
                            data-confirm-title="Reject Application">
                        Reject Application
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Artisan Profile -->
    <div class="profile-section">
        <h2>
            Artisan Profile
            <?php if ($application['profile_verified']): ?>
                <span class="badge badge-accepted" style="font-size: 0.9rem; margin-left: 10px;">✓ Verified</span>
            <?php endif; ?>
        </h2>
        
        <div class="form-group">
            <h3><?php echo htmlspecialchars($application['artisan_first_name'] . ' ' . $application['artisan_last_name']); ?></h3>
            <span class="<?php 
                echo match($application['availability_status']) {
                    'available' => 'status-available',
                    'busy' => 'status-busy',
                    'unavailable' => 'status-unavailable',
                    default => ''
                };
            ?>">
                <?php echo ucfirst($application['availability_status']); ?>
            </span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><strong>Location:</strong></label>
                <p><?php echo htmlspecialchars($application['artisan_location']); ?><?php if ($application['artisan_state']): ?>, <?php echo htmlspecialchars($application['artisan_state']); ?><?php endif; ?></p>
            </div>

            <div class="form-group">
                <label><strong>Experience:</strong></label>
                <p><?php echo htmlspecialchars($application['experience_years']); ?> years</p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><strong>Hourly Rate:</strong></label>
                <p>₦<?php echo number_format($application['hourly_rate'], 2); ?>/hour</p>
            </div>

            <?php if ($application['avg_rating']): ?>
                <div class="form-group">
                    <label><strong>Rating:</strong></label>
                    <p>
                        <span style="color: #ffc107;">★</span> 
                        <?php echo number_format($application['avg_rating'], 1); ?>/5.0 
                        (<?php echo $application['review_count']; ?> reviews)
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($application['bio']): ?>
            <div class="form-group">
                <label><strong>Bio:</strong></label>
                <p><?php echo nl2br(htmlspecialchars($application['bio'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($application['skills']): ?>
            <div class="form-group">
                <label><strong>Skills:</strong></label>
                <div class="skill-tags">
                    <?php
                    $skills = explode(',', $application['skills']);
                    foreach ($skills as $skill): 
                    ?>
                        <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label><strong>Contact:</strong></label>
            <p>
                Email: <a href="mailto:<?php echo htmlspecialchars($application['artisan_email']); ?>">
                    <?php echo htmlspecialchars($application['artisan_email']); ?>
                </a><br>
                Phone: <a href="tel:<?php echo htmlspecialchars($application['artisan_phone']); ?>">
                    <?php echo htmlspecialchars($application['artisan_phone']); ?>
                </a>
            </p>
        </div>
    </div>

    <!-- Application Details -->
    <div class="profile-section">
        <h2>Application</h2>

        <div class="form-group">
            <label><strong>Job Applied For:</strong></label>
            <p><?php echo htmlspecialchars($application['job_title']); ?></p>
        </div>

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

    <!-- Actions -->
    <div class="card">
        <a href="/user/messages.php?action=compose&recipient=<?php echo $application['artisan_id']; ?>" 
           class="btn btn-primary">Send Message</a>
        <a href="/employer/artisan-detail.php?id=<?php echo $application['artisan_id']; ?>" 
           class="btn btn-secondary">View Full Profile</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
