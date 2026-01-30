<?php
require_once '../config/db.php';
include '../includes/header.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<div class='container mt-4'>Patient ID missing.</div>";
    exit;
}

$query = "SELECT v.*, 
          (SELECT COUNT(*) FROM visits WHERE patient_id = ?) as total_visits,
          (SELECT DATEDIFF(MAX(visit_date), MIN(visit_date)) FROM visits WHERE patient_id = ?) as treatment_span
          FROM visits v WHERE v.patient_id = ? ORDER BY v.visit_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$id, $id, $id]);
$visits = $stmt->fetchAll();

$total_v = $visits[0]['total_visits'] ?? 0;
$span = $visits[0]['treatment_span'] ?? 0;
?>

<div class="container mt-4">
    <h3 class="mb-1">Patient History</h3>
    
    <p class="text-muted mb-4">
        Total Visits: <strong><?= $total_v ?></strong> | 
        Treatment Span: <strong><?= $span ?> Days</strong>
    </p>

    <table class="table table-bordered">
        <thead class="bg-light">
            <tr>
                <th>Visit Date</th>
                <th>Fee Paid</th>
                <th>Timeline</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($visits as $v): ?>
            <tr>
                <td><?= $v['visit_date'] ?></td>
                <td>$<?= number_format($v['consultation_fee'], 2) ?></td>
                <td>
                    <?php 
                        $d1 = new DateTime($v['visit_date']);
                        $d2 = new DateTime();
                        echo $d1->diff($d2)->format('%a days ago');
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($visits)): ?>
                <tr><td colspan="3" class="text-center">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <a href="list.php" class="btn btn-sm btn-link mt-3 p-0 text-decoration-none">← Back to List</a>
</div>

<?php include '../includes/footer.php'; ?>