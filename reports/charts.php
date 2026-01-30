<?php
require_once __DIR__ . '/../config/init.php';

include_once '../includes/header.php'; 
require_once '../config/db.php';

try {
   
    $chart_sql = "
        SELECT DATE_FORMAT(d_range, '%b %Y') as m_label, 
               SUM(is_visit) as v_count, 
               SUM(is_followup) as f_count 
        FROM (
            /* Completed visits from start of last year to now */
            SELECT visit_date as d_range, 1 as is_visit, 0 as is_followup 
            FROM visits 
            WHERE visit_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 YEAR), '%Y-01-01') 
            UNION ALL 
            /* Follow-ups due from now until end of next month */
            SELECT follow_up_due as d_range, 0 as is_visit, 1 as is_followup 
            FROM visits 
            WHERE follow_up_due <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
        ) as combined_data 
        GROUP BY YEAR(d_range), MONTH(d_range) 
        ORDER BY MIN(d_range) ASC";

    $chart_data = $pdo->query($chart_sql)->fetchAll(PDO::FETCH_ASSOC);

 
    $labels = json_encode(array_column($chart_data, 'm_label'));
    $visitCounts = json_encode(array_column($chart_data, 'v_count'));
    $followupCounts = json_encode(array_column($chart_data, 'f_count'));

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Clinic Performance Outlook</h3>
                <span class="badge bg-light text-dark border">Jan 2025 - Feb 2026</span>
            </div>

            <div style="position: relative; height:400px; width:100%">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $labels; ?>,
            datasets: [
                {
                    label: 'Actual Patient Visits',
                    data: <?php echo $visitCounts; ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4
                },
                {
                    label: 'Scheduled Follow-ups',
                    data: <?php echo $followupCounts; ?>,
                    borderColor: '#dc3545', 
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    borderDash: [5, 5],
                    pointRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 }
                }
            }
        }
    });
</script>

<?php include_once '../includes/footer.php'; ?>