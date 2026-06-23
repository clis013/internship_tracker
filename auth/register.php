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
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$error = '';
$success = '';
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password']) && isset($_POST['role'])) {
        $name     = test_input($_POST['name']);
        $email    = test_input($_POST['email']);
        $password = test_input($_POST['password']);
        $confirm  = test_input($_POST['confirm_password']);
        $role     = test_input($_POST['role']);

        // Server-side validation
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            header("Location: register.php?error=" . urlencode('All fields are required.'));
            exit();
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: register.php?error=" . urlencode('Invalid email format.'));
            exit();
        } elseif ($password !== $confirm) {
            header("Location: register.php?error=" . urlencode('Passwords do not match.'));
            exit();
        } elseif (strlen($password) < 6) {
            header("Location: register.php?error=" . urlencode('Password must be at least 6 characters.'));
            exit();
        } elseif (!in_array($role, ['student', 'company'])) {
            header("Location: register.php?error=" . urlencode('Invalid role selected.'));
            exit();
        } else {
            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email='$email'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                header("Location: register.php?error=" . urlencode('Email is already registered.'));
                exit();
            } else {
                $hashed = md5($password);
                $insert_query = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed', '$role')";

                if (mysqli_query($conn, $insert_query)) {
                    header("Location: register.php?success=" . urlencode('Account created! You can now log in.'));
                    exit();
                } else {
                    header("Location: register.php?error=" . urlencode('Something went wrong. Please try again.'));
                    exit();
                }
            }
        }
    } else {
        header("Location: register.php?error=" . urlencode('All fields are required.'));
        exit();
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
              <input type="text" name="name" class="form-control glass-input text-white" placeholder="Full name">
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