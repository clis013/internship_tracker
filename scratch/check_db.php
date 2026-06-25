<?php
include 'config/db_connect.php';

// First, migrate any existing 'interviewed' or empty status rows to 'interview'
mysqli_query($conn, "UPDATE applications SET status = 'interview' WHERE status = 'interviewed' OR status = ''");

// Execute the ALTER TABLE query to change 'interviewed' to 'interview' in the ENUM definition
$alter_query = "ALTER TABLE applications MODIFY COLUMN status ENUM('pending', 'reviewed', 'interview', 'accepted', 'rejected') DEFAULT 'pending'";
if (mysqli_query($conn, $alter_query)) {
    echo "Database alter succeeded.\n";
} else {
    echo "Database alter failed: " . mysqli_error($conn) . "\n";
}

$res = mysqli_query($conn, "DESCRIBE applications");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
