<?php
/**
 * Admin Reports
 * 
 * Generate workforce statistics and export basic CSVs
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Reports - Admin';

// Require admin role
requireRole('admin');

$errors = [];
$success = false;

// Filters
$stateFilter = sanitizeInput($_GET['state'] ?? '');
$skillFilter = sanitizeInput($_GET['skill'] ?? '');
$period = sanitizeInput($_GET['period'] ?? '30'); // days

// Skill distribution
$stmt = getDB()->prepare('
    SELECT s.name, COUNT(asn.id) as count
    FROM skills s
    LEFT JOIN artisan_skills asn ON s.id = asn.skill_id
    GROUP BY s.id, s.name
    ORDER BY count DESC
    LIMIT 50
');
$stmt->execute();
$skillDistribution = $stmt->fetchAll();

// Location-based workforce (artisans)
if (!empty($stateFilter)) {
    $stmt = getDB()->prepare('
        SELECT state, COUNT(*) as count
        FROM artisan_profiles
        WHERE state = ?
        GROUP BY state
        ORDER BY count DESC
    ');
    $stmt->execute([$stateFilter]);
} else {
    $stmt = getDB()->prepare('
        SELECT state, COUNT(*) as count
        FROM artisan_profiles
        WHERE state IS NOT NULL AND state <> ''
        GROUP BY state
        ORDER BY count DESC
        LIMIT 50
    ');
    $stmt->execute();
}
$locationData = $stmt->fetchAll();

// Employment statistics
$stmt = getDB()->prepare('
    SELECT 
        SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_jobs,
        SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_jobs,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_jobs,
        SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_jobs
    FROM jobs
');
$stmt->execute();
$jobStats = $stmt->fetch();

// Matches (accepted applications in period)
$stmt = getDB()->prepare('
    SELECT COUNT(*) as count
    FROM job_applications
    WHERE status = "accepted" AND applied_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
');
$stmt->execute([(int)$period]);
$matchesCount = $stmt->fetch()['count'] ?? 0;

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container main-content">
    <div class="page-header">
        <h1>Reports</h1>
        <p>Workforce statistics and insights</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Filters</h2>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($stateFilter); ?>" placeholder="e.g., Lagos" />
                </div>
                <div class="form-group">
                    <label for="period">Period (days)</label>
                    <input type="number" id="period" name="period" min="1" max="365" value="<?php echo (int)$period; ?>" />
                </div>
                <button class="btn btn-primary" type="submit">Apply</button>
            </form>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2>Skill Distribution (Top)</h2></div>
            <div class="card-body">
                <ul class="list">
                    <?php foreach ($skillDistribution as $s): ?>
                        <li>
                            <span><?php echo htmlspecialchars($s['name']); ?></span>
                            <span class="badge"><?php echo (int)$s['count']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Artisans by State</h2></div>
            <div class="card-body">
                <ul class="list">
                    <?php foreach ($locationData as $row): ?>
                        <li>
                            <span><?php echo htmlspecialchars($row['state']); ?></span>
                            <span class="badge"><?php echo (int)$row['count']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h2>Job Status Overview</h2></div>
            <div class="card-body">
                <ul class="list">
                    <li><span>Open</span><span class="badge"><?php echo (int)$jobStats['open_jobs']; ?></span></li>
                    <li><span>In Progress</span><span class="badge"><?php echo (int)$jobStats['in_progress_jobs']; ?></span></li>
                    <li><span>Completed</span><span class="badge"><?php echo (int)$jobStats['completed_jobs']; ?></span></li>
                    <li><span>Closed</span><span class="badge"><?php echo (int)$jobStats['closed_jobs']; ?></span></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Accepted Matches (Period)</h2></div>
            <div class="card-body">
                <p class="metric">Accepted Applications: <strong><?php echo (int)$matchesCount; ?></strong></p>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
