<?php
session_start();  //start PHP session
include '../config/db_connect.php'; //connect database
include '../includes/header.php'; //page header
include '../includes/navbar.php'; //navigation bar
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
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email    = test_input($_POST['email']);
        $password = test_input($_POST['password']);

        if (empty($email) || empty($password)) {
            header("Location: login.php?error=" . urlencode('Please enter your email and password.'));
            exit();
        } else {
            $password = md5($password);
            $query = "SELECT id, name, password, role FROM users WHERE email='$email' AND password='$password'";
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                //send each role to their own dashboard
                if ($user['role'] === 'student') {
                    header("Location: /internship_tracker/student/dashboard.php");
                } elseif ($user['role'] === 'company') {
                    header("Location: /internship_tracker/company/dashboard.php");
                } elseif ($user['role'] === 'admin') {
                    header("Location: /internship_tracker/admin/dashboard.php");
                }
                exit();
            } else {
                header("Location: login.php?error=" . urlencode('Incorrect email or password.'));
                exit();
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
          <h4 class="mb-4 text-white fw-bolder fs-3" style="letter-spacing: 0.5px;text-shadow: 0 0 10px rgba(224, 240, 255, 0.3);">Login</h4>

          <?php if ($error): ?>
            <div class="alert bg-transparent border border-danger text-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form id="loginForm" method="POST" novalidate>  
            <div class="mb-3">
              <label class="form-label text-white">Email</label>
              <input type="email" name="email" class="form-control glass-input text-white" required>
            </div>
            <div class="mb-3">
              <label class="form-label text-white">Password</label>
              <div class="input-group">
                <input type="password" name="password" id="loginPassword" class="form-control glass-input border-end-0 text-white" required>
                <button class="btn btn-glass-white border-start-0" type="button" 
                  style="border-radius: 0 8px 8px 0 !important; padding: 0.375rem 0.75rem !important;" 
                  onclick="togglePassword('loginPassword')">Show</button>
              </div>
            </div>
            <button type="submit" class="btn btn-glass-white w-100" style="border-radius: 8px !important; padding: 0.5rem !important;">Login</button>
          </form>
          <div class="d-flex justify-content-between mt-3 mb-0 small text-white-50">
            <span>No account? <a href="register.php" class="text-info text-decoration-underline fw-bold" style="color: #81e6ff !important;">Register here</a></span>
            <a href="forgot_password.php" class="text-info text-decoration-underline fw-bold" style="color: #81e6ff !important;">Forgot password?</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>