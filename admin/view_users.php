<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

$error = '';
$success = '';

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    if ($user_id === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        header("Location: view_users.php?deleted=1");
        exit();
    }
}

// Filter by role
$role_filter = $_GET['role'] ?? '';
$sql = "SELECT id, name, email, role, created_at FROM users";
$params = [];
$types = '';

if (in_array($role_filter, ['student', 'company', 'admin'])) {
    $sql .= " WHERE role = ?";
    $params[] = $role_filter;
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);
?>

<div class="container mt-4">
    <h3 class="mb-4">Manage Users</h3>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">User deleted successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <select name="role" class="form-select" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
                <option value="company" <?= $role_filter === 'company' ? 'selected' : '' ?>>Companies</option>
                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($u['role'])) ?></span></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></td>
                        <td>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="view_users.php?delete=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this user? This will remove all their related data.')">Delete</a>
                            <?php else: ?>
                                <span class="text-muted small">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>