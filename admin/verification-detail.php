<?php
/**
 * Admin Verification Detail
 * 
 * Review individual verification request with full details
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Verification Detail - Admin';

// Require admin role
requireRole('admin');

$adminId = getCurrentUserId();
$errors = [];
$success = false;

// Get verification ID
$verificationId = (int)($_GET['id'] ?? 0);

if ($verificationId <= 0) {
    header('Location: /admin/verifications.php');
    exit;
}

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $action = sanitizeInput($_POST['action']);
        $comments = sanitizeInput($_POST['comments'] ?? '');
        
        try {
            getDB()->beginTransaction();
            
            if ($action === 'approve') {
                // Update verification log
                $stmt = getDB()->prepare('
                    UPDATE verification_logs
                    SET status = "approved", admin_id = ?, comments = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([$adminId, $comments, $verificationId]);
                
                // Get user from verification
                $stmt = getDB()->prepare('SELECT user_id FROM verification_logs WHERE id = ?');
                $stmt->execute([$verificationId]);
                $verification = $stmt->fetch();
                $targetUserId = $verification['user_id'];
                
                // Get user role
                $stmt = getDB()->prepare('SELECT role FROM users WHERE id = ?');
                $stmt->execute([$targetUserId]);
                $user = $stmt->fetch();
                
                // Update profile verification status
                if ($user['role'] === 'artisan') {
                    $stmt = getDB()->prepare('
                        UPDATE artisan_profiles SET verification_status = "verified"
                        WHERE user_id = ?
                    ');
                } elseif ($user['role'] === 'employer') {
                    $stmt = getDB()->prepare('
                        UPDATE employer_profiles SET verification_status = "verified"
                        WHERE user_id = ?
                    ');
                }
                $stmt->execute([$targetUserId]);
                
                // Update user profile_verified flag
                $stmt = getDB()->prepare('UPDATE users SET profile_verified = TRUE WHERE id = ?');
                $stmt->execute([$targetUserId]);
                
                // Create notification
                createNotification(
                    $targetUserId,
                    'profile_verified',
                    'Profile Verified',
                    'Your profile has been verified by the government agency. ' . ($comments ? 'Comments: ' . $comments : ''),
                    null
                );
                
                getDB()->commit();
                $success = 'Profile approved successfully.';
                
            } elseif ($action === 'reject') {
                // Update verification log
                $stmt = getDB()->prepare('
                    UPDATE verification_logs
                    SET status = "rejected", admin_id = ?, comments = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([$adminId, $comments, $verificationId]);
                
                // Get user from verification
                $stmt = getDB()->prepare('SELECT user_id FROM verification_logs WHERE id = ?');
                $stmt->execute([$verificationId]);
                $verification = $stmt->fetch();
                $targetUserId = $verification['user_id'];
                
                // Get user role
                $stmt = getDB()->prepare('SELECT role FROM users WHERE id = ?');
                $stmt->execute([$targetUserId]);
                $user = $stmt->fetch();
                
                // Update profile verification status
                if ($user['role'] === 'artisan') {
                    $stmt = getDB()->prepare('
                        UPDATE artisan_profiles SET verification_status = "rejected"
                        WHERE user_id = ?
                    ');
                } elseif ($user['role'] === 'employer') {
                    $stmt = getDB()->prepare('
                        UPDATE employer_profiles SET verification_status = "rejected"
                        WHERE user_id = ?
                    ');
                }
                $stmt->execute([$targetUserId]);
                
                // Create notification
                createNotification(
                    $targetUserId,
                    'profile_rejected',
                    'Profile Verification Rejected',
                    'Your profile verification has been rejected. ' . ($comments ? 'Reason: ' . $comments : 'Please review and update your information.'),
                    null
                );
                
                getDB()->commit();
                $success = 'Profile rejected. User has been notified.';
            }
        } catch (Exception $e) {
            getDB()->rollBack();
            $errors[] = 'Failed to process verification: ' . $e->getMessage();
        }
    }
}

// Get verification details with user information
$stmt = getDB()->prepare('
    SELECT vl.*, 
           u.id as user_id, u.first_name, u.last_name, u.email, u.phone, u.role, 
           u.status, u.email_verified, u.profile_verified, u.created_at as user_created,
           admin.first_name as admin_first_name, admin.last_name as admin_last_name
    FROM verification_logs vl
    JOIN users u ON vl.user_id = u.id
    LEFT JOIN users admin ON vl.admin_id = admin.id
    WHERE vl.id = ?
');
$stmt->execute([$verificationId]);
$verification = $stmt->fetch();

if (!$verification) {
    header('Location: /admin/verifications.php');
    exit;
}

// Get profile-specific information
if ($verification['role'] === 'artisan') {
    // Get artisan profile
    $stmt = getDB()->prepare('
        SELECT ap.*, 
               GROUP_CONCAT(DISTINCT s.name SEPARATOR ", ") as skills
        FROM artisan_profiles ap
        LEFT JOIN artisan_skills ask ON ap.id = ask.artisan_id
        LEFT JOIN skills s ON ask.skill_id = s.id
        WHERE ap.user_id = ?
        GROUP BY ap.id
    ');
    $stmt->execute([$verification['user_id']]);
    $profile = $stmt->fetch();
    
    // Get documents
    $stmt = getDB()->prepare('
        SELECT * FROM documents 
        WHERE artisan_id = ?
        ORDER BY uploaded_at DESC
    ');
    $stmt->execute([$profile['id'] ?? 0]);
    $documents = $stmt->fetchAll();
    
} elseif ($verification['role'] === 'employer') {
    // Get employer profile
    $stmt = getDB()->prepare('
        SELECT * FROM employer_profiles
        WHERE user_id = ?
    ');
    $stmt->execute([$verification['user_id']]);
    $profile = $stmt->fetch();
    
    $documents = [];
}

// Get verification history
$stmt = getDB()->prepare('
    SELECT vl.*, 
           admin.first_name as admin_first_name, admin.last_name as admin_last_name
    FROM verification_logs vl
    LEFT JOIN users admin ON vl.admin_id = admin.id
    WHERE vl.user_id = ?
    ORDER BY vl.created_at DESC
');
$stmt->execute([$verification['user_id']]);
$verificationHistory = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container main-content">
    <div class="page-header">
        <h1>Verification Review</h1>
        <a href="/admin/verifications.php" class="btn btn-secondary">← Back to Verifications</a>
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

    <div class="verification-detail">
        <!-- Verification Status Card -->
        <div class="card">
            <div class="card-header">
                <h2>Verification Status</h2>
                <span class="badge badge-<?php echo $verification['status']; ?>">
                    <?php echo ucfirst($verification['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Verification Type:</label>
                        <span><?php echo htmlspecialchars($verification['verification_type'] ?? 'Profile Verification'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Submitted:</label>
                        <span><?php echo date('F j, Y, g:i a', strtotime($verification['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Last Updated:</label>
                        <span><?php echo date('F j, Y, g:i a', strtotime($verification['updated_at'])); ?></span>
                    </div>
                    <?php if ($verification['admin_first_name']): ?>
                        <div class="info-item">
                            <label>Reviewed By:</label>
                            <span><?php echo htmlspecialchars($verification['admin_first_name'] . ' ' . $verification['admin_last_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($verification['comments']): ?>
                        <div class="info-item full-width">
                            <label>Admin Comments:</label>
                            <p><?php echo nl2br(htmlspecialchars($verification['comments'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Information Card -->
        <div class="card">
            <div class="card-header">
                <h2>User Information</h2>
                <span class="badge badge-<?php echo $verification['role']; ?>">
                    <?php echo ucfirst($verification['role']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name:</label>
                        <span><?php echo htmlspecialchars($verification['first_name'] . ' ' . $verification['last_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($verification['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone:</label>
                        <span><?php echo htmlspecialchars($verification['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Account Status:</label>
                        <span class="badge badge-<?php echo $verification['status']; ?>">
                            <?php echo ucfirst($verification['status']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <label>Email Verified:</label>
                        <span><?php echo $verification['email_verified'] ? '✓ Yes' : '✗ No'; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Profile Verified:</label>
                        <span><?php echo $verification['profile_verified'] ? '✓ Yes' : '✗ No'; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Member Since:</label>
                        <span><?php echo date('F j, Y', strtotime($verification['user_created'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Actions:</label>
                        <a href="/admin/user-detail.php?id=<?php echo $verification['user_id']; ?>" class="btn btn-sm btn-secondary">
                            View Full Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Details Card -->
        <?php if (isset($profile) && $profile): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo ucfirst($verification['role']); ?> Profile Details</h2>
                </div>
                <div class="card-body">
                    <?php if ($verification['role'] === 'artisan'): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Location:</label>
                                <span><?php echo htmlspecialchars($profile['location'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>State:</label>
                                <span><?php echo htmlspecialchars($profile['state'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Years of Experience:</label>
                                <span><?php echo htmlspecialchars($profile['years_of_experience']); ?> years</span>
                            </div>
                            <div class="info-item">
                                <label>Hourly Rate:</label>
                                <span>₦<?php echo number_format($profile['hourly_rate'] ?? 0, 2); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Availability:</label>
                                <span class="badge badge-<?php echo $profile['availability_status']; ?>">
                                    <?php echo ucfirst($profile['availability_status']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Rating:</label>
                                <span>⭐ <?php echo number_format($profile['rating'], 2); ?> (<?php echo $profile['total_reviews']; ?> reviews)</span>
                            </div>
                            <div class="info-item full-width">
                                <label>Skills:</label>
                                <span><?php echo htmlspecialchars($profile['skills'] ?? 'No skills listed'); ?></span>
                            </div>
                            <?php if ($profile['bio']): ?>
                                <div class="info-item full-width">
                                    <label>Bio:</label>
                                    <p><?php echo nl2br(htmlspecialchars($profile['bio'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($verification['role'] === 'employer'): ?>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Company Name:</label>
                                <span><?php echo htmlspecialchars($profile['company_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Company Type:</label>
                                <span><?php echo ucfirst($profile['company_type']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($profile['company_phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Website:</label>
                                <span>
                                    <?php if ($profile['company_website']): ?>
                                        <a href="<?php echo htmlspecialchars($profile['company_website']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($profile['company_website']); ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <label>Rating:</label>
                                <span>⭐ <?php echo number_format($profile['rating'], 2); ?> (<?php echo $profile['total_reviews']; ?> reviews)</span>
                            </div>
                            <?php if ($profile['company_address']): ?>
                                <div class="info-item full-width">
                                    <label>Address:</label>
                                    <span><?php echo htmlspecialchars($profile['company_address']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($profile['company_description']): ?>
                                <div class="info-item full-width">
                                    <label>Description:</label>
                                    <p><?php echo nl2br(htmlspecialchars($profile['company_description'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Documents Card (for artisans) -->
        <?php if ($verification['role'] === 'artisan' && !empty($documents)): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Uploaded Documents</h2>
                </div>
                <div class="card-body">
                    <div class="documents-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item">
                                <div class="document-info">
                                    <span class="document-type"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                                    <span class="document-name"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                                    <span class="document-size"><?php echo round($doc['file_size'] / 1024, 2); ?> KB</span>
                                    <span class="document-date"><?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?></span>
                                </div>
                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                    View Document
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Verification History Card -->
        <?php if (count($verificationHistory) > 1): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Verification History</h2>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($verificationHistory as $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?php echo $history['status']; ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="badge badge-<?php echo $history['status']; ?>">
                                            <?php echo ucfirst($history['status']); ?>
                                        </span>
                                        <span class="timeline-date">
                                            <?php echo date('M j, Y, g:i a', strtotime($history['created_at'])); ?>
                                        </span>
                                    </div>
                                    <?php if ($history['admin_first_name']): ?>
                                        <p class="timeline-admin">
                                            Reviewed by: <?php echo htmlspecialchars($history['admin_first_name'] . ' ' . $history['admin_last_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($history['comments']): ?>
                                        <p class="timeline-comment">
                                            <?php echo nl2br(htmlspecialchars($history['comments'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Form (only show if pending) -->
        <?php if ($verification['status'] === 'pending'): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Review Actions</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="verification-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="comments">Comments / Notes</label>
                            <textarea 
                                name="comments" 
                                id="comments" 
                                rows="4" 
                                placeholder="Add any comments or notes for this verification decision..."
                                class="form-control"
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="action" value="approve" class="btn btn-success">
                                ✓ Approve Verification
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">
                                ✗ Reject Verification
                            </button>
                            <a href="/admin/verifications.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
