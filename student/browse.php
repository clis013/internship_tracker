<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

// Check student resume
$stmt_resume_chk = mysqli_prepare($conn, "SELECT resume FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_resume_chk, "i", $student_id);
mysqli_stmt_execute($stmt_resume_chk);
$res_resume_chk = mysqli_stmt_get_result($stmt_resume_chk);
$student_resume_data = mysqli_fetch_assoc($res_resume_chk);
$has_resume = !empty($student_resume_data['resume']);

// Search / filter
$search        = trim($_GET['search'] ?? '');
$field         = trim($_GET['field'] ?? '');
$location      = trim($_GET['location'] ?? '');
$industry      = trim($_GET['industry'] ?? '');
$min_allowance = trim($_GET['min_allowance'] ?? '');

$sql = "SELECT j.id, j.company_id, j.title, j.description, j.location, j.allowance, j.field, j.created_at, 
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

// Search parameters query string for side links
$search_query = http_build_query([
    'search' => $search,
    'field' => $field,
    'location' => $location,
    'industry' => $industry,
    'min_allowance' => $min_allowance
]);
?>

<script>document.body.classList.add('light-theme');</script>

<style>
.glass-row-item.active-job {
    background: rgba(255, 255, 255, 0.25) !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
    border-left: 4px solid #38b6ff !important;
    box-shadow: 0 8px 32px rgba(56, 182, 255, 0.2) !important;
}
.list-group-item-action:hover {
    background-color: rgba(255, 255, 255, 0.05) !important;
    color: #ffffff !important;
}
</style>

<div class="container mt-4 pb-5">
    <h3 class="fw-bold text-white mb-4">Browse Internships</h3>

    <!-- Filters Panel -->
    <form method="GET" class="glass-card p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-white">Search Keywords</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control glass-input border-end-0 text-white" placeholder="Search by title or keyword..."
                           value="<?= htmlspecialchars($search) ?>">
                    <span class="input-group-text glass-input border-start-0 text-white"><i class="bi bi-search"></i></span>
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-bold text-white">Field</label>
                <select name="field" class="form-select glass-select text-white">
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
                <label class="form-label small fw-bold text-white">Location</label>
                <select name="location" class="form-select glass-select text-white">
                    <option value="">All Locations</option>
                    <?php mysqli_data_seek($location_list, 0); ?>
                    <?php while ($loc = mysqli_fetch_assoc($location_list)): ?>
                        <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $location === $loc['location'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc['location']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-white">Industry</label>
                <select name="industry" class="form-select glass-select text-white">
                    <option value="">All Industries</option>
                    <?php mysqli_data_seek($industry_list, 0); ?>
                    <?php while ($ind = mysqli_fetch_assoc($industry_list)): ?>
                        <option value="<?= htmlspecialchars($ind['industry']) ?>" <?= $industry === $ind['industry'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ind['industry']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-white">Min Allowance ($)</label>
                <input type="number" name="min_allowance" class="form-control glass-input text-white" placeholder="e.g. 500"
                       value="<?= htmlspecialchars($min_allowance) ?>">
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                <a href="browse.php" class="btn btn-glass-secondary px-3">Reset Filters</a>
                <button type="submit" class="btn btn-glass-primary px-4 fw-bold">Search & Filter</button>
            </div>
        </div>
    </form>

    <?php if (empty($jobs_list)): ?>
        <div class="glass-card p-4 text-center mb-4"><i class="bi bi-info-circle-fill text-info fs-3 mb-2 d-block"></i>No internships found matching your criteria.</div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left Column: Scrollable Internship List -->
            <div class="col-lg-4 col-md-5">
                <div class="job-list-scroll pe-2">
                    <?php foreach ($jobs_list as $job): ?>
                        <?php 
                            $isActive = $selected_job && ($job['id'] == $selected_job['id']);
                            $cardClass = $isActive ? 'glass-row-item active-job border-primary border-start border-4' : 'glass-row-item';
                        ?>
                        <div class="mb-3 job-card <?= $cardClass ?>" onclick="location.href='browse.php?id=<?= $job['id'] ?>&<?= $search_query ?>'">
                            <div class="card-body p-3">
                                <h6 class="card-title fw-bold mb-1 text-truncate text-white"><?= htmlspecialchars($job['title']) ?></h6>
                                <div class="text-white-50 small mb-2 text-truncate fw-semibold"><?= htmlspecialchars($job['company_name']) ?></div>
                                
                                <?php if (!empty($job['company_industry'])): ?>
                                    <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-10 mb-2 small fw-semibold" style="font-size: 0.7rem;">
                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($job['company_industry']) ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div class="d-flex flex-wrap gap-2 mb-2 small text-white-50">
                                    <?php if ($job['location']): ?>
                                        <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($job['field']): ?>
                                        <span>🏷️ <?= htmlspecialchars($job['field']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="small text-success fw-bold">
                                    <i class="bi bi-cash-stack me-1"></i><?= $job['allowance'] ? '$' . htmlspecialchars($job['allowance']) : 'Not specified' ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Detail View -->
            <div class="col-lg-8 col-md-7">
                <?php if ($selected_job): ?>
                    <div class="glass-card shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                            <div>
                                <h4 class="fw-bold mb-1 text-white"><?= htmlspecialchars($selected_job['title']) ?></h4>
                                <h5 class="text-white-50 fw-semibold"><?= htmlspecialchars($selected_job['company_name']) ?></h5>
                                <?php if (!empty($selected_job['company_industry'])): ?>
                                    <span class="badge bg-white bg-opacity-10 text-white border border-light border-opacity-10 small fw-semibold py-1 px-2">
                                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($selected_job['company_industry']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if (in_array($selected_job['id'], $applied_ids)): ?>
                                    <button class="btn btn-glass-secondary px-4 py-2 fw-bold" disabled>
                                        <i class="bi bi-check-circle-fill"></i> Already Applied
                                    </button>
                                <?php else: ?>
                                    <?php if ($has_resume): ?>
                                        <a href="apply.php?job_id=<?= (int)$selected_job['id'] ?>" class="btn btn-glass-primary px-4 py-2 fw-bold">
                                            Apply Now <i class="bi bi-arrow-right-short"></i>
                                        </a>
                                    <?php else: ?>
                                        <div class="alert bg-transparent border border-warning text-warning p-2 mb-0 rounded d-flex align-items-center gap-2">
                                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                            <span class="small">Upload resume to apply. <a href="profile.php" class="text-warning fw-bold text-decoration-underline">Upload</a></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mb-4 text-white-50 small border-bottom border-light border-opacity-10 pb-3">
                            <?php if ($selected_job['location']): ?>
                                <div><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($selected_job['location']) ?></div>
                            <?php endif; ?>
                            <?php if ($selected_job['field']): ?>
                                <div><i class="bi bi-tag-fill text-primary me-1"></i> <?= htmlspecialchars($selected_job['field']) ?></div>
                            <?php endif; ?>
                            <div>
                                <i class="bi bi-cash-stack text-success me-1"></i> 
                                Allowance: <strong><?= $selected_job['allowance'] ? '$' . htmlspecialchars($selected_job['allowance']) : 'Not specified' ?></strong>
                            </div>
                            <div><i class="bi bi-calendar3 me-1"></i> Posted <?= htmlspecialchars(date('d M Y', strtotime($selected_job['created_at']))) ?></div>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold mb-3 text-white">Job Description</h5>
                            <div class="lh-base text-white-50" style="white-space: pre-line;">
                                <?= htmlspecialchars($selected_job['description'] ?? '') ?>
                            </div>
                        </div>

                        <hr class="my-4 border-light border-opacity-10">

                        <!-- Other Internships offered by the company -->
                        <div>
                            <h5 class="fw-bold mb-3 text-white">Other Internships from <?= htmlspecialchars($selected_job['company_name']) ?></h5>
                            <?php if (!$res_other || mysqli_num_rows($res_other) === 0): ?>
                                <p class="text-white-50 small">No other active internship vacancies from this company.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($other = mysqli_fetch_assoc($res_other)): ?>
                                        <a href="browse.php?id=<?= $other['id'] ?>&<?= $search_query ?>" class="list-group-item list-group-item-action bg-transparent border-0 border-bottom border-light border-opacity-10 d-flex justify-content-between align-items-center px-0 py-2">
                                            <div>
                                                <div class="fw-bold text-info"><?= htmlspecialchars($other['title']) ?></div>
                                                <div class="text-white-50 small">
                                                    <?php if ($other['location']): ?>📍 <?= htmlspecialchars($other['location']) ?> <?php endif; ?>
                                                    <?php if ($other['field']): ?>| 🏷️ <?= htmlspecialchars($other['field']) ?> <?php endif; ?>
                                                </div>
                                            </div>
                                            <i class="bi bi-chevron-right text-white-50"></i>
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