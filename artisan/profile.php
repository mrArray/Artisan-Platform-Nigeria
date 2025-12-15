<?php
/**
 * Artisan Profile Management
 * 
 * CRUD operations for artisan profile, skills, and documents
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'My Profile - Artisan';

// Require artisan role
requireRole('artisan');

$userId = getCurrentUserId();
$errors = [];
$success = false;

// Get artisan profile
$stmt = getDB()->prepare('
    SELECT ap.*, u.email, u.phone, u.first_name, u.last_name
    FROM artisan_profiles ap
    JOIN users u ON ap.user_id = u.id
    WHERE ap.user_id = ?
');
$stmt->execute([$userId]);
$profile = $stmt->fetch();
$artisanId = $profile['id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $state = sanitizeInput($_POST['state'] ?? '');
        $yearsExp = (int)($_POST['years_experience'] ?? 0);
        $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
        $availability = sanitizeInput($_POST['availability_status'] ?? '');
        
        if (empty($location)) {
            $errors[] = 'Location is required.';
        }
        if (empty($state)) {
            $errors[] = 'State is required.';
        }
        if (!in_array($availability, ['available', 'busy', 'unavailable'])) {
            $errors[] = 'Invalid availability status.';
        }
        
        if (empty($errors)) {
            try {
                $stmt = getDB()->prepare('
                    UPDATE artisan_profiles
                    SET bio = ?, location = ?, state = ?, years_of_experience = ?,
                        hourly_rate = ?, availability_status = ?
                    WHERE user_id = ?
                ');
                $stmt->execute([$bio, $location, $state, $yearsExp, $hourlyRate, $availability, $userId]);
                $success = true;
                
                // Refresh profile data
                $stmt = getDB()->prepare('
                    SELECT ap.*, u.email, u.phone, u.first_name, u.last_name
                    FROM artisan_profiles ap
                    JOIN users u ON ap.user_id = u.id
                    WHERE ap.user_id = ?
                ');
                $stmt->execute([$userId]);
                $profile = $stmt->fetch();
            } catch (Exception $e) {
                $errors[] = 'Failed to update profile.';
            }
        }
    }
}

// Handle skill addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $skillId = (int)($_POST['skill_id'] ?? 0);
        $proficiency = sanitizeInput($_POST['proficiency_level'] ?? '');
        $yearsExp = (int)($_POST['years_experience'] ?? 0);
        
        if ($skillId <= 0) {
            $errors[] = 'Please select a skill.';
        }
        if (!in_array($proficiency, ['beginner', 'intermediate', 'advanced', 'expert'])) {
            $errors[] = 'Invalid proficiency level.';
        }
        
        if (empty($errors)) {
            try {
                $stmt = getDB()->prepare('
                    INSERT INTO artisan_skills (artisan_id, skill_id, proficiency_level, years_of_experience)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$artisanId, $skillId, $proficiency, $yearsExp]);
                $success = true;
            } catch (Exception $e) {
                $errors[] = 'Skill already added or error occurred.';
            }
        }
    }
}

// Handle skill deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_skill') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $skillId = (int)($_POST['skill_id'] ?? 0);
        
        try {
            $stmt = getDB()->prepare('
                DELETE FROM artisan_skills WHERE artisan_id = ? AND skill_id = ?
            ');
            $stmt->execute([$artisanId, $skillId]);
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'Failed to delete skill.';
        }
    }
}

// Get artisan skills
$stmt = getDB()->prepare('
    SELECT s.id, s.name, s.category, asn.proficiency_level, asn.years_of_experience
    FROM artisan_skills asn
    JOIN skills s ON asn.skill_id = s.id
    WHERE asn.artisan_id = ?
    ORDER BY s.name
');
$stmt->execute([$artisanId]);
$artisanSkills = $stmt->fetchAll();

// Get available skills
$stmt = getDB()->prepare('
    SELECT id, name, category FROM skills ORDER BY category, name
');
$stmt->execute();
$availableSkills = $stmt->fetchAll();

// Get artisan documents
$stmt = getDB()->prepare('
    SELECT * FROM documents WHERE artisan_id = ? ORDER BY uploaded_at DESC
');
$stmt->execute([$artisanId]);
$documents = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="profile-container">
    <h1>My Profile</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Changes saved successfully!
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

    <!-- Profile Information Section -->
    <div class="profile-section">
        <h2>Personal Information</h2>
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" disabled>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" disabled>
                </div>
            </div>

            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location/City *</label>
                    <input type="text" id="location" name="location" required 
                           value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="state">State *</label>
                    <input type="text" id="state" name="state" required 
                           value="<?php echo htmlspecialchars($profile['state'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="years_experience">Years of Experience</label>
                    <input type="number" id="years_experience" name="years_experience" min="0" 
                           value="<?php echo $profile['years_of_experience'] ?? 0; ?>">
                </div>
                <div class="form-group">
                    <label for="hourly_rate">Hourly Rate (₦)</label>
                    <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0" 
                           value="<?php echo $profile['hourly_rate'] ?? 0; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="availability_status">Availability Status *</label>
                <select id="availability_status" name="availability_status" required>
                    <option value="available" <?php echo $profile['availability_status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="busy" <?php echo $profile['availability_status'] === 'busy' ? 'selected' : ''; ?>>Busy</option>
                    <option value="unavailable" <?php echo $profile['availability_status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <!-- Skills Section -->
    <div class="profile-section">
        <h2>Skills</h2>

        <?php if (!empty($artisanSkills)): ?>
            <div class="skills-list">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Skill</th>
                            <th>Category</th>
                            <th>Proficiency</th>
                            <th>Years</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artisanSkills as $skill): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($skill['name']); ?></td>
                                <td><?php echo htmlspecialchars($skill['category']); ?></td>
                                <td><?php echo ucfirst($skill['proficiency_level']); ?></td>
                                <td><?php echo $skill['years_of_experience']; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete_skill">
                                        <input type="hidden" name="skill_id" value="<?php echo $skill['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                data-confirm="Are you sure you want to delete this skill?"
                                                data-confirm-title="Delete Skill">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No skills added yet.</p>
        <?php endif; ?>

        <h3>Add a Skill</h3>
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="add_skill">

            <div class="form-row">
                <div class="form-group">
                    <label for="skill_id">Select Skill *</label>
                    <select id="skill_id" name="skill_id" required>
                        <option value="">-- Choose a Skill --</option>
                        <?php foreach ($availableSkills as $skill): ?>
                            <option value="<?php echo $skill['id']; ?>">
                                <?php echo htmlspecialchars($skill['name']); ?> (<?php echo htmlspecialchars($skill['category']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="proficiency_level">Proficiency Level *</label>
                    <select id="proficiency_level" name="proficiency_level" required>
                        <option value="">-- Select Level --</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                        <option value="expert">Expert</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="years_experience">Years of Experience with this Skill</label>
                <input type="number" id="years_experience" name="years_experience" min="0" value="0">
            </div>

            <button type="submit" class="btn btn-primary">Add Skill</button>
        </form>
    </div>

    <!-- Documents Section -->
    <div class="profile-section">
        <h2>Documents & Certificates</h2>

        <?php if (!empty($documents)): ?>
            <div class="documents-list">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                                <td><?php echo number_format($doc['file_size'] / 1024, 2); ?> KB</td>
                                <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                <td>
                                    <a href="/uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                       class="btn btn-sm btn-secondary" download>Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No documents uploaded yet.</p>
        <?php endif; ?>

        <h3>Upload Document</h3>
        <form method="POST" enctype="multipart/form-data" class="form" action="/artisan/upload-document.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="document_type">Document Type *</label>
                <select id="document_type" name="document_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="certificate">Certificate</option>
                    <option value="license">License</option>
                    <option value="portfolio">Portfolio/Work Sample</option>
                    <option value="identification">Identification</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="document">Choose File *</label>
                <input type="file" id="document" name="document" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <small>Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max 5MB)</small>
            </div>

            <button type="submit" class="btn btn-primary">Upload Document</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
