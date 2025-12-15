<?php
/**
 * Employer Artisan Search Page
 * 
 * Allows employers to search and find artisans by skills, location, and experience
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireRole('employer');

$pageTitle = 'Find Artisans';
$userId = $_SESSION['user_id'];

// Search parameters
$searchTerm = trim($_GET['search'] ?? '');
$skillFilter = trim($_GET['skill'] ?? '');
$locationFilter = trim($_GET['location'] ?? '');
$stateFilter = trim($_GET['state'] ?? '');
$experienceFilter = $_GET['experience'] ?? '';
$availabilityFilter = $_GET['availability'] ?? '';

// Build query
try {
    $query = '
        SELECT 
            ap.*,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.profile_verified,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.artisan_id = ap.id) as avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.artisan_id = ap.id) as review_count,
            GROUP_CONCAT(DISTINCT s.skill_name) as skills
        FROM artisan_profiles ap
        INNER JOIN users u ON ap.user_id = u.id
        LEFT JOIN artisan_skills ask ON ap.id = ask.artisan_id
        LEFT JOIN skills s ON ask.skill_id = s.id
        WHERE u.status = "active"
    ';

    $params = [];

    if ($searchTerm) {
        $query .= ' AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            ap.bio LIKE ? OR
            s.skill_name LIKE ?
        )';
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }

    if ($skillFilter) {
        $query .= ' AND s.skill_name LIKE ?';
        $params[] = '%' . $skillFilter . '%';
    }

    if ($locationFilter) {
        $query .= ' AND ap.location LIKE ?';
        $params[] = '%' . $locationFilter . '%';
    }

    if ($stateFilter) {
        $query .= ' AND ap.state = ?';
        $params[] = $stateFilter;
    }

    if ($experienceFilter) {
        switch ($experienceFilter) {
            case 'beginner':
                $query .= ' AND ap.experience_years <= 2';
                break;
            case 'intermediate':
                $query .= ' AND ap.experience_years BETWEEN 3 AND 5';
                break;
            case 'advanced':
                $query .= ' AND ap.experience_years > 5';
                break;
        }
    }

    if ($availabilityFilter) {
        $query .= ' AND ap.availability_status = ?';
        $params[] = $availabilityFilter;
    }

    $query .= ' GROUP BY ap.id ORDER BY u.profile_verified DESC, ap.id DESC';

    $stmt = getDB()->prepare($query);
    $stmt->execute($params);
    $artisans = $stmt->fetchAll();

    // Get all skills for filter dropdown
    $stmt = getDB()->prepare('SELECT DISTINCT skill_name FROM skills ORDER BY skill_name');
    $stmt->execute();
    $allSkills = $stmt->fetchAll();

    // Get all states
    $nigerianStates = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
        'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe',
        'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara',
        'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau',
        'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
    ];

} catch (PDOException $e) {
    error_log('Error searching artisans: ' . $e->getMessage());
    $artisans = [];
    $allSkills = [];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Find Artisans</h1>
        <p>Search for skilled artisans and professionals</p>
    </div>

    <!-- Search and Filters -->
    <div class="card search-section">
        <form method="GET" action="/employer/artisans.php" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" 
                           placeholder="Search by name, skills, or bio">
                </div>

                <div class="form-group">
                    <label for="skill">Skill</label>
                    <select id="skill" name="skill">
                        <option value="">All Skills</option>
                        <?php foreach ($allSkills as $skill): ?>
                            <option value="<?php echo htmlspecialchars($skill['skill_name']); ?>"
                                    <?php echo $skillFilter === $skill['skill_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($skill['skill_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo htmlspecialchars($locationFilter); ?>" 
                           placeholder="City or area">
                </div>

                <div class="form-group">
                    <label for="state">State</label>
                    <select id="state" name="state">
                        <option value="">All States</option>
                        <?php foreach ($nigerianStates as $state): ?>
                            <option value="<?php echo htmlspecialchars($state); ?>"
                                    <?php echo $stateFilter === $state ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($state); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="experience">Experience Level</label>
                    <select id="experience" name="experience">
                        <option value="">Any Experience</option>
                        <option value="beginner" <?php echo $experienceFilter === 'beginner' ? 'selected' : ''; ?>>Beginner (0-2 years)</option>
                        <option value="intermediate" <?php echo $experienceFilter === 'intermediate' ? 'selected' : ''; ?>>Intermediate (3-5 years)</option>
                        <option value="advanced" <?php echo $experienceFilter === 'advanced' ? 'selected' : ''; ?>>Advanced (5+ years)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <option value="">Any Availability</option>
                        <option value="available" <?php echo $availabilityFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="busy" <?php echo $availabilityFilter === 'busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="unavailable" <?php echo $availabilityFilter === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/employer/artisans.php" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="profile-section">
        <h2>
            Search Results 
            <?php if (count($artisans) > 0): ?>
                (<?php echo count($artisans); ?> found)
            <?php endif; ?>
        </h2>

        <?php if (count($artisans) > 0): ?>
            <div class="jobs-grid">
                <?php foreach ($artisans as $artisan): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <h3>
                                <?php echo htmlspecialchars($artisan['first_name'] . ' ' . $artisan['last_name']); ?>
                                <?php if ($artisan['profile_verified']): ?>
                                    <span class="badge badge-accepted" style="font-size: 0.75rem;">✓ Verified</span>
                                <?php endif; ?>
                            </h3>
                            <?php
                            $statusClass = match($artisan['availability_status']) {
                                'available' => 'status-available',
                                'busy' => 'status-busy',
                                'unavailable' => 'status-unavailable',
                                default => ''
                            };
                            ?>
                            <span class="<?php echo $statusClass; ?>">
                                <?php echo ucfirst($artisan['availability_status']); ?>
                            </span>
                        </div>

                        <div class="job-details">
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($artisan['location']); ?><?php if ($artisan['state']): ?>, <?php echo htmlspecialchars($artisan['state']); ?><?php endif; ?></p>
                            <p><strong>Experience:</strong> <?php echo htmlspecialchars($artisan['experience_years']); ?> years</p>
                            <p><strong>Rate:</strong> ₦<?php echo number_format($artisan['hourly_rate'], 2); ?>/hour</p>
                            
                            <?php if ($artisan['avg_rating']): ?>
                                <p><strong>Rating:</strong> 
                                    <span style="color: #ffc107;">★</span> 
                                    <?php echo number_format($artisan['avg_rating'], 1); ?>/5.0 
                                    (<?php echo $artisan['review_count']; ?> reviews)
                                </p>
                            <?php endif; ?>
                        </div>

                        <?php if ($artisan['bio']): ?>
                            <div class="job-description">
                                <p><?php echo nl2br(htmlspecialchars(substr($artisan['bio'], 0, 150))); ?><?php echo strlen($artisan['bio']) > 150 ? '...' : ''; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($artisan['skills']): ?>
                            <div class="job-skills">
                                <strong>Skills:</strong>
                                <div class="skill-tags">
                                    <?php
                                    $skills = explode(',', $artisan['skills']);
                                    $displaySkills = array_slice($skills, 0, 5);
                                    foreach ($displaySkills as $skill): 
                                    ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($skills) > 5): ?>
                                        <span class="skill-tag">+<?php echo count($skills) - 5; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="job-actions">
                            <a href="/employer/artisan-detail.php?id=<?php echo $artisan['id']; ?>" 
                               class="btn btn-primary">View Profile</a>
                            <a href="/user/messages.php?action=compose&recipient=<?php echo $artisan['user_id']; ?>" 
                               class="btn btn-secondary">Message</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <p>No artisans found matching your criteria.</p>
                <p>Try adjusting your filters or search terms.</p>
                <a href="/employer/artisans.php" class="btn btn-primary">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
