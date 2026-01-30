<?php
require_once '../config/db.php';
include '../includes/header.php';

// 1. SQL: Monthly Revenue (Last 6 Months)
$revenue_query = $pdo->query("
    SELECT DATE_FORMAT(visit_date, '%b %Y') as month_name, 
           SUM(consultation_fee + lab_fee) as total_revenue
    FROM visits 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(visit_date), MONTH(visit_date)
    ORDER BY visit_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. SQL: Patient Join Trends
$growth_query = $pdo->query("
    SELECT DATE_FORMAT(join_date, '%b %Y') as month_name, 
           COUNT(patient_id) as new_patients
    FROM patients 
    GROUP BY YEAR(join_date), MONTH(join_date)
    ORDER BY join_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js (PHP to JS conversion)
$rev_labels = json_encode(array_column($revenue_query, 'month_name'));
$rev_data = json_encode(array_column($revenue_query, 'total_revenue'));

$grow_labels = json_encode(array_column($growth_query, 'month_name'));
$grow_data = json_encode(array_column($growth_query, 'new_patients'));
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-pie-chart-fill"></i> Performance Analytics</h2>

    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Revenue Trend (Consultation + Lab)</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">New Patient Growth</h5>
                </div>
                <div class="card-body">
                    <canvas id="growthChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 1. Revenue Chart Logic
const revCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revCtx, {
    type: 'line',
    data: {
        labels: <?php echo $rev_labels; ?>,
        datasets: [{
            label: 'Total Revenue ($)',
            data: <?php echo $rev_data; ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            fill: true,
            tension: 0.3
        }]
    }
});

// 2. Growth Chart Logic
const growCtx = document.getElementById('growthChart').getContext('2d');
new Chart(growCtx, {
    type: 'bar',
    data: {
        labels: <?php echo $grow_labels; ?>,
        datasets: [{
            label: 'New Patients',
            data: <?php echo $grow_data; ?>,
            backgroundColor: '#2ecc71'
        }]
    },
    options: {
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php include '../includes/footer.php'; ?>