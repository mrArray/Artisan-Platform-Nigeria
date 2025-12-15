<?php
/**
 * User Registration Page
 * 
 * Allows users to register as Artisan, Employer, or Admin
 * Includes form validation and secure password hashing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Register - Artisan Platform';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        // Get and sanitize input
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = sanitizeInput($_POST['role'] ?? '');
        
        // Validation
        if (empty($firstName)) {
            $errors[] = 'First name is required.';
        }
        if (empty($lastName)) {
            $errors[] = 'Last name is required.';
        }
        if (empty($email) || !validateEmail($email)) {
            $errors[] = 'Valid email is required.';
        }
        if (empty($phone)) {
            $errors[] = 'Phone number is required.';
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        if (!in_array($role, ['artisan', 'employer'])) {
            $errors[] = 'Invalid role selected.';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = getDB()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered.';
            }
        }
        
        // Register user if no errors
        if (empty($errors)) {
            try {
                $hashedPassword = hashPassword($password);
                
                $stmt = getDB()->prepare('
                    INSERT INTO users (email, password, first_name, last_name, phone, role)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                
                $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $role]);
                $userId = getDB()->lastInsertId();
                
                // Create profile based on role
                if ($role === 'artisan') {
                    $stmt = getDB()->prepare('
                        INSERT INTO artisan_profiles (user_id)
                        VALUES (?)
                    ');
                    $stmt->execute([$userId]);
                } elseif ($role === 'employer') {
                    $stmt = getDB()->prepare('
                        INSERT INTO employer_profiles (user_id)
                        VALUES (?)
                    ');
                    $stmt->execute([$userId]);
                }
                
                // Create verification log
                $stmt = getDB()->prepare('
                    INSERT INTO verification_logs (user_id, verification_type, status)
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([$userId, 'profile', 'pending']);
                
                $success = true;
                // Redirect to login after 2 seconds
                header('Refresh: 2; url=/auth/login.php');
            } catch (Exception $e) {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Create Account</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Registration successful! Redirecting to login...
            </div>
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
        
        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required 
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required 
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" required 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="role">Register As *</label>
                <select id="role" name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="artisan" <?php echo ($_POST['role'] ?? '') === 'artisan' ? 'selected' : ''; ?>>
                        Artisan / Professional
                    </option>
                    <option value="employer" <?php echo ($_POST['role'] ?? '') === 'employer' ? 'selected' : ''; ?>>
                        Employer / Company
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required 
                       minlength="8" placeholder="Minimum 8 characters">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       minlength="8">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <p class="auth-link">
            Already have an account? <a href="/auth/login.php">Login here</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
