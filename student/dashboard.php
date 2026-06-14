<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

// Stats
$stmt = mysqli_prepare($conn, "SELECT 
    COUNT(*) AS total,
    SUM(status = 'pending') AS pending,
    SUM(status = 'reviewed') AS reviewed,
    SUM(status = 'accepted') AS accepted,
    SUM(status = 'rejected') AS rejected
    FROM applications WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Recent applications
$stmt = mysqli_prepare($conn, "SELECT a.id, a.status, a.applied_at, j.title, u.name AS company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.company_id = u.id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);

function status_badge($status) {
    $map = [
        'pending'  => 'secondary',
        'reviewed' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$class\">" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$stats['total'] ?></h5>
                    <p class="card-text text-muted mb-0">Total Applications</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$stats['pending'] ?></h5>
                    <p class="card-text text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$stats['accepted'] ?></h5>
                    <p class="card-text text-muted mb-0">Accepted</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$stats['rejected'] ?></h5>
                    <p class="card-text text-muted mb-0">Rejected</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Recent Applications</h5>
        <a href="browse.php" class="btn btn-primary btn-sm">Browse Internships</a>
    </div>

    <?php if (mysqli_num_rows($recent) === 0): ?>
        <div class="alert alert-info">You haven't applied to any internships yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Applied On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($row['applied_at']))) ?></td>
                            <td><?= status_badge($row['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <a href="my_applications.php" class="btn btn-outline-secondary btn-sm">View All Applications</a>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>