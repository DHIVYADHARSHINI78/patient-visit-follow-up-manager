<?php
session_start();
require_once 'config/db.php';

// 1. Security Check: Redirect to login if session is not set
if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$p_id = $_SESSION['patient_id'];

try {
    // 2. Comprehensive Patient Data Fetch (Including Age and Follow-up Status)
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
    $stmt->execute([$p_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) { 
        die("Patient record not found."); 
    }

    // 3. Fetch Full Visit History
    $visit_stmt = $pdo->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_id DESC");
    $visit_stmt->execute([$p_id]);
    $visits = $visit_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - <?= htmlspecialchars($patient['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .profile-icon { width: 80px; height: 80px; font-size: 2rem; }
        .card { border: none; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-person-circle"></i> Patient Portal</span>
        <div class="d-flex align-items-center">
            <span class="text-white me-3 small d-none d-md-block">Welcome, <?= htmlspecialchars($patient['name']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm rounded-4">
                <div class="card-body text-center p-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 profile-icon">
                        <?= strtoupper(substr($patient['name'], 0, 1)) ?>
                    </div>
                    <h4 class="fw-bold"><?= htmlspecialchars($patient['name']) ?></h4>
                   
                    <hr>
                    <div class="text-start">
                        <p class="mb-2"><strong>Age:</strong> <?= $patient['age'] ?> years</p>
                        <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                        <p class="mb-2"><strong>Next Due:</strong> <?= $patient['next_due'] ?? 'N/A' ?></p>
                        <p class="mb-0"><strong>Status:</strong> 
                            <span class="badge <?= $patient['follow_up_status'] == 'OVERDUE' ? 'bg-danger' : 'bg-success' ?>">
                                <?= $patient['follow_up_status'] ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-clock-history text-primary"></i> Medical Visit History</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Visit Date</th>
            <th>Clinical Notes / Diagnosis</th>
            <th>Follow-up Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($visits) > 0): ?>
            <?php foreach ($visits as $v): ?>
            <tr>
                <td class="fw-bold"><?= date('d M Y', strtotime($v['visit_date'])) ?></td>
                
                <td>
                    <?php 
                        // Data irundha display pannum, illana "No notes" nu kaatum
                        if (!empty($v['diagnosis'])) {
                            echo htmlspecialchars($v['diagnosis']);
                        } elseif (!empty($v['reason'])) {
                            echo htmlspecialchars($v['reason']);
                        } else {
                            echo '<span class="text-muted italic">Routine Checkup</span>';
                        }
                    ?>
                </td>
                
                <td>
                    <?php if ($v['follow_up_due']): ?>
                        <span class="badge bg-light text-dark border">
                            <?= date('d M Y', strtotime($v['follow_up_due'])) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center text-muted p-4">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-4 text-muted">
    <p class="small">&copy; 2026 Clinic Pro Management System</p>
</footer>

</body>
</html>