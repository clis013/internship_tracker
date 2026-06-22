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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_reminder') {
        $task = trim($_POST['task'] ?? '');
        $due_date = trim($_POST['due_date'] ?? '');
        
        if (empty($task) || empty($due_date)) {
            $error = "Task description and due date are required.";
        } else {
            $formatted_due_date = date('Y-m-d H:i:s', strtotime($due_date));
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO reminders (student_id, task, due_date) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "iss", $student_id, $task, $formatted_due_date);
            if (mysqli_stmt_execute($stmt_ins)) {
                $success = "Reminder added successfully.";
            } else {
                $error = "Failed to add reminder.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_reminder') {
        $reminder_id = (int)($_POST['reminder_id'] ?? 0);
        // Verify ownership
        $stmt_del = mysqli_prepare($conn, "DELETE FROM reminders WHERE id = ? AND student_id = ?");
        mysqli_stmt_bind_param($stmt_del, "ii", $reminder_id, $student_id);
        if (mysqli_stmt_execute($stmt_del)) {
            $success = "Reminder completed/removed.";
        } else {
            $error = "Failed to remove reminder.";
        }
    }
}

// Fetch reminders sorted by due_date ASC (closer due dates first)
$sql_reminders = "SELECT r.id, r.task, r.due_date, r.app_id, j.title AS job_title, u.name AS company_name
                  FROM reminders r
                  LEFT JOIN applications a ON r.app_id = a.id
                  LEFT JOIN jobs j ON a.job_id = j.id
                  LEFT JOIN users u ON j.company_id = u.id
                  WHERE r.student_id = ?
                  ORDER BY r.due_date ASC";
$stmt_reminders = mysqli_prepare($conn, $sql_reminders);
mysqli_stmt_bind_param($stmt_reminders, "i", $student_id);
mysqli_stmt_execute($stmt_reminders);
$reminders = mysqli_stmt_get_result($stmt_reminders);

// Stats
$stmt = mysqli_prepare($conn, "SELECT 
    COUNT(*) AS total,
    SUM(status = 'pending') AS pending,
    SUM(status = 'reviewed') AS reviewed,
    SUM(status = 'accepted') AS accepted,
    SUM(status = 'rejected') AS rejected
    FROM applications WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Recent applications (limit to 3)
$stmt = mysqli_prepare($conn, "SELECT a.id, a.status, a.applied_at, j.title, u.name AS company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.company_id = u.id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC LIMIT 3");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);

// Jobs you may be interested in (limit to 3, excluding already applied)
$stmt_interest = mysqli_prepare($conn, "SELECT j.id, j.title, j.location, j.field, u.name AS company_name
    FROM jobs j
    JOIN users u ON j.company_id = u.id
    WHERE j.status = 'active' AND j.id NOT IN (
        SELECT job_id FROM applications WHERE student_id = ?
    )
    ORDER BY j.created_at DESC LIMIT 3");
mysqli_stmt_bind_param($stmt_interest, "i", $student_id);
mysqli_stmt_execute($stmt_interest);
$interest_jobs = mysqli_stmt_get_result($stmt_interest);

function status_badge($status) {
    $map = [
        'pending'     => 'secondary',
        'reviewed'    => 'info',
        'interviewed' => 'primary',
        'accepted'    => 'success',
        'rejected'    => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class=\" badge-uniform bg-$class\">" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<div class="container mt-4">
    <h3 class="mb-4 text-white">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show bg-transparent border-danger text-danger" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show bg-transparent border-success text-success" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="glass-hero mb-5">
        <div class="glass-hero-content row align-items-center justify-content-between">
        <div class="col-md-6 mb-4 mb-md-0" style="padding-left: 40px;">
            <h1 class="hero-title">Innovate.<br>Elevate.<br>Transform.</h1>
            <p class="hero-subtitle mt-3">Empowering your internship journey with cutting-edge opportunities and streamlined application tracking. Build your future today.</p>
            <div class="d-flex gap-3 mt-4">
                    <a href="browse.php" class="btn btn-glass-white rounded-pill">Explore Solutions</a>
                    <a href="profile.php" class="btn btn-glass-light-secondary rounded-pill px-4 py-2"><i class="bi bi-asterisk"></i> Update Profile</a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="d-flex align-items-start justify-content-center justify-content-md-end gap-1" style="margin-right: -80px;">
                <img src="../assets/images/IMG_3342.PNG" alt="Your Idea Our Expertise" class="img-fluid hero-puzzle-img">
                <img src="../assets/images/homepage-logo.png" alt="Stacked Keys" class="img-fluid hero-stacked-keys">
            </div>
        </div>
    </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card glass-card-light p-4 h-100" style="cursor: default;">
                <h5 class="fw-bold text-white mb-4"><i class="bi bi-bar-chart-fill text-info me-2"></i> Application Overview</h5>
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="card text-center bg-transparent border border-secondary border-opacity-25 rounded-3 py-3 dashboard-card hover-lift" onclick="location.href='my_applications.php'" style="cursor: pointer;">
                            <div class="card-body py-2">
                                <div class="fs-4 text-info fw-bold mb-1"><?= (int)$stats['total'] ?></div>
                                <div class="small text-white-50">Total Applications</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card text-center bg-transparent border border-secondary border-opacity-25 rounded-3 py-3 dashboard-card hover-lift" onclick="location.href='my_applications.php?status=pending'" style="cursor: pointer;">
                            <div class="card-body py-2">
                                <div class="fs-4 text-warning fw-bold mb-1"><?= (int)($stats['pending'] + $stats['reviewed']) ?></div>
                                <div class="small text-white-50">Pending Review</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card text-center bg-transparent border border-secondary border-opacity-25 rounded-3 py-3 dashboard-card hover-lift" onclick="location.href='my_applications.php?status=accepted'" style="cursor: pointer;">
                            <div class="card-body py-2">
                                <div class="fs-4 text-success fw-bold mb-1"><?= (int)$stats['accepted'] ?></div>
                                <div class="small text-white-50">Accepted Offers</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card text-center bg-transparent border border-secondary border-opacity-25 rounded-3 py-3 dashboard-card hover-lift" onclick="location.href='my_applications.php?status=rejected'" style="cursor: pointer;">
                            <div class="card-body py-2">
                                <div class="fs-4 text-danger fw-bold mb-1"><?= (int)$stats['rejected'] ?></div>
                                <div class="small text-white-50">Rejected Applications</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm glass-card-light h-100" style="cursor: default;">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-2 text-white">Profile Completeness</h5>
                        <p class="small text-white-50 mb-4">Keep your details fresh to attract top companies.</p>
                        <div class="progress mb-3 bg-dark" style="height: 8px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mt-auto">
                        <span class="text-white-50">85% Completed</span>
                        <a href="profile.php" class="text-info fw-bold text-decoration-none">Update Profile <i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm glass-card-light mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-white">Recent Applications</h5>
                        <a href="my_applications.php" class="btn btn-sm btn-glass-white rounded-pill" style="padding: 0.25rem 0.75rem !important;">View all</a>
                    </div>
                    
                    <?php if (mysqli_num_rows($recent) === 0): ?>
                        <div class="alert bg-transparent border border-secondary text-white-50 text-center py-4 my-2">You haven't applied to any internships yet.</div>
                    <?php else: ?>
                        <div class="row glass-table-header d-none d-md-flex mb-2 px-3 mt-4">
                            <div class="col-md-4">Role</div>
                            <div class="col-md-3">Company</div>
                            <div class="col-md-3">Applied Date</div>
                            <div class="col-md-2">Status</div>
                        </div>
                        
                        <div class="d-flex flex-column">
                            <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                                <div class="row glass-row-item-light align-items-center py-3 px-3 mx-0">
                                    <div class="col-md-4 glass-row-text-primary text-truncate fw-semibold">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </div>
                                    <div class="col-md-3 glass-row-text-secondary text-truncate">
                                        <?= htmlspecialchars($row['company_name']) ?>
                                    </div>
                                    <div class="col-md-3 glass-row-text-muted small">
                                        <?= htmlspecialchars(date('d M Y', strtotime($row['applied_at']))) ?>
                                    </div>
                                    <div class="col-md-2 mt-2 mt-md-0">
                                        <?= status_badge($row['status']) ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm glass-card-light">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-white">Internships You May Be Interested In</h5>
                        <a href="browse.php" class="btn btn-link btn-sm text-info p-0 text-decoration-none">Explore More</a>
                    </div>
                    <?php if (mysqli_num_rows($interest_jobs) === 0): ?>
                        <div class="text-white-50 small">No new vacancies available matching your interests.</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php while ($job = mysqli_fetch_assoc($interest_jobs)): ?>
                                <div class="col-md-4">
                                    <div class="card h-100 glass-card-light">
                                        <div class="card-body p-3 d-flex flex-column">
                                            <h6 class="fw-bold mb-1 text-truncate text-white" title="<?= htmlspecialchars($job['title']) ?>"><?= htmlspecialchars($job['title']) ?></h6>
                                            <div class="text-white-50 small mb-2"><?= htmlspecialchars($job['company_name']) ?></div>
                                            <div class="small text-white-50 mb-3">
                                                <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                                            </div>
                                            <a href="browse.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-glass-white mt-auto w-100 rounded-pill" style="padding: 0.25rem 0.5rem !important;">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm glass-card-light mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-white"><i class="bi bi-bell-fill text-warning me-1"></i> Reminders & Tasks</h5>
                        <button type="button" class="btn btn-sm btn-link text-info p-0" data-bs-toggle="modal" data-bs-target="#addReminderModal" title="Add Reminder">
                            <i class="bi bi-plus-circle-fill fs-5 text-info"></i>
                        </button>
                    </div>
                    
                    <div class="d-flex flex-column gap-2 mt-4">
                        <?php if (mysqli_num_rows($reminders) === 0): ?>
                            <div class="text-white-50 small text-center py-3">No pending reminders. Click the + icon to add one!</div>
                        <?php else: ?>
                            <?php while ($rem = mysqli_fetch_assoc($reminders)): 
                                $is_overdue = strtotime($rem['due_date']) < time();
                                $due_color = $is_overdue ? 'text-danger fw-semibold' : 'text-white-50';
                                $formatted_due = date('d M Y, H:i', strtotime($rem['due_date']));
                            ?>
                                <div class="glass-row-item-light p-3 d-flex align-items-start justify-content-between gap-2 m-0">
                                    <div class="flex-grow-1">
                                        <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold text-white">
                                                <i class="bi bi-calendar-event text-info me-2"></i><?= htmlspecialchars($rem['task']) ?>
                                            </h6>
                                            <small class="<?= $due_color ?>" style="font-size: 0.75rem;"><?= $formatted_due ?></small>
                                        </div>
                                        <?php if ($rem['app_id']): ?>
                                            <span class="badge badge-uniform bg-secondary bg-opacity-50 text-white small" style="font-size: 0.7rem; cursor: pointer;" onclick="location.href='my_applications.php'">
                                                <i class="bi bi-briefcase-fill me-1"></i><?= htmlspecialchars($rem['job_title']) ?> (<?= htmlspecialchars($rem['company_name']) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-uniform bg-info bg-opacity-25 text-info small" style="font-size: 0.7rem;">
                                                <i class="bi bi-pin-angle-fill me-1"></i>Personal Task
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Mark this task as completed?')">
                                        <input type="hidden" name="action" value="delete_reminder">
                                        <input type="hidden" name="reminder_id" value="<?= (int)$rem['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-link p-0 border-0" title="Complete/Delete">
                                            <i class="bi bi-check2-circle fs-5 text-success"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addReminderModal" tabindex="-1" aria-labelledby="addReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card-light text-start" style="transform: none !important; cursor: default !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-white" id="addReminderModalLabel"><i class="bi bi-bell-fill text-warning me-2"></i>Add Reminder</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_reminder">
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-white mb-1">Task / Description</label>
                        <textarea name="task" class="form-control glass-input small text-white" rows="3" placeholder="Write reminder description here (e.g. follow up on internship)..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-white mb-1">Due Date</label>
                        <input type="datetime-local" name="due_date" class="form-control glass-input small text-white" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-glass-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-glass-primary px-4">Add Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>