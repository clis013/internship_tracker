<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$success = '';
$error = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET approval_status = 'approved' WHERE id = ? AND role = 'company'");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Company account approved successfully.';
        } else {
            $error = 'Failed to approve company account.';
        }
    } elseif ($action === 'suspend') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET approval_status = 'suspended' WHERE id = ? AND role = 'company'");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Company account suspended successfully.';
        } else {
            $error = 'Failed to suspend company account.';
        }
    }
    // Redirect to clear URL parameters
    header("Location: manage_companies.php?msg_success=" . urlencode($success) . "&msg_error=" . urlencode($error));
    exit();
}

// Retrieve redirect messages
if (isset($_GET['msg_success']) && $_GET['msg_success'] !== '') {
    $success = $_GET['msg_success'];
}
if (isset($_GET['msg_error']) && $_GET['msg_error'] !== '') {
    $error = $_GET['msg_error'];
}

// Search and filter inputs
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT id, name, email, phone, website, industry, approval_status, created_at,
               (SELECT COUNT(*) FROM jobs j WHERE j.company_id = users.id) AS job_count
        FROM users 
        WHERE role = 'company'";
$params = [];
$types = '';

if ($status_filter !== '') {
    $sql .= " AND approval_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($search !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR industry LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}
$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$companies = mysqli_stmt_get_result($stmt);

function status_badge($status) {
    if ($status === 'approved') {
        return '<span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i>Approved</span>';
    } elseif ($status === 'suspended') {
        return '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Suspended</span>';
    } else {
        return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
    }
}
?>

<div class="container mt-4 pb-5">
    <h3 class="mb-1 fw-bold text-dark">Manage Companies</h3>
    <p class="text-muted mb-4">Approve new company registrations or suspend existing accounts to restrict access.</p>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search & Filter Form -->
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search by company name, email, or industry..."
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
        </div>
        <div class="col-md-2">
            <a href="manage_companies.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <?php if (mysqli_num_rows($companies) === 0): ?>
        <div class="alert alert-info border-0 shadow-sm"><i class="bi bi-info-circle me-2"></i>No company accounts found.</div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Company Details</th>
                            <th>Industry</th>
                            <th>Contact info</th>
                            <th>Jobs Posted</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($company = mysqli_fetch_assoc($companies)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($company['name']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($company['email']) ?></div>
                                    <div class="text-muted small" style="font-size:0.75rem;">Registered: <?= date('d M Y', strtotime($company['created_at'])) ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($company['industry'] ?: '—') ?>
                                </td>
                                <td>
                                    <div class="small">Phone: <?= htmlspecialchars($company['phone'] ?: '—') ?></div>
                                    <?php if ($company['website']): ?>
                                        <div class="small"><a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" class="text-decoration-none"><i class="bi bi-link-45deg"></i> Website</a></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= (int)$company['job_count'] ?> jobs</span>
                                </td>
                                <td>
                                    <?= status_badge($company['approval_status']) ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($company['approval_status'] !== 'approved'): ?>
                                            <a href="manage_companies.php?action=approve&id=<?= (int)$company['id'] ?>" 
                                               class="btn btn-sm btn-success fw-bold" 
                                               onclick="return confirm('Approve access for <?= htmlspecialchars(addslashes($company['name'])) ?>?')">
                                                Approve
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($company['approval_status'] !== 'suspended'): ?>
                                            <a href="manage_companies.php?action=suspend&id=<?= (int)$company['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger fw-bold" 
                                               onclick="return confirm('Suspend access for <?= htmlspecialchars(addslashes($company['name'])) ?>? This blocks them from logging in.')">
                                                Suspend
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
