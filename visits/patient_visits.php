<?php
require_once '../config/db.php';
include '../includes/header.php';


if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='container mt-5 text-center'>
            <div class='alert alert-danger shadow'>
                <h4 class='fw-bold'>Error: No Patient ID specified.</h4>
                <p>To view history, go to the <a href='../patients/list.php' class='alert-link'>Patient List</a> and click 'History'.</p>
            </div>
          </div>";
    include '../includes/footer.php';
    exit;
}

$id = $_GET['id'];


$sql = "SELECT p.name, p.patient_id,
        COUNT(v.visit_id) as total_visits,
        DATEDIFF(MAX(v.visit_date), MIN(v.visit_date)) as treatment_span_days,
        DATEDIFF(CURDATE(), MAX(v.visit_date)) as days_since_last_visit
        FROM patients p
        LEFT JOIN visits v ON p.patient_id = v.patient_id
        WHERE p.patient_id = ?
        GROUP BY p.patient_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$stats = $stmt->fetch();


if (!$stats) {
    echo "<div class='container mt-5'><div class='alert alert-warning'>Patient record not found.</div></div>";
    include '../includes/footer.php';
    exit;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="../patients/list.php">Patients</a></li>
                    <li class="breadcrumb-item active">Visit History</li>
                </ol>
            </nav>
            <h2 class="fw-bold">History: <?= htmlspecialchars($stats['name']) ?></h2>
        </div>
        <a href="../patients/list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <small class="text-muted fw-bold text-uppercase">Total Visits</small>
                <h2 class="fw-bold text-primary"><?= $stats['total_visits'] ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <small class="text-muted fw-bold text-uppercase">Treatment Span</small>
                <h2 class="fw-bold text-success"><?= $stats['treatment_span_days'] ?? 0 ?> <small class="fs-6">Days</small></h2>
                <small class="text-muted">First to Last Visit</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center p-3">
                <small class="text-muted fw-bold text-uppercase">Days Since Last Visit</small>
                <h2 class="fw-bold <?= ($stats['days_since_last_visit'] > 180) ? 'text-danger' : 'text-dark' ?>">
                    <?= $stats['days_since_last_visit'] ?? 'No visits' ?>
                </h2>
                <?php if($stats['days_since_last_visit'] > 180): ?>
                    <small class="text-danger fw-bold">INACTIVE (180+ Days)</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text"></i> Detailed Visit Log</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visit Date</th>
                        <th>Follow-up Due</th>
                        <th>Total Billing</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                   
                    $v_stmt = $pdo->prepare("SELECT *, (consultation_fee + lab_fee) as total FROM visits WHERE patient_id = ? ORDER BY visit_date DESC");
                    $v_stmt->execute([$id]);
                    
                    if ($v_stmt->rowCount() > 0):
                        while($v = $v_stmt->fetch()):
                    ?>
                    <tr>
                        <td class="fw-bold"><?= $v['visit_date'] ?></td>
                        <td><?= $v['follow_up_due'] ?></td>
                        <td class="text-primary fw-bold">$<?= number_format($v['total'], 2) ?></td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">No visit records found for this patient.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>