<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('company');
include '../includes/header.php';
include '../includes/navbar.php';

$company_id = $_SESSION['user_id'];

// Stats
$stmt = mysqli_prepare($conn, "SELECT
    COUNT(*) AS total_jobs,
    SUM(status = 'active') AS active_jobs,
    SUM(status = 'closed') AS closed_jobs
    FROM jobs WHERE company_id = ?");
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$job_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$stmt = mysqli_prepare($conn, "SELECT
    COUNT(*) AS total_applicants,
    SUM(a.status = 'pending') AS pending
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.company_id = ?");
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$app_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Recent job postings
$stmt = mysqli_prepare($conn, "SELECT j.id, j.title, j.status, j.created_at,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
    FROM jobs j
    WHERE j.company_id = ?
    ORDER BY j.created_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$recent_jobs = mysqli_stmt_get_result($stmt);
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-4 text-white">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h3>

    <div class="glass-hero mb-5">
        <div class="glass-hero-content row align-items-center justify-content-between">
            <div class="col-md-6 mb-4 mb-md-0" style="padding-left: 40px;">
                <h1 class="hero-title">Innovate.<br>Elevate.<br>Transform.</h1>
                <p class="hero-subtitle mt-3">Empowering your recruitment process with cutting-edge tools and streamlined applicant tracking. Build your dream team today.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="manage_jobs.php" class="btn btn-glass-primary rounded-pill px-4 py-2">Post a Job</a>
                    <a href="profile.php" class="btn btn-glass-secondary rounded-pill px-4 py-2"><i class="bi bi-asterisk"></i> Company Profile</a>
                </div>
            </div>

            <div class="col-md-6">
                <div class="d-flex align-items-start justify-content-center justify-content-md-end gap-1" style="margin-right: -80px;">
                    <img src="../assets/images/IMG_3342.PNG" alt="Your Idea Our Expertise" class="img-fluid hero-puzzle-img">
                    <img src="../assets/images/homepage-logo.png" alt="Stacked Keys" class="img-fluid hero-stacked-keys">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card glass-card-hover" onclick="location.href='manage_jobs.php'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-info"><?= (int)$job_stats['total_jobs'] ?></h3>
                    <p class="card-text text-muted small mb-0">Total Jobs Posted</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card glass-card-hover" onclick="location.href='manage_jobs.php'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-success"><?= (int)$job_stats['active_jobs'] ?></h3>
                    <p class="card-text text-muted small mb-0">Active Listings</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card glass-card-hover" onclick="location.href='applicants.php'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-primary"><?= (int)$app_stats['total_applicants'] ?></h3>
                    <p class="card-text text-muted small mb-0">Total Applicants</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card glass-card-hover" onclick="location.href='applicants.php?status=pending'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-warning"><?= (int)$app_stats['pending'] ?></h3>
                    <p class="card-text text-muted small mb-0">Pending Review</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm glass-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-white">Recent Job Postings</h5>
                <a href="manage_jobs.php" class="btn btn-glass-secondary btn-sm rounded-pill">Manage Jobs</a>
            </div>

            <?php if (mysqli_num_rows($recent_jobs) === 0): ?>
                <div class="alert bg-transparent border border-secondary text-white text-center py-4 my-2">
                    You haven't posted any internships yet. <a href="manage_jobs.php" class="text-info text-decoration-none">Post your first internship</a>.
                </div>
            <?php else: ?>
                <div class="row glass-table-header d-none d-md-flex mb-2 px-3 mt-4">
                    <div class="col-md-4">Title</div>
                    <div class="col-md-2">Status</div>
                    <div class="col-md-2">Applicants</div>
                    <div class="col-md-2">Posted On</div>
                    <div class="col-md-2 text-end">Action</div>
                </div>
                
                <div class="d-flex flex-column">
                    <?php while ($job = mysqli_fetch_assoc($recent_jobs)): ?>
                        <div class="row glass-row-item align-items-center py-3 px-3 mx-0">
                            <div class="col-md-4 glass-row-text-primary text-truncate fw-semibold">
                                <?= htmlspecialchars($job['title']) ?>
                            </div>
                            <div class="col-md-2 my-1 my-md-0">
                                <span class="badge bg-<?= $job['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars(ucfirst($job['status'])) ?>
                                </span>
                            </div>
                            <div class="col-md-2 glass-row-text-secondary text-white-50">
                                <?= (int)$job['applicant_count'] ?> applicant(s)
                            </div>
                            <div class="col-md-2 glass-row-text-muted small">
                                <?= htmlspecialchars(date('d M Y', strtotime($job['created_at']))) ?>
                            </div>
                            <div class="col-md-2 text-md-end text-start mt-2 mt-md-0">
                                <a href="applicants.php?job_id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-glass-secondary rounded-pill">View Applicants</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>