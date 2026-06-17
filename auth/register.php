<?php
session_start();
include '../config/db_connect.php';
include '../includes/header.php';
include '../includes/navbar.php';

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

            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed, $role);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Account created! You can now log in.';
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
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-4">Create an account</h4>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <form id="registerForm" method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label">Full name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm password</label>
              <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">I am a</label>
              <select name="role" class="form-select" required>
                <option value="">Select role</option>
                <option value="student">Student</option>
                <option value="company">Company</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
          </form>
          <p class="text-center mt-3 mb-0">
            Already have an account? <a href="login.php">Login here</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>