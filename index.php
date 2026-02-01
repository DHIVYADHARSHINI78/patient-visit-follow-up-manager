<?php
include_once 'includes/header.php'; 
require_once 'config/db.php';

try {
    // 1. Fetch Stats: Boys, Girls, Old People (60+), and Total
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM patients WHERE gender = 'Male') as boys, 
        (SELECT COUNT(*) FROM patients WHERE gender = 'Female') as girls, 
        (SELECT COUNT(*) FROM patients WHERE TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 60) as old_people, 
        (SELECT COUNT(*) FROM patients) as total_p,
        (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE()) as total_overdue";
    
    $stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);

    // 2. Visit Trend Chart Data (Last 12 Months)
    $chart_sql = "SELECT DATE_FORMAT(visit_date, '%b %Y') as m_label, COUNT(*) as v_count 
                  FROM visits 
                  WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                  GROUP BY YEAR(visit_date), MONTH(visit_date) 
                  ORDER BY visit_date ASC";
    
    $chart_data = $pdo->query($chart_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = json_encode(array_column($chart_data, 'm_label'));
    $visitCounts = json_encode(array_column($chart_data, 'v_count'));

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="bi bi-speedometer2"></i> Clinic Dashboard</h2>
        <span class="badge bg-light text-dark border p-2"><?= date('D, d M Y') ?></span>
    </div>
    
    <hr>

    <div class="row g-3 mb-4 text-center">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-4">
                <div class="small opacity-75">Boys (Male)</div>
                <h2 class="fw-bold mb-0"><?= $stats['boys'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white p-3 rounded-4">
                <div class="small opacity-75">Girls (Female)</div>
                <h2 class="fw-bold mb-0"><?= $stats['girls'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark p-3 rounded-4">
                <div class="small opacity-75">Old People (60+)</div>
                <h2 class="fw-bold mb-0"><?= $stats['old_people'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-4">
                <div class="small opacity-75">Total Patients</div>
                <h2 class="fw-bold mb-0"><?= $stats['total_p'] ?? 0 ?></h2>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 text-muted text-uppercase">Gender Distribution</h6>
                    <div style="height: 250px;">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 text-muted text-uppercase">Age Categories</h6>
                    <div style="height: 250px;">
                        <canvas id="ageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 text-muted text-uppercase">Visit Trends (1 Year)</h6>
                    <div style="height: 250px;">
                        <canvas id="visitTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Gender Distribution (Doughnut)
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: ['Boys', 'Girls'],
            datasets: [{
                data: [<?= (int)$stats['boys'] ?>, <?= (int)$stats['girls'] ?>],
                backgroundColor: ['#0d6efd', '#dc3545'],
                hoverOffset: 10
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 2. Age Category (Pie)
    new Chart(document.getElementById('ageChart'), {
        type: 'pie',
        data: {
            labels: ['Old (60+)', 'Young/Adults'],
            datasets: [{
                data: [<?= (int)$stats['old_people'] ?>, <?= (int)$stats['total_p'] - (int)$stats['old_people'] ?>],
                backgroundColor: ['#ffc107', '#198754']
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Visit Trends (Line)
    new Chart(document.getElementById('visitTrendChart'), {
        type: 'line',
        data: {
            labels: <?= $labels ?>,
            datasets: [{
                label: 'Visits',
                data: <?= $visitCounts ?>,
                borderColor: '#6f42c1',
                backgroundColor: 'rgba(111, 66, 193, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { 
            maintainAspectRatio: false, 
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>

<?php include_once 'includes/footer.php'; ?>