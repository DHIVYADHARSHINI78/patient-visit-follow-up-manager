<?php
require_once '../config/db.php';
include '../includes/header.php';


$upcoming_sql = "SELECT p.name, v.follow_up_due, DATEDIFF(v.follow_up_due, CURDATE()) as days_until
                 FROM visits v
                 JOIN patients p ON v.patient_id = p.patient_id
                 WHERE v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY v.follow_up_due ASC";
$upcoming_stmt = $pdo->query($upcoming_sql);


$overdue_sql = "SELECT p.name, v.follow_up_due, DATEDIFF(CURDATE(), v.follow_up_due) as days_past
                FROM visits v
                JOIN patients p ON v.patient_id = p.patient_id
                WHERE v.follow_up_due < CURDATE()
                ORDER BY v.follow_up_due DESC";
$overdue_stmt = $pdo->query($overdue_sql);


$missed_sql = "SELECT p.name, v.follow_up_due
               FROM visits v
               JOIN patients p ON v.patient_id = p.patient_id
               WHERE v.follow_up_due < CURDATE()
               AND NOT EXISTS (
                   SELECT 1 FROM visits v2 
                   WHERE v2.patient_id = v.patient_id 
                   AND v2.visit_date > v.follow_up_due
               )
               GROUP BY p.patient_id";
$missed_stmt = $pdo->query($missed_sql);
?>

<div class="container mt-4">
    <h2 class="mb-4 text-center">Follow-Up Analysis Report</h2>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card border-primary shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">📅 Upcoming Follow-ups (Next 7 Days)</h4>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Patient Name</th><th>Due Date</th><th>Time Remaining</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $upcoming_stmt->fetch()): ?>
                            <tr>
                                <td><strong><?= $row['name'] ?></strong></td>
                                <td><?= $row['follow_up_due'] ?></td>
                                <td class="text-primary fw-bold">In <?= $row['days_until'] ?> days</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">⚠️ Overdue Follow-ups</h4>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Patient Name</th><th>Due Date</th><th>Delay Status</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $overdue_stmt->fetch()): ?>
                            <tr>
                                <td><strong><?= $row['name'] ?></strong></td>
                                <td><?= $row['follow_up_due'] ?></td>
                                <td class="text-danger fw-bold"><?= $row['days_past'] ?> days overdue</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card border-warning shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">🚫 Missed Follow-ups (No Return Visit)</h4>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr><th>Patient Name</th><th>Last Known Due Date</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $missed_stmt->fetch()): ?>
                            <tr>
                                <td><strong><?= $row['name'] ?></strong></td>
                                <td><?= $row['follow_up_due'] ?></td>
                                <td><span class="badge bg-secondary">No Recent Visit</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>