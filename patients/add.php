<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $join_date = $_POST['join_date'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // SQL Validation: Check if DOB is in the future
    $check = $pdo->prepare("SELECT IF(? > CURDATE(), 1, 0) as is_future");
    $check->execute([$dob]);
    
    if ($check->fetch()['is_future']) {
        echo "<div class='alert alert-danger'>Error: Date of Birth cannot be in the future!</div>";
    } else {
        $sql = "INSERT INTO patients (name, dob, join_date, phone, address) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $dob, $join_date, $phone, $address]);
        // Use relative path to redirect
        echo "<script>window.location.href='list.php';</script>";
    }
}
?>

<h2>Register New Patient</h2>
<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Date of Birth</label>
        <input type="date" name="dob" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Join Date (Required)</label>
        <input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control">
    </div>
    <button type="submit" class="btn btn-success">Save Patient</button>
</form>