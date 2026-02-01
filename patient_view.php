<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$p_id = $_SESSION['patient_id'];

try {
    // Comprehensive Patient Data Fetch
    $sql = "SELECT *, 
            TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age,
            (SELECT COUNT(*) FROM visits WHERE patient_id = p.patient_id) as total_visits,
            (SELECT follow_up_due FROM visits WHERE patient_id = p.patient_id ORDER BY visit_date DESC LIMIT 1) AS next_due,
            CASE 
                WHEN (SELECT follow_up_due FROM visits WHERE patient_id = p.patient_id ORDER BY visit_date DESC LIMIT 1) < CURDATE() THEN 'OVERDUE'
                ELSE 'ON TRACK'
            END AS follow_up_status
            FROM patients p WHERE patient_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$p_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) { die("Patient record not found."); }

    // Fetch Full Visit History
    $visit_stmt = $pdo->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_date DESC");
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
    <title>Patient Portal | <?= htmlspecialchars($patient['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .navbar { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        /* Dashboard Cards */
        .stat-card { border: none; border-radius: 16px; transition: all 0.3s ease; border: 1px solid #e2e8f0; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        .profile-section { background: white; border-radius: 20px; border: 1px solid #e2e8f0; }
        .profile-avatar { 
            width: 70px; height: 70px; 
            background: #e0e7ff; color: #4338ca; 
            font-size: 1.5rem; font-weight: 700; 
            display: flex; align-items: center; justify-content: center; border-radius: 50%;
        }

        .status-badge { padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Table Styling */
        .table-container { background: white; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
        .table thead th { background: #f1f5f9; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; color: #64748b; padding: 15px; border: none; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        
        .note-bubble { background: #f8fafc; padding: 8px 12px; border-radius: 8px; font-size: 0.9rem; color: #475569; display: inline-block; border-left: 3px solid #cbd5e1; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark py-3 shadow-sm mb-4">
    <div class="container d-flex justify-content-between">
        <span class="navbar-brand fw-bold fs-4"><i class="bi bi-shield-plus me-2"></i>Clinic Pro</span>
        <a href="logout.php" class="btn btn-light btn-sm fw-bold px-3 rounded-pill">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-1">Welcome back, <?= explode(' ', htmlspecialchars($patient['name']))[0] ?>!</h2>
            <p class="text-muted">Here is your medical overview and history.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3"><i class="bi bi-calendar-check text-primary fs-4"></i></div>
                    <div><small class="text-muted d-block">Next Follow-up</small><strong><?= $patient['next_due'] ?? 'None Set' ?></strong></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3"><i class="bi bi-clipboard2-pulse text-success fs-4"></i></div>
                    <div><small class="text-muted d-block">Total Visits</small><strong><?= $patient['total_visits'] ?> Records</strong></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3"><i class="bi bi-person-badge text-info fs-4"></i></div>
                    <div><small class="text-muted d-block">Current Age</small><strong><?= $patient['age'] ?> Years</strong></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-3 <?= $patient['follow_up_status'] == 'OVERDUE' ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 p-3 me-3">
                        <i class="bi bi-activity <?= $patient['follow_up_status'] == 'OVERDUE' ? 'text-danger' : 'text-success' ?> fs-4"></i>
                    </div>
                    <div><small class="text-muted d-block">Account Status</small><strong class="<?= $patient['follow_up_status'] == 'OVERDUE' ? 'text-danger' : 'text-success' ?>"><?= $patient['follow_up_status'] ?></strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="profile-section p-4 text-center">
                <div class="profile-avatar mx-auto mb-3"><?= strtoupper(substr($patient['name'], 0, 1)) ?></div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($patient['name']) ?></h5>
                <p class="text-muted small mb-3">ID: #PAT-00<?= $patient['patient_id'] ?></p>
                
                <div class="text-start mt-4">
                    <div class="mb-3 d-flex justify-content-between">
                        <span class="text-muted small">Phone</span>
                        <span class="fw-semibold small"><?= htmlspecialchars($patient['phone']) ?></span>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <span class="text-muted small">Date of Birth</span>
                        <span class="fw-semibold small"><?= date('d M Y', strtotime($patient['dob'])) ?></span>
                    </div>
                    <div class="mb-0 d-flex justify-content-between">
                        <span class="text-muted small">Gender</span>
                        <span class="fw-semibold small"><?= $patient['gender'] ?? 'Not Specified' ?></span>
                    </div>
                </div>
        
            </div>
        </div>

        <div class="col-lg-8">
            <div class="table-container shadow-sm">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Medical Visit History</h5>
                    <i class="bi bi-filter text-muted"></i>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Visit Date</th>
                                <th>Clinical Assessment</th>
                                <th>Follow-up</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($visits): ?>
                                <?php foreach ($visits as $v): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= date('d M Y', strtotime($v['visit_date'])) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($v['visit_date'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="note-bubble">
                                            <?= !empty($v['diagnosis']) ? htmlspecialchars($v['diagnosis']) : (!empty($v['reason']) ? htmlspecialchars($v['reason']) : 'Routine Observation') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($v['follow_up_due']): ?>
                                            <span class="status-badge bg-primary bg-opacity-10 text-primary">
                                                <?= date('d M Y', strtotime($v['follow_up_due'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">No follow-up</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No medical visits found in the database.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-5">
    <p class="small text-muted mb-0">&copy; 2026 Clinic Pro Management • Secure Patient Access</p>
</footer>

</body>
</html>