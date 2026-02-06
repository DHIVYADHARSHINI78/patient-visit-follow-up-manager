<?php
require_once __DIR__ . '/../config/init.php';
require_once '../config/db.php';
include '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_id = $_POST['patient_id'];
    $v_date = $_POST['visit_date'];
    $c_fee = $_POST['consultation_fee'];
    $l_fee = $_POST['lab_fee'];

    // Insert new visit
    $sql = "INSERT INTO visits (patient_id, visit_date, consultation_fee, lab_fee, follow_up_due) 
            VALUES (?, ?, ?, ?, DATE_ADD(?, INTERVAL 7 DAY))";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$p_id, $v_date, $c_fee, $l_fee, $v_date]);

    // Use header redirect instead of JS for better reliability
    header("Location: list.php");
    exit();
}

$patients = $pdo->query("SELECT patient_id, name FROM patients ORDER BY name ASC")->fetchAll();
?>

<div class="container-md mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h3">Add New Visit</h2>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Back to List</a>
            </div>

            <form method="POST" class="card p-3 p-md-4 shadow-sm border-0">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="" disabled selected>Choose a patient...</option>
                        <?php foreach($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Visit Date</label>
                    <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Consultation Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="consultation_fee" class="form-control" step="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold">Lab Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="lab_fee" class="form-control" step="0.01" value="0.00">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-block">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-save"></i> Save Visit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>