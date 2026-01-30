<?php
require_once '../config/db.php';
include '../includes/header.php';

// SQL handles grouping by Month and Year
$monthlyVisits = $pdo->query("
    SELECT DATE_FORMAT(visit_date, '%M %Y') AS month_name, 
           COUNT(visit_id) AS visit_count,
           SUM(consultation_fee + lab_fee) AS total_revenue
    FROM visits 
    GROUP BY YEAR(visit_date), MONTH(visit_date)
    ORDER BY visit_date DESC
")->fetchAll();

$monthlyJoins = $pdo->query("
    SELECT DATE_FORMAT(join_date, '%M %Y') AS month_name, 
           COUNT(patient_id) AS join_count
    FROM patients
    GROUP BY YEAR(join_date), MONTH(join_date)
    ORDER BY join_date DESC
")->fetchAll();
?>

<h2>Healthcare Monthly Summary</h2>
<div class="row">
    <div class="col-md-6">
        <h4>Visits & Revenue</h4>
        <table class="table table-bordered">
            <tr><th>Month</th><th>Visits</th><th>Revenue</th></tr>
            <?php foreach($monthlyVisits as $row): ?>
                <tr><td><?= $row['month_name'] ?></td><td><?= $row['visit_count'] ?></td><td>$<?= $row['total_revenue'] ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="col-md-6">
        <h4>New Patient Registrations</h4>
        <table class="table table-bordered">
            <tr><th>Month</th><th>New Patients</th></tr>
            <?php foreach($monthlyJoins as $row): ?>
                <tr><td><?= $row['month_name'] ?></td><td><?= $row['join_count'] ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>