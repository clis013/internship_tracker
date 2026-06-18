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

<style>
    body {
        background-image: url('../assets/images/Page_bg.JPG.jpeg') !important;
        background-color: #ffffff !important;
        background-position: center center !important;
        background-repeat: no-repeat !important;
        background-size: contain !important; 
        background-attachment: fixed !important;
    }

    /* ── Text Color Tuning for Dark Glass Visibility ── */
    h3, h4, h5, .modal-t
    
    .glass-table-header { 
        color: rgba(0, 0, 0, 0.5) !important; 
    }

    /* ── 3D Glass Form Fields & Dropdowns ── */
    .glass-input, .glass-select {
        background: rgba(255, 255, 255, 0.6) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(0, 0, 0, 0.15) !important;
        color: #111111 !important;
        box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6), 0 2px 4px rgba(0, 0, 0, 0.04) !important;
    }
    
    .glass-input:focus, .glass-select:focus {
        background: rgba(255, 255, 255, 0.85) !important;
        border-color: #0d6efd !important; /* Accent blue glow on focus */
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15) !important;
        color: #111111 !important;
    }
    
    .glass-input::placeholder {
        color: #666666 !important;
    }
    
    .glass-select option {
        background-color: #ffffff;
        color: #111111;
    }

    /* ── Main Outer 3D Glass Card Container ── */
    .glass-card {
        background: rgba(255, 255, 255, 0.35) !important;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        /* Pure white top/left borders simulate a light source reflecting off glass edge */
        border-top: 1px solid rgba(255, 255, 255, 0.6) !important;
        border-left: 1px solid rgba(255, 255, 255, 0.6) !important;
        border-right: 1px solid rgba(0, 0, 0, 0.05) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
        border-radius: 20px;
        /* Layered multi-shadow gives that crisp volumetric 3D lift off the page */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02), 
                    0 20px 30px rgba(0, 0, 0, 0.06), 
                    inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
    }

    /* ── Inner Stacked 3D Glass Row Panels ── */
    .glass-row-item {
        background: rgba(255, 255, 255, 0.4) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-top: 1px solid rgba(255, 255, 255, 0.7) !important;
        border-left: 1px solid rgba(255, 255, 255, 0.7) !important;
        border-right: 1px solid rgba(0, 0, 0, 0.06) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
        border-radius: 14px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02), inset 0 1px 0 rgba(255, 255, 255, 0.5) !important;
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), 
                    background-color 0.2s ease, 
                    box-shadow 0.2s ease;
    }
    
    .glass-row-item:hover {
        background: rgba(255, 255, 255, 0.75) !important;
        border-top-color: rgba(255, 255, 255, 0.9) !important;
        border-left-color: rgba(255, 255, 255, 0.9) !important;
        /* Pronounced shadow on hover makes row visually rise closer to user */
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.6) !important;
        transform: scale(1.004) translateY(-2px);
    }

    /* ── Action Buttons Remastered ── */
    .btn-glass-primary {
        background: #111111 !important; /* Bold black primary button */
        color: #ffffff !important;
        font-weight: 600;
        border: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.2s ease;
    }
    .btn-glass-primary:hover {
        background: #2b2b2b !important;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2) !important;
        transform: translateY(-1px);
    }

    .btn-glass-secondary {
        color: #111111 !important;
        border: 1px solid rgba(0, 0, 0, 0.12) !important;
        background: rgba(255, 255, 255, 0.5) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
        transition: all 0.2s ease;
    }
    .btn-glass-secondary:hover {
        background: rgba(255, 255, 255, 0.9) !important;
        border-color: rgba(0, 0, 0, 0.25) !important;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05) !important;
        transform: translateY(-1px);
    }
    
    /* Revert close button color vectors back to standard visible crisp dark grey */
    .btn-close-white {
        filter: invert(0) !important;
    }
</style>

<div class="container mt-4">
    <h3 class="mb-4">Manage Internships</h3>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Internship deleted successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3"><?= $edit_job ? 'Edit Internship' : 'Post a New Internship' ?></h5>
            <form method="POST">
                <input type="hidden" name="job_id" value="<?= $edit_job ? (int)$edit_job['id'] : 0 ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= htmlspecialchars($edit_job['title'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" required
                               value="<?= htmlspecialchars($edit_job['location'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Field</label>
                        <input type="text" name="field" class="form-control" placeholder="e.g. IT, Marketing" required
                               value="<?= htmlspecialchars($edit_job['field'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Allowance (USD/month)</label>
                        <input type="text" name="allowance" class="form-control" placeholder="e.g. 500 or Unpaid" required
                               value="<?= htmlspecialchars($edit_job['allowance'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($edit_job['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= (($edit_job['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="closed" <?= (($edit_job['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><?= $edit_job ? 'Update Internship' : 'Post Internship' ?></button>
                    <?php if ($edit_job): ?>
                        <a href="manage_jobs.php" class="btn btn-link">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <h5 class="mb-3">Your Internship Listings</h5>
    <?php if (mysqli_num_rows($jobs) === 0): ?>
        <div class="alert alert-info">No internships posted yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Allowance</th>
                        <th>Field</th>
                        <th>Status</th>
                        <th>Applicants</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['title']) ?></td>
                            <td><?= htmlspecialchars($job['location'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($job['allowance'] ? '$' . $job['allowance'] : '-') ?></td>
                            <td><?= htmlspecialchars($job['field'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $job['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= htmlspecialchars(ucfirst($job['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="applicants.php?job_id=<?= (int)$job['id'] ?>"><?= (int)$job['applicant_count'] ?></a>
                            </td>
                            <td>
                                <a href="manage_jobs.php?edit=<?= (int)$job['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <a href="manage_jobs.php?delete=<?= (int)$job['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this internship? This will also remove all related applications.')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>