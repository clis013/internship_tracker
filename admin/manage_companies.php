<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

// Companies with job stats
$companies = mysqli_query($conn, "SELECT u.id, u.name, u.email, u.phone, u.website, u.description, u.created_at,
        (SELECT COUNT(*) FROM jobs j WHERE j.company_id = u.id) AS job_count,
        (SELECT COUNT(*) FROM jobs j WHERE j.company_id = u.id AND j.status = 'active') AS active_jobs,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.company_id = u.id) AS applicant_count
    FROM users u
    WHERE u.role = 'company'
    ORDER BY u.created_at DESC");
?>

<div class="container mt-4">
    <h3 class="mb-4">Manage Companies</h3>

    <?php if (mysqli_num_rows($companies) === 0): ?>
        <div class="alert alert-info">No companies registered yet.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($c = mysqli_fetch_assoc($companies)): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($c['name']) ?></h5>
                            <p class="mb-1 text-muted"><?= htmlspecialchars($c['email']) ?></p>
                            <?php if ($c['phone']): ?>
                                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($c['phone']) ?></p>
                            <?php endif; ?>
                            <?php if ($c['website']): ?>
                                <p class="mb-1"><strong>Website:</strong> <?= htmlspecialchars($c['website']) ?></p>
                            <?php endif; ?>
                            <?php if ($c['description']): ?>
                                <p class="mb-2 small"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex gap-3 small text-muted">
                                <span><strong><?= (int)$c['job_count'] ?></strong> Jobs Posted</span>
                                <span><strong><?= (int)$c['active_jobs'] ?></strong> Active</span>
                                <span><strong><?= (int)$c['applicant_count'] ?></strong> Applicants</span>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Registered: <?= htmlspecialchars(date('d M Y', strtotime($c['created_at']))) ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>