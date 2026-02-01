<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$current_script = $_SERVER['SCRIPT_NAME'];


if (!isset($_SESSION['user_id'])) {
    if (basename($current_script) !== 'login.php') {
        header("Location: /patient-visit-follow-up-manager/login.php");
        exit();
    }
}
?>