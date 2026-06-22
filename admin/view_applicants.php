<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

// ── Handle Global Application Record Deletion ──────────────────────────────
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (isset($_GET['delete_app'])) {
    $app_id = (int)$_GET['delete_app'];
    $stmt = mysqli_prepare($conn, "DELETE FROM applications WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $app_id);
    mysqli_stmt_execute($stmt);
    $redirect = $job_id ? "view_applicants.php?job_id={$job_id}&deleted=1" : "view_applicants.php?deleted=1";
    header("Location: $redirect");
    exit();
}

// ── Fetch job title if filtering by job ────────────────────────────────────
$job_title_label = '';
if ($job_id > 0) {
    $jstmt = mysqli_prepare($conn, "SELECT title FROM jobs WHERE id = ?");
    mysqli_stmt_bind_param($jstmt, "i", $job_id);
    mysqli_stmt_execute($jstmt);
    $jres = mysqli_stmt_get_result($jstmt);
    if ($jrow = mysqli_fetch_assoc($jres)) {
        $job_title_label = $jrow['title'];
    }
}

// ── Search Engine & Status Pipeline ────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');

$sql = "SELECT a.id, a.status, a.applied_at, a.student_id, j.company_id, 
               s.name AS student_name, 
               j.title AS job_title, 
               c.name AS company_name 
        FROM applications a
        JOIN users s ON a.student_id = s.id
        JOIN jobs j ON a.job_id = j.id
        JOIN users c ON j.company_id = c.id
        WHERE 1=1";
$params = [];
$types  = '';

// Filter by specific job if job_id is provided
if ($job_id > 0) {
    $sql     .= " AND a.job_id = ?";
    $params[] = $job_id;
    $types   .= 'i';
}

if (in_array($status_filter, ['pending', 'reviewed', 'accepted', 'rejected'])) {
    $sql     .= " AND a.status = ?";
    $params[] = $status_filter;
    $types   .= 's';
}

if ($search !== '') {
    $sql     .= " AND (s.name LIKE ? OR j.title LIKE ? OR c.name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}
$sql .= " ORDER BY a.applied_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$apps_res = mysqli_stmt_get_result($stmt);

$app_rows = [];
while ($row = mysqli_fetch_assoc($apps_res)) {
    $app_rows[] = $row;
}

function get_status_badge_class($status) {
    $map = [
        'pending'  => 'secondary',
        'reviewed' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-1 text-white">
        <?php if ($job_id > 0 && $job_title_label): ?>
            Applicants — <span style="opacity:0.75;"><?= htmlspecialchars($job_title_label) ?></span>
        <?php else: ?>
            Global Applications Tracker
        <?php endif; ?>
    </h3>
    <p class="text-white-50 mb-4">
        <?php if ($job_id > 0): ?>
            Showing all applicants for this internship posting. <a href="view_applicants.php" class="text-white-50">View all applications →</a>
        <?php else: ?>
            Audit all submissions across every internship posting.
        <?php endif; ?>
    </p>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success">
            Application submission tracking record deleted successfully.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <input type="text" name="search" class="form-control glass-input border-end-0 text-white" placeholder="Search by student, company, or job role..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn input-group-text glass-input border-start-0 text-white m-0 px-3" style="cursor: pointer;">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select glass-input text-white shadow-sm" onchange="this.form.submit()">
                    <option value="" class="bg-dark text-white">All Application Statuses</option>
                    <option value="pending" class="bg-dark text-white" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="reviewed" class="bg-dark text-white" <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="accepted" class="bg-dark text-white" <?= $status_filter === 'accepted' ? 'selected' : '' ?>>Accepted (Offer)</option>
                    <option value="rejected" class="bg-dark text-white" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <a href="view_applicants.php<?= $job_id > 0 ? '?job_id=' . $job_id : '' ?>" class="btn btn-glass-white w-100" style="border-radius: 8px !important; padding: 0.45rem 0.75rem !important;">Reset</a>
            </div>
        </div>
    </form>

    <p class="text-white-50 small mb-2"><?= count($app_rows) ?> active application pipeline tracking record(s) mapped.</p>

    <div class="card shadow-sm glass-card mb-5">
        <div class="card-body p-4">
            
            <div class="row glass-table-header d-none d-md-flex mb-2 px-3">
                <div class="col-md-1">#</div>
                <div class="col-md-3">Candidate / Student</div>
                <div class="col-md-3">Target Opportunity</div>
                <div class="col-md-2">Date Submitted</div>
                <div class="col-md-1">Status</div>
                <div class="col-md-2 text-end">Actions</div>
            </div>

            <?php if (empty($app_rows)): ?>
                <div class="text-center text-white-50 py-4">No submission pipelines found matching criteria parameters.</div>
            <?php endif; ?>

            <div class="d-flex flex-column">
                <?php foreach ($app_rows as $a): ?>
                    <div class="row glass-row-item align-items-center py-3 px-3 mx-0">
                        
                        <div class="col-md-1 glass-row-text-muted small">
                            <span class="d-md-none fw-bold me-1">ID:</span><?= (int)$a['id'] ?>
                        </div>
                        
                        <div class="col-md-3 glass-row-text-primary text-truncate">
                            <a href="#" class="view-user-trigger text-decoration-none text-white fw-bold" data-user-id="<?= (int)$a['student_id'] ?>">
                                <?= htmlspecialchars($a['student_name']) ?>
                            </a>
                        </div>
                        
                        <div class="col-md-3 glass-row-text-secondary text-truncate">
                            <div class="fw-semibold text-white"><?= htmlspecialchars($a['job_title']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">🏢 <a href="#" class="view-user-trigger text-decoration-none text-white-50" data-user-id="<?= (int)$a['company_id'] ?>"><?= htmlspecialchars($a['company_name']) ?></a></div>
                        </div>
                        
                        <div class="col-md-2 glass-row-text-secondary small">
                            <?= date('d M Y, H:i', strtotime($a['applied_at'])) ?>
                        </div>
                        
                        <div class="col-md-1 my-1 my-md-0">
                            <span class="badge badge-uniform bg-<?= get_status_badge_class($a['status']) ?>">
                                <?= htmlspecialchars(ucfirst($a['status'])) ?>
                            </span>
                        </div>
                        
                        <div class="col-md-2 text-md-end d-flex gap-1 justify-content-start justify-content-md-end mt-2 mt-md-0">
                            <a href="view_applicants.php?delete_app=<?= (int)$a['id'] ?><?= $job_id ? '&job_id='.$job_id : '' ?>"
                               class="btn btn-sm btn-glass-danger rounded-pill px-3"
                               onclick="return confirm('Delete this application record?')">
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