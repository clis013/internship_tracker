<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

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
if ($search !== '') {

    $condition = "(s.name LIKE ? 
                   OR s.email LIKE ? 
                   OR j.title LIKE ? 
                   OR c.name LIKE ?)";

    if (!empty($params)) {
        $sql .= " AND $condition";
    } else {
        $sql .= " WHERE $condition";
    }

    $like = "%$search%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= 'ssss';
}
$sql .= " ORDER BY a.applied_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$applications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $applications[] = $row;
}

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

    <div class="col-md-7">
        <input type="text"
               name="search"
               class="form-control"
               placeholder="Search student, company, internship..."
               value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>

            <option value="pending"
                <?= $status_filter === 'pending' ? 'selected' : '' ?>>
                Pending
            </option>

            <option value="reviewed"
                <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>
                Reviewed
            </option>

            <option value="accepted"
                <?= $status_filter === 'accepted' ? 'selected' : '' ?>>
                Accepted
            </option>

            <option value="rejected"
                <?= $status_filter === 'rejected' ? 'selected' : '' ?>>
                Rejected
            </option>
        </select>
    </div>

    <div class="col-md-1">
        <button type="submit"
                class="btn btn-primary w-100"
                title="Search">
            <i class="bi bi-search"></i>
        </button>
    </div>

    <div class="col-md-1">
        <a href="view_applicants.php"
        class="btn btn-outline-secondary w-100"
        title="Reset">
            Reset
        </a>
    </div>

</form>

<p class="text-muted small mb-2">
    <?= count($applications) ?> application(s) found.
</p>

    <?php if (empty($applications)): ?>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $i => $app): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($app['student_name']) ?><br>
                                <span class="text-muted small"><?= htmlspecialchars($app['student_email']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($app['job_title']) ?></td>
                            <td><?= htmlspecialchars($app['company_name']) ?></td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($app['applied_at']))) ?></td>
                            <td><?= status_badge($app['status']) ?></td>

                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#applicationModal<?= $i ?>">
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($applications as $i => $app): ?>

<div class="modal fade"
     id="applicationModal<?= $i ?>"
     tabindex="-1">

    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Application Details
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">

                <h6 class="fw-semibold mb-3">
                    Student Information
                </h6>

                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="30%">Name</th>
                        <td><?= htmlspecialchars($app['student_name']) ?></td>
                    </tr>

                    <tr>
                        <th>Email</th>
                        <td><?= htmlspecialchars($app['student_email']) ?></td>
                    </tr>
                </table>

                <hr>

                <h6 class="fw-semibold mb-3">
                    Internship Information
                </h6>

                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="30%">Internship</th>
                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                    </tr>

                    <tr>
                        <th>Company</th>
                        <td><?= htmlspecialchars($app['company_name']) ?></td>
                    </tr>
                </table>

                <hr>

                <h6 class="fw-semibold mb-3">
                    Application Information
                </h6>

                <table class="table table-sm table-borderless">
                    <tr>
                        <th width="30%">Status</th>
                        <td><?= status_badge($app['status']) ?></td>
                    </tr>

                    <tr>
                        <th>Applied Date</th>
                        <td>
                            <?= date('d M Y H:i', strtotime($app['applied_at'])) ?>
                        </td>
                    </tr>
                </table>

                <?php if (!empty($app['cover_letter'])): ?>

                    <hr>

                    <h6 class="fw-semibold mb-2">
                        Cover Letter
                    </h6>

                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($app['cover_letter'])) ?>
                    </div>

                <?php endif; ?>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>

</div>

<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>