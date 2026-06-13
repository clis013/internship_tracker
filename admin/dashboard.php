<?php
session_start();
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container mt-4">
  <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
  <p>Admin dashboard — coming soon.</p>
</div>
<?php include '../includes/footer.php'; ?>