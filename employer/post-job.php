<?php
/**
 * Post Job Page
 * 
 * Allows employers to create new job postings
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Post a Job - Employer';

// Require employer role
requireRole('employer');

$userId = getCurrentUserId();
$errors = [];
$success = false;

// Get employer profile
$stmt = getDB()->prepare('
    SELECT id FROM employer_profiles WHERE user_id = ?
');
$stmt->execute([$userId]);
$employer = $stmt->fetch();
$employerId = $employer['id'];

// Get all skills for selection
$stmt = getDB()->prepare('
    SELECT id, name, category FROM skills ORDER BY category, name
');
$stmt->execute();
$skills = $stmt->fetchAll();

// Handle job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $category = sanitizeInput($_POST['category'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $budgetMin = (float)($_POST['budget_min'] ?? 0);
        $budgetMax = (float)($_POST['budget_max'] ?? 0);
        $duration = sanitizeInput($_POST['duration'] ?? '');
        $experienceLevel = sanitizeInput($_POST['experience_level'] ?? '');
        $deadline = $_POST['deadline'] ?? '';
        $requiredSkills = $_POST['required_skills'] ?? [];
        
        // Validation
        if (empty($title)) {
            $errors[] = 'Job title is required.';
        }
        if (empty($description)) {
            $errors[] = 'Job description is required.';
        }
        if (empty($location)) {
            $errors[] = 'Location is required.';
        }
        if (empty($state)) {
            $errors[] = 'State is required.';
        }
        if ($budgetMin <= 0 || $budgetMax <= 0) {
            $errors[] = 'Budget amounts must be greater than zero.';
        }
        if ($budgetMin > $budgetMax) {
            $errors[] = 'Minimum budget cannot exceed maximum budget.';
        }
        if (empty($experienceLevel) || !in_array($experienceLevel, ['beginner', 'intermediate', 'advanced'])) {
            $errors[] = 'Valid experience level is required.';
        }
        if (!empty($deadline)) {
            $deadlineTime = strtotime($deadline);
            if ($deadlineTime === false || $deadlineTime < time()) {
                $errors[] = 'Deadline must be in the future.';
            }
        }
        
        // Insert job if no errors
        if (empty($errors)) {
            try {
                $requiredSkillsStr = !empty($requiredSkills) ? implode(',', array_map('sanitizeInput', $requiredSkills)) : '';
                
                $stmt = getDB()->prepare('
                    INSERT INTO jobs (
                        employer_id, title, description, category, location, state,
                        budget_min, budget_max, duration, experience_level,
                        required_skills, deadline, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                
                $stmt->execute([
                    $employerId,
                    $title,
                    $description,
                    $category,
                    $location,
                    $state,
                    $budgetMin,
                    $budgetMax,
                    $duration,
                    $experienceLevel,
                    $requiredSkillsStr,
                    !empty($deadline) ? $deadline : null,
                    'open'
                ]);
                
                $jobId = getDB()->lastInsertId();
                
                // Create notification for artisans
                $stmt = getDB()->prepare('
                    SELECT id FROM users WHERE role = "artisan" LIMIT 100
                ');
                $stmt->execute();
                $artisans = $stmt->fetchAll();
                
                foreach ($artisans as $artisan) {
                    createNotification(
                        $artisan['id'],
                        'new_job',
                        'New Job Posted: ' . $title,
                        'A new job matching your skills has been posted.',
                        $jobId
                    );
                }
                
                $success = true;
                // Redirect after 2 seconds
                header('Refresh: 2; url=/employer/my-jobs.php');
            } catch (Exception $e) {
                $errors[] = 'Failed to post job. Please try again.';
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="form-container">
        <h1>Post a New Job</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Job posted successfully! Redirecting to your jobs...
            </div>
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

        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <!-- Basic Information -->
            <fieldset>
                <legend>Job Information</legend>

                <div class="form-group">
                    <label for="title">Job Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                           placeholder="e.g., Experienced Plumber needed for residential project">
                </div>

                <div class="form-group">
                    <label for="description">Job Description *</label>
                    <textarea id="description" name="description" required rows="6"
                              placeholder="Provide detailed description of the job, responsibilities, and requirements"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">-- Select Category --</option>
                            <option value="Construction" <?php echo ($_POST['category'] ?? '') === 'Construction' ? 'selected' : ''; ?>>Construction</option>
                            <option value="Beauty" <?php echo ($_POST['category'] ?? '') === 'Beauty' ? 'selected' : ''; ?>>Beauty</option>
                            <option value="Fashion" <?php echo ($_POST['category'] ?? '') === 'Fashion' ? 'selected' : ''; ?>>Fashion</option>
                            <option value="Technology" <?php echo ($_POST['category'] ?? '') === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                            <option value="Design" <?php echo ($_POST['category'] ?? '') === 'Design' ? 'selected' : ''; ?>>Design</option>
                            <option value="Media" <?php echo ($_POST['category'] ?? '') === 'Media' ? 'selected' : ''; ?>>Media</option>
                            <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="experience_level">Experience Level Required *</label>
                        <select id="experience_level" name="experience_level" required>
                            <option value="">-- Select Level --</option>
                            <option value="beginner" <?php echo ($_POST['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo ($_POST['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo ($_POST['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <!-- Location & Duration -->
            <fieldset>
                <legend>Location & Duration</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location/City *</label>
                        <input type="text" id="location" name="location" required 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                               placeholder="e.g., Lagos">
                    </div>

                    <div class="form-group">
                        <label for="state">State *</label>
                        <input type="text" id="state" name="state" required 
                               value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>"
                               placeholder="e.g., Lagos State">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration">Duration</label>
                        <input type="text" id="duration" name="duration" 
                               value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>"
                               placeholder="e.g., 2 weeks, 1 month, ongoing">
                    </div>

                    <div class="form-group">
                        <label for="deadline">Application Deadline</label>
                        <input type="date" id="deadline" name="deadline" 
                               value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
                    </div>
                </div>
            </fieldset>

            <!-- Budget -->
            <fieldset>
                <legend>Budget</legend>

                <div class="form-row">
                    <div class="form-group">
                        <label for="budget_min">Minimum Budget (₦) *</label>
                        <input type="number" id="budget_min" name="budget_min" required step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_POST['budget_min'] ?? ''); ?>"
                               placeholder="e.g., 50000">
                    </div>

                    <div class="form-group">
                        <label for="budget_max">Maximum Budget (₦) *</label>
                        <input type="number" id="budget_max" name="budget_max" required step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_POST['budget_max'] ?? ''); ?>"
                               placeholder="e.g., 150000">
                    </div>
                </div>
            </fieldset>

            <!-- Required Skills -->
            <fieldset>
                <legend>Required Skills</legend>

                <div class="form-group">
                    <label>Select Required Skills (Optional)</label>
                    <div class="skills-checkboxes">
                        <?php foreach ($skills as $skill): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="skill_<?php echo $skill['id']; ?>" 
                                       name="required_skills[]" value="<?php echo htmlspecialchars($skill['name']); ?>"
                                       <?php echo in_array($skill['name'], $_POST['required_skills'] ?? []) ? 'checked' : ''; ?>>
                                <label for="skill_<?php echo $skill['id']; ?>">
                                    <?php echo htmlspecialchars($skill['name']); ?> 
                                    <span class="skill-category">(<?php echo htmlspecialchars($skill['category']); ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">Post Job</button>
                <a href="/employer/my-jobs.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
    .form-container {
        max-width: 800px;
        margin: 40px auto;
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-container h1 {
        margin-bottom: 30px;
        color: #333;
    }

    fieldset {
        margin-bottom: 30px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }

    legend {
        padding: 0 10px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    .skills-checkboxes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-item input[type="checkbox"] {
        width: auto;
        margin: 0;
    }

    .checkbox-item label {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .skill-category {
        font-size: 0.85rem;
        color: #999;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .form-actions .btn {
        flex: 1;
    }

    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
        }

        .skills-checkboxes {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
