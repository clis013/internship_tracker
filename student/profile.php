<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/resume_upload.php';
include '../includes/header.php';
include '../includes/navbar.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, bio, resume FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'profile') {
        $name  = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $bio   = trim($_POST['bio']);

        if (empty($name)) {
            $error = 'Name cannot be empty.';
        } else {
            // Handle resume upload (optional)
            $upload = handle_resume_upload('resume', dirname(__DIR__) . '/uploads/resumes');
            if ($upload['error']) {
                $error = $upload['error'];
            } else {
                if ($upload['path']) {
                    // Delete old resume file if it exists
                    if (!empty($user['resume'])) {
                        $old_path = dirname(__DIR__) . '/' . $user['resume'];
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, bio = ?, resume = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "ssssi", $name, $phone, $bio, $upload['path'], $user_id);
                    $user['resume'] = $upload['path'];
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, bio = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt, "sssi", $name, $phone, $bio, $user_id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['name'] = $name;
                    $success = 'Profile updated successfully.';
                    $user['name']  = $name;
                    $user['phone'] = $phone;
                    $user['bio']   = $bio;
                } else {
                    $error = 'Failed to update profile.';
                }
            }
        }
    } elseif ($form === 'password') {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!password_verify($current, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);
            mysqli_stmt_execute($stmt);
            $success = 'Password changed successfully.';
        }
    } elseif ($form === 'delete_resume') {
        if (!empty($user['resume'])) {
            $old_path = dirname(__DIR__) . '/' . $user['resume'];
            if (file_exists($old_path)) {
                @unlink($old_path);
            }
            $stmt = mysqli_prepare($conn, "UPDATE users SET resume = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $user['resume'] = null;
            $success = 'Resume removed.';
        }
    }
}
?>

<div class="container mt-4">
    <h3 class="mb-4">My Profile</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Profile Information</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form" value="profile">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <div class="form-text">Email cannot be changed.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">About Me / Skills</label>
                            <textarea name="bio" class="form-control" rows="4" placeholder="e.g. Skills, interests, achievements..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resume (PDF, DOC, or DOCX)</label>
                            <?php if (!empty($user['resume'])): ?>
                                <div class="mb-2">
                                    <a href="/internship_tracker/<?= htmlspecialchars($user['resume']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        📄 View Current Resume
                                    </a>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                            <div class="form-text">This resume will be used as your default for applications. Uploading a new file replaces the old one.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                    <?php if (!empty($user['resume'])): ?>
                        <form method="POST" class="mt-2" onsubmit="return confirm('Remove your resume?')">
                            <input type="hidden" name="form" value="delete_resume">
                            <button type="submit" class="btn btn-sm btn-link text-danger ps-0">Remove Resume</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Change Password</h5>
                    <form method="POST">
                        <input type="hidden" name="form" value="password">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>