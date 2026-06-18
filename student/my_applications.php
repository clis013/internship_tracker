<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';
$open_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_notes') {
        $app_id = (int)($_POST['app_id'] ?? 0);
        $notes  = trim($_POST['notes'] ?? '');
        
        $stmt_notes = mysqli_prepare($conn, "UPDATE applications SET notes = ? WHERE id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt_notes, "sii", $notes, $app_id, $student_id);
        if (mysqli_stmt_execute($stmt_notes)) {
            $success = "Notes saved successfully.";
            $open_id = $app_id;
        } else {
            $error = "Failed to save notes.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'withdraw') {
        $app_id = (int)($_POST['app_id'] ?? 0);
        
        // Verify and fetch application
        $stmt = mysqli_prepare($conn, "SELECT resume FROM applications WHERE id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $app_id, $student_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($res);
        
        if ($app) {
            if (!empty($app['resume'])) {
                $resume_path = dirname(__DIR__) . '/' . $app['resume'];
                if (file_exists($resume_path)) {
                    @unlink($resume_path);
                }
            }
            
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM applications WHERE id = ? AND student_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $app_id, $student_id);
            if (mysqli_stmt_execute($delete_stmt)) {
                $success = "Application withdrawn successfully.";
            } else {
                $error = "Failed to withdraw application. Please try again.";
            }
        } else {
            $error = "Application not found or unauthorized.";
        }
    }
}

// Fetch search, filter, and sort values
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort   = trim($_GET['sort'] ?? 'newest');

$sql = "SELECT a.id, a.status, a.applied_at, a.cover_letter, a.notes,
               j.id AS job_id, j.title, j.location, j.field, j.description AS job_description,
               u.name AS company_name, u.email AS company_email, u.phone AS company_phone
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.company_id = u.id
        WHERE a.student_id = ?";

$params = [$student_id];
$types  = 'i';

if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR u.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($status !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($sort === 'oldest') {
    $sql .= " ORDER BY a.applied_at ASC";
} elseif ($sort === 'company') {
    $sql .= " ORDER BY u.name ASC";
} else { // default to newest
    $sql .= " ORDER BY a.applied_at DESC";
}

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$apps = mysqli_stmt_get_result($stmt);

function status_badge($status) {
    $map = [
        'pending'  => 'secondary',
        'reviewed' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$class\">" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-4">My Applications</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search, Filter, and Sort Bar -->
    <form method="GET" class="card shadow-sm border-0 p-3 mb-4 bg-white">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search Company or Job Title..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="reviewed" <?= $status === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Applied</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest Applied</option>
                    <option value="company" <?= $sort === 'company' ? 'selected' : '' ?>>Company (A-Z)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Apply</button>
                <a href="my_applications.php" class="btn btn-light" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </form>

    <?php if (mysqli_num_rows($apps) === 0): ?>
        <div class="alert alert-info border-0 shadow-sm text-center py-5">
            <i class="bi bi-info-circle fs-2 mb-3 d-block text-secondary"></i>
            No applications found matching your criteria.
            <a href="browse.php" class="d-block mt-2 fw-semibold">Browse internships to get started</a>
        </div>
    <?php else: ?>
        <div class="accordion" id="appAccordion">
            <?php $i = 0; while ($app = mysqli_fetch_assoc($apps)): $i++; ?>
                <?php 
                    $isExpanded = ($app['id'] == $open_id || ($open_id == 0 && $i === 1 && empty($search) && empty($status)));
                ?>
                <div class="accordion-item border-0 mb-3 shadow-sm rounded overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $isExpanded ? '' : 'collapsed' ?> bg-white py-3" type="button" data-bs-toggle="collapse"
                                data-bs-target="#app<?= $i ?>" aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>">
                            <div class="d-flex justify-content-between w-100 me-3 align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($app['title']) ?> — <span class="text-secondary fw-semibold"><?= htmlspecialchars($app['company_name']) ?></span></span>
                                <span><?= status_badge($app['status']) ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="app<?= $i ?>" class="accordion-collapse collapse <?= $isExpanded ? 'show' : '' ?>" data-bs-parent="#appAccordion">
                        <div class="accordion-body bg-white border-top">
                            <!-- Stepper Progress Tracker -->
                            <h6 class="fw-bold text-dark mb-3">Application Progress</h6>
                            <div class="stepper-wrapper d-flex justify-content-between mb-4 mt-2">
                                <?php
                                $appStatus = $app['status'];
                                if ($appStatus === 'rejected') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Rejected', 'state' => 'rejected']
                                    ];
                                } elseif ($appStatus === 'accepted') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Technical Interview', 'state' => 'completed'],
                                        ['label' => 'HR Interview', 'state' => 'completed'],
                                        ['label' => 'Accepted', 'state' => 'accepted']
                                    ];
                                } elseif ($appStatus === 'reviewed') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Technical Interview', 'state' => 'active'],
                                        ['label' => 'HR Interview', 'state' => 'pending'],
                                        ['label' => 'Decision', 'state' => 'pending']
                                    ];
                                } else { // pending
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'active'],
                                        ['label' => 'Technical Interview', 'state' => 'pending'],
                                        ['label' => 'HR Interview', 'state' => 'pending'],
                                        ['label' => 'Decision', 'state' => 'pending']
                                    ];
                                }
                                
                                foreach ($steps as $step):
                                    $badgeClass = 'bg-secondary';
                                    if ($step['state'] === 'completed') $badgeClass = 'bg-success';
                                    elseif ($step['state'] === 'active') $badgeClass = 'bg-primary';
                                    elseif ($step['state'] === 'rejected') $badgeClass = 'bg-danger';
                                    elseif ($step['state'] === 'accepted') $badgeClass = 'bg-success';
                                ?>
                                    <div class="step-item text-center flex-fill">
                                        <div class="step-counter rounded-circle text-white <?= $badgeClass ?> mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.85rem;">
                                            <?php if ($step['state'] === 'completed' || $step['state'] === 'accepted'): ?>
                                                ✓
                                            <?php elseif ($step['state'] === 'rejected'): ?>
                                                ✗
                                            <?php else: ?>
                                                •
                                            <?php endif; ?>
                                        </div>
                                        <div class="step-name small text-muted"><?= htmlspecialchars($step['label']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="row g-3">
                                <!-- Details & Cover Letter (Left Column) -->
                                <div class="col-md-7">
                                    <p class="mb-1 small"><strong>Applied on:</strong> <?= htmlspecialchars(date('d M Y, H:i', strtotime($app['applied_at']))) ?></p>
                                    <?php if ($app['location']): ?>
                                        <p class="mb-1 small"><strong>Location:</strong> <?= htmlspecialchars($app['location']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($app['field']): ?>
                                        <p class="mb-1 small"><strong>Field:</strong> <?= htmlspecialchars($app['field']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <h6 class="fw-bold text-dark small mb-1">Key Contacts:</h6>
                                        <ul class="list-unstyled small text-muted mb-0">
                                            <li><i class="bi bi-person me-1"></i> HR Recruiter</li>
                                            <li><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($app['company_email']) ?></li>
                                            <?php if (!empty($app['company_phone'])): ?>
                                                <li><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($app['company_phone']) ?></li>
                                            <?php else: ?>
                                                <li><i class="bi bi-telephone me-1"></i> +603-7967 1234</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <div class="mt-3">
                                        <h6 class="fw-bold text-dark small mb-1">Job Description Snippet:</h6>
                                        <p class="text-muted small mb-2 text-truncate-2">
                                            <?= htmlspecialchars(mb_strimwidth($app['job_description'] ?? '', 0, 200, '...')) ?>
                                        </p>
                                        <a href="browse.php?id=<?= $app['job_id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.75rem;">
                                            <i class="bi bi-eye"></i> View Full Details
                                        </a>
                                    </div>

                                    <div class="mt-3">
                                        <h6 class="fw-bold text-dark small mb-1">Your Cover Letter:</h6>
                                        <p class="text-muted small mb-0 lh-sm"><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                                    </div>
                                </div>

                                <!-- Notes Section (Right Column) -->
                                <div class="col-md-5 border-start ps-3">
                                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-pencil-square text-primary me-1"></i> My Notes</h6>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_notes">
                                        <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                        
                                        <textarea name="notes" class="form-control small mb-2" rows="6" placeholder="Write interview preparation notes, follow-up dates, or thoughts here..."><?= htmlspecialchars($app['notes'] ?? '') ?></textarea>
                                        <button type="submit" class="btn btn-sm btn-primary w-100">Save Notes</button>
                                    </form>
                                </div>
                            </div>

                            <hr>
                            
                            <form method="POST" onsubmit="return confirm('Are you sure you want to withdraw this application? This action cannot be undone.')">
                                <input type="hidden" name="action" value="withdraw">
                                <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="bi bi-x-circle"></i> Withdraw Application
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>