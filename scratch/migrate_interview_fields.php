<?php
include 'config/db_connect.php';

// Add interview_date if not exists
$res = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'interview_date'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN interview_date VARCHAR(50) NULL AFTER status");
}

// Add interview_time if not exists
$res = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'interview_time'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN interview_time VARCHAR(50) NULL AFTER interview_date");
}

// Add interview_venue if not exists
$res = mysqli_query($conn, "SHOW COLUMNS FROM applications LIKE 'interview_venue'");
if (mysqli_num_rows($res) === 0) {
    mysqli_query($conn, "ALTER TABLE applications ADD COLUMN interview_venue VARCHAR(255) NULL AFTER interview_time");
}

echo "Migration complete. Current columns:\n";
$res = mysqli_query($conn, "DESCRIBE applications");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
