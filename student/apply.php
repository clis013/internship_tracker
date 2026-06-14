<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/resume_upload.php';
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : (int)($_POST['job_id'] ?? 0);

// Fetch job details
$stmt = mysqli_prepare($conn, "SELECT j.*, u.name AS company_name FROM jobs j
    JOIN users u ON j.company_id = u.id
    WHERE j.id = ? AND j.status = 'active'");
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$job = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$job) {
    echo '<div class="container mt-4"><div class="alert alert-danger">Internship not found or no longer active.</div>
          <a href="browse.php" class="btn btn-primary">Back to Browse</a></div>';
    include '../includes/footer.php';
    exit();
}

// Check if already applied
$stmt = mysqli_prepare($conn, "SELECT id FROM applications WHERE student_id = ? AND job_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $student_id, $job_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$already_applied = mysqli_stmt_num_rows($stmt) > 0;

// Get student's default resume
$stmt = mysqli_prepare($conn, "SELECT resume FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$default_resume = $student['resume'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {
    $cover_letter = trim($_POST['cover_letter'] ?? '');

    if (empty($cover_letter)) {
        $error = 'Please write a short cover letter / message.';
    } else {
        // Optional resume override for this application
        $upload = handle_resume_upload('resume', dirname(__DIR__) . '/uploads/resumes');
        if ($upload['error']) {
            $error = $upload['error'];
        } elseif (!$upload['path'] && !$default_resume) {
            $error = 'Please upload a resume (you have no default resume on your profile).';
        } else {
            $resume_path = $upload['path']; // null = use default resume from users table

            $stmt = mysqli_prepare($conn, "INSERT INTO applications (student_id, job_id, cover_letter, resume) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $student_id, $job_id, $cover_letter, $resume_path);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Application submitted successfully!';
                $already_applied = true;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4><?= htmlspecialchars($job['title']) ?></h4>
                    <h6 class="text-muted mb-3"><?= htmlspecialchars($job['company_name']) ?></h6>

                    <?php if ($job['location']): ?>
                        <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
                    <?php endif; ?>
                    <?php if ($job['field']): ?>
                        <p class="mb-1"><strong>Field:</strong> <?= htmlspecialchars($job['field']) ?></p>
                    <?php endif; ?>

                    <hr>
                    <p><?= nl2br(htmlspecialchars($job['description'] ?? '')) ?></p>
                    <hr>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if ($already_applied): ?>
                        <div class="alert alert-info mb-0">You have already applied for this internship.</div>
                        <a href="my_application.php" class="btn btn-outline-primary mt-3">View My Applications</a>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="job_id" value="<?= (int)$job_id ?>">
                            <div class="mb-3">
                                <label class="form-label">Cover Letter / Message to Company</label>
                                <textarea name="cover_letter" class="form-control" rows="5" required
                                    placeholder="Briefly explain why you're a good fit for this internship..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Resume</label>
                                <?php if ($default_resume): ?>
                                    <div class="form-text mb-2">
                                        Your default resume will be used:
                                        <a href="/internship_tracker/<?= htmlspecialchars($default_resume) ?>" target="_blank">View Current Resume</a>.
                                        You may upload a different one below for this application only.
                                    </div>
                                <?php else: ?>
                                    <div class="form-text mb-2 text-warning">
                                        You don't have a default resume on your profile. Please upload one here, or
                                        <a href="profile.php">add one to your profile</a> for future applications.
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                            <a href="browse.php" class="btn btn-link">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>