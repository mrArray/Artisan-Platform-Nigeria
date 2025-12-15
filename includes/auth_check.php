<?php
/**
 * Authentication and Session Management
 * 
 * This file handles session initialization, authentication checks,
 * and CSRF token generation for security
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Check if user has specific role
function hasRole($role) {
    return isLoggedIn() && getCurrentUserRole() === $role;
}

// Check if user has any of the specified roles
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    return in_array(getCurrentUserRole(), (array)$roles);
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

// Redirect to login if user doesn't have required role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /index.php');
        exit;
    }
}

// Redirect to login if user doesn't have any of the required roles
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        header('Location: /index.php');
        exit;
    }
}

// Logout function
function logout() {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// Set user session data
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];
}

// Get current user data from database
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

// Get user by ID
function getUserById($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Create notification
function createNotification($userId, $type, $title, $message, $relatedId = null) {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = getDB()->prepare('
        INSERT INTO notifications (user_id, type, title, message, related_id)
        VALUES (?, ?, ?, ?, ?)
    ');
    
    return $stmt->execute([$userId, $type, $title, $message, $relatedId]);
}

// Get unread notifications count
function getUnreadNotificationsCount($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = getDB()->prepare('
        SELECT COUNT(*) as count FROM notifications
        WHERE user_id = ? AND is_read = FALSE
    ');
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// Get unread messages count
function getUnreadMessagesCount($userId) {
    require_once __DIR__ . '/../config/database.php';
    
    $stmt = getDB()->prepare('
        SELECT COUNT(*) as count FROM messages
        WHERE recipient_id = ? AND is_read = FALSE
    ');
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}
?>
