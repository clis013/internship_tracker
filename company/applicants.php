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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = (int)$_POST['app_id'];
    $new_status = $_POST['status'];

    if (in_array($new_status, ['pending', 'reviewed', 'accepted', 'rejected'])) {
        // Ensure the application belongs to a job owned by this company
        $stmt = mysqli_prepare($conn, "UPDATE applications a
            JOIN jobs j ON a.job_id = j.id
            SET a.status = ?
            WHERE a.id = ? AND j.company_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $new_status, $app_id, $company_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Application status updated.';
        } else {
            $error = 'Failed to update status.';
        }
    } else {
        $error = 'Invalid status value.';
    }
}

// Optional filters
$job_id_filter   = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status_filter   = isset($_GET['status']) && in_array($_GET['status'], ['pending','reviewed','accepted','rejected'])
                   ? $_GET['status'] : '';

// Get this company's jobs for the filter dropdown
$stmt = mysqli_prepare($conn, "SELECT id, title FROM jobs WHERE company_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$job_list = mysqli_stmt_get_result($stmt);
$job_options = [];
while ($r = mysqli_fetch_assoc($job_list)) {
    $job_options[] = $r;
}

// Fetch applicants
$sql = "SELECT a.id, a.status, a.applied_at, a.cover_letter, a.resume AS app_resume,
        j.id AS job_id, j.title AS job_title,
        u.id AS student_id, u.name AS student_name, u.email AS student_email, u.phone, u.bio, u.resume AS profile_resume,
        u.academic_info, u.skills, u.profile_picture
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.student_id = u.id
    WHERE j.company_id = ?";
$params = [$company_id];
$types = 'i';

if ($job_id_filter > 0) {
    $sql .= " AND j.id = ?";
    $params[] = $job_id_filter;
    $types .= 'i';
}
if ($status_filter !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$sql .= " ORDER BY a.applied_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$applicants = mysqli_stmt_get_result($stmt);

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
    <h3 class="mb-4">Applicants</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-4">
            <select name="job_id" class="form-select glass-select text-white" onchange="this.form.submit()">
                <option value="0">All Internships</option>
                <?php foreach ($job_options as $j): ?>
                    <option value="<?= (int)$j['id'] ?>" <?= $job_id_filter === (int)$j['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select glass-select text-white" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending"   <?= $status_filter === 'pending'  ? 'selected' : '' ?>>Pending Review</option>
                <option value="reviewed"  <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                <option value="accepted"  <?= $status_filter === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                <option value="rejected"  <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <?php if ($job_id_filter || $status_filter): ?>
        <div class="col-md-2">
            <a href="applicants.php" class="btn btn-glass-secondary rounded-pill w-100">Reset</a>
        </div>
        <?php endif; ?>
    </form>

    <?php if (mysqli_num_rows($applicants) === 0): ?>
        <div class="alert bg-transparent border border-info text-info text-center py-4 my-2">No applicants found.</div>
    <?php else: ?>
        <div class="accordion" id="applicantAccordion">
            <?php $i = 0; while ($app = mysqli_fetch_assoc($applicants)): $i++;
                // Resume override takes precedence, fall back to profile resume
                $resume_path = $app['app_resume'] ?: $app['profile_resume'];
            ?>
                <div class="accordion-item glass-row-item border-0 mb-3 overflow-hidden" style="background: transparent;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse"
                                data-bs-target="#applicant<?= $i ?>"
                                style="background: transparent; color: #ffffff; box-shadow: none;">
                            <div class="d-flex justify-content-between w-100 me-3 align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-white">
                                    <span class="text-info text-decoration-underline" style="cursor: pointer;" onclick="event.stopPropagation(); const m<?= $i ?> = new bootstrap.Modal(document.getElementById('studentProfileModal<?= $i ?>')); m<?= $i ?>.show();" title="Click to view full profile">
                                        <?= htmlspecialchars($app['student_name']) ?>
                                    </span>
                                     — <span class="text-white-50 fw-semibold"><?= htmlspecialchars($app['job_title']) ?></span>
                                </span>
                                <span><?= status_badge($app['status']) ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="applicant<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#applicantAccordion">
                        <div class="accordion-body border-top border-light border-opacity-10" style="background: rgba(255, 255, 255, 0.02);">
                            <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($app['student_email']) ?></p>
                            <?php if ($app['phone']): ?>
                                <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($app['phone']) ?></p>
                            <?php endif; ?>
                            <?php if ($app['bio']): ?>
                                <p class="mb-2"><strong>About:</strong> <?= nl2br(htmlspecialchars($app['bio'])) ?></p>
                            <?php endif; ?>
                            <p class="mb-2"><strong>Applied on:</strong> <?= htmlspecialchars(date('d M Y, H:i', strtotime($app['applied_at']))) ?></p>
                            <?php if ($resume_path): ?>
                                <p class="mb-2">
                                    <strong>Resume:</strong>
                                    <a href="/internship_tracker/<?= htmlspecialchars($resume_path) ?>" download class="btn btn-sm btn-glass-primary rounded-pill ms-2 px-3 py-1">
                                        📥 Download Resume
                                    </a>
                                </p>
                            <?php else: ?>
                                <p class="mb-2 text-white-50"><strong>Resume:</strong> Not provided</p>
                            <?php endif; ?>
                            <hr class="border-light border-opacity-10 my-3">
                            <p class="mb-1"><strong>Cover Letter:</strong></p>
                            <p class="text-white-50 small lh-sm"><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                            <hr class="border-light border-opacity-10 my-3">
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                <label class="form-label mb-0 text-white fw-semibold">Status:</label>
                                <select name="status" class="form-select form-select-sm w-auto glass-select text-white">
                                    <?php foreach (['pending', 'reviewed', 'accepted', 'rejected'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-glass-primary rounded-pill px-3 py-1">Update</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Applicant Profile Modal -->
                <div class="modal fade" id="studentProfileModal<?= $i ?>" tabindex="-1" aria-labelledby="studentProfileModalLabel<?= $i ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content glass-card text-start" style="background: rgba(15, 15, 15, 0.9) !important; cursor: default;">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title text-white fw-bold" id="studentProfileModalLabel<?= $i ?>"><i class="bi bi-person-fill text-info me-2"></i>Applicant Profile</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body py-4">
                                <div class="row g-4">
                                    <!-- Avatar and Quick Info -->
                                    <div class="col-md-4 text-center border-end border-light border-opacity-10">
                                        <div class="profile-avatar-container mx-auto mb-3" style="width: 120px; height: 120px;">
                                            <?php if (!empty($app['profile_picture'])): ?>
                                                <img src="/internship_tracker/<?= htmlspecialchars($app['profile_picture']) ?>" class="profile-avatar w-100 h-100 rounded-circle object-fit-cover border border-2 border-secondary" alt="Avatar">
                                            <?php else: ?>
                                                <div class="profile-avatar-placeholder w-100 h-100 rounded-circle bg-dark bg-opacity-50 d-flex align-items-center justify-content-center border border-2 border-secondary">
                                                    <i class="bi bi-person-fill fs-1 text-white-50"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars($app['student_name']) ?></h5>
                                        <p class="text-white-50 small mb-3">Student Applicant</p>
                                        <hr class="border-light border-opacity-10">
                                        <div class="text-start">
                                            <p class="mb-2 small"><strong class="text-white">Email:</strong> <br><span class="text-white-50" style="word-break: break-all;"><?= htmlspecialchars($app['student_email']) ?></span></p>
                                            <p class="mb-2 small"><strong class="text-white">Phone:</strong> <br><span class="text-white-50"><?= htmlspecialchars($app['phone'] ?: 'Not provided') ?></span></p>
                                        </div>
                                    </div>
                                    <!-- Detailed Info -->
                                    <div class="col-md-8">
                                        <h6 class="fw-bold text-white mb-2"><i class="bi bi-info-circle-fill text-info me-2"></i>About / Bio</h6>
                                        <p class="text-white-50 small mb-4"><?= nl2br(htmlspecialchars($app['bio'] ?: 'No bio details provided.')) ?></p>
                                        
                                        <h6 class="fw-bold text-white mb-2"><i class="bi bi-mortarboard-fill text-warning me-2"></i>Academic Info</h6>
                                        <p class="text-white-50 small mb-4"><?= nl2br(htmlspecialchars($app['academic_info'] ?: 'No academic details provided.')) ?></p>
                                        
                                        <h6 class="fw-bold text-white mb-2"><i class="bi bi-gem text-success me-2"></i>Skills</h6>
                                        <p class="text-white-50 small mb-0"><?= htmlspecialchars($app['skills'] ?: 'No skills listed.') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-glass-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>