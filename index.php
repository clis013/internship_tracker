<?php
session_start();
include 'config/db_connect.php';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5">
  <div class="container text-center">
    <h1 class="display-5 fw-bold mb-3">Find Your Internship</h1>
    <p class="lead mb-4">
      Connecting students with companies for meaningful internship experiences.
    </p>
    <?php if (!isset($_SESSION['role'])): ?>
      <a href="auth/register.php" class="btn btn-light btn-lg me-2">Get Started</a>
      <a href="auth/login.php" class="btn btn-outline-light btn-lg">Login</a>
    <?php elseif ($_SESSION['role'] === 'student'): ?>
      <a href="student/browse.php" class="btn btn-light btn-lg">Browse Internships</a>
    <?php elseif ($_SESSION['role'] === 'company'): ?>
      <a href="company/manage_jobs.php" class="btn btn-light btn-lg">Manage Your Listings</a>
    <?php elseif ($_SESSION['role'] === 'admin'): ?>
      <a href="admin/dashboard.php" class="btn btn-light btn-lg">Go to Dashboard</a>
    <?php endif; ?>
  </div>
</div>

<!-- Stats Bar -->
<?php
$total_jobs     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM jobs WHERE status='active'"))['c'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"))['c'];
$total_companies= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='company'"))['c'];
?>
<div class="bg-light border-bottom py-3">
  <div class="container">
    <div class="row text-center">
      <div class="col-4">
        <div class="fs-4 fw-bold text-primary"><?= $total_jobs ?></div>
        <div class="text-muted small">Active listings</div>
      </div>
      <div class="col-4">
        <div class="fs-4 fw-bold text-primary"><?= $total_students ?></div>
        <div class="text-muted small">Students</div>
      </div>
      <div class="col-4">
        <div class="fs-4 fw-bold text-primary"><?= $total_companies ?></div>
        <div class="text-muted small">Companies</div>
      </div>
    </div>
  </div>
</div>

<!-- Featured Listings -->
<div class="container py-5">
  <h4 class="mb-4">Latest Internships</h4>
  <div class="row g-3">
    <?php
    $jobs = mysqli_query($conn,
      "SELECT j.*, u.name AS company_name
       FROM jobs j
       JOIN users u ON j.company_id = u.id
       WHERE j.status = 'active'
       ORDER BY j.created_at DESC
       LIMIT 6"
    );

    if (mysqli_num_rows($jobs) === 0):
    ?>
      <div class="col-12">
        <p class="text-muted">No internship listings yet. Check back soon.</p>
      </div>
    <?php else: ?>
      <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
        <div class="col-md-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h6 class="card-title fw-bold"><?= htmlspecialchars($job['title']) ?></h6>
              <p class="text-muted small mb-1">
                <?= htmlspecialchars($job['company_name']) ?>
              </p>
              <p class="text-muted small mb-2">
                <?= htmlspecialchars($job['location']) ?> &middot; <?= htmlspecialchars($job['field']) ?>
              </p>
              <p class="card-text small">
                <?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...
              </p>
            </div>
            <div class="card-footer bg-transparent border-0">
              <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                <a href="student/apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">Apply Now</a>
              <?php else: ?>
                <a href="auth/register.php" class="btn btn-outline-primary btn-sm">Register to Apply</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<!-- How It Works -->
<div class="bg-light py-5">
  <div class="container">
    <h4 class="text-center mb-5">How it works</h4>
    <div class="row text-center g-4">
      <div class="col-md-4">
        <div class="fs-1 mb-3">📝</div>
        <h6 class="fw-bold">1. Register</h6>
        <p class="text-muted small">Create a student or company account in minutes.</p>
      </div>
      <div class="col-md-4">
        <div class="fs-1 mb-3">🔍</div>
        <h6 class="fw-bold">2. Browse or Post</h6>
        <p class="text-muted small">Students browse listings. Companies post opportunities.</p>
      </div>
      <div class="col-md-4">
        <div class="fs-1 mb-3">✅</div>
        <h6 class="fw-bold">3. Apply and Connect</h6>
        <p class="text-muted small">Students apply, companies review and respond.</p>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>