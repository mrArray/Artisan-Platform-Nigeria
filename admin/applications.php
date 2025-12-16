<?php
/**
 * Admin Applications List
 * 
 * View and filter all job applications submitted by artisans
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'All Applications - Admin';
requireRole('admin');

$errors = [];

// Filters
$search = sanitizeInput($_GET['search'] ?? ''); // job title/artisan name
$status = sanitizeInput($_GET['status'] ?? '');
$state = sanitizeInput($_GET['state'] ?? '');
$skill = sanitizeInput($_GET['skill'] ?? '');

$query = '\n    SELECT ja.*,\n           j.title as job_title, j.state as job_state, j.employer_id,\n           ap.id as artisan_profile_id, ap.state as artisan_state, ap.years_of_experience,\n           u.first_name, u.last_name\n    FROM job_applications ja\n    JOIN jobs j ON ja.job_id = j.id\n    JOIN artisan_profiles ap ON ja.artisan_id = ap.id\n    JOIN users u ON ap.user_id = u.id\n    WHERE 1=1\n';
$params = [];

if ($search) {
    $query .= ' AND (j.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status && in_array($status, ['pending','accepted','rejected','withdrawn'])) {
    $query .= ' AND ja.status = ?';
    $params[] = $status;
}
if ($state) {
    $query .= ' AND (ap.state = ? OR j.state = ?)';
    $params[] = $state; $params[] = $state;
}
if ($skill) {
    // Filter by artisan skill name
    $query .= ' AND ap.id IN (\n        SELECT ask.artisan_id FROM artisan_skills ask\n        JOIN skills s ON ask.skill_id = s.id\n        WHERE s.name LIKE ?\n    )';
    $params[] = '%' . $skill . '%';
}

$query .= ' ORDER BY ja.applied_date DESC LIMIT 200';

$stmt = getDB()->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container main-content">
    <div class="page-header">
        <h1>Applications</h1>
        <p>All job applications submitted by artisans</p>
    </div>

    <div class="card">
        <div class="card-header"><h2>Filters</h2></div>
        <div class="card-body">
            <form method="GET" class="grid-4">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Job title or artisan name" />
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Any</option>
                        <?php foreach (['pending','accepted','rejected','withdrawn'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($state); ?>" placeholder="e.g., Lagos" />
                </div>
                <div class="form-group">
                    <label>Skill</label>
                    <input type="text" name="skill" value="<?php echo htmlspecialchars($skill); ?>" placeholder="e.g., Plumbing" />
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Apply</button>
                    <a class="btn btn-secondary" href="/admin/applications.php">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Results (<?php echo count($applications); ?>)</h2></div>
        <div class="card-body">
            <div class="table">
                <div class="table-row table-header">
                    <div>Job</div>
                    <div>Artisan</div>
                    <div>Status</div>
                    <div>Proposed Rate</div>
                    <div>Applied</div>
                    <div>Actions</div>
                </div>
                <?php foreach ($applications as $app): ?>
                    <div class="table-row">
                        <div><?php echo htmlspecialchars($app['job_title']); ?></div>
                        <div><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                        <div><span class="badge badge-<?php echo $app['status']; ?>"><?php echo ucfirst($app['status']); ?></span></div>
                        <div>₦<?php echo number_format($app['proposed_rate'] ?? 0, 2); ?></div>
                        <div><?php echo date('M j, Y', strtotime($app['applied_date'])); ?></div>
                        <div>
                            <a class="btn btn-sm btn-secondary" href="/employer/application-detail.php?id=<?php echo $app['id']; ?>">View</a>
                            <a class="btn btn-sm btn-secondary" href="/admin/user-detail.php?id=<?php echo $app['artisan_profile_id']; ?>">Artisan</a>
                            <a class="btn btn-sm btn-secondary" href="/employer/job-detail.php?id=<?php echo $app['job_id']; ?>">Job</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
