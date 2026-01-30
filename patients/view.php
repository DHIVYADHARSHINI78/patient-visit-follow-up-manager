<?php
require_once __DIR__ . '/../config/init.php';
require_once '../config/db.php';
include '../includes/header.php';

$id = $_GET['id'] ?? 0;

$sql = "SELECT *, 
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age,
        DATEDIFF(CURDATE(), (SELECT MAX(visit_date) FROM visits WHERE patient_id = p.patient_id)) AS days_since_last,
        (SELECT follow_up_due FROM visits WHERE patient_id = p.patient_id ORDER BY visit_date DESC LIMIT 1) AS next_due,
        CASE 
            WHEN (SELECT follow_up_due FROM visits WHERE patient_id = p.patient_id ORDER BY visit_date DESC LIMIT 1) < CURDATE() THEN 'OVERDUE'
            ELSE 'ON TRACK'
        END AS follow_up_status
        FROM patients p WHERE patient_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) { die("Patient not found."); }
?>

<h2>Patient Profile: <?= $patient['name'] ?></h2>
<div class="card p-4 shadow-sm">
    <div class="row">
        <div class="col-md-6">
            <p><strong>Age:</strong> <?= $patient['age'] ?> years</p>
            <p><strong>DOB:</strong> <?= $patient['dob'] ?></p>
            <p><strong>Joined:</strong> <?= $patient['join_date'] ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Days Since Last Visit:</strong> <?= $patient['days_since_last'] ?? 'No visits' ?></p>
            <p><strong>Next Follow-up:</strong> <?= $patient['next_due'] ?? 'N/A' ?></p>
            <p><strong>Status:</strong> 
                <span class="badge <?= $patient['follow_up_status'] == 'OVERDUE' ? 'bg-danger' : 'bg-success' ?>">
                    <?= $patient['follow_up_status'] ?>
                </span>
            </p>
        </div>
    </div>
    <hr>
    <a href="edit.php?id=<?= $patient['patient_id'] ?>" class="btn btn-warning">Edit Details</a>
    <a href="../visits/patient_visits.php?id=<?= $patient['patient_id'] ?>" class="btn btn-info">View Visit History</a>
</div>