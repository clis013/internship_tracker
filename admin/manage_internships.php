<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$error   = '';
$success = '';

// ── Delete internship posting ──────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM jobs WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    mysqli_stmt_execute($stmt);
    header("Location: manage_internships.php?deleted=1");
    exit();
}

// ── Filters & search ───────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');

$sql = "SELECT j.id, j.title, j.field, j.location, j.status, j.created_at, u.name AS company_name, u.id AS company_id 
        FROM jobs j 
        JOIN users u ON j.company_id = u.id 
        WHERE 1=1";
$params = [];
$types  = '';

if (in_array($status_filter, ['active', 'closed'])) {
    $sql     .= " AND j.status = ?";
    $params[] = $status_filter;
    $types   .= 's';
}

if ($search !== '') {
    $sql     .= " AND (j.title LIKE ? OR u.name LIKE ? OR j.field LIKE ? OR j.location LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ssss';
}
$sql .= " ORDER BY j.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$internships = mysqli_stmt_get_result($stmt);

$job_rows = [];
while ($row = mysqli_fetch_assoc($internships)) {
    $job_rows[] = $row;
}
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-1 text-white">Manage Internships</h3>
    <p class="text-white-50 mb-4">Review active vacancies, filter listings, and moderate postings.</p>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success">
            Internship posting deleted successfully.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <input type="text" name="search" class="form-control glass-input border-end-0 text-white" placeholder="Search by title, company, field, or location..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn input-group-text glass-input border-start-0 text-white m-0 px-3" style="cursor: pointer;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select glass-input text-white shadow-sm" onchange="this.form.submit()">
                    <option value="" class="bg-dark text-white">All Statuses</option>
                    <option value="active" class="bg-dark text-white" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="closed" class="bg-dark text-white" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="manage_internships.php" class="btn btn-glass-secondary w-100 shadow-sm">Reset</a>
            </div>
        </div>
    </form>

    <p class="text-white-50 small mb-2"><?= count($job_rows) ?> internship(s) found.</p>

    <div class="card shadow-sm glass-card mb-5">
        <div class="card-body p-4">
            
            <div class="row glass-table-header d-none d-md-flex mb-2 px-3">
                <div class="col-md-1">#</div>
                <div class="col-md-3">Role / Title</div>
                <div class="col-md-2">Company</div>
                <div class="col-md-2">Field & Location</div>
                <div class="col-md-2">Status</div>
                <div class="col-md-2 text-end">Actions</div>
            </div>

            <?php if (empty($job_rows)): ?>
                <div class="text-center text-white-50 py-4">No internships found.</div>
            <?php endif; ?>

            <div class="d-flex flex-column">
                <?php foreach ($job_rows as $j): ?>
                    <div class="row glass-row-item align-items-center py-3 px-3 mx-0">
                        
                        <div class="col-md-1 glass-row-text-muted small">
                            <span class="d-md-none fw-bold me-1">ID:</span><?= (int)$j['id'] ?>
                        </div>
                        
                        <div class="col-md-3 glass-row-text-primary text-truncate">
                            <?= htmlspecialchars($j['title']) ?>
                        </div>
                        
                        <div class="col-md-2 glass-row-text-secondary text-truncate">
                            <a href="#" class="view-user-trigger text-decoration-none text-white-50 fw-semibold" data-user-id="<?= (int)$j['company_id'] ?>">
                                <?= htmlspecialchars($j['company_name']) ?>
                            </a>
                        </div>
                        
                        <div class="col-md-2 glass-row-text-secondary small text-truncate">
                            <div class="fw-semibold text-white-50"><?= htmlspecialchars($j['field']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">📍 <?= htmlspecialchars($j['location']) ?></div>
                        </div>
                        
                        <div class="col-md-2 my-1 my-md-0">
                            <span class="badge bg-<?= $j['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= htmlspecialchars(ucfirst($j['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="col-md-2 text-md-end d-flex gap-1 justify-content-start justify-content-md-end mt-2 mt-md-0">
                            <a href="view_applicants.php?job_id=<?= (int)$j['id'] ?>" class="btn btn-sm btn-glass-secondary rounded-pill px-3">
                                Applicants
                            </a>
                            <a href="manage_internships.php?delete=<?= (int)$j['id'] ?>"
                               class="btn btn-sm btn-glass-danger rounded-pill px-3"
                               onclick="return confirm('Delete this internship posting? This removes all associated student applications.')">
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