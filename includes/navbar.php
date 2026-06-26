<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-lg-5">
  <div class="container-fluid px-0 d-flex align-items-center">
    <!-- Centered Brand & Menu Links Container -->
    <div class="container d-flex justify-content-between align-items-center p-0">
      <a class="navbar-brand" href="/internship_tracker/">InternTrack</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navMenu" aria-controls="navMenu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="navMenu" aria-labelledby="navMenuLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="navMenuLabel">Menu</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-2">

          <?php if (!isset($_SESSION['role'])): ?>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'login.php') ? 'active' : '' ?>" href="/internship_tracker/auth/login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'register.php') ? 'active' : '' ?>" href="/internship_tracker/auth/register.php">Register</a></li>

          <?php elseif ($_SESSION['role'] === 'student'): ?>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="/internship_tracker/student/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'browse.php' || $current_page == 'apply.php') ? 'active' : '' ?>" href="/internship_tracker/student/browse.php">Browse Jobs</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'my_applications.php') ? 'active' : '' ?>" href="/internship_tracker/student/my_applications.php">My Applications</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'profile.php') ? 'active' : '' ?>" href="/internship_tracker/student/profile.php">Profile</a></li>

          <?php elseif ($_SESSION['role'] === 'company'): ?>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="/internship_tracker/company/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'manage_jobs.php') ? 'active' : '' ?>" href="/internship_tracker/company/manage_jobs.php">Manage Jobs</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'applicants.php') ? 'active' : '' ?>" href="/internship_tracker/company/applicants.php">Applicants</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'profile.php') ? 'active' : '' ?>" href="/internship_tracker/company/profile.php">Profile</a></li>

          <?php elseif ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="/internship_tracker/admin/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'view_users.php') ? 'active' : '' ?>" href="/internship_tracker/admin/view_users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'manage_internships.php') ? 'active' : '' ?>" href="/internship_tracker/admin/manage_internships.php">Jobs</a></li>
            <li class="nav-item"><a class="nav-link <?= ($current_page == 'view_applicants.php') ? 'active' : '' ?>" href="/internship_tracker/admin/view_applicants.php">Applications</a></li>

          <?php endif; ?>

        </ul>
        
        <!-- Mobile Logout (inside collapsible menu) -->
        <?php if (isset($_SESSION['role'])): ?>
          <ul class="navbar-nav d-lg-none mt-2">
            <li class="nav-item"><a class="nav-link text-danger" href="/internship_tracker/auth/logout.php">Logout</a></li>
          </ul>
        <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Desktop Logout (pushed to the far right of the screen) -->
    <?php if (isset($_SESSION['role'])): ?>
      <div class="d-none d-lg-block ms-lg-4">
        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link text-danger" href="/internship_tracker/auth/logout.php">Logout</a></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</nav>