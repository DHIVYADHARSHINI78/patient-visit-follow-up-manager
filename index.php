<?php

include_once 'includes/header.php'; 
require_once 'config/db.php';

try {

    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM visits WHERE YEAR(visit_date) = YEAR(CURDATE()) - 1) as visits_last_year, 
        (SELECT COUNT(*) FROM visits WHERE MONTH(follow_up_due) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(follow_up_due) = YEAR(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))) as upcoming_month_follows, 
        (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE()) as total_overdue, 
        (SELECT COUNT(*) FROM patients) as total_p";
    
    $stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);


    $chart_sql = "SELECT DATE_FORMAT(d_range, '%b %Y') as m_label, SUM(is_visit) as v_count, SUM(is_followup) as f_count 
                  FROM (
                      SELECT visit_date as d_range, 1 as is_visit, 0 as is_followup FROM visits 
                      WHERE visit_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 YEAR), '%Y-01-01') 
                      UNION ALL 
                      SELECT follow_up_due as d_range, 0 as is_visit, 1 as is_followup FROM visits 
                      WHERE follow_up_due <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
                  ) as combined_data 
                  GROUP BY YEAR(d_range), MONTH(d_range) 
                  ORDER BY MIN(d_range) ASC";
    
    $chart_data = $pdo->query($chart_sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = json_encode(array_column($chart_data, 'm_label'));
    $visitCounts = json_encode(array_column($chart_data, 'v_count'));
    $followupCounts = json_encode(array_column($chart_data, 'f_count'));

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <h2 class="fw-bold">Dashboard</h2>
    <hr>

    <div class="row g-3 mb-4 text-center">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm border-0 p-3">
                <div class="small opacity-75">Visits (Last Year)</div>
                <h2 class="fw-bold mb-0"><?= $stats['visits_last_year'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm border-0 p-3">
                <div class="small opacity-75">Follow-ups (Next Month)</div>
                <h2 class="fw-bold mb-0"><?= $stats['upcoming_month_follows'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow-sm border-0 p-3">
                <div class="small opacity-75">Overdue Follow-ups</div>
                <h2 class="fw-bold mb-0"><?= $stats['total_overdue'] ?? 0 ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm border-0 p-3">
                <div class="small opacity-75">Total Patients</div>
                <h2 class="fw-bold mb-0"><?= $stats['total_p'] ?? 0 ?></h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-graph-up"></i> Performance & Forecast</h5>
            <div style="position: relative; height:350px;">
                <canvas id="indexChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('indexChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $labels; ?>,
            datasets: [
                {
                    label: 'Actual Visits',
                    data: <?php echo $visitCounts; ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Expected Follow-ups',
                    data: <?php echo $followupCounts; ?>,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>

<?php include_once 'includes/footer.php'; ?>