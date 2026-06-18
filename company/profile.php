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

<style>
    body {
        background-image: url('../assets/images/Page_bg.JPG.jpeg') !important;
        background-color: #ffffff !important;
        background-position: center center !important;
        background-repeat: no-repeat !important;
        background-size: contain !important; 
        background-attachment: fixed !important;
    }

    /* ── High-Contrast Dark Typography (All Texts to Black) ── */
    h3, h4, h5, .modal-title,
    .glass-row-text-primary, 
    p.text-white,
    label,
    .fw-bold { 
        color: #111111 !important; 
    }
    
    .glass-row-text-secondary, 
    .modal-body,
    p.text-white-50, 
    .text-white-50 { 
        color: #333333 !important; 
    }
    
    .glass-row-text-muted, .text-muted { 
        color: #555555 !important; 
    }<style>
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