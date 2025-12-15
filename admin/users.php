<?php
/**
 * Admin User Management
 * 
 * Manage all users, activate, suspend, or delete accounts
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Manage Users - Admin';

// Require admin role
requireRole('admin');

$userId = getCurrentUserId();
$errors = [];
$success = false;

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $action = sanitizeInput($_POST['action']);
        
        if ($targetUserId <= 0 || $targetUserId === $userId) {
            $errors[] = 'Invalid user or cannot modify your own account.';
        } else {
            try {
                if ($action === 'activate') {
                    $stmt = getDB()->prepare('UPDATE users SET status = "active" WHERE id = ?');
                    $stmt->execute([$targetUserId]);
                    $success = 'User activated successfully.';
                } elseif ($action === 'suspend') {
                    $stmt = getDB()->prepare('UPDATE users SET status = "suspended" WHERE id = ?');
                    $stmt->execute([$targetUserId]);
                    $success = 'User suspended successfully.';
                } elseif ($action === 'delete') {
                    $stmt = getDB()->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$targetUserId]);
                    $success = 'User deleted successfully.';
                }
            } catch (Exception $e) {
                $errors[] = 'Failed to update user.';
            }
        }
    }
}

// Get filters
$roleFilter = sanitizeInput($_GET['role'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$searchQuery = sanitizeInput($_GET['search'] ?? '');

// Build query
$query = 'SELECT * FROM users WHERE 1=1';
$params = [];

if (!empty($roleFilter) && in_array($roleFilter, ['artisan', 'employer', 'admin'])) {
    $query .= ' AND role = ?';
    $params[] = $roleFilter;
}

if (!empty($statusFilter) && in_array($statusFilter, ['active', 'suspended', 'inactive'])) {
    $query .= ' AND status = ?';
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $query .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
    $search = '%' . $searchQuery . '%';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$query .= ' ORDER BY created_at DESC LIMIT 100';

$stmt = getDB()->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$stmt = getDB()->prepare('SELECT role, COUNT(*) as count FROM users GROUP BY role');
$stmt->execute();
$roleStats = [];
foreach ($stmt->fetchAll() as $stat) {
    $roleStats[$stat['role']] = $stat['count'];
}

$stmt = getDB()->prepare('SELECT status, COUNT(*) as count FROM users GROUP BY status');
$stmt->execute();
$statusStats = [];
foreach ($stmt->fetchAll() as $stat) {
    $statusStats[$stat['status']] = $stat['count'];
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="admin-users-container">
    <h1>Manage Users</h1>

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
            <h3>Total Users</h3>
            <p class="stat-number"><?php echo array_sum($roleStats); ?></p>
        </div>

        <div class="stat-card">
            <h3>Artisans</h3>
            <p class="stat-number"><?php echo $roleStats['artisan'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Employers</h3>
            <p class="stat-number"><?php echo $roleStats['employer'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Admins</h3>
            <p class="stat-number"><?php echo $roleStats['admin'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Active</h3>
            <p class="stat-number"><?php echo $statusStats['active'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Suspended</h3>
            <p class="stat-number"><?php echo $statusStats['suspended'] ?? 0; ?></p>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="search-section">
        <form method="GET" class="search-form">
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search by name or email..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>

                <div class="form-group">
                    <select name="role">
                        <option value="">-- All Roles --</option>
                        <option value="artisan" <?php echo $roleFilter === 'artisan' ? 'selected' : ''; ?>>Artisan</option>
                        <option value="employer" <?php echo $roleFilter === 'employer' ? 'selected' : ''; ?>>Employer</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <select name="status">
                        <option value="">-- All Status --</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/admin/users.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="users-table">
        <?php if (!empty($users)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-btn">Actions ▼</button>
                                    <div class="dropdown-menu">
                                        <a href="/admin/user-detail.php?id=<?php echo $user['id']; ?>">View Details</a>
                                        
                                        <?php if ($user['status'] !== 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-link">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['status'] !== 'suspended' && $user['id'] !== $userId): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-link" 
                                                        data-confirm="Are you sure you want to suspend this user?"
                                                        data-confirm-title="Suspend User">Suspend</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['id'] !== $userId): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-link delete-link" 
                                                        data-confirm="Are you sure you want to delete this user? This action cannot be undone."
                                                        data-confirm-title="Delete User">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-results">
                <p>No users found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .admin-users-container {
        max-width: 1200px;
        margin: 40px auto;
    }

    .search-section {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .search-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-form .form-group {
        flex: 1;
        min-width: 200px;
        margin-bottom: 0;
    }

    .search-form .btn {
        align-self: flex-end;
    }

    .users-table {
        background-color: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .action-dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-btn {
        cursor: pointer;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        z-index: 10;
        min-width: 150px;
    }

    .action-dropdown:hover .dropdown-menu {
        display: block;
    }

    .dropdown-menu a,
    .dropdown-menu button {
        display: block;
        width: 100%;
        padding: 10px 15px;
        text-align: left;
        text-decoration: none;
        color: #333;
        border: none;
        background: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dropdown-menu a:hover,
    .dropdown-menu button:hover {
        background-color: #f5f5f5;
        color: #007bff;
    }

    .action-link {
        padding: 10px 15px;
        font-size: 0.9rem;
    }

    .delete-link:hover {
        color: #dc3545;
    }

    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
        }

        .search-form .form-group {
            width: 100%;
        }

        .search-form .btn {
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
