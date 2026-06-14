<?php
session_start();
include 'config/db_connect.php';
include 'includes/header.php';
include 'includes/navbar.php';

// If logged in, redirect to their dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: /internship_tracker/student/dashboard.php");
    } elseif ($_SESSION['role'] === 'company') {
        header("Location: /internship_tracker/company/dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: /internship_tracker/admin/dashboard.php");
    }
    exit();
}

// Quick stats for landing page
$jobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM jobs WHERE status = 'active'"));
$companies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'company'"));

// Latest active internships
$latest_jobs = mysqli_query($conn, "SELECT j.title, j.location, j.field, u.name AS company_name
    FROM jobs j
    JOIN users u ON j.company_id = u.id
    WHERE j.status = 'active'
    ORDER BY j.created_at DESC LIMIT 6");
?>

<!-- Hero Section -->
<div class="bg-dark text-white py-5">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-3">Find Your Next Internship</h1>
        <p class="lead mb-4">Connecting students with companies offering real-world internship opportunities.</p>
        <a href="auth/register.php" class="btn btn-primary btn-lg me-2">Get Started</a>
        <a href="auth/login.php" class="btn btn-outline-light btn-lg">Login</a>
    </div>
</div>

<!-- Stats -->
<div class="container py-5">
    <div class="row text-center g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="fw-bold text-primary"><?= (int)$jobs['total'] ?></h2>
                    <p class="text-muted mb-0">Active Internship Openings</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="fw-bold text-primary"><?= (int)$companies['total'] ?></h2>
                    <p class="text-muted mb-0">Registered Companies</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Internships -->
    <h3 class="mb-4">Latest Internship Opportunities</h3>
    <?php if (mysqli_num_rows($latest_jobs) === 0): ?>
        <div class="alert alert-info">No internships posted yet. Check back soon!</div>
    <?php else: ?>
        <div class="row g-3 mb-4">
            <?php while ($job = mysqli_fetch_assoc($latest_jobs)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($job['company_name']) ?></h6>
                            <ul class="list-unstyled small text-muted mb-0">
                                <?php if ($job['location']): ?>
                                    <li>📍 <?= htmlspecialchars($job['location']) ?></li>
                                <?php endif; ?>
                                <?php if ($job['field']): ?>
                                    <li>🏷️ <?= htmlspecialchars($job['field']) ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center">
            <a href="auth/login.php" class="btn btn-primary">Login to Apply</a>
        </div>
    <?php endif; ?>
</div>

<!-- How it works -->
<div class="bg-light py-5">
    <div class="container">
        <h3 class="text-center mb-4">How It Works</h3>
        <div class="row text-center g-4">
            <div class="col-md-4">
                <h5>1. Create an Account</h5>
                <p class="text-muted">Register as a student looking for internships or a company offering them.</p>
            </div>
            <div class="col-md-4">
                <h5>2. Browse or Post</h5>
                <p class="text-muted">Students browse available internships. Companies post and manage listings.</p>
            </div>
            <div class="col-md-4">
                <h5>3. Apply & Track</h5>
                <p class="text-muted">Students apply with a cover letter and track application status in real time.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>