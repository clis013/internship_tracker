<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$status_filter = $_GET['status'] ?? '';

$sql = "SELECT a.id, a.status, a.applied_at, a.cover_letter,
        s.name AS student_name, s.email AS student_email,
        j.title AS job_title, c.name AS company_name
    FROM applications a
    JOIN users s ON a.student_id = s.id
    JOIN jobs j ON a.job_id = j.id
    JOIN users c ON j.company_id = c.id";

$params = [];
$types = '';
if (in_array($status_filter, ['pending', 'reviewed', 'accepted', 'rejected'])) {
    $sql .= " WHERE a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$sql .= " ORDER BY a.applied_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$applications = mysqli_stmt_get_result($stmt);

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
    <h3 class="mb-4">All Applications</h3>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="reviewed" <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                <option value="accepted" <?= $status_filter === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
    </form>

    <?php if (mysqli_num_rows($applications) === 0): ?>
        <div class="alert alert-info">No applications found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Internship</th>
                        <th>Company</th>
                        <th>Applied On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($app = mysqli_fetch_assoc($applications)): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($app['student_name']) ?><br>
                                <span class="text-muted small"><?= htmlspecialchars($app['student_email']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($app['job_title']) ?></td>
                            <td><?= htmlspecialchars($app['company_name']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($app['applied_at']))) ?></td>
                            <td><?= status_badge($app['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>