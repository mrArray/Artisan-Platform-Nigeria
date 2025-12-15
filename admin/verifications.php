<?php
/**
 * Admin Verification Management
 * 
 * Review and approve/reject user profile verifications
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Manage Verifications - Admin';

// Require admin role
requireRole('admin');

$userId = getCurrentUserId();
$errors = [];
$success = false;

// Handle verification action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $verificationId = (int)($_POST['verification_id'] ?? 0);
        $action = sanitizeInput($_POST['action']);
        $comments = sanitizeInput($_POST['comments'] ?? '');
        
        if ($verificationId <= 0) {
            $errors[] = 'Invalid verification record.';
        } else {
            try {
                if ($action === 'approve') {
                    // Update verification log
                    $stmt = getDB()->prepare('
                        UPDATE verification_logs
                        SET status = "approved", admin_id = ?, comments = ?, updated_at = NOW()
                        WHERE id = ?
                    ');
                    $stmt->execute([$userId, $comments, $verificationId]);
                    
                    // Get user and update their profile
                    $stmt = getDB()->prepare('
                        SELECT user_id FROM verification_logs WHERE id = ?
                    ');
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
                    } else {
                        $stmt = getDB()->prepare('
                            UPDATE employer_profiles SET verification_status = "verified"
                            WHERE user_id = ?
                        ');
                    }
                    $stmt->execute([$targetUserId]);
                    
                    // Update user profile_verified flag
                    $stmt = getDB()->prepare('
                        UPDATE users SET profile_verified = TRUE WHERE id = ?
                    ');
                    $stmt->execute([$targetUserId]);
                    
                    // Create notification
                    createNotification(
                        $targetUserId,
                        'profile_verified',
                        'Profile Verified',
                        'Your profile has been verified by the government agency.',
                        null
                    );
                    
                    $success = 'Profile approved successfully.';
                    
                } elseif ($action === 'reject') {
                    // Update verification log
                    $stmt = getDB()->prepare('
                        UPDATE verification_logs
                        SET status = "rejected", admin_id = ?, comments = ?, updated_at = NOW()
                        WHERE id = ?
                    ');
                    $stmt->execute([$userId, $comments, $verificationId]);
                    
                    // Get user
                    $stmt = getDB()->prepare('
                        SELECT user_id FROM verification_logs WHERE id = ?
                    ');
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
                    } else {
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
                        'Your profile verification was rejected. Please review the comments and resubmit.',
                        null
                    );
                    
                    $success = 'Profile rejected successfully.';
                }
            } catch (Exception $e) {
                $errors[] = 'Failed to process verification.';
            }
        }
    }
}

// Get filter
$statusFilter = sanitizeInput($_GET['status'] ?? 'pending');

// Get pending verifications
$query = '
    SELECT vl.*, u.first_name, u.last_name, u.email, u.role
    FROM verification_logs vl
    JOIN users u ON vl.user_id = u.id
    WHERE 1=1
';

$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $query .= ' AND vl.status = ?';
    $params[] = $statusFilter;
}

$query .= ' ORDER BY vl.created_at DESC LIMIT 100';

$stmt = getDB()->prepare($query);
$stmt->execute($params);
$verifications = $stmt->fetchAll();

// Get statistics
$stmt = getDB()->prepare('SELECT status, COUNT(*) as count FROM verification_logs GROUP BY status');
$stmt->execute();
$stats = [];
foreach ($stmt->fetchAll() as $stat) {
    $stats[$stat['status']] = $stat['count'];
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="verifications-container">
    <h1>Manage Verifications</h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Pending</h3>
            <p class="stat-number"><?php echo $stats['pending'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Approved</h3>
            <p class="stat-number"><?php echo $stats['approved'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Rejected</h3>
            <p class="stat-number"><?php echo $stats['rejected'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Total</h3>
            <p class="stat-number"><?php echo array_sum($stats); ?></p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="/admin/verifications.php?status=pending" 
           class="tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
            Pending (<?php echo $stats['pending'] ?? 0; ?>)
        </a>
        <a href="/admin/verifications.php?status=approved" 
           class="tab <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">
            Approved (<?php echo $stats['approved'] ?? 0; ?>)
        </a>
        <a href="/admin/verifications.php?status=rejected" 
           class="tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
            Rejected (<?php echo $stats['rejected'] ?? 0; ?>)
        </a>
    </div>

    <!-- Verifications List -->
    <div class="verifications-list">
        <?php if (!empty($verifications)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verifications as $verification): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($verification['first_name'] . ' ' . $verification['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($verification['email']); ?></td>
                            <td><?php echo ucfirst($verification['role']); ?></td>
                            <td><?php echo ucfirst($verification['verification_type']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $verification['status']; ?>">
                                    <?php echo ucfirst($verification['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($verification['created_at'])); ?></td>
                            <td>
                                <?php if ($verification['status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-success" onclick="showApproveForm(<?php echo $verification['id']; ?>)">Approve</button>
                                        <button class="btn btn-sm btn-danger" onclick="showRejectForm(<?php echo $verification['id']; ?>)">Reject</button>
                                    </div>
                                <?php else: ?>
                                    <a href="/admin/verification-detail.php?id=<?php echo $verification['id']; ?>" 
                                       class="btn btn-sm btn-secondary">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-results">
                <p>No verifications found with status "<?php echo htmlspecialchars($statusFilter); ?>".</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal('approveModal')">&times;</span>
        <h2>Approve Profile</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="verification_id" id="approveVerificationId">
            
            <div class="form-group">
                <label for="approveComments">Comments (Optional)</label>
                <textarea id="approveComments" name="comments" rows="4" placeholder="Add any comments..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn btn-success">Approve</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal('rejectModal')">&times;</span>
        <h2>Reject Profile</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="verification_id" id="rejectVerificationId">
            
            <div class="form-group">
                <label for="rejectComments">Reason for Rejection *</label>
                <textarea id="rejectComments" name="comments" rows="4" required placeholder="Explain why this profile is being rejected..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn btn-danger">Reject</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    .verifications-container {
        max-width: 1200px;
        margin: 40px auto;
    }

    .filter-tabs {
        display: flex;
        gap: 10px;
        margin: 30px 0;
        border-bottom: 2px solid #ddd;
    }

    .tab {
        padding: 12px 20px;
        text-decoration: none;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .tab:hover {
        color: #007bff;
    }

    .tab.active {
        color: #007bff;
        border-bottom-color: #007bff;
    }

    .verifications-list {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .action-buttons {
        display: flex;
        gap: 5px;
    }

    .action-buttons .btn {
        white-space: nowrap;
    }

    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    .close {
        position: absolute;
        right: 15px;
        top: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #999;
    }

    .close:hover {
        color: #333;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .modal-actions .btn {
        flex: 1;
    }

    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
        }

        .table {
            font-size: 0.85rem;
        }

        .table th,
        .table td {
            padding: 8px;
        }
    }
</style>

<script>
    function showApproveForm(verificationId) {
        document.getElementById('approveVerificationId').value = verificationId;
        document.getElementById('approveModal').style.display = 'flex';
    }

    function showRejectForm(verificationId) {
        document.getElementById('rejectVerificationId').value = verificationId;
        document.getElementById('rejectModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function(event) {
        const approveModal = document.getElementById('approveModal');
        const rejectModal = document.getElementById('rejectModal');
        if (event.target === approveModal) {
            approveModal.style.display = 'none';
        }
        if (event.target === rejectModal) {
            rejectModal.style.display = 'none';
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
