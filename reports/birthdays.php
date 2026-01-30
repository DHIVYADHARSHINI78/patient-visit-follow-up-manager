<?php
require_once __DIR__ . '/../config/init.php';

require_once '../config/db.php';
include '../includes/header.php';


$upcomingBirthdays = $pdo->query("
    SELECT name, dob, 
    DATE_FORMAT(dob, '%d %M') AS birthday_date,
    TIMESTAMPDIFF(YEAR, dob, CURDATE()) + 1 AS turning_age
    FROM patients 
    WHERE DATE_ADD(dob, INTERVAL TIMESTAMPDIFF(YEAR, dob, CURDATE()) + 1 YEAR) 
          BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchAll();


$milestones = $pdo->query("
    SELECT name, dob, 
    (YEAR(CURDATE()) - YEAR(dob)) AS reaching_age
    FROM patients 
    WHERE (YEAR(CURDATE()) - YEAR(dob)) IN (40, 50, 60)
    ORDER BY reaching_age ASC
")->fetchAll();
?>

<div class="container mt-4">
    <h2 class="mb-4 text-center">Birthday & Milestone Report</h2>

    <div class="card border-info shadow-sm mb-5">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">🎂 Birthdays in the Next 30 Days</h4>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>Patient Name</th><th>Celebration Date</th><th>Turning Age</th></tr>
                </thead>
                <tbody>
                    <?php if (count($upcomingBirthdays) > 0): ?>
                        <?php foreach($upcomingBirthdays as $row): ?>
                            <tr>
                                <td><strong><?= $row['name'] ?></strong></td>
                                <td><?= $row['birthday_date'] ?></td>
                                <td><span class="badge bg-primary"><?= $row['turning_age'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No birthdays in the next 30 days.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-dark shadow-sm">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">🌟 Milestone Ages This Year (40, 50, 60)</h4>
        </div>
        <div class="card-body">
            <p class="text-muted small">These patients reach a significant milestone age during the current year (<?= date('Y') ?>).</p>
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>Patient Name</th><th>Original DOB</th><th>Milestone Age</th></tr>
                </thead>
                <tbody>
                    <?php if (count($milestones) > 0): ?>
                        <?php foreach($milestones as $row): ?>
                            <tr>
                                <td><strong><?= $row['name'] ?></strong></td>
                                <td><?= $row['dob'] ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark fs-6">
                                        Turning <?= $row['reaching_age'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No milestone birthdays this year.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>