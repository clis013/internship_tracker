<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$error   = '';
$success = '';

// ── Handle Approval Action ─────────────────────────────────────────────────
if (isset($_GET['approve'])) {
    $comp_id = (int)$_GET['approve'];
    $stmt = mysqli_prepare($conn, "UPDATE users SET approval_status = 'approved' WHERE id = ? AND role = 'company'");
    mysqli_stmt_bind_param($stmt, "i", $comp_id);
    mysqli_stmt_execute($stmt);
    header("Location: manage_companies.php?action_success=approved");
    exit();
}

// ── Handle Delete Action ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $comp_id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ? AND role = 'company'");
    mysqli_stmt_bind_param($stmt, "i", $comp_id);
    mysqli_stmt_execute($stmt);
    header("Location: manage_companies.php?action_success=deleted");
    exit();
}

// ── Filters & Search Engine ────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');

$sql = "SELECT id, name, email, approval_status, created_at FROM users WHERE role = 'company'";
$params = [];
$types  = '';

if (in_array($status_filter, ['approved', 'pending'])) {
    $sql     .= " AND approval_status = ?";
    $params[] = $status_filter;
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
$companies_res = mysqli_stmt_get_result($stmt);

$company_rows = [];
while ($row = mysqli_fetch_assoc($companies_res)) {
    $company_rows[] = $row;
}
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-1 text-white">Manage Companies</h3>
    <p class="text-white-50 mb-4">Verify company registrations, review approval statuses, and manage corporate accounts.</p>

    <?php if (isset($_GET['action_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success">
            Company account updated successfully.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <input type="text" name="search" class="form-control glass-input border-end-0 text-white" placeholder="Search by corporate name or email..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn input-group-text glass-input border-start-0 text-white m-0 px-3" style="cursor: pointer;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select glass-input text-white shadow-sm" onchange="this.form.submit()">
                    <option value="" class="bg-dark text-white">All Verification Statuses</option>
                    <option value="pending" class="bg-dark text-white" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="approved" class="bg-dark text-white" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="view_users.php" class="btn btn-glass-white w-100" style="border-radius: 8px !important; padding: 0.45rem 0.75rem !important;">Reset</a>
            </div>
        </div>
    </form>

    <p class="text-white-50 small mb-2"><?= count($company_rows) ?> company verified or pending record(s) found.</p>

    <div class="card shadow-sm glass-card mb-5">
        <div class="card-body p-4">
            
            <div class="row glass-table-header d-none d-md-flex mb-2 px-3">
                <div class="col-md-1">#</div>
                <div class="col-md-3">Company Name</div>
                <div class="col-md-3">Corporate Email</div>
                <div class="col-md-2">Status</div>
                <div class="col-md-1">Joined</div>
                <div class="col-md-2 text-end">Actions</div>
            </div>

            <?php if (empty($company_rows)): ?>
                <div class="text-center text-white-50 py-4">No corporate accounts found matching options.</div>
            <?php endif; ?>

            <div class="d-flex flex-column">
                <?php foreach ($company_rows as $c): ?>
                    <div class="row glass-row-item align-items-center py-3 px-3 mx-0">
                        
                        <div class="col-md-1 glass-row-text-muted small">
                            <span class="d-md-none fw-bold me-1">ID:</span><?= (int)$c['id'] ?>
                        </div>
                        
                        <div class="col-md-3 glass-row-text-primary text-truncate">
                            <a href="#" class="view-user-trigger text-decoration-none text-white fw-bold" data-user-id="<?= (int)$c['id'] ?>">
                                <?= htmlspecialchars($c['name']) ?>
                            </a>
                        </div>
                        
                        <div class="col-md-3 glass-row-text-secondary text-truncate">
                            <?= htmlspecialchars($c['email']) ?>
                        </div>
                        
                        <div class="col-md-2 my-1 my-md-0">
                            <span class="badge badge-uniform bg-<?= $c['approval_status'] === 'approved' ? 'success' : 'warning text-dark' ?>">
                                <?= htmlspecialchars(ucfirst($c['approval_status'])) ?>
                            </span>
                        </div>
                        
                        <div class="col-md-1 glass-row-text-secondary small">
                            <?= date('d M Y', strtotime($c['created_at'])) ?>
                        </div>
                        
                        <div class="col-md-2 text-md-end d-flex gap-1 justify-content-start justify-content-md-end mt-2 mt-md-0">
                            <?php if ($c['approval_status'] === 'pending'): ?>
                                <a href="manage_companies.php?approve=<?= (int)$c['id'] ?>" 
                                   class="btn btn-sm btn-glass-primary rounded-pill px-3">
                                    Approve
                                </a>
                            <?php endif; ?>
                            <a href="manage_companies.php?delete=<?= (int)$c['id'] ?>"
                               class="btn btn-sm btn-glass-danger rounded-pill px-3"
                               onclick="return confirm('Completely purge corporate listing registration for <?= htmlspecialchars(addslashes($c['name'])) ?>? This deletes all postings associated with them.')">
                                Delete
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>