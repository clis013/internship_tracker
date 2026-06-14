<?php
session_start();
include '../config/db_connect.php';
include '../includes/session_check.php';
check_role('student');
include '../includes/header.php';
include '../includes/navbar.php';

$student_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT a.id, a.status, a.applied_at, a.cover_letter,
        j.title, j.location, j.field, u.name AS company_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.company_id = u.id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$apps = mysqli_stmt_get_result($stmt);

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
    <h3 class="mb-4">My Applications</h3>

    <?php if (mysqli_num_rows($apps) === 0): ?>
        <div class="alert alert-info">
            You haven't applied to any internships yet.
            <a href="browse.php">Browse internships</a> to get started.
        </div>
    <?php else: ?>
        <div class="accordion" id="appAccordion">
            <?php $i = 0; while ($app = mysqli_fetch_assoc($apps)): $i++; ?>
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#app<?= $i ?>">
                            <div class="d-flex justify-content-between w-100 me-3">
                                <span><?= htmlspecialchars($app['title']) ?> — <?= htmlspecialchars($app['company_name']) ?></span>
                                <span><?= status_badge($app['status']) ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="app<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#appAccordion">
                        <div class="accordion-body">
                            <p class="mb-1"><strong>Applied on:</strong> <?= htmlspecialchars(date('d M Y, H:i', strtotime($app['applied_at']))) ?></p>
                            <?php if ($app['location']): ?>
                                <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($app['location']) ?></p>
                            <?php endif; ?>
                            <?php if ($app['field']): ?>
                                <p class="mb-1"><strong>Field:</strong> <?= htmlspecialchars($app['field']) ?></p>
                            <?php endif; ?>
                            <hr>
                            <p class="mb-1"><strong>Your Cover Letter:</strong></p>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>