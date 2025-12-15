<?php
/**
 * Home Page
 * 
 * Landing page for the Artisan Platform
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Artisan Platform - Empowering Artisans in Nigeria';
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="home-container">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Artisan Platform Nigeria</h1>
            <p>Connecting skilled artisans with employers for transparent and efficient digital recruitment</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="hero-buttons">
                    <a href="/auth/register.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="/auth/login.php" class="btn btn-secondary btn-lg">Login</a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <?php if (hasRole('artisan')): ?>
                        <a href="/artisan/jobs.php" class="btn btn-primary btn-lg">Browse Jobs</a>
                        <a href="/artisan/dashboard.php" class="btn btn-secondary btn-lg">Go to Dashboard</a>
                    <?php elseif (hasRole('employer')): ?>
                        <a href="/employer/post-job.php" class="btn btn-primary btn-lg">Post a Job</a>
                        <a href="/employer/dashboard.php" class="btn btn-secondary btn-lg">Go to Dashboard</a>
                    <?php elseif (hasRole('admin')): ?>
                        <a href="/admin/dashboard.php" class="btn btn-primary btn-lg">Admin Dashboard</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2>Why Choose Artisan Platform?</h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Easy Job Matching</h3>
                    <p>Find the perfect job or hire the right artisan with our advanced search and filtering system.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Verified</h3>
                    <p>All users are verified by government agencies, ensuring trust and reliability.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3>Direct Communication</h3>
                    <p>Message directly with employers or artisans to discuss project details.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3>Ratings & Reviews</h3>
                    <p>Build your reputation with honest ratings and reviews from clients.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics & Reports</h3>
                    <p>Track your performance and get insights into the job market.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3>Nationwide Reach</h3>
                    <p>Connect with opportunities across all states in Nigeria.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <h2>Platform Statistics</h2>
            
            <div class="stats-grid">
                <?php
                // Get statistics from database
                $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM users WHERE role = "artisan"');
                $stmt->execute();
                $artisanCount = $stmt->fetch()['count'];

                $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM users WHERE role = "employer"');
                $stmt->execute();
                $employerCount = $stmt->fetch()['count'];

                $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM jobs WHERE status = "open"');
                $stmt->execute();
                $jobCount = $stmt->fetch()['count'];

                $stmt = getDB()->prepare('SELECT COUNT(*) as count FROM job_applications WHERE status = "accepted"');
                $stmt->execute();
                $successfulMatches = $stmt->fetch()['count'];
                ?>
                
                <div class="stat-box">
                    <h3><?php echo number_format($artisanCount); ?></h3>
                    <p>Registered Artisans</p>
                </div>

                <div class="stat-box">
                    <h3><?php echo number_format($employerCount); ?></h3>
                    <p>Employers</p>
                </div>

                <div class="stat-box">
                    <h3><?php echo number_format($jobCount); ?></h3>
                    <p>Active Jobs</p>
                </div>

                <div class="stat-box">
                    <h3><?php echo number_format($successfulMatches); ?></h3>
                    <p>Successful Matches</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section">
        <div class="container">
            <h2>How It Works</h2>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Create Account</h3>
                    <p>Register as an artisan or employer with your basic information.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Complete Profile</h3>
                    <p>Add your skills, experience, and documents for verification.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Get Verified</h3>
                    <p>Government agencies verify your profile to ensure authenticity.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Find Opportunities</h3>
                    <p>Browse jobs or post vacancies and connect with the right people.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">5</div>
                    <h3>Communicate</h3>
                    <p>Message directly and discuss project details with potential partners.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">6</div>
                    <h3>Build Reputation</h3>
                    <p>Receive ratings and reviews to build your professional reputation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of artisans and employers transforming the Nigerian workforce</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="cta-buttons">
                    <a href="/auth/register.php?role=artisan" class="btn btn-primary btn-lg">I'm an Artisan</a>
                    <a href="/auth/register.php?role=employer" class="btn btn-secondary btn-lg">I'm an Employer</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #333;
        padding: 80px 20px;
        text-align: center;
        border-bottom: 3px solid #007bff;
    }

    .hero-content h1 {
        font-size: 3rem;
        margin-bottom: 20px;
        color: #007bff;
    }

    .hero-content p {
        font-size: 1.3rem;
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        color: #555;
    }

    .hero-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-lg {
        padding: 15px 40px;
        font-size: 1.1rem;
    }

    /* Features Section */
    .features-section {
        padding: 80px 20px;
        background-color: white;
    }

    .features-section h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 50px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .feature-card {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 123, 255, 0.2);
        border-color: #007bff;
    }

    .feature-icon {
        font-size: 3rem;
        margin-bottom: 15px;
    }

    .feature-card h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .feature-card p {
        color: #666;
        line-height: 1.6;
    }

    /* Statistics Section */
    .stats-section {
        background-color: #f5f5f5;
        padding: 80px 20px;
    }

    .stats-section h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 50px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
    }

    .stat-box {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stat-box h3 {
        font-size: 2.5rem;
        color: #007bff;
        margin-bottom: 10px;
    }

    .stat-box p {
        color: #666;
        font-size: 1.1rem;
    }

    /* How It Works Section */
    .how-it-works-section {
        padding: 80px 20px;
        background-color: white;
    }

    .how-it-works-section h2 {
        text-align: center;
        font-size: 2.5rem;
        margin-bottom: 50px;
    }

    .steps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
    }

    .step-card {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        position: relative;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }

    .step-number {
        display: inline-block;
        width: 50px;
        height: 50px;
        background-color: #007bff;
        color: white;
        border-radius: 50%;
        line-height: 50px;
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .step-card h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .step-card p {
        color: #666;
        line-height: 1.6;
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #333;
        padding: 80px 20px;
        text-align: center;
        border-top: 3px solid #007bff;
    }

    .cta-section h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: #007bff;
    }

    .cta-section p {
        font-size: 1.2rem;
        margin-bottom: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        color: #555;
    }

    .cta-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 2rem;
        }

        .hero-content p {
            font-size: 1.1rem;
        }

        .features-section h2,
        .stats-section h2,
        .how-it-works-section h2,
        .cta-section h2 {
            font-size: 1.8rem;
        }

        .features-grid,
        .stats-grid,
        .steps-grid {
            grid-template-columns: 1fr;
        }

        .hero-buttons,
        .cta-buttons {
            flex-direction: column;
        }

        .btn-lg {
            width: 100%;
        }
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
