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

$sql = "SELECT j.id, j.company_id, j.title, j.description, j.location, j.field, j.created_at, u.name AS company_name
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

// Load all jobs into an array
$jobs_list = [];
while ($row = mysqli_fetch_assoc($jobs)) {
    $jobs_list[] = $row;
}

// Determine selected job
$selected_job = null;
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($selected_id > 0) {
    foreach ($jobs_list as $job) {
        if ((int)$job['id'] === $selected_id) {
            $selected_job = $job;
            break;
        }
    }
}
// Default to first job in search list if none selected
if (!$selected_job && !empty($jobs_list)) {
    $selected_job = $jobs_list[0];
}

// Fetch other jobs offered by the company of the selected job
$res_other = null;
if ($selected_job) {
    $stmt_other = mysqli_prepare($conn, "SELECT id, title, location, field FROM jobs WHERE company_id = ? AND id != ? AND status = 'active' ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt_other, "ii", $selected_job['company_id'], $selected_job['id']);
    mysqli_stmt_execute($stmt_other);
    $res_other = mysqli_stmt_get_result($stmt_other);
}

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

// Search parameters query string for side links
$search_query = http_build_query([
    'search' => $search,
    'field' => $field
]);
?>

<div class="container mt-4">
    <h3 class="mb-4">Browse Internships</h3>

    <!-- Search and Filter Form -->
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

    <?php if (empty($jobs_list)): ?>
        <div class="alert alert-info">No internships found matching your criteria.</div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left Column: Scrollable Internship List -->
            <div class="col-lg-4 col-md-5">
                <div class="job-list-scroll pe-2">
                    <?php foreach ($jobs_list as $job): ?>
                        <?php 
                            $isActive = $selected_job && ($job['id'] == $selected_job['id']);
                            $cardClass = $isActive ? 'border-primary bg-white active-job' : 'bg-white';
                        ?>
                        <div class="card mb-3 job-card shadow-sm <?= $cardClass ?>" onclick="location.href='browse.php?id=<?= $job['id'] ?>&<?= $search_query ?>'">
                            <div class="card-body p-3">
                                <h6 class="card-title fw-bold mb-1 text-truncate"><?= htmlspecialchars($job['title']) ?></h6>
                                <div class="text-muted small mb-2 text-truncate"><?= htmlspecialchars($job['company_name']) ?></div>
                                
                                <div class="d-flex flex-wrap gap-2 mb-2 small text-muted">
                                    <?php if ($job['location']): ?>
                                        <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($job['field']): ?>
                                        <span>🏷️ <?= htmlspecialchars($job['field']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="card-text small text-muted mb-0 text-truncate-2">
                                    <?= htmlspecialchars(mb_strimwidth($job['description'] ?? '', 0, 100, '...')) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Detail View -->
            <div class="col-lg-8 col-md-7">
                <?php if ($selected_job): ?>
                    <div class="card shadow-sm border-0 bg-white p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                            <div>
                                <h4 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($selected_job['title']) ?></h4>
                                <h5 class="text-secondary"><?= htmlspecialchars($selected_job['company_name']) ?></h5>
                            </div>
                            <div>
                                <?php if (in_array($selected_job['id'], $applied_ids)): ?>
                                    <button class="btn btn-secondary px-4 py-2" disabled>
                                        <i class="bi bi-check-circle-fill"></i> Already Applied
                                    </button>
                                <?php else: ?>
                                    <a href="apply.php?job_id=<?= (int)$selected_job['id'] ?>" class="btn btn-primary px-4 py-2">
                                        Apply Now <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mb-4 text-muted small border-bottom pb-3">
                            <?php if ($selected_job['location']): ?>
                                <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($selected_job['location']) ?></div>
                            <?php endif; ?>
                            <?php if ($selected_job['field']): ?>
                                <div><i class="bi bi-tag"></i> <?= htmlspecialchars($selected_job['field']) ?></div>
                            <?php endif; ?>
                            <div><i class="bi bi-calendar3"></i> Posted <?= htmlspecialchars(date('d M Y', strtotime($selected_job['created_at']))) ?></div>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold mb-3 text-dark">Job Description</h5>
                            <div class="lh-base text-muted" style="white-space: pre-line;">
                                <?= htmlspecialchars($selected_job['description'] ?? '') ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Other Internships offered by the company -->
                        <div>
                            <h5 class="fw-bold mb-3 text-dark">Other Internships from <?= htmlspecialchars($selected_job['company_name']) ?></h5>
                            <?php if (!$res_other || mysqli_num_rows($res_other) === 0): ?>
                                <p class="text-muted small">No other active internship vacancies from this company.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($other = mysqli_fetch_assoc($res_other)): ?>
                                        <a href="browse.php?id=<?= $other['id'] ?>&<?= $search_query ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0 py-2 border-0 border-bottom">
                                            <div>
                                                <div class="fw-bold text-primary"><?= htmlspecialchars($other['title']) ?></div>
                                                <div class="text-muted small">
                                                    <?php if ($other['location']): ?>📍 <?= htmlspecialchars($other['location']) ?> <?php endif; ?>
                                                    <?php if ($other['field']): ?>| 🏷️ <?= htmlspecialchars($other['field']) ?> <?php endif; ?>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right text-muted"></i>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                              <?php endif; ?>
                          </div>
                      </div>
                  <?php endif; ?>
              </div>
          </div>
      <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>