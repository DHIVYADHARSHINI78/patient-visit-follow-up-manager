<?php
require_once 'config/db.php';
include 'includes/header.php';

// 1. SQL: Summary Statistics
$stats = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM visits WHERE YEAR(visit_date) = YEAR(CURDATE()) - 1) as visits_last_year,
    (SELECT COUNT(*) FROM visits WHERE MONTH(follow_up_due) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(follow_up_due) = YEAR(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))) as upcoming_month_follows,
    (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE()) as total_overdue,
    (SELECT COUNT(*) FROM patients) as total_p
")->fetch();

// 2. SQL: Timeline Data (All Last Year + Current Year + Upcoming Month)
$chart_sql = "
    SELECT 
        DATE_FORMAT(d_range, '%b %Y') as m_label,
        SUM(is_visit) as v_count,
        SUM(is_followup) as f_count
    FROM (
        /* Completed visits from start of last year to now */
        SELECT visit_date as d_range, 1 as is_visit, 0 as is_followup FROM visits 
        WHERE visit_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 YEAR), '%Y-01-01')
        UNION ALL
        /* Follow-ups due from now until end of next month */
        SELECT follow_up_due as d_range, 0 as is_visit, 1 as is_followup FROM visits 
        WHERE follow_up_due <= LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
    ) as combined_data
    GROUP BY YEAR(d_range), MONTH(d_range)
    ORDER BY MIN(d_range) ASC";

$chart_data = $pdo->query($chart_sql)->fetchAll(PDO::FETCH_ASSOC);

$labels = json_encode(array_column($chart_data, 'm_label'));
$visitCounts = json_encode(array_column($chart_data, 'v_count'));
$followupCounts = json_encode(array_column($chart_data, 'f_count'));
?>

<div class="container-fluid py-4" style="background-color: #f8f9fa; min-height: 100vh;">
    <div class="mb-4">
        <h2 class="fw-bold text-dark">Annual Performance & Outlook</h2>
        <p class="text-muted small">Tracking history from Jan <?= date('Y') - 1 ?> to <?= date('M Y', strtotime('first day of next month')) ?></p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small fw-bold text-uppercase mb-2">Visits Last Year (Total)</p>
                    <h2 class="fw-bold mb-0 text-secondary"><?= number_format($stats['visits_last_year']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small fw-bold text-uppercase mb-2">Due Next Month (<?= date('M', strtotime('first day of next month')) ?>)</p>
                    <h2 class="fw-bold mb-0 text-primary"><?= $stats['upcoming_month_follows'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small fw-bold text-uppercase mb-2">Total Overdue</p>
                    <h2 class="fw-bold mb-0 text-danger"><?= $stats['total_overdue'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <p class="text-muted small fw-bold text-uppercase mb-2">Total Patients</p>
                    <h2 class="fw-bold mb-0 text-dark"><?= $stats['total_p'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div style="height: 450px;">
                    <canvas id="annualTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('annualTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $labels ?>,
        datasets: [
            {
                label: 'Visits (History)',
                data: <?= $visitCounts ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderRadius: 5
            },
            {
                label: 'Follow-ups (Projected)',
                data: <?= $followupCounts ?>,
                backgroundColor: 'rgba(46, 204, 113, 0.7)',
                borderRadius: 5
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
            y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>