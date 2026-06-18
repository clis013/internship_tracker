<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

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
        'pending'  => 'secondary',
        'reviewed' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-$class\">" . htmlspecialchars(ucfirst($status)) . "</span>";
}
?>

<div class="container mt-4">
    <h3 class="mb-4 text-white">Welcome, <?= htmlspecialchars($_SESSION['name']) ?> 👋</h3>

    <div class="glass-hero mb-5">
        <div class="glass-hero-content row align-items-center justify-content-between">
        <!-- Added style="padding-left: 40px;" to push the text inward -->
        <div class="col-md-6 mb-4 mb-md-0" style="padding-left: 40px;">
            <h1 class="hero-title">Innovate.<br>Elevate.<br>Transform.</h1>
            <p class="hero-subtitle mt-3">Empowering your internship journey with cutting-edge opportunities and streamlined application tracking. Build your future today.</p>
            <div class="d-flex gap-3 mt-4">
                <a href="browse.php" class="btn btn-glass-white rounded-pill">Explore Solutions</a>
                <a href="profile.php" class="btn btn-glass-secondary rounded-pill px-4 py-2"><i class="bi bi-asterisk"></i> Update Profile</a>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Added style="margin-right: -40px;" to pull the images past the invisible padding -->
            <div class="d-flex align-items-start justify-content-center justify-content-md-end gap-1" style="margin-right: -80px;">
                <img src="../assets/images/IMG_3342.PNG" alt="Your Idea Our Expertise" class="img-fluid hero-puzzle-img">
                
                <img src="../assets/images/homepage-logo.png" alt="Stacked Keys" class="img-fluid hero-stacked-keys">
            </div>
        </div>
    </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card" onclick="location.href='my_applications.php'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-info"><?= (int)$stats['total'] ?></h3>
                    <p class="card-text text-muted small mb-0">Total Applications</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card" onclick="location.href='my_applications.php?status=pending'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-warning"><?= (int)($stats['pending'] + $stats['reviewed']) ?></h3>
                    <p class="card-text text-muted small mb-0">Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card" onclick="location.href='my_applications.php?status=accepted'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-success"><?= (int)$stats['accepted'] ?></h3>
                    <p class="card-text text-muted small mb-0">Accepted Offers</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm glass-card" onclick="location.href='my_applications.php?status=rejected'">
                <div class="card-body py-4">
                    <h3 class="card-title fw-bold text-danger"><?= (int)$stats['rejected'] ?></h3>
                    <p class="card-text text-muted small mb-0">Rejected Applications</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm glass-card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-white">Recent Applications</h5>
                        <a href="my_applications.php" class="btn btn-glass-secondary btn-sm rounded-pill">View All</a>
                    </div>
                    <?php if (mysqli_num_rows($recent) === 0): ?>
                        <div class="alert bg-transparent border border-secondary text-white text-center py-4 my-2">You haven't applied to any internships yet.</div>
                    <?php else: ?>
                        <div class="table-responsive border-0">
                            <table class="table table-hover align-middle mb-0 border-0">
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                                        <tr>
                                            <td class="fw-semibold text-white"><?= htmlspecialchars($row['title']) ?></td>
                                            <td class="text-white-50"><?= htmlspecialchars($row['company_name']) ?></td>
                                            <td class="text-white-50 small"><?= htmlspecialchars(date('d M Y', strtotime($row['applied_at']))) ?></td>
                                            <td><?= status_badge($row['status']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm glass-card">
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
                                    <div class="card h-100 glass-card">
                                        <div class="card-body p-3 d-flex flex-column">
                                            <h6 class="fw-bold mb-1 text-truncate text-white" title="<?= htmlspecialchars($job['title']) ?>"><?= htmlspecialchars($job['title']) ?></h6>
                                            <div class="text-white-50 small mb-2"><?= htmlspecialchars($job['company_name']) ?></div>
                                            <div class="small text-white-50 mb-3">
                                                <span>📍 <?= htmlspecialchars($job['location']) ?></span>
                                            </div>
                                            <a href="browse.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-glass-secondary mt-auto w-100 rounded-pill">View Details</a>
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
            <div class="card shadow-sm glass-card mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-white"><i class="bi bi-bell-fill text-warning me-1"></i> Reminders & Tasks</h5>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold text-white"><i class="bi bi-calendar-event text-info me-2"></i>Technical Interview</h6>
                                <small class="text-danger">June 22</small>
                            </div>
                            <p class="mb-1 small text-white-50">Interview with TechCorp Solutions scheduled at 10:00 AM.</p>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold text-white"><i class="bi bi-file-earmark-person text-success me-2"></i>Update Resume</h6>
                                <small class="text-warning">Friday</small>
                            </div>
                            <p class="mb-1 small text-white-50">Submit your updated project summaries on your resume file.</p>
                        </div>
                        <div class="list-group-item px-0 py-3 border-0">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fw-bold text-white"><i class="bi bi-send text-info me-2"></i>Follow up on Meta</h6>
                                <small class="text-white-50">Next Week</small>
                            </div>
                            <p class="mb-1 small text-white-50">Send follow-up email regarding application status to Recruiter.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm glass-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2 text-white">Profile Completeness</h5>
                    <p class="small text-white-50">Keep your details fresh to attract top companies.</p>
                    <div class="progress mb-3 bg-dark" style="height: 8px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small">
                        <span class="text-white-50">85% Completed</span>
                        <a href="profile.php" class="text-info fw-bold text-decoration-none">Update Profile <i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>