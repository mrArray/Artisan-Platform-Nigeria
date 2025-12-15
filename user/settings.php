<?php
/**
 * User Settings Page
 * 
 * Allows users to update their account settings and password
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();

$pageTitle = 'Account Settings';
$userId = $_SESSION['user_id'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Get user data
try {
    $stmt = getDB()->prepare('
        SELECT email, first_name, last_name, phone, role
        FROM users
        WHERE id = ?
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = 'User not found';
        header('Location: /index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error fetching user: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred';
    header('Location: /index.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header('Location: /user/settings.php');
        exit;
    }

    // Update basic info
    if (isset($_POST['update_info'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $errors = [];

        if (empty($firstName)) {
            $errors[] = 'First name is required';
        }

        if (empty($lastName)) {
            $errors[] = 'Last name is required';
        }

        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        }

        if (empty($errors)) {
            try {
                $stmt = getDB()->prepare('
                    UPDATE users
                    SET first_name = ?, last_name = ?, phone = ?
                    WHERE id = ?
                ');
                $stmt->execute([$firstName, $lastName, $phone, $userId]);

                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['success'] = 'Account information updated successfully';
                
                header('Location: /user/settings.php');
                exit;
            } catch (PDOException $e) {
                error_log('Error updating user info: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update account information';
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }

    // Change password
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }

        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }

        if (empty($errors)) {
            try {
                // Verify current password
                $stmt = getDB()->prepare('SELECT password FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $userData = $stmt->fetch();

                if (!password_verify($currentPassword, $userData['password'])) {
                    $_SESSION['error'] = 'Current password is incorrect';
                } else {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = getDB()->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $stmt->execute([$hashedPassword, $userId]);

                    $_SESSION['success'] = 'Password changed successfully';
                    header('Location: /user/settings.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Error changing password: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to change password';
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Account Settings</h1>
        <p>Manage your account information and security</p>
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
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Account Information Section -->
    <div class="profile-section">
        <h2>Account Information</h2>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                <small>Email cannot be changed. Contact support if needed.</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled>
            </div>

            <button type="submit" name="update_info" class="btn btn-primary">Update Information</button>
        </form>
    </div>

    <!-- Change Password Section -->
    <div class="profile-section">
        <h2>Change Password</h2>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
                <small>Must be at least 8 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>

            <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
        </form>
    </div>

    <!-- Account Actions -->
    <div class="profile-section">
        <h2>Account Actions</h2>
        
        <div class="action-buttons">
            <a href="/auth/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
