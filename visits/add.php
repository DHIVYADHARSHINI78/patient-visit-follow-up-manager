<?php
require_once __DIR__ . '/../config/init.php';

require_once '../config/db.php';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_id = $_POST['patient_id'];
    $v_date = $_POST['visit_date'];
    $c_fee = $_POST['consultation_fee'];
    $l_fee = $_POST['lab_fee'];

   
    $sql = "INSERT INTO visits (patient_id, visit_date, consultation_fee, lab_fee, follow_up_due) 
            VALUES (?, ?, ?, ?, DATE_ADD(?, INTERVAL 7 DAY))";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$p_id, $v_date, $c_fee, $l_fee, $v_date]);
    
    echo "<script>window.location.href='list.php';</script>";
}

$patients = $pdo->query("SELECT patient_id, name FROM patients")->fetchAll();
?>

<h2>Add New Visit</h2>
<form method="POST" class="card p-4 shadow-sm">
    <div class="mb-3">
        <label>Select Patient</label>
        <select name="patient_id" class="form-control" required>
            <?php foreach($patients as $p): ?>
                <option value="<?= $p['patient_id'] ?>"><?= $p['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Visit Date</label>
        <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Consultation Fee</label>
            <input type="number" name="consultation_fee" class="form-control" step="0.01" required>
        </div>
        <div class="col-md-6 mb-3">
            <label>Lab Fee</label>
            <input type="number" name="lab_fee" class="form-control" step="0.01" value="0">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Save Visit</button>
</form>