<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('admin');
include '../includes/header.php';
include '../includes/navbar.php';

// ── Summary stats ──────────────────────────────────────────────────────────
$users = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            SUM(role='student')  AS students,
            SUM(role='company')  AS companies,
            SUM(role='company' AND approval_status='pending') AS pending_companies
     FROM users"));

$jobs = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            SUM(status='active') AS active,
            SUM(status='closed') AS closed
     FROM jobs"));

$apps = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            SUM(status='pending')  AS pending,
            SUM(status='accepted') AS accepted,
            SUM(status='rejected') AS rejected,
            SUM(status='reviewed') AS reviewed
     FROM applications"));

// ── Recent activity (5 of each) ────────────────────────────────────────────
$recent_users = mysqli_query($conn,
    "SELECT id, name, email, role, created_at FROM users
     ORDER BY created_at DESC LIMIT 5");

$recent_jobs = mysqli_query($conn,
    "SELECT j.id, j.title, j.status, j.created_at, u.name AS company_name, u.id AS company_id
     FROM jobs j JOIN users u ON j.company_id = u.id
     ORDER BY j.created_at DESC LIMIT 5");

$recent_apps = mysqli_query($conn,
    "SELECT a.id, a.status, a.applied_at, a.student_id,
            s.name AS student_name,
            j.title AS job_title,
            c.name AS company_name
     FROM applications a
     JOIN users s ON a.student_id = s.id
     JOIN jobs  j ON a.job_id     = j.id
     JOIN users c ON j.company_id = c.id
     ORDER BY a.applied_at DESC LIMIT 5");

$app_total = max((int)$apps['total'], 1);

// ── Internships by field ───────────────────────────────────────────────────
$by_field = mysqli_query($conn,
    "SELECT field, COUNT(*) AS cnt FROM jobs
     WHERE field IS NOT NULL AND field != ''
     GROUP BY field ORDER BY cnt DESC LIMIT 6");

function role_badge($role) {
    $map = ['student'=>'primary','company'=>'success','admin'=>'danger'];
    $c = $map[$role] ?? 'secondary';
    return "<span class='badge badge-uniform bg-$c'>".htmlspecialchars(ucfirst($role))."</span>";
}
function app_badge($status) {
    $map = ['pending'=>'secondary','reviewed'=>'info','accepted'=>'success','rejected'=>'danger'];
    $c = $map[$status] ?? 'secondary';
    return "<span class='badge badge-uniform bg-$c'>".htmlspecialchars(ucfirst($status))."</span>";
}
?>

<div class="container-lg mt-4 pb-5">

    <div class="d-flex align-items-baseline justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-white">Dashboard</h4>
            <p class="text-white-50 small mb-0 mt-1">
                Welcome back, <?= htmlspecialchars($_SESSION['name']) ?> &mdash;
                <?= date('l, d F Y') ?>
            </p>
        </div>
    </div>

    <div class="glass-hero mb-5">
        <div class="glass-hero-content row align-items-center justify-content-between">
            <div class="col-md-6 mb-4 mb-md-0" style="padding-left: 40px;">
                <h1 class="hero-title">Manage.<br>Oversee.<br>Optimize.</h1>
                <p class="hero-subtitle mt-3">Centralized control for your internship platform. Monitor user activity, manage job postings, and track application metrics in real-time.</p>
                <div class="d-flex gap-3 mt-4">
                    <a href="view_users.php" class="btn btn-glass-white rounded-pill">Manage Users</a>
                    <a href="manage_internships.php" class="btn btn-glass-secondary rounded-pill px-4 py-2"><i class="bi bi-briefcase"></i> View Jobs</a>
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

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg">
            <a href="view_users.php?role=student" class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-mortarboard-fill text-info"></i>
                </div>
                <div>
                    <div class="stat-value text-info"><?= (int)$users['students'] ?></div>
                    <div class="stat-label">Students</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg">
            <a href="view_users.php?role=company" class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-building-fill text-success"></i>
                </div>
                <div>
                    <div class="stat-value text-success"><?= (int)$users['companies'] ?></div>
                    <div class="stat-label">Companies</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg">
            <a href="manage_companies.php?status=pending" class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split text-warning"></i>
                </div>
                <div>
                    <div class="stat-value text-warning"><?= (int)$users['pending_companies'] ?></div>
                    <div class="stat-label">Pending Companies</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg">
            <a href="manage_internships.php" class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-briefcase-fill text-primary"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?= (int)$jobs['total'] ?></div>
                    <div class="stat-label">Internships</div>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg">
            <a href="view_applicants.php" class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-person-fill text-danger"></i>
                </div>
                <div>
                    <div class="stat-value text-danger"><?= (int)$apps['total'] ?></div>
                    <div class="stat-label">Applications</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="dash-card p-3 h-100 d-flex flex-column align-items-center">
                <p class="text-white-50 small fw-semibold mb-2 text-uppercase w-100" style="letter-spacing:.05em; font-size:.7rem">Job Postings Status</p>
                <div class="d-flex align-items-center justify-content-center my-auto" style="position: relative; height: 120px; width: 120px;">
                    <canvas id="jobStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dash-card p-3 h-100">
                <p class="text-white-50 small fw-semibold mb-2 text-uppercase" style="letter-spacing:.05em; font-size:.7rem">Applications by Status</p>
                <?php
                $statuses = [
                    ['label'=>'Pending',  'color'=>'secondary', 'val'=>(int)$apps['pending']],
                    ['label'=>'Reviewed', 'color'=>'info',      'val'=>(int)$apps['reviewed']],
                    ['label'=>'Accepted', 'color'=>'success',   'val'=>(int)$apps['accepted']],
                    ['label'=>'Rejected', 'color'=>'danger',    'val'=>(int)$apps['rejected']],
                ];
                foreach ($statuses as $s):
                    $pct = round($s['val'] / $app_total * 100);
                ?>
                <div class="prog-row">
                    <div class="prog-label">
                        <span><?= $s['label'] ?></span>
                        <span class="text-white-50"><?= $s['val'] ?> <span class="text-white-50" style="font-size:.72rem">(<?= $pct ?>%)</span></span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-<?= $s['color'] ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dash-card p-3 h-100">
                <p class="text-white-50 small fw-semibold mb-2 text-uppercase" style="letter-spacing:.05em; font-size:.7rem">Internships by Field</p>
                <?php
                $fields = [];
                while ($f = mysqli_fetch_assoc($by_field)) $fields[] = $f;
                $max_field = max(array_column($fields, 'cnt') ?: [1]);
                if (empty($fields)):
                ?>
                    <p class="text-white-50 small mb-0">No field data yet.</p>
                <?php else: foreach ($fields as $f):
                    $pct = round($f['cnt'] / $max_field * 100);
                ?>
                <div class="prog-row">
                    <div class="prog-label">
                        <span><?= htmlspecialchars($f['field']) ?></span>
                        <span class="text-white-50"><?= $f['cnt'] ?></span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar bg-info" style="width:<?= $pct ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold text-uppercase mb-3 mt-4" style="letter-spacing:.06em; font-size:.75rem; color:rgba(255,255,255,0.7)">Recent Activity</h6>
    <div class="row g-3">

        <div class="col-md-4">
            <div class="dash-card h-100">
                <div class="card-head">
                    <span><i class="bi bi-person-plus me-1 text-info"></i> New Registrations</span>
                    <a href="..." class="btn btn-sm btn-glass-white rounded-pill" style="padding: 0.25rem 0.75rem !important;">View all</a>
                </div>
                <?php
                $has = false;
                while ($u = mysqli_fetch_assoc($recent_users)):
                    $has = true;
                ?>
                <div class="act-item">
                    <div class="flex-grow-1 min-width-0">
                        <div class="act-name">
                            <a href="#" class="view-user-trigger text-decoration-none text-white fw-bold" data-user-id="<?= (int)$u['id'] ?>">
                                <?= htmlspecialchars($u['name']) ?>
                            </a>
                        </div>
                        <div class="act-sub"><?= htmlspecialchars($u['email']) ?></div>
                        <div class="act-time"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></div>
                    </div>
                    <?= role_badge($u['role']) ?>
                </div>
                <?php endwhile; ?>
                <?php if (!$has): ?>
                    <div class="act-item text-white-50 small">No users yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dash-card h-100">
                <div class="card-head">
                    <span><i class="bi bi-briefcase me-1 text-warning"></i> New Postings</span>
                    <a href="..." class="btn btn-sm btn-glass-white rounded-pill" style="padding: 0.25rem 0.75rem !important;">View all</a>
                </div>
                <?php
                $has = false;
                while ($j = mysqli_fetch_assoc($recent_jobs)):
                    $has = true;
                ?>
                <div class="act-item">
                    <div class="flex-grow-1 min-width-0">
                        <div class="act-name"><?= htmlspecialchars($j['title']) ?></div>
                        <div class="act-sub">
                            <a href="#" class="view-user-trigger text-decoration-none text-white-50" data-user-id="<?= (int)$j['company_id'] ?>">
                                <?= htmlspecialchars($j['company_name']) ?>
                            </a>
                        </div>
                        <div class="act-time"><?= date('d M Y, H:i', strtotime($j['created_at'])) ?></div>
                    </div>
                    <span class="badge badge-uniform bg-<?= $j['status']==='active'?'success':'secondary' ?>">
                        <?= ucfirst($j['status']) ?>
                    </span>
                </div>
                <?php endwhile; ?>
                <?php if (!$has): ?>
                    <div class="act-item text-white-50 small">No postings yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="dash-card h-100">
                <div class="card-head">
                    <span><i class="bi bi-file-earmark-text me-1 text-danger"></i> New Applications</span>
                    <a href="..." class="btn btn-sm btn-glass-white rounded-pill" style="padding: 0.25rem 0.75rem !important;">View all</a>
                </div>
                <?php
                $has = false;
                while ($a = mysqli_fetch_assoc($recent_apps)):
                    $has = true;
                ?>
                <div class="act-item">
                    <div class="flex-grow-1 min-width-0">
                        <div class="act-name">
                            <a href="#" class="view-user-trigger text-decoration-none text-white fw-bold" data-user-id="<?= (int)$a['student_id'] ?>">
                                <?= htmlspecialchars($a['student_name']) ?>
                            </a>
                        </div>
                        <div class="act-sub"><?= htmlspecialchars($a['job_title']) ?></div>
                        <div class="act-time"><?= date('d M Y, H:i', strtotime($a['applied_at'])) ?></div>
                    </div>
                    <?= app_badge($a['status']) ?>
                </div>
                <?php endwhile; ?>
                <?php if (!$has): ?>
                    <div class="act-item text-white-50 small">No applications yet.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('jobStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Closed'],
            datasets: [{
                data: [<?= (int)$jobs['active'] ?>, <?= (int)$jobs['closed'] ?>],
                backgroundColor: [
                    '#10b981', // Emerald green
                    'rgba(255, 255, 255, 0.2)'  // Slate/gray translucent
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '84%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { family: "'Inter', sans-serif", size: 12, weight: 'bold' },
                    bodyFont: { family: "'Inter', sans-serif", size: 11 },
                    padding: 8,
                    cornerRadius: 6,
                    displayColors: false
                }
            }
        },
        plugins: [{
            id: 'textCenter',
            beforeDraw: function(chart) {
                const width = chart.width,
                      height = chart.height,
                      ctx = chart.ctx;
                ctx.restore();
                
                // Draw Total Number
                ctx.font = "bold 1.35rem 'Inter', sans-serif";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#ffffff";
                const textTotal = "<?= (int)$jobs['total'] ?>";
                const totalX = Math.round((width - ctx.measureText(textTotal).width) / 2);
                const totalY = (height / 2) - 8;
                ctx.fillText(textTotal, totalX, totalY);
                
                // Draw "JOBS" Label
                ctx.font = "600 0.52rem 'Inter', sans-serif";
                ctx.fillStyle = "rgba(255, 255, 255, 0.55)";
                const textLabel = "TOTAL JOBS";
                const labelX = Math.round((width - ctx.measureText(textLabel).width) / 2);
                const labelY = (height / 2) + 8;
                ctx.fillText(textLabel, labelX, labelY);
                
                ctx.save();
            }
        }]
    });
});
</script>

<?php include '../includes/footer.php'; ?>