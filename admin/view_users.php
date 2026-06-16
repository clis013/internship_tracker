<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$error   = '';
$success = '';

// ── Delete user ────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $del_id);
        mysqli_stmt_execute($stmt);
        header("Location: view_users.php?deleted=1");
        exit();
    }
}

// ── Filters & search ───────────────────────────────────────────────────────
$role_filter = $_GET['role']   ?? '';
$search      = trim($_GET['search'] ?? '');

$sql    = "SELECT id, name, email, role, phone, website, description, created_at FROM users WHERE 1=1";
$params = [];
$types  = '';

if (in_array($role_filter, ['student', 'company', 'admin'])) {
    $sql     .= " AND role = ?";
    $params[] = $role_filter;
    $types   .= 's';
}
if ($search !== '') {
    $sql     .= " AND (name LIKE ? OR email LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);

$user_rows = [];
while ($u = mysqli_fetch_assoc($users)) $user_rows[] = $u;

// ── Per-user stats for modals ──────────────────────────────────────────────
function get_company_stats($conn, int $id): array {
    $stmt = mysqli_prepare($conn,
        "SELECT
            (SELECT COUNT(*) FROM jobs j WHERE j.company_id = ?)               AS job_count,
            (SELECT COUNT(*) FROM jobs j WHERE j.company_id = ? AND j.status = 'active') AS active_jobs,
            (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.company_id = ?) AS applicant_count"
    );
    mysqli_stmt_bind_param($stmt, "iii", $id, $id, $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ?: ['job_count' => 0, 'active_jobs' => 0, 'applicant_count' => 0];
}

function get_student_stats($conn, int $id): array {
    $stmt = mysqli_prepare($conn,
        "SELECT
            COUNT(*)                                                  AS total_apps,
            SUM(status = 'pending')                                   AS pending,
            SUM(status = 'accepted')                                  AS accepted,
            SUM(status = 'rejected')                                  AS rejected
         FROM applications WHERE student_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row ?: ['total_apps' => 0, 'pending' => 0, 'accepted' => 0, 'rejected' => 0];
}

// ── Helpers ────────────────────────────────────────────────────────────────
function role_badge(string $role): string {
    $map = ['student' => 'primary', 'company' => 'success', 'admin' => 'danger'];
    $c   = $map[$role] ?? 'secondary';
    return "<span class='badge bg-$c'>" . htmlspecialchars(ucfirst($role)) . "</span>";
}

function stat_pill(string $label, $value, string $colour = 'secondary'): string {
    return "<div class='text-center px-3 py-2 rounded bg-$colour bg-opacity-10 border border-$colour border-opacity-25'>
                <div class='fw-bold fs-5 text-$colour'>" . (int)$value . "</div>
                <div class='text-muted small'>" . htmlspecialchars($label) . "</div>
            </div>";
}
?>

<div class="container mt-4">
    <h3 class="mb-1">Manage Users</h3>
    <p class="text-muted mb-4">View, inspect, and delete registered accounts.</p>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">User deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Search & filter -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email…"
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">All Roles</option>
                <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
                <option value="company" <?= $role_filter === 'company' ? 'selected' : '' ?>>Companies</option>
                <option value="admin"   <?= $role_filter === 'admin'   ? 'selected' : '' ?>>Admins</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit"
                    class="btn btn-primary w-100"
                    title="Search">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-2">
            <a href="view_users.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($user_rows) ?> user(s) found.</p>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($user_rows)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($user_rows as $i => $u): ?>
                    <tr>
                        <td class="text-muted small"><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= role_badge($u['role']) ?></td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#userModal<?= $i ?>">
                                View
                            </button>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="view_users.php?delete=<?= (int)$u['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>? This removes all their data.')">
                                    Delete
                                </a>
                            <?php else: ?>
                                <span class="text-muted small align-self-center">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Role-aware user detail modals ── -->
<?php foreach ($user_rows as $i => $u):
    $role = $u['role'];
    if ($role === 'company') $stats = get_company_stats($conn, (int)$u['id']);
    if ($role === 'student') $stats = get_student_stats($conn, (int)$u['id']);
?>
<div class="modal fade" id="userModal<?= $i ?>" tabindex="-1" aria-labelledby="userModalLabel<?= $i ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered <?= $role === 'company' ? 'modal-lg' : '' ?>">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="userModalLabel<?= $i ?>"><?= htmlspecialchars($u['name']) ?></h5>
                    <?= role_badge($role) ?>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- ── COMPANY modal body ── -->
                <?php if ($role === 'company'): ?>

                    <!-- Activity stats pills -->
                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <?= stat_pill('Jobs Posted',  $stats['job_count'],       'primary') ?>
                        <?= stat_pill('Active Jobs',  $stats['active_jobs'],     'success') ?>
                        <?= stat_pill('Applicants',   $stats['applicant_count'], 'warning') ?>
                    </div>

                    <!-- Profile details -->
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th style="width:30%">ID</th>         <td><?= (int)$u['id'] ?></td></tr>
                        <tr><th>Email</th>       <td><?= htmlspecialchars($u['email']) ?></td></tr>
                        <?php if (!empty($u['phone'])): ?>
                        <tr><th>Phone</th>       <td><?= htmlspecialchars($u['phone']) ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($u['website'])): ?>
                        <tr><th>Website</th>
                            <td><a href="<?= htmlspecialchars($u['website']) ?>" target="_blank" rel="noopener">
                                <?= htmlspecialchars($u['website']) ?></a></td></tr>
                        <?php endif; ?>
                        <tr><th>Registered</th> <td><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td></tr>
                    </table>

                    <?php if (!empty($u['description'])): ?>
                        <hr>
                        <p class="small text-muted mb-1 fw-semibold">About</p>
                        <p class="small mb-0"><?= nl2br(htmlspecialchars($u['description'])) ?></p>
                    <?php endif; ?>

                <!-- ── STUDENT modal body ── -->
                <?php elseif ($role === 'student'): ?>

                    <!-- Application stats pills -->
                    <div class="d-flex gap-2 flex-wrap mb-4">
                        <?= stat_pill('Applied',   $stats['total_apps'], 'primary') ?>
                        <?= stat_pill('Pending',   $stats['pending'],    'warning') ?>
                        <?= stat_pill('Accepted',  $stats['accepted'],   'success') ?>
                        <?= stat_pill('Rejected',  $stats['rejected'],   'danger')  ?>
                    </div>

                    <table class="table table-sm table-borderless mb-0">
                        <tr><th style="width:30%">ID</th>         <td><?= (int)$u['id'] ?></td></tr>
                        <tr><th>Email</th>       <td><?= htmlspecialchars($u['email']) ?></td></tr>
                        <tr><th>Registered</th> <td><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td></tr>
                    </table>

                <!-- ── ADMIN modal body ── -->
                <?php else: ?>

                    <table class="table table-sm table-borderless mb-0">
                        <tr><th style="width:30%">ID</th>         <td><?= (int)$u['id'] ?></td></tr>
                        <tr><th>Email</th>       <td><?= htmlspecialchars($u['email']) ?></td></tr>
                        <tr><th>Registered</th> <td><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td></tr>
                    </table>
                    <p class="text-muted small mt-3 mb-0">Administrator accounts have no additional activity stats.</p>

                <?php endif; ?>

            </div><!-- /modal-body -->

            <!-- Footer -->
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                    <a href="view_users.php?delete=<?= (int)$u['id'] ?>"
                       class="btn btn-danger"
                       onclick="return confirm('Delete this user?')">Delete User</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>