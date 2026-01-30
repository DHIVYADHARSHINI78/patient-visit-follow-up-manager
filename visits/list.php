<?php
require_once __DIR__ . '/../config/init.php';


require_once '../config/db.php';
 include '../includes/header.php';

// 1. Pagination Configuration
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// 2. Get filter inputs
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

// 3. Build the Base WHERE Clause
$where = " WHERE (p.name LIKE :search) ";
$params = ['search' => "%$search%"];

if ($status_filter == 'Overdue') {
    $where .= " AND v.follow_up_due < CURDATE()";
} elseif ($status_filter == 'Upcoming') {
    $where .= " AND v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
}

// 4. SQL: Get Total Count
$count_sql = "SELECT COUNT(*) FROM visits v JOIN patients p ON v.patient_id = p.patient_id $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// 5. SQL: Fetch Paginated Results
$sql = "SELECT v.*, p.name,
        DATEDIFF(CURDATE(), v.visit_date) AS days_since_visit,
        (v.consultation_fee + v.lab_fee) AS total_bill,
        CASE 
            WHEN v.follow_up_due < CURDATE() THEN 'Overdue'
            WHEN v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
            ELSE 'Scheduled'
        END AS visit_status
        FROM visits v
        JOIN patients p ON v.patient_id = p.patient_id
        $where
        ORDER BY v.visit_date DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>All Medical Visits</h2>
    <a href="add.php" class="btn btn-primary">+ Record New Visit</a>
</div>

<form method="GET" class="row g-3 mb-4 bg-light p-3 rounded border shadow-sm">
    <div class="col-md-5">
        <input type="text" name="search" class="form-control" placeholder="Search by Patient Name..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-4">
        <select name="status_filter" class="form-control">
            <option value="">All Statuses</option>
            <option value="Overdue" <?= $status_filter == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
            <option value="Upcoming" <?= $status_filter == 'Upcoming' ? 'selected' : '' ?>>Upcoming (Next 7 Days)</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="btn-group w-100">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="list.php" class="btn btn-outline-danger">Clear</a>
        </div>
    </div>
</form>

<table class="table table-hover border shadow-sm">
    <thead class="table-dark">
        <tr>
            <th>Patient</th>
            <th>Visit Date</th>
            <th>Consultation ($)</th>
            <th>Lab ($)</th>
            <th>Total ($)</th>
            <th>Follow-up Due</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($stmt->rowCount() > 0): ?>
            <?php while($row = $stmt->fetch()): ?>
            <tr>
                <td><strong><?= $row['name'] ?></strong></td>
                <td><?= $row['visit_date'] ?></td>
                <td><?= number_format($row['consultation_fee'], 2) ?></td>
                <td><?= number_format($row['lab_fee'], 2) ?></td>
                <td class="fw-bold"><?= number_format($row['total_bill'], 2) ?></td>
                <td><?= $row['follow_up_due'] ?></td>
                <td>
                    <span class="badge <?= $row['visit_status'] == 'Overdue' ? 'bg-danger' : 'bg-success' ?>">
                        <?= $row['visit_status'] ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" class="text-center py-4">No visits found matching your criteria.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): 
            $query = $_GET;
            $query['page'] = $i;
            $url = "?" . http_build_query($query);
        ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="<?= $url ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>