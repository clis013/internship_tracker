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

// Optional job filter
$job_id_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

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
        u.id AS student_id, u.name AS student_name, u.email AS student_email, u.phone, u.bio, u.resume AS profile_resume
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
            <select name="job_id" class="form-select" onchange="this.form.submit()">
                <option value="0">All Internships</option>
                <?php foreach ($job_options as $j): ?>
                    <option value="<?= (int)$j['id'] ?>" <?= $job_id_filter === (int)$j['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if (mysqli_num_rows($applicants) === 0): ?>
        <div class="alert alert-info">No applicants found.</div>
    <?php else: ?>
        <div class="accordion" id="applicantAccordion">
            <?php $i = 0; while ($app = mysqli_fetch_assoc($applicants)): $i++;
                // Resume override takes precedence, fall back to profile resume
                $resume_path = $app['app_resume'] ?: $app['profile_resume'];
            ?>
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#applicant<?= $i ?>">
                            <div class="d-flex justify-content-between w-100 me-3">
                                <span><?= htmlspecialchars($app['student_name']) ?> — <?= htmlspecialchars($app['job_title']) ?></span>
                                <span><?= status_badge($app['status']) ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="applicant<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#applicantAccordion">
                        <div class="accordion-body">
                            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($app['student_email']) ?></p>
                            <?php if ($app['phone']): ?>
                                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($app['phone']) ?></p>
                            <?php endif; ?>
                            <?php if ($app['bio']): ?>
                                <p class="mb-1"><strong>About:</strong> <?= nl2br(htmlspecialchars($app['bio'])) ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><strong>Applied on:</strong> <?= htmlspecialchars(date('d M Y, H:i', strtotime($app['applied_at']))) ?></p>
                            <?php if ($resume_path): ?>
                                <p class="mb-1">
                                    <strong>Resume:</strong>
                                    <a href="/internship_tracker/<?= htmlspecialchars($resume_path) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                        📄 View Resume
                                    </a>
                                </p>
                            <?php else: ?>
                                <p class="mb-1 text-muted"><strong>Resume:</strong> Not provided</p>
                            <?php endif; ?>
                            <hr>
                            <p class="mb-1"><strong>Cover Letter:</strong></p>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                            <hr>
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                <label class="form-label mb-0">Status:</label>
                                <select name="status" class="form-select form-select-sm w-auto">
                                    <?php foreach (['pending', 'reviewed', 'accepted', 'rejected'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $app['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>