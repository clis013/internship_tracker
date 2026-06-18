<nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4" href="/internship_tracker/">InternTrack</a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-3">

        <?php if (isset($_SESSION['role'])): ?>
          <?php if ($_SESSION['role'] === 'student'): ?>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/student/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/student/browse.php">Browse Jobs</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/student/my_applications.php">My Applications</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/student/profile.php">Profile</a></li>

          <?php elseif ($_SESSION['role'] === 'company'): ?>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/company/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/company/manage_jobs.php">Manage Jobs</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/company/applicants.php">Applicants</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/company/profile.php">Profile</a></li>

          <?php elseif ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/admin/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/admin/view_users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/admin/manage_companies.php">Companies</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/admin/manage_internships.php">Internships</a></li>
            <li class="nav-item"><a class="nav-link" href="/internship_tracker/admin/view_applicants.php">Applications</a></li>
          <?php endif; ?>
        <?php endif; ?>

      </ul>
      
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-3">
        <?php if (!isset($_SESSION['role'])): ?>
          <li class="nav-item"><a class="nav-link" href="/internship_tracker/auth/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="/internship_tracker/auth/register.php">Register</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link text-danger fw-semibold" href="/internship_tracker/auth/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>