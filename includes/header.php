<?php
/**
 * Header Template
 * 
 * Displays navigation bar and common header elements
 */

require_once __DIR__ . '/auth_check.php';
$isLoggedIn = isLoggedIn();
$userRole = getCurrentUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Artisan Platform'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="navbar-brand">
                    <a href="/index.php" class="logo">
                        <span class="logo-text">Artisan Platform</span>
                    </a>
                </div>

                <div class="navbar-menu">
                    <ul class="nav-items">
                        <li><a href="/index.php" class="nav-link">Home</a></li>
                        
                        <?php if ($isLoggedIn): ?>
                            <?php if ($userRole === 'artisan'): ?>
                                <li><a href="/artisan/dashboard.php" class="nav-link">Dashboard</a></li>
                                <li><a href="/artisan/jobs.php" class="nav-link">Find Jobs</a></li>
                                <li><a href="/artisan/applications.php" class="nav-link">My Applications</a></li>
                                <li><a href="/artisan/profile.php" class="nav-link">My Profile</a></li>
                            <?php elseif ($userRole === 'employer'): ?>
                                <li><a href="/employer/dashboard.php" class="nav-link">Dashboard</a></li>
                                <li><a href="/employer/post-job.php" class="nav-link">Post Job</a></li>
                                <li><a href="/employer/my-jobs.php" class="nav-link">My Jobs</a></li>
                                <li><a href="/employer/artisans.php" class="nav-link">Find Artisans</a></li>
                            <?php elseif ($userRole === 'admin'): ?>
                                <li><a href="/admin/dashboard.php" class="nav-link">Dashboard</a></li>
                                <li><a href="/admin/users.php" class="nav-link">Manage Users</a></li>
                                <li><a href="/admin/verifications.php" class="nav-link">Verifications</a></li>
                                <li><a href="/admin/reports.php" class="nav-link">Reports</a></li>
                            <?php endif; ?>
                            
                            <li class="nav-item-dropdown">
                                <a href="#" class="nav-link dropdown-toggle">
                                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a href="/user/messages.php">Messages</a></li>
                                    <li><a href="/user/notifications.php">Notifications</a></li>
                                    <li><a href="/user/settings.php">Settings</a></li>
                                    <li><hr></li>
                                    <li><a href="/auth/logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li><a href="/auth/login.php" class="nav-link">Login</a></li>
                            <li><a href="/auth/register.php" class="nav-link btn-primary">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
