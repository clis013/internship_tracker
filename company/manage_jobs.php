<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('company');
include '../includes/header.php';
include '../includes/navbar.php';

$company_id = $_SESSION['user_id'];
$error = '';
$success = '';
$edit_job = null;

// Handle delete
if (isset($_GET['delete'])) {
    $job_id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM jobs WHERE id = ? AND company_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $company_id);
    mysqli_stmt_execute($stmt);
    header("Location: manage_jobs.php?deleted=1");
    exit();
}

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $allowance   = trim($_POST['allowance'] ?? '');
    $field       = trim($_POST['field']);
    $status      = $_POST['status'];
    $job_id      = (int)($_POST['job_id'] ?? 0);

    if (empty($title) || empty($description) || empty($location) || empty($field) || empty($allowance)) {
        $error = 'All fields (Title, Description, Location, Allowance, and Field) are required.';
    } elseif (!in_array($status, ['active', 'closed'])) {
        $error = 'Invalid status.';
    } else {
        if ($job_id > 0) {
            // Update existing job (only if it belongs to this company)
            $stmt = mysqli_prepare($conn, "UPDATE jobs SET title=?, description=?, location=?, allowance=?, field=?, status=? WHERE id=? AND company_id=?");
            mysqli_stmt_bind_param($stmt, "ssssssii", $title, $description, $location, $allowance, $field, $status, $job_id, $company_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Internship updated successfully.';
            } else {
                $error = 'Failed to update internship.';
            }
        } else {
            // Insert new job
            $stmt = mysqli_prepare($conn, "INSERT INTO jobs (company_id, title, description, location, allowance, field, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issssss", $company_id, $title, $description, $location, $allowance, $field, $status);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Internship posted successfully.';
            } else {
                $error = 'Failed to post internship.';
            }
        }
    }
}

// Handle edit request (load job into form)
if (isset($_GET['edit'])) {
    $job_id = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM jobs WHERE id = ? AND company_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $company_id);
    mysqli_stmt_execute($stmt);
    $edit_job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Fetch all jobs for this company
$stmt = mysqli_prepare($conn, "SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
    FROM jobs j WHERE j.company_id = ? ORDER BY j.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$jobs = mysqli_stmt_get_result($stmt);
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-4">Manage Internships</h3>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success" role="alert">
            Internship deleted successfully.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show bg-transparent border-danger text-danger" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card glass-card shadow-sm mb-4" style="cursor: default;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3 text-white"><?= $edit_job ? 'Edit Internship' : 'Post a New Internship' ?></h5>
            <form method="POST">
                <input type="hidden" name="job_id" value="<?= $edit_job ? (int)$edit_job['id'] : 0 ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-white">Title</label>
                        <input type="text" name="title" class="form-control glass-input text-white" required
                               value="<?= htmlspecialchars($edit_job['title'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">Location</label>
                        <input type="text" name="location" class="form-control glass-input text-white" required
                               value="<?= htmlspecialchars($edit_job['location'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">Field</label>
                        <input type="text" name="field" class="form-control glass-input text-white" placeholder="e.g. IT, Marketing" required
                               value="<?= htmlspecialchars($edit_job['field'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">Allowance (USD/month)</label>
                        <input type="text" name="allowance" class="form-control glass-input text-white" placeholder="e.g. 500 or Unpaid" required
                               value="<?= htmlspecialchars($edit_job['allowance'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-white">Description</label>
                        <textarea name="description" class="form-control glass-input text-white" rows="4" required><?= htmlspecialchars($edit_job['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">Status</label>
                        <select name="status" class="form-select glass-select text-white">
                            <option value="active" <?= (($edit_job['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="closed" <?= (($edit_job['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-glass-primary rounded-pill px-4 py-2"><?= $edit_job ? 'Update Internship' : 'Post Internship' ?></button>
                    <?php if ($edit_job): ?>
                        <a href="manage_jobs.php" class="btn btn-glass-secondary rounded-pill px-4 py-2 ms-2 text-decoration-none">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <h5 class="mb-3 text-white">Your Internship Listings</h5>
    <?php if (mysqli_num_rows($jobs) === 0): ?>
        <div class="alert bg-transparent border border-info text-info text-center py-4 my-2">No internships posted yet.</div>
    <?php else: ?>
        <div class="card glass-card mb-4">
            <div class="card-body p-4">
                <div class="row glass-table-header d-none d-md-flex mb-2 px-3">
                    <div class="col-md-3">Title</div>
                    <div class="col-md-2">Field & Location</div>
                    <div class="col-md-2">Allowance</div>
                    <div class="col-md-1">Status</div>
                    <div class="col-md-2">Applicants</div>
                    <div class="col-md-2 text-end">Actions</div>
                </div>
                
                <div class="d-flex flex-column">
                    <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                        <div class="row glass-row-item align-items-center py-3 px-3 mx-0">
                            <div class="col-md-3 glass-row-text-primary text-truncate fw-semibold">
                                <?= htmlspecialchars($job['title']) ?>
                            </div>
                            <div class="col-md-2 glass-row-text-secondary small text-truncate">
                                <div class="fw-semibold text-white-50"><?= htmlspecialchars($job['field'] ?? '-') ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">📍 <?= htmlspecialchars($job['location'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-2 glass-row-text-secondary text-white-50">
                                <?= htmlspecialchars($job['allowance'] ? '$' . $job['allowance'] . '/mo' : '-') ?>
                            </div>
                            <div class="col-md-1 my-1 my-md-0">
                                <span class="badge bg-<?= $job['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars(ucfirst($job['status'])) ?>
                                </span>
                            </div>
                            <div class="col-md-2">
                                <a href="applicants.php?job_id=<?= (int)$job['id'] ?>" class="text-info fw-bold text-decoration-none"><?= (int)$job['applicant_count'] ?> applicant(s)</a>
                            </div>
                            <div class="col-md-2 text-md-end text-start mt-2 mt-md-0 d-flex gap-1 justify-content-start justify-content-md-end">
                                <a href="manage_jobs.php?edit=<?= (int)$job['id'] ?>" class="btn btn-sm btn-glass-secondary rounded-pill">Edit</a>
                                <a href="manage_jobs.php?delete=<?= (int)$job['id'] ?>" class="btn btn-sm btn-glass-danger rounded-pill"
                                   onclick="return confirm('Delete this internship? This will also remove all related applications.')">Delete</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>