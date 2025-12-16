<?php
/**
 * Admin Jobs List
 * 
 * View and filter all jobs posted by employers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'All Jobs - Admin';
requireRole('admin');

$errors = [];

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$state = sanitizeInput($_GET['state'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$employer = sanitizeInput($_GET['employer'] ?? '');

$query = '\n    SELECT j.*,\n           e.company_name, e.company_type, e.state as employer_state\n    FROM jobs j\n    JOIN employer_profiles e ON j.employer_id = e.id\n    WHERE 1=1\n';
$params = [];

if ($search) {
    $query .= ' AND (j.title LIKE ? OR j.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like;
}
if ($state) {
    $query .= ' AND (j.state = ? OR j.location LIKE ?)';
    $params[] = $state; $params[] = '%' . $state . '%';
}
if ($status && in_array($status, ['open','in_progress','completed','closed'])) {
    $query .= ' AND j.status = ?';
    $params[] = $status;
}
if ($employer) {
    $query .= ' AND e.company_name LIKE ?';
    $params[] = '%' . $employer . '%';
}

$query .= ' ORDER BY j.posted_date DESC LIMIT 200';

$stmt = getDB()->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container main-content">
    <div class="page-header">
        <h1>Jobs</h1>
        <p>All job postings across the platform</p>
    </div>

    <div class="card">
        <div class="card-header"><h2>Filters</h2></div>
        <div class="card-body">
            <form method="GET" class="grid-4">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Title or description" />
                </div>
                <div class="form-group">
                    <label>State/Location</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($state); ?>" placeholder="e.g., Lagos" />
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Any</option>
                        <?php foreach (['open','in_progress','completed','closed'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Employer</label>
                    <input type="text" name="employer" value="<?php echo htmlspecialchars($employer); ?>" placeholder="Company name" />
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Apply</button>
                    <a class="btn btn-secondary" href="/admin/jobs.php">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Results (<?php echo count($jobs); ?>)</h2></div>
        <div class="card-body">
            <div class="table">
                <div class="table-row table-header">
                    <div>Title</div>
                    <div>Employer</div>
                    <div>Location</div>
                    <div>Status</div>
                    <div>Budget</div>
                    <div>Posted</div>
                    <div>Actions</div>
                </div>
                <?php foreach ($jobs as $job): ?>
                    <div class="table-row">
                        <div><?php echo htmlspecialchars($job['title']); ?></div>
                        <div><?php echo htmlspecialchars($job['company_name']); ?></div>
                        <div><?php echo htmlspecialchars(($job['location'] ?: $job['state'])); ?></div>
                        <div><span class="badge badge-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span></div>
                        <div>₦<?php echo number_format($job['budget_min'] ?? 0, 2); ?> - ₦<?php echo number_format($job['budget_max'] ?? 0, 2); ?></div>
                        <div><?php echo date('M j, Y', strtotime($job['posted_date'])); ?></div>
                        <div>
                            <a class="btn btn-sm btn-secondary" href="/employer/job-detail.php?id=<?php echo $job['id']; ?>">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
