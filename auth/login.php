<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/navbar.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, password, role, approval_status FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'company' && $user['approval_status'] !== 'approved') {
                if ($user['approval_status'] === 'pending') {
                    $error = 'Your company account is pending administrator approval.';
                } else {
                    $error = 'Your company account has been suspended.';
                }
            } else {
                // Password correct — create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                // Send each role to their own dashboard
                if ($user['role'] === 'student') {
                    header("Location: /internship_tracker/student/dashboard.php");
                } elseif ($user['role'] === 'company') {
                    header("Location: /internship_tracker/company/dashboard.php");
                } elseif ($user['role'] === 'admin') {
                    header("Location: /internship_tracker/admin/dashboard.php");
                }
                exit();
            }
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-4">Login</h4>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form id="loginForm" method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group">
                <input type="password" name="password" id="loginPassword" class="form-control" required>
                <button class="btn btn-outline-secondary" type="button"
                  onclick="togglePassword('loginPassword')">Show</button>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
          <div class="d-flex justify-content-between mt-3 mb-0 small">
            <span>No account? <a href="register.php" class="text-decoration-none">Register here</a></span>
            <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>