<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/navbar.php';

$error = '';
$simulated_email_body = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists in database
        $stmt = mysqli_prepare($conn, "SELECT name FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Generate a secure reset token and save to session
            $token = bin2hex(random_bytes(16));
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_expires'] = time() + 3600; // 1 hour expiry

            $reset_link = "/internship_tracker/auth/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
            
            $success = 'A password recovery link has been generated.';
            $simulated_email_body = $reset_link;
        } else {
            $error = 'Email address not found in our records.';
        }
    }
}
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h4 class="fw-bold mb-3">Forgot Password</h4>
          <p class="text-muted small mb-4">Enter your registered email address below, and we will simulate sending a password reset link.</p>

          <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($error) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
              <?= htmlspecialchars($success) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <!-- Simulated Outgoing Email UI Box -->
            <div class="card bg-light border-warning mb-4 shadow-sm">
              <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold py-2 small d-flex align-items-center">
                <i class="bi bi-envelope-paper-fill me-2"></i> Simulated Email Delivery (Local Demo)
              </div>
              <div class="card-body py-3">
                <p class="card-text small mb-2"><strong>To:</strong> <?= htmlspecialchars($_POST['email']) ?></p>
                <p class="card-text small mb-3"><strong>Subject:</strong> Reset your Internship Tracker Password</p>
                <div class="border rounded bg-white p-3 font-monospace small mb-2">
                  Hello <?= htmlspecialchars($user['name']) ?>,<br><br>
                  We received a request to reset your password. You can reset it by clicking the link below:<br><br>
                  <a href="<?= htmlspecialchars($simulated_email_body) ?>" class="btn btn-sm btn-primary text-white text-decoration-none fw-bold px-3 py-1 mt-1">Reset Password</a><br><br>
                  If you didn't request a password reset, you can safely ignore this email.
                </div>
              </div>
            </div>
          <?php endif; ?>

          <form method="POST" id="forgotPasswordForm" novalidate>
            <div class="mb-3">
              <label class="form-label small fw-bold">Email Address</label>
              <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Get Reset Link</button>
          </form>
          
          <div class="text-center mt-3 mb-0">
            <a href="login.php" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const emailInput = form.querySelector('input[name="email"]');
            emailInput.classList.remove('is-invalid');
            
            const emailVal = emailInput.value.trim();
            if (!emailVal) {
                e.preventDefault();
                emailInput.classList.add('is-invalid');
                let feedback = emailInput.parentNode.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    emailInput.parentNode.appendChild(feedback);
                }
                feedback.textContent = 'Email field cannot be empty.';
            } else {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!re.test(emailVal)) {
                    e.preventDefault();
                    emailInput.classList.add('is-invalid');
                    let feedback = emailInput.parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        emailInput.parentNode.appendChild(feedback);
                    }
                    feedback.textContent = 'Please enter a valid email address.';
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
