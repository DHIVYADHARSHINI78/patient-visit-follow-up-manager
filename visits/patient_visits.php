<?php
session_start();
require_once '../config/db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<h3>Error: No Patient ID specified.</h3>";
    echo "<a href='../patients/list.php'>Go back to Patient List</a>";
    exit;
}

$patient_id = $_GET['id'];


$pstmt = $pdo->prepare("SELECT name, dob, gender FROM patients WHERE id = ?");
$pstmt->execute([$patient_id]);
$patient = $pstmt->fetch();

if (!$patient) {
    echo "<h3>Invalid Patient ID</h3>";
    exit;
}


$vstmt = $pdo->prepare("
    SELECT 
        visit_date,
        consultation_fee,
        medicine_fee,
        next_followup,
        DATEDIFF(next_followup, CURDATE()) AS followup_days
    FROM visits
    WHERE patient_id = ?
    ORDER BY visit_date DESC
");
$vstmt->execute([$patient_id]);
$visits = $vstmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Visit History</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>

<h2>Patient Visit History</h2>

<p>
<b>Name:</b> <?= $patient['name'] ?> |
<b>Gender:</b> <?= $patient['gender'] ?> |
<b>DOB:</b> <?= $patient['dob'] ?>
</p>

<hr>

<?php if (count($visits) > 0): ?>
<table>
    <tr>
        <th>Visit Date</th>
        <th>Consultation Fee</th>
        <th>Medicine Fee</th>
        <th>Next Follow-up</th>
        <th>Follow-up Status</th>
    </tr>

    <?php foreach ($visits as $v): ?>
    <tr>
        <td><?= $v['visit_date'] ?></td>
        <td>₹<?= $v['consultation_fee'] ?></td>
        <td>₹<?= $v['medicine_fee'] ?></td>
        <td><?= $v['next_followup'] ?></td>
        <td>
            <?php
            if ($v['followup_days'] < 0) {
                echo "<span style='color:red'>Overdue</span>";
            } elseif ($v['followup_days'] == 0) {
                echo "<span style='color:orange'>Today</span>";
            } else {
                echo $v['followup_days'] . " days left";
            }
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php else: ?>
<p><b>No visit history found for this patient.</b></p>
<?php endif; ?>

<br>
<a href="../patients/list.php">⬅ Back to Patient List</a>

</body>
</html>
