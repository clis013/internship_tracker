<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];
$error = '';
$success = '';
$open_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_reminder') {
        $app_id   = (int)($_POST['app_id'] ?? 0);
        $task     = trim($_POST['task'] ?? '');
        $due_date = trim($_POST['due_date'] ?? '');
        
        // Verify application belongs to student
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM applications WHERE id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt_check, "ii", $app_id, $student_id);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_fetch_assoc($res_check)) {
            // Check if reminder exists
            $stmt_rem = mysqli_prepare($conn, "SELECT id FROM reminders WHERE app_id = ? AND student_id = ?");
            mysqli_stmt_bind_param($stmt_rem, "ii", $app_id, $student_id);
            mysqli_stmt_execute($stmt_rem);
            $res_rem = mysqli_stmt_get_result($stmt_rem);
            $existing_rem = mysqli_fetch_assoc($res_rem);
            
            if (empty($task) || empty($due_date)) {
                if ($existing_rem) {
                    $stmt_del = mysqli_prepare($conn, "DELETE FROM reminders WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_del, "i", $existing_rem['id']);
                    mysqli_stmt_execute($stmt_del);
                    $success = "Reminder cleared successfully.";
                }
            } else {
                $formatted_due_date = date('Y-m-d H:i:s', strtotime($due_date));
                if ($existing_rem) {
                    $stmt_upd = mysqli_prepare($conn, "UPDATE reminders SET task = ?, due_date = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_upd, "ssi", $task, $formatted_due_date, $existing_rem['id']);
                    if (mysqli_stmt_execute($stmt_upd)) {
                        $success = "Reminder updated successfully.";
                    } else {
                        $error = "Failed to update reminder.";
                    }
                } else {
                    $stmt_ins = mysqli_prepare($conn, "INSERT INTO reminders (student_id, app_id, task, due_date) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt_ins, "iiss", $student_id, $app_id, $task, $formatted_due_date);
                    if (mysqli_stmt_execute($stmt_ins)) {
                        $success = "Reminder created successfully.";
                    } else {
                        $error = "Failed to create reminder.";
                    }
                }
            }
            $open_id = $app_id;
        } else {
            $error = "Unauthorized application.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'withdraw') {
        $app_id = (int)($_POST['app_id'] ?? 0);
        
        // Verify and fetch application
        $stmt = mysqli_prepare($conn, "SELECT resume FROM applications WHERE id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $app_id, $student_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $app = mysqli_fetch_assoc($res);
        
        if ($app) {
            if (!empty($app['resume'])) {
                $resume_path = dirname(__DIR__) . '/' . $app['resume'];
                if (file_exists($resume_path)) {
                    @unlink($resume_path);
                }
            }
            
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM applications WHERE id = ? AND student_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "ii", $app_id, $student_id);
            if (mysqli_stmt_execute($delete_stmt)) {
                $success = "Application withdrawn successfully.";
            } else {
                $error = "Failed to withdraw application. Please try again.";
            }
        } else {
            $error = "Application not found or unauthorized.";
        }
    }
}

// Fetch search, filter, and sort values
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$sort   = trim($_GET['sort'] ?? 'newest');

$sql = "SELECT a.id, a.status, a.applied_at, a.cover_letter, a.notes, a.interview_date, a.interview_time, a.interview_venue,
               j.id AS job_id, j.title, j.location, j.field, j.description AS job_description,
               u.name AS company_name, u.email AS company_email, u.phone AS company_phone,
               r.task AS reminder_task, r.due_date AS reminder_due_date, r.id AS reminder_id
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.company_id = u.id
        LEFT JOIN reminders r ON r.app_id = a.id
        WHERE a.student_id = ?";

$params = [$student_id];
$types  = 'i';

if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR u.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($status !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($sort === 'oldest') {
    $sql .= " ORDER BY a.applied_at ASC";
} elseif ($sort === 'company') {
    $sql .= " ORDER BY u.name ASC";
} else { // default to newest
    $sql .= " ORDER BY a.applied_at DESC";
}

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$apps = mysqli_stmt_get_result($stmt);

// Fetch overall application stats for the chart
$stmt_stats = mysqli_prepare($conn, "SELECT 
    COUNT(*) AS total,
    SUM(status = 'pending') AS pending,
    SUM(status = 'reviewed') AS reviewed,
    SUM(status = 'accepted') AS accepted,
    SUM(status = 'rejected') AS rejected
    FROM applications WHERE student_id = ?");
mysqli_stmt_bind_param($stmt_stats, "i", $student_id);
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));

function status_badge($status) {
    $map = [
        'pending'     => 'secondary',
        'reviewed'    => 'info',
        'interview'   => 'primary',
        'accepted'    => 'success',
        'rejected'    => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class=\"badge badge-uniform bg-$class\">" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<script>document.body.classList.add('light-theme');</script>

<div class="container mt-4">
    <h3 class="mb-4">My Applications</h3>

    <div class="row g-4">
        <!-- Left Sidebar: Chart and breakdown -->
        <div class="col-lg-4 col-md-5">
            <?php if ((int)$stats['total'] > 0): ?>
                <div class="card glass-card p-4 mb-4" style="cursor: default;">
                    <h5 class="fw-bold text-white mb-4"><i class="bi bi-pie-chart-fill text-warning me-2"></i> Status Breakdown</h5>
                    
                    <div class="position-relative d-flex align-items-center justify-content-center mb-4" style="height: 180px;">
                        <canvas id="myStatusChart"></canvas>
                        <div class="position-absolute text-center" style="pointer-events: none;">
                            <span class="d-block text-white fs-4 fw-bold"><?= (int)$stats['total'] ?></span>
                            <span class="text-white-50 small" style="font-size: 0.75rem;">Total</span>
                        </div>
                    </div>

                    <hr class="border-secondary border-opacity-50 my-3">

                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded" onclick="location.href='my_applications.php'" style="cursor: pointer; background: rgba(255,255,255,0.05);">
                            <span class="small text-white-50"><i class="bi bi-circle-fill text-info me-2" style="font-size: 0.65rem;"></i>Total</span>
                            <span class="fw-bold text-info"><?= (int)$stats['total'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded" onclick="location.href='my_applications.php?status=pending'" style="cursor: pointer; background: rgba(255,255,255,0.05);">
                            <span class="small text-white-50"><i class="bi bi-circle-fill text-warning me-2" style="font-size: 0.65rem;"></i>Pending</span>
                            <span class="fw-bold text-warning"><?= (int)($stats['pending'] + $stats['reviewed']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded" onclick="location.href='my_applications.php?status=accepted'" style="cursor: pointer; background: rgba(255,255,255,0.05);">
                            <span class="small text-white-50"><i class="bi bi-circle-fill text-success me-2" style="font-size: 0.65rem;"></i>Accepted</span>
                            <span class="fw-bold text-success"><?= (int)$stats['accepted'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded" onclick="location.href='my_applications.php?status=rejected'" style="cursor: pointer; background: rgba(255,255,255,0.05);">
                            <span class="small text-white-50"><i class="bi bi-circle-fill text-danger me-2" style="font-size: 0.65rem;"></i>Rejected</span>
                            <span class="fw-bold text-danger"><?= (int)$stats['rejected'] ?></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card glass-card p-4 text-center mb-4" style="cursor: default;">
                    <i class="bi bi-info-circle-fill text-info fs-1 mb-3"></i>
                    <h5 class="fw-bold text-white mb-2">No Applications</h5>
                    <p class="text-white-50 small mb-0">Submit applications to view breakdown metrics.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side: Search and List -->
        <div class="col-lg-8 col-md-7">

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search, Filter, and Sort Bar -->
    <form method="GET" class="glass-card p-3 mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control glass-input border-end-0 text-white" placeholder="Search Company or Job Title..." value="<?= htmlspecialchars($search) ?>">
                    <span class="input-group-text glass-input border-start-0 text-white"><i class="bi bi-search"></i></span>
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select glass-select text-white">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="reviewed" <?= $status === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="interview" <?= $status === 'interview' ? 'selected' : '' ?>>Interview</option>
                    <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select glass-select text-white">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Applied</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest Applied</option>
                    <option value="company" <?= $sort === 'company' ? 'selected' : '' ?>>Company (A-Z)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-glass-white rounded flex-fill" style="border-radius: 8px !important; padding: 0.375rem 0.75rem !important;">Apply</button>
                <a href="my_applications.php" class="btn btn-glass-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </form>

    <?php if (mysqli_num_rows($apps) === 0): ?>
        <div class="glass-card text-center py-5 mb-4">
            <i class="bi bi-info-circle fs-2 mb-3 d-block text-white-50"></i>
            <span class="text-white">No applications found matching your criteria.</span>
            <a href="browse.php" class="d-block mt-2 fw-semibold text-info">Browse internships to get started</a>
        </div>
    <?php else: ?>
        <div class="accordion" id="appAccordion">
            <?php $i = 0; while ($app = mysqli_fetch_assoc($apps)): $i++; ?>
                <?php 
                    $isExpanded = ($app['id'] == $open_id || ($open_id == 0 && $i === 1 && empty($search) && empty($status)));
                ?>
                <div class="accordion-item glass-row-item border-0 mb-3 overflow-hidden" style="background: transparent;">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $isExpanded ? '' : 'collapsed' ?> py-3" type="button" data-bs-toggle="collapse"
                                data-bs-target="#app<?= $i ?>" aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                                style="background: transparent; color: #ffffff; box-shadow: none;">
                            <div class="d-flex justify-content-between w-100 me-3 align-items-center flex-wrap gap-2">
                                <span class="fw-bold text-white"><?= htmlspecialchars($app['title']) ?> — <span class="text-white-50 fw-semibold"><?= htmlspecialchars($app['company_name']) ?></span></span>
                                <span><?= status_badge($app['status']) ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="app<?= $i ?>" class="accordion-collapse collapse <?= $isExpanded ? 'show' : '' ?>" data-bs-parent="#appAccordion">
                        <div class="accordion-body border-top border-light border-opacity-10" style="background: rgba(255, 255, 255, 0.02);">
                            <!-- Stepper Progress Tracker -->
                            <h6 class="fw-bold text-white mb-3">Application Progress</h6>
                            <div class="stepper-wrapper d-flex justify-content-between mb-4 mt-2">
                                <?php
                                $appStatus = $app['status'];
                                if ($appStatus === 'rejected') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Interview', 'state' => 'completed'],
                                        ['label' => 'Decision', 'state' => 'rejected']
                                    ];
                                } elseif ($appStatus === 'accepted') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Interview', 'state' => 'completed'],
                                        ['label' => 'Decision', 'state' => 'accepted']
                                    ];                                } elseif ($appStatus === 'interview') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Interview', 'state' => 'completed'],
                                        ['label' => 'Decision', 'state' => 'active']
                                    ];
                                } elseif ($appStatus === 'reviewed') {
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'completed'],
                                        ['label' => 'Interview', 'state' => 'active'],
                                        ['label' => 'Decision', 'state' => 'pending']
                                    ];
                                } else { // pending
                                    $steps = [
                                        ['label' => 'Applied', 'state' => 'completed'],
                                        ['label' => 'Resume Screen', 'state' => 'active'],
                                        ['label' => 'Interview', 'state' => 'pending'],
                                        ['label' => 'Decision', 'state' => 'pending']
                                    ];
                                }
                                
                                foreach ($steps as $step):
                                    $badgeClass = 'bg-secondary';
                                    if ($step['state'] === 'completed') $badgeClass = 'bg-success';
                                    elseif ($step['state'] === 'active') $badgeClass = 'bg-primary';
                                    elseif ($step['state'] === 'rejected') $badgeClass = 'bg-danger';
                                    elseif ($step['state'] === 'accepted') $badgeClass = 'bg-success';
                                ?>
                                    <div class="step-item text-center flex-fill">
                                        <div class="step-counter rounded-circle text-white <?= $badgeClass ?> mx-auto mb-1 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 0.85rem;">
                                            <?php if ($step['state'] === 'completed' || $step['state'] === 'accepted'): ?>
                                                ✓
                                            <?php elseif ($step['state'] === 'rejected'): ?>
                                                ✗
                                            <?php else: ?>
                                                •
                                            <?php endif; ?>
                                        </div>
                                        <div class="step-name small text-white-50"><?= htmlspecialchars($step['label']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
 
                            <?php if (in_array($app['status'], ['reviewed', 'interview', 'accepted'])): ?>
                                 <div class="alert bg-success bg-opacity-10 border border-success border-opacity-30 text-white p-3 mb-3 rounded-3" style="backdrop-filter: blur(10px);">
                                     <h6 class="fw-bold text-success mb-2"><i class="bi bi-calendar-check-fill me-2"></i>Interview Scheduled</h6>
                                    <div class="row g-2 small text-white-50 mb-2">
                                        <div class="col-sm-4"><strong>Date:</strong> <span class="text-white"><?= htmlspecialchars($app['interview_date'] ?: 'To be announced') ?></span></div>
                                        <div class="col-sm-4"><strong>Time:</strong> <span class="text-white"><?= htmlspecialchars($app['interview_time'] ?: 'To be announced') ?></span></div>
                                        <div class="col-sm-4"><strong>Venue:</strong> <span class="text-white"><?= htmlspecialchars($app['interview_venue'] ?: 'To be announced') ?></span></div>
                                    </div>
                                    <hr class="border-success border-opacity-20 my-2">
                                    <div class="small text-white-50">
                                        If you have any questions, feel free to contact us at 
                                        <strong class="text-white"><?= htmlspecialchars($app['company_email']) ?></strong>
                                        <?php if (!empty($app['company_phone'])): ?>
                                            or <strong class="text-white"><?= htmlspecialchars($app['company_phone']) ?></strong>
                                        <?php endif; ?>.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <hr class="border-light border-opacity-10">

                            <div class="row g-3">
                                <!-- Details & Cover Letter (Left Column) -->
                                <div class="col-md-7">
                                    <p class="mb-1 small text-white"><strong>Applied on:</strong> <span class="text-white-50"><?= htmlspecialchars(date('d M Y, H:i', strtotime($app['applied_at']))) ?></span></p>
                                    <?php if ($app['location']): ?>
                                        <p class="mb-1 small text-white"><strong>Location:</strong> <span class="text-white-50"><?= htmlspecialchars($app['location']) ?></span></p>
                                    <?php endif; ?>
                                    <?php if ($app['field']): ?>
                                        <p class="mb-1 small text-white"><strong>Field:</strong> <span class="text-white-50"><?= htmlspecialchars($app['field']) ?></span></p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <h6 class="fw-bold text-white small mb-1">Key Contacts:</h6>
                                        <ul class="list-unstyled small text-white-50 mb-0">
                                            <li><i class="bi bi-person me-1"></i> HR Recruiter</li>
                                            <li><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($app['company_email']) ?></li>
                                            <?php if (!empty($app['company_phone'])): ?>
                                                <li><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($app['company_phone']) ?></li>
                                            <?php else: ?>
                                                <li><i class="bi bi-telephone me-1"></i> +603-7967 1234</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>

                                    <div class="mt-3">
                                        <h6 class="fw-bold text-white small mb-1">Job Description Snippet:</h6>
                                        <p class="text-white-50 small mb-2 text-truncate-2">
                                            <?= htmlspecialchars(mb_strimwidth($app['job_description'] ?? '', 0, 200, '...')) ?>
                                        </p>
                                        <a href="browse.php?id=<?= $app['job_id'] ?>" class="btn btn-sm btn-glass-white" style="padding: 0.25rem 0.5rem !important; font-size: 0.75rem !important;">
                                        <i class="bi bi-eye"></i> View Full Details
                                        </a>
                                    </div>

                                    <div class="mt-3">
                                        <h6 class="fw-bold text-white small mb-1">Your Cover Letter:</h6>
                                        <p class="text-white-50 small mb-0 lh-sm"><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                                    </div>
                                </div>

                                <!-- Reminders Section (Right Column) -->
                                <div class="col-md-5 border-start border-light border-opacity-10 ps-3">
                                    <h6 class="fw-bold text-white mb-2"><i class="bi bi-bell-fill text-primary me-1"></i> Make Reminder</h6>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="save_reminder">
                                        <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
                                        
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold text-white mb-1">Task Description</label>
                                            <textarea name="task" class="form-control glass-input small mb-2 text-white" rows="3" placeholder="Write task here (e.g. Prep interview, follow up)..." required><?= htmlspecialchars($app['reminder_task'] ?? '') ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-white mb-1">Due Date</label>
                                            <input type="datetime-local" name="due_date" class="form-control glass-input small text-white" value="<?= isset($app['reminder_due_date']) ? date('Y-m-d\TH:i', strtotime($app['reminder_due_date'])) : '' ?>" required>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-glass-white rounded flex-fill" style="border-radius: 8px !important; padding: 0.375rem 0.75rem !important;">Save Reminder</button>
                                            <?php if (!empty($app['reminder_task'])): ?>
                                                <button type="submit" name="clear_reminder" value="1" class="btn btn-sm btn-glass-secondary text-danger" onclick="this.form.task.required=false; this.form.due_date.required=false; this.form.task.value=''; this.form.due_date.value='';">Clear</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <hr class="border-light border-opacity-10">
                            
                            <div>
                                <button type="button" class="btn btn-glass-danger btn-sm" onclick="triggerWithdraw(<?= (int)$app['id'] ?>, '<?= htmlspecialchars($app['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($app['company_name'], ENT_QUOTES) ?>')">
                                    <i class="bi bi-x-circle"></i> Withdraw Application
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
        </div>
    </div>
</div>

<?php if ((int)$stats['total'] > 0): ?>
<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('myStatusChart').getContext('2d');
    
    const pendingVal = <?= (int)($stats['pending'] + $stats['reviewed']) ?>;
    const acceptedVal = <?= (int)$stats['accepted'] ?>;
    const rejectedVal = <?= (int)$stats['rejected'] ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Accepted', 'Rejected'],
            datasets: [{
                data: [pendingVal, acceptedVal, rejectedVal],
                backgroundColor: ['#eab308', '#10b981', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    display: false // Hide legend to keep the chart compact; tooltips show on hover
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    padding: 8,
                    bodyFont: {
                        family: "'Outfit', sans-serif"
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<!-- Withdraw Confirmation Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-start" style="transform: none !important; cursor: default !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger fw-bold" id="withdrawModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Withdraw Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-white mb-0" style="color: rgba(255, 255, 255, 0.95) !important;">Are you sure you want to withdraw your application for <strong id="withdrawJobTitle" class="text-white"></strong> at <strong id="withdrawCompanyName" class="text-white"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <form method="POST" id="withdrawConfirmForm">
                    <input type="hidden" name="action" value="withdraw">
                    <input type="hidden" name="app_id" id="withdrawAppId" value="">
                    <button type="button" class="btn btn-glass-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Yes, Withdraw</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function triggerWithdraw(appId, jobTitle, companyName) {
    document.getElementById('withdrawAppId').value = appId;
    document.getElementById('withdrawJobTitle').innerText = jobTitle;
    document.getElementById('withdrawCompanyName').innerText = companyName;
    const withdrawModal = new bootstrap.Modal(document.getElementById('withdrawModal'));
    withdrawModal.show();
}
</script>

<?php include '../includes/footer.php'; ?>