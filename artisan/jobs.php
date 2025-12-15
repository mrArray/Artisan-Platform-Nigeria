<?php
/**
 * Job Search and Browse Page for Artisans
 * 
 * Allows artisans to search, filter, and view available job postings
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Find Jobs - Artisan';

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

// Build search query
$query = 'SELECT j.*, ep.company_name FROM jobs j JOIN employer_profiles ep ON j.employer_id = ep.id WHERE j.status = "open"';
$params = [];

// Search by title
if (!empty($_GET['search'])) {
    $search = '%' . sanitizeInput($_GET['search']) . '%';
    $query .= ' AND (j.title LIKE ? OR j.description LIKE ?)';
    $params[] = $search;
    $params[] = $search;
}

// Filter by location
if (!empty($_GET['location'])) {
    $location = '%' . sanitizeInput($_GET['location']) . '%';
    $query .= ' AND j.location LIKE ?';
    $params[] = $location;
}

// Filter by state
if (!empty($_GET['state'])) {
    $state = sanitizeInput($_GET['state']);
    $query .= ' AND j.state = ?';
    $params[] = $state;
}

// Filter by budget range
if (!empty($_GET['budget_min'])) {
    $budgetMin = (float)$_GET['budget_min'];
    $query .= ' AND j.budget_min >= ?';
    $params[] = $budgetMin;
}

if (!empty($_GET['budget_max'])) {
    $budgetMax = (float)$_GET['budget_max'];
    $query .= ' AND j.budget_max <= ?';
    $params[] = $budgetMax;
}

// Filter by experience level
if (!empty($_GET['experience_level'])) {
    $expLevel = sanitizeInput($_GET['experience_level']);
    $query .= ' AND j.experience_level = ?';
    $params[] = $expLevel;
}

// Sort options
$sortBy = sanitizeInput($_GET['sort'] ?? 'posted_date');
$validSorts = ['posted_date', 'budget_max', 'title'];
if (!in_array($sortBy, $validSorts)) {
    $sortBy = 'posted_date';
}

$query .= ' ORDER BY j.' . $sortBy . ' DESC LIMIT 50';

$stmt = getDB()->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get all states for filter
$stmt = getDB()->prepare('SELECT DISTINCT state FROM jobs WHERE state IS NOT NULL ORDER BY state');
$stmt->execute();
$states = $stmt->fetchAll();

// Get already applied jobs
$stmt = getDB()->prepare('
    SELECT job_id FROM job_applications WHERE artisan_id = ?
');
$stmt->execute([$artisanId]);
$appliedJobs = array_column($stmt->fetchAll(), 'job_id');
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="jobs-container">
    <h1>Find Jobs</h1>

    <!-- Search and Filter Section -->
    <div class="search-section">
        <form method="GET" class="search-form">
            <div class="form-row">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search jobs..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <input type="text" name="location" placeholder="Location/City" 
                           value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <select name="state">
                        <option value="">-- All States --</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo htmlspecialchars($state['state']); ?>" 
                                    <?php echo ($_GET['state'] ?? '') === $state['state'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($state['state']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="experience_level">
                        <option value="">-- All Levels --</option>
                        <option value="beginner" <?php echo ($_GET['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                        <option value="intermediate" <?php echo ($_GET['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="advanced" <?php echo ($_GET['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Budget Range (₦)</label>
                    <div class="budget-inputs">
                        <input type="number" name="budget_min" placeholder="Min" min="0" 
                               value="<?php echo htmlspecialchars($_GET['budget_min'] ?? ''); ?>">
                        <span>to</span>
                        <input type="number" name="budget_max" placeholder="Max" min="0" 
                               value="<?php echo htmlspecialchars($_GET['budget_max'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <select name="sort">
                        <option value="posted_date" <?php echo ($_GET['sort'] ?? '') === 'posted_date' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="budget_max" <?php echo ($_GET['sort'] ?? '') === 'budget_max' ? 'selected' : ''; ?>>Highest Budget</option>
                        <option value="title" <?php echo ($_GET['sort'] ?? '') === 'title' ? 'selected' : ''; ?>>Job Title (A-Z)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Search</button>
            <a href="/artisan/jobs.php" class="btn btn-secondary">Clear Filters</a>
        </form>
    </div>

    <!-- Jobs Listing -->
    <div class="jobs-listing">
        <?php if (!empty($jobs)): ?>
            <p class="results-count">Found <?php echo count($jobs); ?> job(s)</p>

            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                            <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                        </div>

                        <div class="job-details">
                            <p class="location">
                                <strong>📍</strong> <?php echo htmlspecialchars($job['location'] ?? $job['state']); ?>
                            </p>
                            <p class="budget">
                                <strong>💰</strong> ₦<?php echo number_format($job['budget_min']); ?> - ₦<?php echo number_format($job['budget_max']); ?>
                            </p>
                            <p class="experience">
                                <strong>📊</strong> <?php echo ucfirst($job['experience_level']); ?> Level
                            </p>
                            <p class="posted">
                                <strong>📅</strong> Posted <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                            </p>
                        </div>

                        <div class="job-description">
                            <p><?php echo htmlspecialchars(substr($job['description'], 0, 150)); ?>...</p>
                        </div>

                        <div class="job-skills">
                            <?php if (!empty($job['required_skills'])): ?>
                                <strong>Skills Required:</strong>
                                <div class="skill-tags">
                                    <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="job-actions">
                            <a href="/artisan/job-detail.php?id=<?php echo $job['id']; ?>" 
                               class="btn btn-secondary">View Details</a>
                            <?php if (in_array($job['id'], $appliedJobs)): ?>
                                <button class="btn btn-disabled" disabled>Already Applied</button>
                            <?php else: ?>
                                <a href="/artisan/apply-job.php?id=<?php echo $job['id']; ?>" 
                                   class="btn btn-primary">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>No jobs found matching your criteria.</p>
                <a href="/artisan/jobs.php" class="btn btn-primary">View All Jobs</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
