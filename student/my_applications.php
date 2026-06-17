<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

<<<<<<< Updated upstream
$stmt = mysqli_prepare($conn, "SELECT a.id, a.status, a.applied_at, a.cover_letter,
        j.title, j.location, j.field, u.name AS company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.company_id = u.id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC");
mysqli_stmt_bind_param($stmt, "i", $student_id);
=======
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

$sql = "SELECT a.id, a.status, a.applied_at, a.cover_letter, a.notes, a.resume,
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
>>>>>>> Stashed changes
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

<div class="container mt-4">
    <h3 class="mb-4">My Applications</h3>

    <?php if (mysqli_num_rows($apps) === 0): ?>
        <div class="alert alert-info">
            You haven't applied to any internships yet.
            <a href="browse.php">Browse internships</a> to get started.
        </div>
    <?php else: ?>
        <div class="accordion" id="appAccordion">
            <?php $i = 0; while ($app = mysqli_fetch_assoc($apps)): $i++; ?>
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#app<?= $i ?>">
                            <div class="d-flex justify-content-between w-100 me-3">
                                <span><?= htmlspecialchars($app['title']) ?> — <?= htmlspecialchars($app['company_name']) ?></span>
                                <span><?= status_badge($app['status']) ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="app<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#appAccordion">
                        <div class="accordion-body">
                            <p class="mb-1"><strong>Applied on:</strong> <?= htmlspecialchars(date('d M Y, H:i', strtotime($app['applied_at']))) ?></p>
                            <?php if ($app['location']): ?>
                                <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($app['location']) ?></p>
                            <?php endif; ?>
                            <?php if ($app['field']): ?>
                                <p class="mb-1"><strong>Field:</strong> <?= htmlspecialchars($app['field']) ?></p>
                            <?php endif; ?>
                            <hr>
<<<<<<< Updated upstream
                            <p class="mb-1"><strong>Your Cover Letter:</strong></p>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
=======

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

                                    <div class="mt-3">
                                        <h6 class="fw-bold text-dark small mb-1">Submitted Resume:</h6>
                                        <?php if (!empty($app['resume'])): ?>
                                            <a href="/internship_tracker/<?= htmlspecialchars($app['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size: 0.75rem;">
                                                <i class="bi bi-file-earmark-pdf"></i> View Submitted Resume
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">No resume provided</span>
                                        <?php endif; ?>
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
>>>>>>> Stashed changes
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>