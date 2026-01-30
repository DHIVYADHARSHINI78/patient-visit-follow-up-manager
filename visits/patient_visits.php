<?php
require_once __DIR__ . '/../config/init.php';
require_once '../config/db.php';
include '../includes/header.php';

// Validate patient_id
$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo "<div class='alert alert-danger'>No patient selected.</div>";
    exit;
}

// Fetch patient info
$patient = $pdo->prepare("SELECT name, dob FROM patients WHERE patient_id = ?");
$patient->execute([$patient_id]);
$patient = $patient->fetch();

if (!$patient) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    exit;
}

// Fetch all visits
$sql = "
SELECT 
    v.visit_id,
    v.visit_date,
    v.consultation_fee,
    v.lab_fee,
    v.follow_up_due,
    DATEDIFF(CURDATE(), v.visit_date) AS days_since_visit,
    (v.consultation_fee + v.lab_fee) AS total_bill,
    CASE 
        WHEN v.follow_up_due < CURDATE() THEN 'Overdue'
        WHEN v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
        ELSE 'Scheduled'
    END AS visit_status
FROM visits v
WHERE v.patient_id = ?
ORDER BY v.visit_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$patient_id]);
$visits = $stmt->fetchAll();

// Visit summary
$summary_sql = "
SELECT 
    COUNT(*) AS total_visits,
    MIN(visit_date) AS first_visit,
    MAX(visit_date) AS last_visit,
    DATEDIFF(MAX(visit_date), MIN(visit_date)) AS days_between_visits,
    MIN(follow_up_due) AS next_follow_up
FROM visits
WHERE patient_id = ?
";
$summary_stmt = $pdo->prepare($summary_sql);
$summary_stmt->execute([$patient_id]);
$summary = $summary_stmt->fetch();
?>

<div class="container mt-4">
    <h2>Visit History: <?= htmlspecialchars($patient['name']) ?></h2>
    <p>DOB: <?= htmlspecialchars($patient['dob']) ?></p>

    <div class="card p-3 mb-4 shadow-sm">
        <h5>Summary</h5>
        <ul>
            <li>Total Visits: <?= $summary['total_visits'] ?></li>
            <li>First Visit: <?= $summary['first_visit'] ?></li>
            <li>Last Visit: <?= $summary['last_visit'] ?></li>
            <li>Days Between First & Last Visit: <?= $summary['days_between_visits'] ?></li>
            <li>Next Follow-Up: <?= $summary['next_follow_up'] ?></li>
        </ul>
    </div>

    <table class="table table-bordered table-hover shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>Visit Date</th>
                <th>Consultation ($)</th>
                <th>Lab ($)</th>
                <th>Total ($)</th>
                <th>Follow-Up Due</th>
                <th>Days Since Visit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($visits): ?>
                <?php foreach ($visits as $v): ?>
                    <tr>
                        <td><?= $v['visit_date'] ?></td>
                        <td><?= number_format($v['consultation_fee'], 2) ?></td>
                        <td><?= number_format($v['lab_fee'], 2) ?></td>
                        <td class="fw-bold"><?= number_format($v['total_bill'], 2) ?></td>
                        <td><?= $v['follow_up_due'] ?></td>
                        <td><?= $v['days_since_visit'] ?></td>
                        <td>
                            <span class="badge <?= $v['visit_status'] == 'Overdue' ? 'bg-danger' : ($v['visit_status'] == 'Upcoming' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                <?= $v['visit_status'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center py-4">No visits found for this patient.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="add.php" class="btn btn-primary mt-3">Add New Visit</a>
    <a href="list.php" class="btn btn-secondary mt-3">Back to All Visits</a>
</div>

<?php include '../includes/footer.php'; ?>
