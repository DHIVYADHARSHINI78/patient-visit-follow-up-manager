<?php
require_once __DIR__ . '/../config/init.php';
require_once '../config/db.php';
include '../includes/header.php';

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$where = " WHERE (p.name LIKE :search) ";
$params = ['search' => "%$search%"];

if ($status_filter == 'Overdue') {
    $where .= " AND v.follow_up_due < CURDATE()";
} elseif ($status_filter == 'Upcoming') {
    $where .= " AND v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
}

// Total rows for pagination
$count_sql = "SELECT COUNT(*) FROM visits v JOIN patients p ON v.patient_id = p.patient_id $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Main visits query
$sql = "SELECT v.*, p.name, p.patient_id,
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

<div class="container-fluid px-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <h2 class="mb-0">All Medical Visits</h2>
        <a href="add.php" class="btn btn-primary w-100 w-md-auto">+ Record New Visit</a>
    </div>

    <form method="GET" class="row g-2 mb-4 bg-white p-3 rounded shadow-sm border">
        <div class="col-12 col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search Patient..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <select name="status_filter" class="form-select">
                <option value="">All Statuses</option>
                <option value="Overdue" <?= $status_filter == 'Overdue' ? 'selected' : '' ?>>Overdue</option>
                <option value="Upcoming" <?= $status_filter == 'Upcoming' ? 'selected' : '' ?>>Upcoming (7 Days)</option>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
                <a href="list.php" class="btn btn-outline-danger">Clear</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-nowrap">Patient</th>
                        <th class="text-nowrap">Visit Date</th>
                        <th class="text-nowrap d-none d-lg-table-cell">Consultation</th>
                        <th class="text-nowrap d-none d-lg-table-cell">Lab Fee</th>
                        <th class="text-nowrap">Total</th>
                        <th class="text-nowrap">Follow-up</th>
                        <th class="text-nowrap text-center">Status</th>
                        <th class="text-nowrap text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($stmt->rowCount() > 0): ?>
                    <?php while($row = $stmt->fetch()): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-primary"><?= htmlspecialchars($row['name']) ?></div>
                            <small class="text-muted d-lg-none">
                                Fees: $<?= number_format($row['total_bill'], 2) ?>
                            </small>
                        </td>
                        <td class="text-nowrap small"><?= $row['visit_date'] ?></td>
                        <td class="d-none d-lg-table-cell">$<?= number_format($row['consultation_fee'], 2) ?></td>
                        <td class="d-none d-lg-table-cell">$<?= number_format($row['lab_fee'], 2) ?></td>
                        <td class="fw-bold">$<?= number_format($row['total_bill'], 2) ?></td>
                        <td class="text-nowrap small"><?= $row['follow_up_due'] ?></td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?= $row['visit_status'] == 'Overdue' ? 'bg-danger' : ($row['visit_status'] == 'Upcoming' ? 'bg-warning text-dark' : 'bg-success') ?>">
                                <?= $row['visit_status'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="patient_visits.php?id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-outline-info shadow-sm">
                                <i class="bi bi-eye"></i> <span class="d-none d-md-inline">History</span>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No visits found.</td></tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination pagination-sm justify-content-center flex-wrap">
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
</div>

<?php include '../includes/footer.php'; ?>