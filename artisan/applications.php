<?php
/**
 * Job Applications Page
 * 
 * Displays artisan's job applications and their status
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'My Applications - Artisan';

// Require artisan role
requireRole('artisan');

$userId = getCurrentUserId();

// Get artisan profile
$stmt = getDB()->prepare('
    SELECT id FROM artisan_profiles WHERE user_id = ?
');
$stmt->execute([$userId]);
$artisan = $stmt->fetch();
$artisanId = $artisan['id'];

// Get filter
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$validStatuses = ['pending', 'accepted', 'rejected', 'withdrawn'];

// Build query
$query = '
    SELECT ja.*, j.title, j.budget_min, j.budget_max, j.posted_date,
           ep.company_name, u.first_name, u.last_name
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN employer_profiles ep ON j.employer_id = ep.id
    JOIN users u ON ep.user_id = u.id
    WHERE ja.artisan_id = ?
';

$params = [$artisanId];

if (!empty($statusFilter) && in_array($statusFilter, $validStatuses)) {
    $query .= ' AND ja.status = ?';
    $params[] = $statusFilter;
}

$query .= ' ORDER BY ja.applied_date DESC';

$stmt = getDB()->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Get statistics
$stmt = getDB()->prepare('
    SELECT status, COUNT(*) as count FROM job_applications
    WHERE artisan_id = ?
    GROUP BY status
');
$stmt->execute([$artisanId]);
$stats = $stmt->fetchAll();
$statsByStatus = [];
foreach ($stats as $stat) {
    $statsByStatus[$stat['status']] = $stat['count'];
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="applications-container">
    <h1>My Job Applications</h1>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Applications</h3>
            <p class="stat-number"><?php echo array_sum($statsByStatus); ?></p>
        </div>

        <div class="stat-card">
            <h3>Pending</h3>
            <p class="stat-number"><?php echo $statsByStatus['pending'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Accepted</h3>
            <p class="stat-number"><?php echo $statsByStatus['accepted'] ?? 0; ?></p>
        </div>

        <div class="stat-card">
            <h3>Rejected</h3>
            <p class="stat-number"><?php echo $statsByStatus['rejected'] ?? 0; ?></p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="/artisan/applications.php" 
           class="tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">
            All Applications
        </a>
        <a href="/artisan/applications.php?status=pending" 
           class="tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
            Pending
        </a>
        <a href="/artisan/applications.php?status=accepted" 
           class="tab <?php echo $statusFilter === 'accepted' ? 'active' : ''; ?>">
            Accepted
        </a>
        <a href="/artisan/applications.php?status=rejected" 
           class="tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
            Rejected
        </a>
    </div>

    <!-- Applications List -->
    <div class="applications-list">
        <?php if (!empty($applications)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Applied Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['title']); ?></td>
                            <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                            <td>
                                ₦<?php echo number_format($app['budget_min']); ?> - 
                                ₦<?php echo number_format($app['budget_max']); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                            <td>
                                <a href="/artisan/application-detail.php?id=<?php echo $app['id']; ?>" 
                                   class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-results">
                <p>
                    <?php if (!empty($statusFilter)): ?>
                        No applications with status "<?php echo htmlspecialchars($statusFilter); ?>".
                    <?php else: ?>
                        You haven't applied for any jobs yet.
                    <?php endif; ?>
                </p>
                <a href="/artisan/jobs.php" class="btn btn-primary">Browse Jobs</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .applications-container {
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

    .applications-list {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .no-results {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }

    .no-results p {
        margin-bottom: 20px;
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
        .filter-tabs {
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 15px;
            font-size: 0.95rem;
        }

        .table {
            font-size: 0.9rem;
        }

        .table th,
        .table td {
            padding: 10px;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
