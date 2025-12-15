<?php
/**
 * Logout Handler
 * 
 * Destroys session and redirects to home page
 */

require_once __DIR__ . '/../includes/auth_check.php';

// Destroy session
session_destroy();

// Redirect to home
header('Location: /index.php');
exit;
?>
