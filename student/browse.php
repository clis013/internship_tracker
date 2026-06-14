<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

// Search / filter
$search = trim($_GET['search'] ?? '');
$field  = trim($_GET['field'] ?? '');

$sql = "SELECT j.id, j.title, j.description, j.location, j.field, j.created_at, u.name AS company_name
        FROM jobs j
        JOIN users u ON j.company_id = u.id
        WHERE j.status = 'active'";
$params = [];
$types  = '';

if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($field !== '') {
    $sql .= " AND j.field = ?";
    $params[] = $field;
    $types .= 's';
}
$sql .= " ORDER BY j.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$jobs = mysqli_stmt_get_result($stmt);

// distinct fields for filter dropdown
$field_list = mysqli_query($conn, "SELECT DISTINCT field FROM jobs WHERE field IS NOT NULL AND field != '' ORDER BY field");

// jobs the student already applied to
$applied_ids = [];
$stmt2 = mysqli_prepare($conn, "SELECT job_id FROM applications WHERE student_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $student_id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
while ($r = mysqli_fetch_assoc($res2)) {
    $applied_ids[] = $r['job_id'];
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Browse Internships</h3>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search by title or keyword..."
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="field" class="form-select">
                <option value="">All Fields</option>
                <?php while ($f = mysqli_fetch_assoc($field_list)): ?>
                    <option value="<?= htmlspecialchars($f['field']) ?>" <?= $field === $f['field'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['field']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
    </form>

    <?php if (mysqli_num_rows($jobs) === 0): ?>
        <div class="alert alert-info">No internships found matching your criteria.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($job['company_name']) ?></h6>
                            <p class="card-text small">
                                <?= htmlspecialchars(mb_strimwidth($job['description'] ?? '', 0, 100, '...')) ?>
                            </p>
                            <ul class="list-unstyled small text-muted mb-3">
                                <?php if ($job['location']): ?>
                                    <li>📍 <?= htmlspecialchars($job['location']) ?></li>
                                <?php endif; ?>
                                <?php if ($job['field']): ?>
                                    <li>🏷️ <?= htmlspecialchars($job['field']) ?></li>
                                <?php endif; ?>
                            </ul>
                            <div class="mt-auto">
                                <?php if (in_array($job['id'], $applied_ids)): ?>
                                    <button class="btn btn-secondary w-100" disabled>Already Applied</button>
                                <?php else: ?>
                                    <a href="apply.php?job_id=<?= (int)$job['id'] ?>" class="btn btn-primary w-100">Apply Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>