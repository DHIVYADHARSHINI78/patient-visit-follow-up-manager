<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $join_date = $_POST['join_date'];
    $phone = $_POST['phone'];
    $address = $_POST['address']; 

   
    $check = $pdo->prepare("SELECT IF(? > CURDATE(), 1, 0) as is_future");
    $check->execute([$dob]);
    
    if ($check->fetch()['is_future']) {
        $error = "Error: Date of Birth cannot be in the future!";
    } else {
        $sql = "INSERT INTO patients (name, dob, join_date, phone, address) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $dob, $join_date, $phone, $address]);
        
        echo "<script>window.location.href='list.php';</script>";
        exit;
    }
}
?>

<div class="container mt-4">
    <h2 class="fw-bold mb-4">Register New Patient</h2>

    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST" class="card p-4 shadow-sm border-0">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter patient name" required>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. 9876543210">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Date of Birth</label>
                <input type="date" name="dob" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Join Date</label>
                <input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Address</label>
                <textarea name="address" class="form-control" rows="3" placeholder="Enter complete residential address" required></textarea>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary px-5">Save Patient</button>
            <a href="list.php" class="btn btn-light px-4">Back to List</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>