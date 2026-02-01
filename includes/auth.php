<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current script name (e.g., /project/index.php)
$current_script = $_SERVER['SCRIPT_NAME'];

// If the user is NOT logged in AND they are NOT on the login page, boot them out
if (!isset($_SESSION['user_id'])) {
    if (basename($current_script) !== 'login.php') {
        header("Location: /patient-visit-follow-up-manager/login.php");
        exit();
    }
}
?>