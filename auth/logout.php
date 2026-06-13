<?php
session_start();
session_destroy();
header("Location: /internship_tracker/auth/login.php");
exit();
?>