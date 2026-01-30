<?php
require_once '../config/db.php';
include '../includes/header.php';


$sql = "SELECT 
            p.name, 
            p.patient_id,
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age,
            COUNT(v.visit_id) AS total_visits,
            MAX(v.visit_date) AS last_visit_date,
            DATEDIFF(CURDATE(), MAX(v.visit_date)) AS days_since_last_visit,
            MAX(v.follow_up_due) AS next_followup,
            CASE 
                WHEN MAX(v.follow_up_due) < CURDATE() THEN 'Overdue'
                WHEN MAX(v.follow_up_due) = CURDATE() THEN 'Today'
                ELSE 'Upcoming'
            END AS followup_status
        FROM patients p
        LEFT JOIN visits v ON p.patient_id = v.patient_id
        GROUP BY p.patient_id
        ORDER BY last_visit_date DESC";

$stmt = $pdo->query($sql);
?>

<style>

    @media print {
        @page {
            margin: 0.5cm; 
        }
        body {
            background-color: white !important;
            padding: 0;
            margin: 0;
        }
        .btn, .sidebar, nav, .no-print {
            display: none !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
        .table {
            width: 100% !important;
        }
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Full Summary Report</h2>
        <button class="btn btn-primary no-print" onclick="window.print()">
            <i class="bi bi-file-earmark-pdf"></i> Export to PDF
        </button>
    </div>

    <div class="card border-0 shadow-sm p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Patient Name</th>
                        <th>Age</th>
                        <th>Total Visits</th>
                        <th>Last Visit</th>
                        <th>Days Since</th>
                        <th>Next Follow-up</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt->fetch()): ?>
                    <tr>
                        <td class="fw-bold text-primary"><?= $row['name'] ?></td>
                        <td><?= $row['age'] ?> yrs</td>
                        <td><?= $row['total_visits'] ?></td>
                        <td><?= $row['last_visit_date'] ?? 'N/A' ?></td>
                        <td><?= ($row['days_since_last_visit'] !== null) ? $row['days_since_last_visit'] . " d" : "-" ?></td>
                        <td><?= $row['next_followup'] ?? '-' ?></td>
                        <td>
                            <span class="fw-bold <?= ($row['followup_status'] == 'Overdue') ? 'text-danger' : 'text-success' ?>">
                                <?= $row['followup_status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>