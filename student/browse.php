<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

// Search / filter
$search        = trim($_GET['search'] ?? '');
$field         = trim($_GET['field'] ?? '');
$location      = trim($_GET['location'] ?? '');
$industry      = trim($_GET['industry'] ?? '');
$min_allowance = trim($_GET['min_allowance'] ?? '');

$sql = "SELECT j.id, j.title, j.description, j.location, j.allowance, j.field, j.created_at, 
               u.name AS company_name, u.industry AS company_industry
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
if ($location !== '') {
    $sql .= " AND j.location = ?";
    $params[] = $location;
    $types .= 's';
}
if ($industry !== '') {
    $sql .= " AND u.industry = ?";
    $params[] = $industry;
    $types .= 's';
}
if ($min_allowance !== '') {
    $sql .= " AND j.allowance IS NOT NULL AND j.allowance != '' AND CAST(j.allowance AS UNSIGNED) >= ?";
    $params[] = (int)$min_allowance;
    $types .= 'i';
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

// distinct locations for filter dropdown
$location_list = mysqli_query($conn, "SELECT DISTINCT location FROM jobs WHERE location IS NOT NULL AND location != '' ORDER BY location");

// distinct industries for filter dropdown
$industry_list = mysqli_query($conn, "SELECT DISTINCT industry FROM users WHERE role = 'company' AND industry IS NOT NULL AND industry != '' ORDER BY industry");

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
    <h3 class="fw-bold text-dark mb-4">Browse Internships</h3>

    <!-- Filters Panel -->
    <form method="GET" class="card p-4 shadow-sm border-0 mb-4 bg-white">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Search Keywords</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by title or keyword..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary">Field</label>
                <select name="field" class="form-select">
                    <option value="">All Fields</option>
                    <?php 
                    mysqli_data_seek($field_list, 0);
                    while ($f = mysqli_fetch_assoc($field_list)): 
                    ?>
                        <option value="<?= htmlspecialchars($f['field']) ?>" <?= $field === $f['field'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['field']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary">Location</label>
                <select name="location" class="form-select">
                    <option value="">All Locations</option>
                    <?php while ($loc = mysqli_fetch_assoc($location_list)): ?>
                        <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $location === $loc['location'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['location']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary">Industry</label>
                <select name="industry" class="form-select">
                    <option value="">All Industries</option>
                    <?php while ($ind = mysqli_fetch_assoc($industry_list)): ?>
                        <option value="<?= htmlspecialchars($ind['industry']) ?>" <?= $industry === $ind['industry'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ind['industry']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary">Min Allowance ($)</label>
                <input type="number" name="min_allowance" class="form-control" placeholder="e.g. 500"
                       value="<?= htmlspecialchars($min_allowance) ?>">
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                <a href="browse.php" class="btn btn-outline-secondary px-3">Reset Filters</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">Search & Filter</button>
            </div>
        </div>
    </form>

    <?php if (mysqli_num_rows($jobs) === 0): ?>
        <div class="alert alert-info py-3 shadow-sm border-0"><i class="bi bi-info-circle me-2"></i>No internships found matching your criteria.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($job = mysqli_fetch_assoc($jobs)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 position-relative">
                        <div class="card-body d-flex flex-column p-4">
                            <h5 class="card-title fw-bold text-dark mb-1"><?= htmlspecialchars($job['title']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted fw-semibold small"><?= htmlspecialchars($job['company_name']) ?></h6>
                            
                            <?php if (!empty($job['company_industry'])): ?>
                                <span class="badge bg-light text-dark align-self-start border mb-3 small py-1 px-2 fw-semibold">
                                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($job['company_industry']) ?>
                                </span>
                            <?php endif; ?>

                            <p class="card-text small text-muted flex-grow-1">
                                <?= htmlspecialchars(mb_strimwidth($job['description'] ?? '', 0, 120, '...')) ?>
                            </p>
                            
                            <hr class="my-3 text-muted opacity-25">
                            
                            <ul class="list-unstyled small text-muted mb-3 row g-2">
                                <?php if ($job['location']): ?>
                                    <li class="col-6"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($job['location']) ?></li>
                                <?php endif; ?>
                                <?php if ($job['field']): ?>
                                    <li class="col-6"><i class="bi bi-tag-fill text-primary me-1"></i> <?= htmlspecialchars($job['field']) ?></li>
                                <?php endif; ?>
                                <li class="col-12 mt-1">
                                    <i class="bi bi-cash-stack text-success me-1"></i> 
                                    Allowance: <strong><?= $job['allowance'] ? '$' . htmlspecialchars($job['allowance']) : '<span class="text-muted fw-normal">Not specified</span>' ?></strong>
                                </li>
                            </ul>
                            
                            <div class="mt-auto pt-2">
                                <?php if (in_array($job['id'], $applied_ids)): ?>
                                    <button class="btn btn-secondary w-100 py-2 fw-bold" disabled>Already Applied</button>
                                <?php else: ?>
                                    <a href="apply.php?job_id=<?= (int)$job['id'] ?>" class="btn btn-primary w-100 py-2 fw-bold">Apply Now</a>
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