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
            COUNT(*)                                                          AS total_apps,
            SUM(status = 'pending')                                           AS pending,
            SUM(status = 'accepted')                                          AS accepted,
            SUM(status = 'rejected')                                          AS rejected
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

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-1 text-white">Manage Users</h3>
    <p class="text-white-50 mb-4">View, inspect, and delete registered accounts.</p>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success">User deleted successfully.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger bg-transparent border-danger text-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row g-2">
            <!-- Search Bar with Clickable Icon -->
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <input type="text" name="search" class="form-control glass-input border-end-0 text-white" placeholder="Search by name or email…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn input-group-text glass-input border-start-0 text-white m-0 px-3" style="cursor: pointer;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <!-- Glass Dropdown (Auto-submits on change) -->
            <div class="col-md-4">
                <select name="role" class="form-select glass-input text-white shadow-sm" onchange="this.form.submit()">
                    <option value="" class="bg-dark text-white">All Roles</option>
                    <option value="student" class="bg-dark text-white" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="company" class="bg-dark text-white" <?= $role_filter === 'company' ? 'selected' : '' ?>>Companies</option>
                    <option value="admin" class="bg-dark text-white" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
            </div>
            <!-- Reset Button -->
            <div class="col-md-2">
                <a href="view_users.php" class="btn btn-glass-white w-100" style="border-radius: 8px !important; padding: 0.45rem 0.75rem !important;">Reset</a>
            </div>
        </div>
    </form>

    <p class="text-white-50 small mb-2"><?= count($user_rows) ?> user(s) found.</p>

    <div class="card shadow-sm glass-card mb-5">
        <div class="card-body p-4">
            
            <div class="row glass-table-header d-none d-md-flex mb-2 px-3">
                <div class="col-md-1">#</div>
                <div class="col-md-2">Name</div>
                <div class="col-md-3">Email</div>
                <div class="col-md-2">Role</div>
                <div class="col-md-2">Registered</div>
                <div class="col-md-2 text-end">Actions</div>
            </div>

            <?php if (empty($user_rows)): ?>
                <div class="text-center text-white-50 py-4">No users found.</div>
            <?php endif; ?>

            <div class="d-flex flex-column">
                <?php foreach ($user_rows as $i => $u): ?>
                    <div class="row glass-row-item align-items-center py-3 px-3 mx-0">
                        
                        <div class="col-md-1 glass-row-text-muted small">
                            <span class="d-md-none fw-bold me-1">ID:</span><?= (int)$u['id'] ?>
                        </div>
                        
                        <div class="col-md-2 glass-row-text-primary text-truncate">
                            <a href="#" class="view-user-trigger text-decoration-none text-white fw-bold" data-user-id="<?= (int)$u['id'] ?>">
                                <?= htmlspecialchars($u['name']) ?>
                            </a>
                        </div>
                        
                        <div class="col-md-3 glass-row-text-secondary text-truncate">
                            <?= htmlspecialchars($u['email']) ?>
                        </div>
                        
                        <div class="col-md-2 my-1 my-md-0">
                            <?= role_badge($u['role']) ?>
                        </div>
                        
                        <div class="col-md-2 glass-row-text-secondary small">
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </div>
                        
                        <div class="col-md-2 text-md-end d-flex gap-1 justify-content-start justify-content-md-end mt-2 mt-md-0">
                            <a href="..." class="btn btn-sm btn-glass-white rounded-pill" style="padding: 0.25rem 0.75rem !important;">View</a>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="view_users.php?delete=<?= (int)$u['id'] ?>"
                                   class="btn btn-sm btn-glass-danger rounded-pill px-3"
                                   onclick="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>? This removes all their data.')">
                                    Delete
                                </a>
                            <?php else: ?>
                                <span class="glass-row-text-muted small align-self-center ms-2">(You)</span>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>