<?php
function check_role($required_role) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /internship_tracker/auth/login.php");
        exit();
    }
    if ($_SESSION['role'] !== $required_role) {
        header("Location: /internship_tracker/auth/login.php");
        exit();
    }
}
?>