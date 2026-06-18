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
$stmt = mysqli_prepare($conn, "SELECT name, email, phone, description, industry, website FROM users WHERE id = ?");
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
            $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, phone = ?, description = ?, industry = ?, website = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $description, $industry, $website, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['name'] = $name;
                $success = 'Profile updated successfully.';
                $user['name']        = $name;
                $user['phone']       = $phone;
                $user['description'] = $description;
                $user['industry']    = $industry;
                $user['website']     = $website;
            } else {
                $error = 'Failed to update profile.';
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
    }
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Company Profile</h3>

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
                    <h5 class="card-title mb-3">Company Information</h5>
                    <form method="POST">
                        <input type="hidden" name="form" value="profile">
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
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
                            <label class="form-label">Industry Category</label>
                            <select name="industry" class="form-select" required>
                                <option value="">Select Industry</option>
                                <option value="Technology / IT" <?= ($user['industry'] ?? '') === 'Technology / IT' ? 'selected' : '' ?>>Technology / IT</option>
                                <option value="Finance / Banking" <?= ($user['industry'] ?? '') === 'Finance / Banking' ? 'selected' : '' ?>>Finance / Banking</option>
                                <option value="Healthcare / Medical" <?= ($user['industry'] ?? '') === 'Healthcare / Medical' ? 'selected' : '' ?>>Healthcare / Medical</option>
                                <option value="Education" <?= ($user['industry'] ?? '') === 'Education' ? 'selected' : '' ?>>Education</option>
                                <option value="Marketing / Advertising" <?= ($user['industry'] ?? '') === 'Marketing / Advertising' ? 'selected' : '' ?>>Marketing / Advertising</option>
                                <option value="Engineering" <?= ($user['industry'] ?? '') === 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                <option value="Other" <?= ($user['industry'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($user['website'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Tell students about your company..."><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
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