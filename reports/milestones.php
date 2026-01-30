<?php
require_once '../config/db.php';
include '../includes/header.php';

$sql = "SELECT name, dob, 
        YEAR(CURDATE()) - YEAR(dob) as turning_age
        FROM patients
        WHERE YEAR(CURDATE()) - YEAR(dob) IN (40, 50, 60)";

$stmt = $pdo->query($sql);
?>

<h2>Milestone Birthdays (This Year)</h2>
<ul class="list-group">
    <?php while($row = $stmt->fetch()): ?>
        <li class="list-group-item">
            <?= $row['name'] ?> is turning <strong><?= $row['turning_age'] ?></strong> on <?= $row['dob'] ?>
        </li>
    <?php endwhile; ?>
</ul>