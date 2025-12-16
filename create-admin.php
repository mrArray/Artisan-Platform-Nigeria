<?php
/**
 * Create Admin User Script
 * 
 * Run from terminal to create an admin account
 * Usage: php create-admin.php
 */

require_once __DIR__ . '/config/database.php';

echo "=== Artisan Platform - Admin Account Creator ===\n\n";

// Get admin details from command line
echo "Enter admin details (press Enter for defaults):\n\n";

// First Name
echo "First Name [Admin]: ";
$firstName = trim(fgets(STDIN));
if (empty($firstName)) {
    $firstName = 'Admin';
}

// Last Name
echo "Last Name [User]: ";
$lastName = trim(fgets(STDIN));
if (empty($lastName)) {
    $lastName = 'User';
}

// Email
echo "Email [admin@artisanplatform.ng]: ";
$email = trim(fgets(STDIN));
if (empty($email)) {
    $email = 'admin@artisanplatform.ng';
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email address.\n");
}

// Check if email already exists
$stmt = getDB()->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    die("Error: Email already exists in the database.\n");
}

// Phone
echo "Phone [+2341234567890]: ";
$phone = trim(fgets(STDIN));
if (empty($phone)) {
    $phone = '+2341234567890';
}

// Password
echo "Password [admin123]: ";
$password = trim(fgets(STDIN));
if (empty($password)) {
    $password = 'admin123';
}

// Confirm details
echo "\n--- Confirm Details ---\n";
echo "First Name: $firstName\n";
echo "Last Name: $lastName\n";
echo "Email: $email\n";
echo "Phone: $phone\n";
echo "Password: $password\n";
echo "Role: admin\n";
echo "\nCreate this admin account? (yes/no) [yes]: ";

$confirm = trim(fgets(STDIN));
if (!empty($confirm) && strtolower($confirm) !== 'yes' && strtolower($confirm) !== 'y') {
    die("Admin creation cancelled.\n");
}

// Hash password
echo "\nHashing password...\n";
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Insert admin user
try {
    echo "Creating admin account...\n";
    
    $stmt = getDB()->prepare('
        INSERT INTO users (email, password, first_name, last_name, phone, role, status, email_verified, profile_verified)
        VALUES (?, ?, ?, ?, ?, "admin", "active", 1, 1)
    ');
    
    $stmt->execute([
        $email,
        $hashedPassword,
        $firstName,
        $lastName,
        $phone
    ]);
    
    $adminId = getDB()->lastInsertId();
    
    echo "\n✓ Admin account created successfully!\n";
    echo "User ID: $adminId\n";
    echo "\nLogin Credentials:\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "\nYou can now login at: http://localhost:8000/auth/login.php\n";
    
} catch (PDOException $e) {
    die("Error: Failed to create admin account - " . $e->getMessage() . "\n");
}
