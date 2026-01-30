<?php
require_once __DIR__ . '/../config/init.php';
require_once '../config/db.php';
include '../includes/header.php';

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $phone = $_POST['phone']; 
    $address = $_POST['address']; 
    $join_date = $_POST['join_date'];


    $check = $pdo->prepare("SELECT IF(? > CURDATE(), 1, 0) as is_future");
    $check->execute([$dob]);
    
    if ($check->fetch()['is_future']) {
        $error = "Error: Date of Birth cannot be in the future.";
    } else {
      
        $sql = "UPDATE patients 
                SET name = ?, dob = ?, phone = ?, address = ?, join_date = ? 
                WHERE patient_id = ?";
        
        $pdo->prepare($sql)->execute([$name, $dob, $phone, $address, $join_date, $id]);
        
        echo "<script>window.location='list.php';</script>";
        exit;
    }
}


$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    die("Patient not found.");
}
?>

<div class="container mt-4">
    <h2 class="fw-bold mb-4">Edit Patient Profile</h2>
    
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST" class="card p-4 shadow-sm border-0">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($p['phone']) ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Date of Birth</label>
                <input type="date" name="dob" class="form-control" value="<?= $p['dob'] ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Joining Date</label>
                <input type="date" name="join_date" class="form-control" value="<?= $p['join_date'] ?>" required>
            </div>

            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Address</label>
                <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($p['address']) ?></textarea>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success px-4">Save Changes</button>
            <a href="list.php" class="btn btn-secondary px-4">Cancel</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>