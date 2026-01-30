<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /patient-visit-follow-up-manager/login.php");
    exit();
}
