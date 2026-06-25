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

<style>
    /* The whole page background */
    body.homepage {
        background: url('assets/images/Homepage_bg.JPG.jpeg?v=1') no-repeat center center fixed !important;
        background-size: cover !important;
    }

    /* The Header background */
    .hero-section {
        background: url('assets/images/Hompage_header.JPG.jpeg?v=1') no-repeat center top !important;
        background-size: cover !important;
        box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.4); 
    }

    /* Bottom Section */
    .bottom-tree-section {
        background: url('assets/images/Hompage_header.JPG.jpeg?v=1') no-repeat center bottom !important;
        background-size: cover !important;
        box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.6) !important; 
        color: #ffffff !important;
    }

    /* DARK Glass for middle content boxes */
    .dark-glass-card {
        background: rgba(15, 15, 15, 0.40) !important; /* Slightly higher opacity so it stays dark over white waves */
        backdrop-filter: blur(16px) saturate(120%) !important;
        -webkit-backdrop-filter: blur(16px) saturate(120%) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.25) !important;
        border-left: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 16px !important; 
        box-shadow: 0 10px 32px 0 rgba(0, 0, 0, 0.35), inset 0 1px 2px rgba(255, 255, 255, 0.1) !important;
        color: #ffffff !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    }
    .dark-glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 45px 0 rgba(0, 0, 0, 0.45), inset 0 1px 2px rgba(255, 255, 255, 0.2) !important;
        background: rgba(15, 15, 15, 0.95) !important; 
    }

    /* LIGHT Glass for "How it Works" section */
    .light-glass-card {
        background: rgba(255, 255, 255, 0.15) !important;
        backdrop-filter: blur(16px) saturate(120%) !important;
        -webkit-backdrop-filter: blur(16px) saturate(120%) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.6) !important;
        border-left: 1px solid rgba(255, 255, 255, 0.4) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 16px !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3) !important;
        color: #ffffff !important;
        transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    }

    .light-glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.4) !important;
        background: rgba(255, 255, 255, 0.25) !important;
    }

    /* Force the footer to be transparent so the tree image shows through */
    body.homepage footer, body.homepage footer div {
        background: transparent !important;
        color: #ffffff !important;
    }
</style>

<script>document.body.classList.add('homepage');</script>

<div class="hero-section text-white py-5">
    <div class="container text-center py-5">
        <h1 class="display-5 fw-bold mb-3">Find Your Next Internship</h1>
        <p class="lead mb-4">Connecting students with companies offering real-world internship opportunities.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="auth/register.php" class="btn btn-glass-white btn-lg px-4" style="border-radius: 8px !important;">Get Started</a>
            <a href="auth/login.php" class="btn btn-glass-secondary btn-lg px-4" style="border-radius: 8px !important;">Login</a>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row text-center g-4 mb-5">
        <div class="col-md-6">
            <div class="dark-glass-card shadow-sm h-100 p-4">
                <div class="card-body">
                    <h2 class="fw-bold mb-1" style="color: #5dade2 !important; font-size: 3.5rem;"><?= (int)$jobs['total'] ?></h2>
                    <p class="text-white-50 mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Active Internship Openings</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dark-glass-card shadow-sm h-100 p-4">
                <div class="card-body">
                    <h2 class="fw-bold mb-1" style="color: #5dade2 !important; font-size: 3.5rem;"><?= (int)$companies['total'] ?></h2>
                    <p class="text-white-50 mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Registered Companies</p>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-4 fw-bold text-dark">Latest Internship Opportunities</h3>
    <?php if (mysqli_num_rows($latest_jobs) === 0): ?>
        <div class="alert bg-transparent border border-dark text-dark fw-bold text-center py-4">No internships posted yet. Check back soon!</div>
    <?php else: ?>
        <div class="row g-3 mb-4">
            <?php while ($job = mysqli_fetch_assoc($latest_jobs)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="dark-glass-card h-100 shadow-sm p-4">
                        <div class="card-body p-0">
                            <h5 class="card-title fw-bold text-white mb-1"><?= htmlspecialchars($job['title']) ?></h5>
                            <h6 class="card-subtitle mb-3" style="color: #5dade2 !important;"><?= htmlspecialchars($job['company_name']) ?></h6>
                            <ul class="list-unstyled small text-white-50 mb-0 fw-semibold">
                                <?php if ($job['location']): ?>
                                    <li class="mb-2"><i class="bi bi-geo-alt-fill me-2" style="color: #5dade2;"></i> <?= htmlspecialchars($job['location']) ?></li>
                                <?php endif; ?>
                                <?php if ($job['field']): ?>
                                    <li><i class="bi bi-tag-fill me-2" style="color: #5dade2;"></i> <?= htmlspecialchars($job['field']) ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center mt-5">
            <a href="auth/login.php" class="btn btn-dark btn-lg px-5 py-2 shadow-sm fw-bold" style="border-radius: 8px !important;">Login to Apply</a>
        </div>
    <?php endif; ?>
</div>

<div class="bottom-tree-section">
    <div class="py-5" style="border-top: 1px solid rgba(255,255,255,0.2);">
        <div class="container">
            <h3 class="text-center mb-5 fw-bold text-white">How It Works</h3>
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="light-glass-card p-4 h-100">
                        <div class="mb-3"><i class="bi bi-person-plus-fill fs-1 text-info"></i></div>
                        <h5 class="fw-bold text-white">1. Create an Account</h5>
                        <p class="text-white mb-0" style="opacity: 0.9;">Register as a student looking for internships or a company offering them.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="light-glass-card p-4 h-100">
                        <div class="mb-3"><i class="bi bi-search fs-1 text-info"></i></div>
                        <h5 class="fw-bold text-white">2. Browse or Post</h5>
                        <p class="text-white mb-0" style="opacity: 0.9;">Students browse available internships. Companies post and manage listings.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="light-glass-card p-4 h-100">
                        <div class="mb-3"><i class="bi bi-send-check-fill fs-1 text-info"></i></div>
                        <h5 class="fw-bold text-white">3. Apply & Track</h5>
                        <p class="text-white mb-0" style="opacity: 0.9;">Students apply with a cover letter and track application status in real time.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-auto">
        <?php include 'includes/footer.php'; ?>
    </footer>
</div>