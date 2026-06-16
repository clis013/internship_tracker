<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$success = '';
$error   = '';

// ── Delete internship ───────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM jobs WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $del_id);
    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_internships.php?deleted=1");
        exit();
    } else {
        $error = "Failed to delete internship.";
    }
}

// ── Filters ─────────────────────────────────────────────────────────────────
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');

$sql    = "SELECT j.id, j.title, j.field, j.location, j.status, j.created_at,
                  u.name AS company_name, u.email AS company_email,
                  u.website AS company_website,
                  (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS app_count
           FROM jobs j
           JOIN users u ON j.company_id = u.id
           WHERE 1=1";
$params = [];
$types  = '';

if (in_array($status_filter, ['active', 'closed'])) {
    $sql   .= " AND j.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if ($search !== '') {
    $sql   .= " AND (j.title LIKE ? OR u.name LIKE ? OR j.field LIKE ? OR j.location LIKE ?)";
    $like   = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}
$sql .= " ORDER BY j.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Pull all rows so we can use them for both table and modals
$jobs = [];
while ($row = mysqli_fetch_assoc($result)) $jobs[] = $row;
?>

<div class="container mt-4">
    <h3 class="mb-1">Manage Internships</h3>
    <p class="text-muted mb-4">View, inspect, and delete internship postings.</p>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Internship deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ── Search & filter ── -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by title, company, field, or location..."
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
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
            <a href="manage_internships.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>

    <p class="text-muted small mb-2"><?= count($jobs) ?> internship(s) found.</p>

    <!-- ── Table ── -->
    <?php if (empty($jobs)): ?>
        <div class="alert alert-info">No internships found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Field</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Applications</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $i => $j): ?>
                        <tr>
                            <td class="text-muted small"><?= (int)$j['id'] ?></td>
                            <td><?= htmlspecialchars($j['title']) ?></td>
                            <td><?= htmlspecialchars($j['company_name']) ?></td>
                            <td><?= htmlspecialchars($j['field'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($j['location'] ?: '—') ?></td>
                            <td>
                                <span class="badge bg-<?= $j['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($j['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill"><?= (int)$j['app_count'] ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($j['created_at'])) ?></td>
                            <td class="d-flex gap-1">
                                <!-- View detail modal -->
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#jobModal<?= $i ?>">
                                    View
                                </button>
                                <!-- Delete -->
                                <a href="manage_internships.php?delete=<?= (int)$j['id'] ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($j['title'])) ?>\'? This will also delete all its applications.')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Internship detail modals ── -->
<?php foreach ($jobs as $i => $j): ?>
<div class="modal fade" id="jobModal<?= $i ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($j['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Internship details -->
                <h6 class="fw-semibold mb-2">Internship Details</h6>
                <table class="table table-sm table-borderless mb-3">
                    <tr><th style="width:30%">Title</th><td><?= htmlspecialchars($j['title']) ?></td></tr>
                    <tr><th>Field</th><td><?= htmlspecialchars($j['field'] ?: '—') ?></td></tr>
                    <tr><th>Location</th><td><?= htmlspecialchars($j['location'] ?: '—') ?></td></tr>
                    <tr><th>Status</th>
                        <td>
                            <span class="badge bg-<?= $j['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($j['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr><th>Applications</th><td><?= (int)$j['app_count'] ?></td></tr>
                    <tr><th>Posted</th><td><?= date('d M Y, H:i', strtotime($j['created_at'])) ?></td></tr>
                </table>

                <!-- Full description -->
                <?php
                // Fetch full description for this job
                $desc_stmt = mysqli_prepare($conn, "SELECT description FROM jobs WHERE id = ?");
                mysqli_stmt_bind_param($desc_stmt, "i", $j['id']);
                mysqli_stmt_execute($desc_stmt);
                $desc_row = mysqli_fetch_assoc(mysqli_stmt_get_result($desc_stmt));
                if ($desc_row['description']):
                ?>
                <h6 class="fw-semibold mb-2">Description</h6>
                <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($desc_row['description'])) ?></p>
                <?php endif; ?>

                <hr>

                <!-- Company details -->
                <h6 class="fw-semibold mb-2">Company</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><th style="width:30%">Name</th><td><?= htmlspecialchars($j['company_name']) ?></td></tr>
                    <tr><th>Email</th><td><?= htmlspecialchars($j['company_email']) ?></td></tr>
                    <tr>
                        <th>Website</th>
                        <td>
                            <?php if ($j['company_website']): ?>
                                <a href="<?= htmlspecialchars($j['company_website']) ?>" target="_blank">
                                    <?= htmlspecialchars($j['company_website']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="manage_internships.php?delete=<?= (int)$j['id'] ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Delete this internship and all its applications?')">
                    Delete
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>