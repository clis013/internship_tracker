<?php
include 'config/db_connect.php';
$res = mysqli_query($conn, "DESCRIBE applications");
while ($row = mysqli_fetch_assoc($res)) {
    var_dump($row);
}
