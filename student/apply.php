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
            if ($upload['path']) {
                $resume_path = $upload['path'];
            } else {
                // Copy the default resume so that the application has its own independent copy
                $ext = pathinfo($default_resume, PATHINFO_EXTENSION);
                $unique_name = 'resume_copy_' . uniqid() . '_' . time() . '.' . $ext;
                $source_file = dirname(__DIR__) . '/' . $default_resume;
                $dest_dir = dirname(__DIR__) . '/uploads/resumes';
                $dest_file = $dest_dir . '/' . $unique_name;
                
                if (file_exists($source_file)) {
                    if (!is_dir($dest_dir)) {
                        mkdir($dest_dir, 0755, true);
                    }
                    if (copy($source_file, $dest_file)) {
                        $resume_path = 'uploads/resumes/' . $unique_name;
                    } else {
                        $resume_path = $default_resume; // fallback if copy fails
                    }
                } else {
                    $resume_path = null;
                }
            }

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

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if (!$default_resume): ?>
                <div class="glass-card text-white p-4 text-center">
                    <i class="bi bi-file-earmark-person-fill text-warning display-4 mb-3"></i>
                    <h4 class="text-white fw-bold mb-2">Resume Required</h4>
                    <p class="text-white-50 mb-4">You must upload your resume on your profile page before you can apply for this internship job.</p>
                    <a href="profile.php" class="btn btn-glass-primary px-4 py-2">Go to Profile to Upload Resume</a>
                    <a href="browse.php" class="btn btn-glass-secondary px-4 py-2 ms-2">Back to Browse</a>
                </div>
            <?php else: ?>
                <div class="glass-card text-white p-4">
                    <h4 class="text-white fw-bold mb-1"><?= htmlspecialchars($job['title']) ?></h4>
                    <h6 class="text-info mb-3"><?= htmlspecialchars($job['company_name']) ?></h6>

                    <?php if ($job['location']): ?>
                        <p class="mb-1 text-white-50"><strong class="text-white">Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
                    <?php endif; ?>
                    <?php if ($job['field']): ?>
                        <p class="mb-1 text-white-50"><strong class="text-white">Field:</strong> <?= htmlspecialchars($job['field']) ?></p>
                    <?php endif; ?>

                    <hr class="border-light border-opacity-10 my-3">
                    <p class="text-white-50 small lh-sm"><?= nl2br(htmlspecialchars($job['description'] ?? '')) ?></p>
                    <hr class="border-light border-opacity-10 my-3">

                    <?php if ($error): ?>
                         <div class="alert bg-transparent border border-danger text-danger my-3"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                         <div class="alert bg-transparent border border-success text-success my-3"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if ($already_applied): ?>
                        <div class="alert bg-transparent border border-info text-info mb-0">You have already applied for this internship.</div>
                        <a href="my_applications.php" class="btn btn-glass-secondary mt-3">View My Applications</a>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="job_id" value="<?= (int)$job_id ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-white">Cover Letter / Message to Company</label>
                                <textarea name="cover_letter" class="form-control glass-input text-white" rows="5" required
                                    placeholder="Briefly explain why you're a good fit for this internship..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-white">Resume (PDF only)</label>
                                <div class="form-text small text-white-50 mb-2">
                                    Your default resume will be used:
                                    <a href="/internship_tracker/<?= htmlspecialchars($default_resume) ?>" target="_blank" class="text-info text-decoration-underline">View Current Resume</a>.
                                    You may upload a different one below for this application only.
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-glass-primary" onclick="document.getElementById('appResumeInput').click();">
                                        <i class="bi bi-upload me-1"></i> Upload Different Resume
                                    </button>
                                    <span id="appResumeFileName" class="small text-white-50"></span>
                                </div>
                                <input type="file" name="resume" id="appResumeInput" style="display: none;" accept=".pdf" onchange="showAppSelectedResumeName(this)">
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-glass-primary px-4 py-2">Submit Application</button>
                                <a href="browse.php" class="btn btn-glass-secondary px-4 py-2 ms-2">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAppSelectedResumeName(input) {
    const fileNameSpan = document.getElementById('appResumeFileName');
    if (input && input.files && input.files[0]) {
        fileNameSpan.textContent = "Selected: " + input.files[0].name;
    } else {
        fileNameSpan.textContent = "";
    }
}
</script>

<?php include '../includes/footer.php'; ?>