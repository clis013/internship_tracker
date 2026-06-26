<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/navbar.php';

$error = '';
$success = '';
$is_valid_token = false;

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// Check if token matches session and is not expired
if (
    !empty($email) && 
    !empty($token) && 
    isset($_SESSION['reset_email']) && 
    isset($_SESSION['reset_token']) && 
    isset($_SESSION['reset_expires']) &&
    $email === $_SESSION['reset_email'] && 
    $token === $_SESSION['reset_token']
) {
    if (time() > $_SESSION['reset_expires']) {
        $error = 'This reset link has expired. Please request a new one.';
    } else {
        $is_valid_token = true;
    }
} else {
    $error = 'Invalid reset link. Please request a new link from the forgot password page.';
}

if ($is_valid_token && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password)) {
        $error = 'Password field cannot be empty.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash and update the password
        $hashed = md5($new_password);
        $query = "UPDATE users SET password='$hashed' WHERE email='$email'";

        if (mysqli_query($conn, $query)) {
            $success = 'Your password has been successfully reset! You can now log in.';
            // Invalidate the token
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_expires']);
            $is_valid_token = false;
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>



<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="glass-card shadow-sm border-0">
        <div class="card-body p-4">
          <h4 class="fw-bold mb-3">Reset Password</h4>

          <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success mb-4" role="alert">
              <?= htmlspecialchars($success) ?>
            </div>
            <div class="text-center">
              <a href="login.php" class="btn btn-primary px-4">Log In Now</a>
            </div>
          <?php endif; ?>

          <?php if ($is_valid_token): ?>
            <form method="POST" id="resetPasswordForm" novalidate>
              <div class="mb-3">
                <label class="form-label small fw-bold text-white">New Password</label>
                <div class="input-group password-group">
                  <input type="password" name="password" id="resetPasswordInput" class="form-control glass-input border-end-0 text-white" required placeholder="At least 6 characters">
                  <button class="btn btn-glass-white border-start-0" type="button" 
                    style="border-radius: 0 8px 8px 0 !important; padding: 0.375rem 0.75rem !important;" 
                    onclick="togglePassword('resetPasswordInput')">Show</button>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold text-white">Confirm New Password</label>
                <div class="input-group password-group">
                  <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control glass-input border-end-0 text-white" required placeholder="Repeat new password">
                  <button class="btn btn-glass-white border-start-0" type="button" 
                    style="border-radius: 0 8px 8px 0 !important; padding: 0.375rem 0.75rem !important;" 
                    onclick="togglePassword('confirmPasswordInput')">Show</button>
                </div>
              </div>
              <button type="submit" class="btn btn-glass-white w-100 mt-2" style="border-radius: 8px !important; padding: 0.5rem !important;">Reset Password</button>
            </form>
          <?php endif; ?>

          <?php if (!$is_valid_token && !$success): ?>
            <div class="text-center mt-3">
              <a href="forgot_password.php" class="btn btn-outline-primary btn-sm px-3">Request New Link</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const passwordInput = form.querySelector('input[name="password"]');
            const confirmInput = form.querySelector('input[name="confirm_password"]');

            // Reset validation errors
            passwordInput.classList.remove('is-invalid');
            confirmInput.classList.remove('is-invalid');
            
            const removeFeedback = (el) => {
                const group = el.closest('.input-group') || el.parentNode;
                const feedback = group.querySelector('.invalid-feedback');
                if (feedback) feedback.remove();
            };
            
            const addFeedback = (el, msg) => {
                el.classList.add('is-invalid');
                const group = el.closest('.input-group') || el.parentNode;
                let feedback = group.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    group.appendChild(feedback);
                }
                feedback.textContent = msg;
            };

            removeFeedback(passwordInput);
            removeFeedback(confirmInput);

            if (!passwordInput.value) {
                addFeedback(passwordInput, 'Password field cannot be empty.');
                isValid = false;
            } else if (passwordInput.value.length < 6) {
                addFeedback(passwordInput, 'Password must be at least 6 characters.');
                isValid = false;
            }

            if (!confirmInput.value) {
                addFeedback(confirmInput, 'Please confirm your password.');
                isValid = false;
            } else if (passwordInput.value !== confirmInput.value) {
                addFeedback(confirmInput, 'Passwords do not match.');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
