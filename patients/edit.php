<?php
require_once '../config/db.php';
include '../includes/header.php';

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];

    // SQL Validation: Check if the new DOB is in the future
    $check = $pdo->prepare("SELECT IF(? > CURDATE(), 1, 0) as is_future");
    $check->execute([$dob]);
    
    if ($check->fetch()['is_future']) {
        $error = "Error: Date of Birth cannot be in the future.";
    } else {
        $sql = "UPDATE patients SET name = ?, dob = ? WHERE patient_id = ?";
        $pdo->prepare($sql)->execute([$name, $dob, $id]);
        echo "<script>window.location='list.php';</script>";
    }
}

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
?>

<h2>Edit Patient Info</h2>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="POST" class="card p-4">
    <div class="mb-3">
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= $p['name'] ?>" required>
    </div>
    <div class="mb-3">
        <label>Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="<?= $p['dob'] ?>" required>
    </div>
    <button type="submit" class="btn btn-success">Update Patient</button>
    <a href="list.php" class="btn btn-secondary">Cancel</a>
</form>