<?php
$dir = 'c:/xampp/htdocs/internship_tracker/uploads/resumes';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
file_put_contents($dir . '/test_resume.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << >> /Contents 4 0 R >>\nendobj\n4 0 obj\n<< /Length 50 >>\nstream\nBT /F1 24 Tf 100 700 Td (This is a dummy test resume PDF) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000220 00000 n\ntrailer\n<< /Size 5 >>\nstartxref\n320\n%%EOF");

include 'c:/xampp/htdocs/internship_tracker/config/db_connect.php';

// 1. Update all existing student users to have a default profile resume
$stmt1 = mysqli_prepare($conn, "UPDATE users SET resume = 'uploads/resumes/test_resume.pdf' WHERE role = 'student' AND (resume IS NULL OR resume = '')");
if (mysqli_stmt_execute($stmt1)) {
    echo "Successfully updated " . mysqli_affected_rows($conn) . " students with default resumes.\n";
} else {
    echo "Failed to update students.\n";
}

// 2. Update all existing applications to have a resume path
$stmt2 = mysqli_prepare($conn, "UPDATE applications SET resume = 'uploads/resumes/test_resume.pdf' WHERE (resume IS NULL OR resume = '')");
if (mysqli_stmt_execute($stmt2)) {
    echo "Successfully updated " . mysqli_affected_rows($conn) . " applications with resumes.\n";
} else {
    echo "Failed to update applications.\n";
}
?>
