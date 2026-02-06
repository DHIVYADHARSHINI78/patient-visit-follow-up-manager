<?php
include_once 'includes/header.php'; 
require_once 'config/db.php';

try {
    // 1. Professional Stats (Demographics & Financials)
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM patients WHERE gender = 'Male') as male_pts, 
        (SELECT COUNT(*) FROM patients WHERE gender = 'Female') as female_pts, 
        (SELECT COUNT(*) FROM patients WHERE TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 60) as geriatric, 
        (SELECT COUNT(*) FROM patients) as total_p,
        (SELECT SUM(consultation_fee + lab_fee) FROM visits) as total_revenue,
        (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE()) as total_overdue";
    
    $stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

    // 2. Clinical Visit Trends (Last 6 Months)
    $chart_sql = "SELECT DATE_FORMAT(visit_date, '%b %Y') as m_label, COUNT(*) as v_count 
                  FROM visits 
                  WHERE visit_date >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')
                  GROUP BY YEAR(visit_date), MONTH(visit_date) 
                  ORDER BY visit_date ASC";
    
    $chart_data = $pdo->query($chart_sql)->fetchAll(PDO::FETCH_ASSOC);
    $labels = json_encode(array_column($chart_data, 'm_label'));
    $visitCounts = json_encode(array_column($chart_data, 'v_count'));

} catch (PDOException $e) {
    die("System Error: " . $e->getMessage());
}
?>

<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Clinical Analytics</h2>
        </div>
        <div class="text-end">
            <span class="d-block fw-bold"><?= date('l, d M Y') ?></span>
            <span class="badge bg-soft-success text-success border border-success px-3">System Online</span>
        </div>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white border-start border-primary border-5">
                <div class="text-muted small fw-bold text-uppercase">Total Revenue</div>
                <h3 class="fw-bold mb-0">$<?= number_format($stats['total_revenue'] ?? 0, 2) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white border-start border-info border-5">
                <div class="text-muted small fw-bold text-uppercase">Total Patients</div>
                <h3 class="fw-bold mb-0"><?= $stats['total_p'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white border-start border-warning border-5">
                <div class="text-muted small fw-bold text-uppercase">Geriatric Patients (60+)</div>
                <h3 class="fw-bold mb-0"><?= $stats['geriatric'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 rounded-4 bg-white border-start border-danger border-5">
                <div class="text-muted small fw-bold text-uppercase">Overdue Follow-ups</div>
                <h3 class="fw-bold mb-0 text-danger"><?= $stats['total_overdue'] ?></h3>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 text-muted"><i class="bi bi-graph-up me-2"></i>PATIENT VISIT VOLUME (6 MONTHS)</h6>
                    <div style="height: 300px;">
                        <canvas id="visitTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 text-muted"><i class="bi bi-people me-2"></i>DEMOGRAPHICS</h6>
                    <div style="height: 250px;">
                        <canvas id="genderChart"></canvas>
                    </div>
                    <div class="mt-3 small text-center text-muted">
                        Gender-based patient distribution
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Professional Color Palette
    const colors = {
        primary: '#4e73df',
        success: '#1cc88a',
        danger: '#e74a3b',
        warning: '#f6c23e',
        info: '#36b9cc'
    };

    // 1. Gender Distribution (Doughnut)
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [<?= (int)$stats['male_pts'] ?>, <?= (int)$stats['female_pts'] ?>],
                backgroundColor: [colors.primary, colors.danger],
                hoverOffset: 15,
                borderWidth: 0
            }]
        },
        options: { 
            maintainAspectRatio: false, 
            cutout: '70%',
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } } 
        }
    });

    // 2. Visit Trends (Area Chart)
    new Chart(document.getElementById('visitTrendChart'), {
        type: 'line',
        data: {
            labels: <?= $labels ?>,
            datasets: [{
                label: 'Monthly Patient Visits',
                data: <?= $visitCounts ?>,
                borderColor: colors.primary,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointBackgroundColor: colors.primary
            }]
        },
        options: { 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: { 
                y: { grid: { display: false }, beginAtZero: true },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include_once 'includes/footer.php'; ?>