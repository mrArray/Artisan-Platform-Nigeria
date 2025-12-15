<?php
/**
 * Admin Dashboard
 * 
 * Government agency dashboard for user management, verification, and reporting
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Admin Dashboard';

// Require admin role
requireRole('admin');

$userId = getCurrentUserId();

// Get statistics
$stmt = getDB()->prepare('SELECT COUNT(*) as count FROM users WHERE role = "artisan"');
$stmt->execute();
$artisanCount = $stmt->fetch()['count'];

$stmt = getDB()->prepare('SELECT COUNT(*) as count FROM users WHERE role = "employer"');
$stmt->execute();
$employerCount = $stmt->fetch()['count'];

$stmt = getDB()->prepare('SELECT COUNT(*) as count FROM artisan_profiles WHERE verification_status = "pending"');
$stmt->execute();
$pendingArtisans = $stmt->fetch()['count'];

$stmt = getDB()->prepare('SELECT COUNT(*) as count FROM employer_profiles WHERE verification_status = "pending"');
$stmt->execute();
$pendingEmployers = $stmt->fetch()['count'];

$stmt = getDB()->prepare('SELECT COUNT(*) as count FROM jobs WHERE status = "open"');
$stmt->execute();
$activeJobs = $stmt->fetch()['count'];

$stmt = getDB()->prepare('SELECT COUNT(*) as count FROM job_applications WHERE status = "accepted"');
$stmt->execute();
$successfulMatches = $stmt->fetch()['count'];

// Get recent verifications needed
$stmt = getDB()->prepare('
    SELECT vl.*, u.first_name, u.last_name, u.email, u.role
    FROM verification_logs vl
    JOIN users u ON vl.user_id = u.id
    WHERE vl.status = "pending"
    ORDER BY vl.created_at DESC
    LIMIT 10
');
$stmt->execute();
$pendingVerifications = $stmt->fetchAll();

// Get recent suspended users
$stmt = getDB()->prepare('
    SELECT id, first_name, last_name, email, role, status
    FROM users
    WHERE status != "active"
    ORDER BY updated_at DESC
    LIMIT 5
');
$stmt->execute();
$suspendedUsers = $stmt->fetchAll();

// Get skill distribution
$stmt = getDB()->prepare('
    SELECT s.name, COUNT(asn.id) as count
    FROM skills s
    LEFT JOIN artisan_skills asn ON s.id = asn.skill_id
    GROUP BY s.id, s.name
    ORDER BY count DESC
    LIMIT 10
');
$stmt->execute();
$skillDistribution = $stmt->fetchAll();

// Get location-based workforce
$stmt = getDB()->prepare('
    SELECT state, COUNT(*) as count
    FROM artisan_profiles
    WHERE state IS NOT NULL
    GROUP BY state
    ORDER BY count DESC
    LIMIT 10
');
$stmt->execute();
$locationData = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Government Agency Workforce Management System</p>
    </div>

    <!-- Key Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Artisans</h3>
            <p class="stat-number"><?php echo number_format($artisanCount); ?></p>
            <a href="/admin/users.php?role=artisan">View All</a>
        </div>

        <div class="stat-card">
            <h3>Total Employers</h3>
            <p class="stat-number"><?php echo number_format($employerCount); ?></p>
            <a href="/admin/users.php?role=employer">View All</a>
        </div>

        <div class="stat-card">
            <h3>Pending Verifications</h3>
            <p class="stat-number"><?php echo $pendingArtisans + $pendingEmployers; ?></p>
            <a href="/admin/verifications.php">Review</a>
        </div>

        <div class="stat-card">
            <h3>Active Jobs</h3>
            <p class="stat-number"><?php echo number_format($activeJobs); ?></p>
            <a href="/admin/jobs.php">View All</a>
        </div>

        <div class="stat-card">
            <h3>Successful Matches</h3>
            <p class="stat-number"><?php echo number_format($successfulMatches); ?></p>
            <a href="/admin/reports.php">View Reports</a>
        </div>

        <div class="stat-card">
            <h3>Suspended Accounts</h3>
            <p class="stat-number"><?php echo count($suspendedUsers); ?></p>
            <a href="/admin/users.php?status=suspended">Manage</a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="/admin/verifications.php" class="btn btn-primary">Review Verifications</a>
            <a href="/admin/users.php" class="btn btn-secondary">Manage Users</a>
            <a href="/admin/reports.php" class="btn btn-secondary">Generate Reports</a>
        </div>
    </div>

    <!-- Pending Verifications -->
    <div class="recent-section">
        <h3>Pending Verifications</h3>
        <?php if (!empty($pendingVerifications)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Type</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingVerifications as $verification): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($verification['first_name'] . ' ' . $verification['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($verification['email']); ?></td>
                            <td><?php echo ucfirst($verification['role']); ?></td>
                            <td><?php echo ucfirst($verification['verification_type']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($verification['created_at'])); ?></td>
                            <td>
                                <a href="/admin/verification-detail.php?id=<?php echo $verification['id']; ?>" 
                                   class="btn btn-sm btn-primary">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending verifications.</p>
        <?php endif; ?>
    </div>

    <!-- Suspended Accounts -->
    <div class="recent-section">
        <h3>Suspended Accounts</h3>
        <?php if (!empty($suspendedUsers)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suspendedUsers as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="/admin/user-detail.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-secondary">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No suspended accounts.</p>
        <?php endif; ?>
    </div>

    <!-- Skill Distribution -->
    <div class="recent-section">
        <h3>Top Skills Distribution</h3>
        <?php if (!empty($skillDistribution)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Skill</th>
                        <th>Artisans</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalSkills = array_sum(array_column($skillDistribution, 'count'));
                    foreach ($skillDistribution as $skill): 
                        $percentage = $totalSkills > 0 ? ($skill['count'] / $totalSkills) * 100 : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($skill['name']); ?></td>
                            <td><?php echo $skill['count']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <?php echo number_format($percentage, 1); ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No skill data available.</p>
        <?php endif; ?>
    </div>

    <!-- Location-based Workforce -->
    <div class="recent-section">
        <h3>Workforce by Location (Top 10 States)</h3>
        <?php if (!empty($locationData)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>State</th>
                        <th>Artisans</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalArtisans = array_sum(array_column($locationData, 'count'));
                    foreach ($locationData as $location): 
                        $percentage = $totalArtisans > 0 ? ($location['count'] / $totalArtisans) * 100 : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($location['state']); ?></td>
                            <td><?php echo $location['count']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <?php echo number_format($percentage, 1); ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No location data available.</p>
        <?php endif; ?>
    </div>
</div>

<style>
    .progress-bar {
        display: inline-block;
        width: 200px;
        height: 20px;
        background-color: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        margin-right: 10px;
    }

    .progress {
        height: 100%;
        background-color: #007bff;
        transition: width 0.3s ease;
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
