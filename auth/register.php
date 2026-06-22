<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    html, body {
        /* Notice the ?v=2 added right after the file extension */
        background: url('../assets/images/Login_bg.JPG.jpeg?v=2') no-repeat center center fixed !important;
        background-size: cover !important;
        background-color: #000000 !important;
    }
</style>

<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'] ?? '';

    // Server-side validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['student', 'company'])) {
        $error = 'Invalid role selected.';
    } else {
        // Check if email already exists
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = 'Email is already registered.';
        } else {
            // Hash the password — never store plain text
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $approval_status = ($role === 'company') ? 'pending' : 'approved';
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, approval_status) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed, $role, $approval_status);

            if (mysqli_stmt_execute($stmt)) {
                if ($role === 'company') {
                    $success = 'Account created! Company accounts require administrator approval before logging in.';
                } else {
                    $success = 'Account created! You can now log in.';
                }
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="glass-card shadow-sm p-4">
        <div class="card-body p-0">
          <h4 class="mb-4 text-white fw-bolder fs-3" style="letter-spacing: 0.5px; text-shadow: 0 0 10px rgba(224, 240, 255, 0.3);">Create an account</h4>

          <?php if ($error): ?>
            <div class="alert bg-transparent border border-danger text-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert bg-transparent border border-success text-success"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <form id="registerForm" method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label text-white">Full name</label>
              <input type="text" name="full_name" class="form-control glass-input text-white" placeholder="Full name">
            </div>
            <div class="mb-3">
              <label class="form-label text-white">Email</label>
              <input type="email" name="email" class="form-control glass-input text-white" placeholder="Email">
            </div>
            <div class="mb-3">
              <label class="form-label text-white">Password</label>
              <input type="password" name="password" class="form-control glass-input text-white" placeholder="Password">
            </div>
            <div class="mb-3">
              <label class="form-label text-white">Confirm password</label>
              <input type="password" name="confirm_password" class="form-control glass-input text-white" placeholder="Confirm password">
            </div>
            <div class="mb-3">
              <label class="form-label text-white">I am a</label>
              <select name="role" class="form-select glass-select text-white" required>
                <option value="" class="bg-dark">Select role</option>
                <option value="student" class="bg-dark">Student</option>
                <option value="company" class="bg-dark">Company</option>
              </select>
            </div>
            <button type="submit" class="btn btn-glass-white w-100" style="border-radius: 8px !important; padding: 0.5rem !important;">Register</button>
          </form>
          <p class="text-center mt-3 mb-0 text-white-50">
            Already have an account? <a href="login.php" class="text-decoration-none fw-bold" style="color: #81e6ff !important;">Login here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>