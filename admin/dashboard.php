<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

// Overall stats
$users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(role = 'student') AS students,
    SUM(role = 'company') AS companies,
    SUM(role = 'admin') AS admins
    FROM users"));

$jobs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(status = 'active') AS active,
    SUM(status = 'closed') AS closed
    FROM jobs"));

$apps = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    COUNT(*) AS total,
    SUM(status = 'pending') AS pending,
    SUM(status = 'accepted') AS accepted,
    SUM(status = 'rejected') AS rejected
    FROM applications"));

// Recent users
$recent_users = mysqli_query($conn, "SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
?>

<div class="container mt-4">
    <h3 class="mb-4">Admin Dashboard</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$users['total'] ?></h5>
                    <p class="card-text text-muted mb-0">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$users['students'] ?></h5>
                    <p class="card-text text-muted mb-0">Students</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$users['companies'] ?></h5>
                    <p class="card-text text-muted mb-0">Companies</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$jobs['total'] ?></h5>
                    <p class="card-text text-muted mb-0">Total Internships</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$jobs['active'] ?></h5>
                    <p class="card-text text-muted mb-0">Active Internships</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$apps['total'] ?></h5>
                    <p class="card-text text-muted mb-0">Total Applications</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$apps['pending'] ?></h5>
                    <p class="card-text text-muted mb-0">Pending Applications</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?= (int)$apps['accepted'] ?></h5>
                    <p class="card-text text-muted mb-0">Accepted</p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Recently Registered Users</h5>
        <a href="view_users.php" class="btn btn-primary btn-sm">View All Users</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered On</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($recent_users)): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($u['role'])) ?></span></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>