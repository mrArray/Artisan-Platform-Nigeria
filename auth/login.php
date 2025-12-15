<?php
/**
 * User Login Page
 * 
 * Authenticates users and creates session
 * Supports all user roles (artisan, employer, admin)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Login - Artisan Platform';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($email)) {
            $errors[] = 'Email is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        if (empty($errors)) {
            try {
                // Get user from database
                $stmt = getDB()->prepare('
                    SELECT * FROM users WHERE email = ?
                ');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                // Verify password
                if ($user && verifyPassword($password, $user['password'])) {
                    // Check if account is active
                    if ($user['status'] !== 'active') {
                        $errors[] = 'Your account is ' . $user['status'] . '. Please contact support.';
                    } else {
                        // Set session
                        setUserSession($user);
                        
                        // Update last login
                        $stmt = getDB()->prepare('
                            UPDATE users SET last_login = NOW() WHERE id = ?
                        ');
                        $stmt->execute([$user['id']]);
                        
                        // Redirect based on role
                        switch ($user['role']) {
                            case 'artisan':
                                header('Location: /artisan/dashboard.php');
                                break;
                            case 'employer':
                                header('Location: /employer/dashboard.php');
                                break;
                            case 'admin':
                                header('Location: /admin/dashboard.php');
                                break;
                            default:
                                header('Location: /index.php');
                        }
                        exit;
                    }
                } else {
                    $errors[] = 'Invalid email or password.';
                }
            } catch (Exception $e) {
                $errors[] = 'Login failed. Please try again.';
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Login to Your Account</h1>
        
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
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group checkbox">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        
        <p class="auth-link">
            Don't have an account? <a href="/auth/register.php">Register here</a>
        </p>
        
        <p class="auth-link">
            <a href="/auth/forgot-password.php">Forgot your password?</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
