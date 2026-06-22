<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('company');
include '../includes/header.php';
include '../includes/navbar.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current company data
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, description, industry, website, profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'profile') {
        $name        = trim($_POST['name']);
        $phone       = trim($_POST['phone']);
        $description = trim($_POST['description']);
        $industry    = trim($_POST['industry'] ?? '');
        $website     = trim($_POST['website']);

        if (empty($name)) {
            $error = 'Company name cannot be empty.';
        } else {
            // Handle profile picture upload (optional)
            $profile_picture_path = $user['profile_picture'];
            if (!empty($_FILES['profile_picture']['name'])) {
                $file = $_FILES['profile_picture'];
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validate MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (in_array($ext, $allowed_ext) && in_array($mime, $allowed_mime)) {
                    if ($file['size'] <= 2 * 1024 * 1024) { // Max 2MB
                        $upload_dir = dirname(__DIR__) . '/uploads/profile_pics';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $unique_name = 'avatar_' . uniqid() . '_' . time() . '.' . $ext;
                        $destination = $upload_dir . '/' . $unique_name;
                        
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            if (!empty($user['profile_picture'])) {
                                $old_pic = dirname(__DIR__) . '/' . $user['profile_picture'];
                                if (file_exists($old_pic)) {
                                    @unlink($old_pic);
                                }
                            }
                            $profile_picture_path = 'uploads/profile_pics/' . $unique_name;
                        } else {
                            $error = 'Failed to save uploaded profile picture.';
                        }
                    } else {
                        $error = 'Profile picture must be under 2MB.';
                    }
                } else {
                    $error = 'Only JPG, JPEG, PNG, and GIF images are allowed for profile picture.';
                }
            }

            if (empty($error)) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, description = ?, industry = ?, website = ?, profile_picture = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssssssi", $name, $phone, $description, $industry, $website, $profile_picture_path, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['name'] = $name;
                    $success = 'Profile updated successfully.';
                    $user['name']        = $name;
                    $user['phone']       = $phone;
                    $user['description'] = $description;
                    $user['industry']    = $industry;
                    $user['website']     = $website;
                    $user['profile_picture'] = $profile_picture_path;
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
    } elseif ($form === 'delete_profile_picture') {
        if (!empty($user['profile_picture'])) {
            $old_pic = dirname(__DIR__) . '/' . $user['profile_picture'];
            if (file_exists($old_pic)) {
                @unlink($old_pic);
            }
            $stmt = mysqli_prepare($conn, "UPDATE users SET profile_picture = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $user['profile_picture'] = null;
            $success = 'Logo removed.';
        }
    } elseif ($form === 'delete_account') {
        // Fetch and delete profile picture from local storage
        $stmt = mysqli_prepare($conn, "SELECT profile_picture FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['profile_picture'])) {
                $file_path = dirname(__DIR__) . '/' . $row['profile_picture'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        }
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        session_destroy();
        header("Location: /internship_tracker/auth/register.php");
        exit();
    }
}
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Left Column: Avatar & Action triggers -->
        <div class="col-md-4">
            <div class="glass-card shadow-sm text-center p-4" style="cursor: default;">
                <div class="card-body">
                    <div class="profile-avatar-container position-relative mx-auto mb-3" style="width: 150px; height: 150px; cursor: pointer;" onclick="document.getElementById('profilePicInput').click();">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="/internship_tracker/<?= htmlspecialchars($user['profile_picture']) ?>" class="profile-avatar w-100 h-100 rounded-circle object-fit-cover" alt="Company Logo">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder w-100 h-100 rounded-circle bg-dark bg-opacity-50 d-flex align-items-center justify-content-center border border-2 border-secondary">
                                <i class="bi bi-camera-fill fs-2 text-white-50"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="avatar-hover-overlay position-absolute top-0 start-0 w-100 h-100 rounded-circle d-flex align-items-center justify-content-center bg-dark bg-opacity-75 text-white opacity-0 transition-opacity">
                            <span class="small"><i class="bi bi-camera-fill me-1"></i> Upload Logo</span>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mb-1 text-white"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-white-50 small mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <?php if (!empty($user['profile_picture'])): ?>
                        <form method="POST" class="mb-3" onsubmit="return confirm('Remove your logo?')">
                            <input type="hidden" name="form" value="delete_profile_picture">
                            <button type="submit" class="btn btn-sm btn-glass-danger">Remove Logo</button>
                        </form>
                    <?php endif; ?>

                    <hr class="my-4 border-secondary border-opacity-50">

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-glass-white" data-bs-toggle="modal" data-bs-target="#changePasswordModal" style="border-radius: 8px !important; padding: 0.375rem 0.75rem !important;">
                        <i class="bi bi-key-fill me-1"></i> Change Password
                        </button>
                        <button type="button" class="btn btn-glass-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="bi bi-trash3-fill me-1"></i> Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Profile Form -->
        <div class="col-md-8">
            <div class="glass-card shadow-sm p-4" style="cursor: default;">
                <div class="card-body">
                    <h4 class="fw-bold mb-4 text-white">Company Information</h4>
                    
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

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form" value="profile">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white">Company Name</label>
                                <input type="text" name="name" class="form-control glass-input text-white" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white">Phone Number</label>
                                <input type="text" name="phone" class="form-control glass-input text-white" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-white">Email Address</label>
                            <input type="email" class="form-control glass-input text-white-50" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <div class="form-text small text-white-50">Your email address cannot be changed.</div>
                        </div>

                        <!-- Hidden Logo Uploader: Triggered via Left Column Circular Avatar -->
                        <input type="file" name="profile_picture" id="profilePicInput" accept="image/*" style="display: none;">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white">Industry Category</label>
                                <select name="industry" class="form-select glass-select text-white" required>
                                    <option value="">Select Industry</option>
                                    <option value="Technology / IT" <?= ($user['industry'] ?? '') === 'Technology / IT' || ($user['industry'] ?? '') === 'Technology' ? 'selected' : '' ?>>Technology / IT</option>
                                    <option value="Finance / Banking" <?= ($user['industry'] ?? '') === 'Finance / Banking' || ($user['industry'] ?? '') === 'Finance' ? 'selected' : '' ?>>Finance / Banking</option>
                                    <option value="Healthcare / Medical" <?= ($user['industry'] ?? '') === 'Healthcare / Medical' || ($user['industry'] ?? '') === 'Healthcare' ? 'selected' : '' ?>>Healthcare / Medical</option>
                                    <option value="Education" <?= ($user['industry'] ?? '') === 'Education' ? 'selected' : '' ?>>Education</option>
                                    <option value="Marketing / Advertising" <?= ($user['industry'] ?? '') === 'Marketing / Advertising' || ($user['industry'] ?? '') === 'Marketing' ? 'selected' : '' ?>>Marketing / Advertising</option>
                                    <option value="Engineering" <?= ($user['industry'] ?? '') === 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                    <option value="Other" <?= ($user['industry'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-white">Website</label>
                                <input type="text" name="website" class="form-control glass-input text-white" placeholder="https://..." value="<?= htmlspecialchars($user['website'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-white">Company Description</label>
                            <textarea name="description" class="form-control glass-input text-white" rows="4" placeholder="Tell students about your company..."><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end mt-4 mb-2">
                            <button type="submit" class="btn btn-glass-white" style="border-radius: 8px !important; padding: 0.375rem 1.5rem !important;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-start">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-white fw-bold" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="form" value="password">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-white">Current Password</label>
                        <input type="password" name="current_password" class="form-control glass-input text-white" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-white">New Password</label>
                        <input type="password" name="new_password" class="form-control glass-input text-white" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-white">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control glass-input text-white" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-glass-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-glass-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-start">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger fw-bold" id="deleteAccountModalLabel">Delete Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account? This action cannot be undone.')">
                <input type="hidden" name="form" value="delete_account">
                <div class="modal-body py-4">
                    <p class="text-white-50 small mb-3">
                        Warning: This action is permanent and cannot be undone. Deleting your account will remove your profile details, uploaded logo, and all job listings and student applications.
                    </p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmDeleteModalCheck" required>
                        <label class="form-check-label text-white-50 small" for="confirmDeleteModalCheck">
                            I understand that deleting my account is irreversible and all my data will be permanently removed.
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-glass-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete My Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('profilePicInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let container = document.querySelector('.profile-avatar-container');
            let img = container.querySelector('img');
            let placeholder = container.querySelector('.profile-avatar-placeholder');
            
            if (!img) {
                img = document.createElement('img');
                img.className = 'profile-avatar w-100 h-100 rounded-circle object-fit-cover';
                img.alt = 'Company Logo';
                if (placeholder) {
                    placeholder.remove();
                }
                container.insertBefore(img, container.firstChild);
            }
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../includes/footer.php'; ?>