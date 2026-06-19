<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit();
}
$user_id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT id, name, email, role, phone, website, description, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$u = mysqli_fetch_assoc($res);

if (!$u) {
    echo "User not found.";
    exit();
}

$role = $u['role'];

function role_badge(string $role): string {
    $map = ['student' => 'primary', 'company' => 'success', 'admin' => 'danger'];
    $c   = $map[$role] ?? 'secondary';
    return "<span class='badge badge-uniform bg-$c'>" . htmlspecialchars(ucfirst($role)) . "</span>";
}

function stat_pill(string $label, $value, string $colour = 'secondary'): string {
    return "<div class='text-center px-3 py-2 rounded bg-$colour bg-opacity-10 border border-$colour border-opacity-25 flex-fill'>
                <div class='fw-bold fs-5 text-$colour'>" . (int)$value . "</div>
                <div class='text-muted small' style='font-size:0.75rem;'>" . htmlspecialchars($label) . "</div>
            </div>";
}

if ($role === 'company') {
    $stmt = mysqli_prepare($conn,
        "SELECT
            (SELECT COUNT(*) FROM jobs j WHERE j.company_id = ?)               AS job_count,
            (SELECT COUNT(*) FROM jobs j WHERE j.company_id = ? AND j.status = 'active') AS active_jobs,
            (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.company_id = ?) AS applicant_count"
    );
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$stats) $stats = ['job_count' => 0, 'active_jobs' => 0, 'applicant_count' => 0];
} elseif ($role === 'student') {
    $stmt = mysqli_prepare($conn,
        "SELECT
            COUNT(*)                                                          AS total_apps,
            SUM(status = 'pending')                                           AS pending,
            SUM(status = 'accepted')                                          AS accepted,
            SUM(status = 'rejected')                                          AS rejected
         FROM applications WHERE student_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$stats) $stats = ['total_apps' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0];
}
?>
<!-- Modal Content Body -->
<div class="modal-header border-0 border-bottom border-secondary border-opacity-25">
    <div>
        <h5 class="modal-title mb-1 text-white"><?= htmlspecialchars($u['name']) ?></h5>
        <?= role_badge($role) ?>
    </div>
    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body text-white">
    <?php if ($role === 'company'): ?>
        <div class="d-flex gap-2 flex-wrap mb-4 justify-content-between">
            <?= stat_pill('Jobs Posted',  $stats['job_count'],       'primary') ?>
            <?= stat_pill('Active Jobs',  $stats['active_jobs'],     'success') ?>
            <?= stat_pill('Applicants',   $stats['applicant_count'], 'warning') ?>
        </div>

        <table class="table table-sm table-borderless text-white mb-0">
            <tr><th style="width:30%" class="text-white-50">ID</th>        <td><?= (int)$u['id'] ?></td></tr>
            <tr><th class="text-white-50">Email</th>      <td><?= htmlspecialchars($u['email']) ?></td></tr>
            <?php if (!empty($u['phone'])): ?>
            <tr><th class="text-white-50">Phone</th>      <td><?= htmlspecialchars($u['phone']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($u['website'])): ?>
            <tr><th class="text-white-50">Website</th>
                <td><a href="<?= htmlspecialchars($u['website']) ?>" target="_blank" rel="noopener" class="text-info">
                    <?= htmlspecialchars($u['website']) ?></a></td></tr>
            <?php endif; ?>
            <tr><th class="text-white-50">Registered</th> <td><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td></tr>
        </table>

        <?php if (!empty($u['description'])): ?>
            <hr class="border-secondary border-opacity-50">
            <p class="small text-white-50 mb-1 fw-semibold">About</p>
            <p class="small mb-0"><?= nl2br(htmlspecialchars($u['description'])) ?></p>
        <?php endif; ?>

    <?php elseif ($role === 'student'): ?>
        <div class="d-flex gap-2 flex-wrap mb-4 justify-content-between">
            <?= stat_pill('Applied',   $stats['total_apps'], 'primary') ?>
            <?= stat_pill('Pending',   $stats['pending'],    'warning') ?>
            <?= stat_pill('Accepted',  $stats['accepted'],   'success') ?>
            <?= stat_pill('Rejected',  $stats['rejected'],   'danger')  ?>
        </div>

        <table class="table table-sm table-borderless text-white mb-0">
            <tr><th style="width:30%" class="text-white-50">ID</th>        <td><?= (int)$u['id'] ?></td></tr>
            <tr><th class="text-white-50">Email</th>      <td><?= htmlspecialchars($u['email']) ?></td></tr>
            <tr><th class="text-white-50">Registered</th> <td><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td></tr>
        </table>

    <?php else: ?>
        <table class="table table-sm table-borderless text-white mb-0">
            <tr><th style="width:30%" class="text-white-50">ID</th>        <td><?= (int)$u['id'] ?></td></tr>
            <tr><th class="text-white-50">Email</th>      <td><?= htmlspecialchars($u['email']) ?></td></tr>
            <tr><th class="text-white-50">Registered</th> <td><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td></tr>
        </table>
        <p class="text-white-50 small mt-3 mb-0">Administrator accounts have no additional activity stats.</p>
    <?php endif; ?>
</div>

<div class="modal-footer border-0 border-top border-secondary border-opacity-25">
    <button class="btn btn-glass-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
</div>
