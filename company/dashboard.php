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

<div class="container mt-4">
    <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$job_stats['total_jobs'] ?></h5>
                    <p class="card-text text-muted mb-0">Total Jobs Posted</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$job_stats['active_jobs'] ?></h5>
                    <p class="card-text text-muted mb-0">Active Listings</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$app_stats['total_applicants'] ?></h5>
                    <p class="card-text text-muted mb-0">Total Applicants</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$app_stats['pending'] ?></h5>
                    <p class="card-text text-muted mb-0">Pending Review</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Recent Job Postings</h5>
        <a href="manage_jobs.php" class="btn btn-primary btn-sm">Manage Jobs</a>
    </div>

    <?php if (mysqli_num_rows($recent_jobs) === 0): ?>
        <div class="alert alert-info">
            You haven't posted any internships yet.
            <a href="manage_jobs.php">Post your first internship</a>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Applicants</th>
                        <th>Posted On</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($job = mysqli_fetch_assoc($recent_jobs)): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['title']) ?></td>
                            <td>
                                <span class="badge bg-<?= $job['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars(ucfirst($job['status'])) ?>
                                </span>
                            </td>
                            <td><?= (int)$job['applicant_count'] ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($job['created_at']))) ?></td>
                            <td><a href="applicants.php?job_id=<?= (int)$job['id'] ?>" class="btn btn-sm btn-outline-primary">View Applicants</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>