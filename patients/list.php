<?php
require_once __DIR__ . '/../config/init.php';

require_once __DIR__ . '/../config/db.php';

include_once __DIR__ . '/../includes/header.php'; 

$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


$search = $_GET['search'] ?? '';
$min_age = $_GET['min_age'] ?? '';
$max_age = $_GET['max_age'] ?? '';


$where = " WHERE (name LIKE :search)";
$params = ['search' => "%$search%"];

if ($min_age !== '') {
    $where .= " AND TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= :min_age";
    $params['min_age'] = $min_age;
}
if ($max_age !== '') {
    $where .= " AND TIMESTAMPDIFF(YEAR, dob, CURDATE()) <= :max_age";
    $params['max_age'] = $max_age;
}

$count_sql = "SELECT COUNT(*) FROM patients $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT *, 
        YEAR(join_date) as join_year, MONTH(join_date) as join_month, DAY(join_date) as join_day,
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age_years,
        CONCAT(TIMESTAMPDIFF(YEAR, dob, CURDATE()), ' Years, ', TIMESTAMPDIFF(MONTH, dob, CURDATE()) % 12, ' Months') AS full_age,
        (SELECT COUNT(*) FROM visits WHERE patient_id = patients.patient_id) AS total_visits
        FROM patients
        $where
        ORDER BY name ASC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">All Patients</h2>
    <a href="add.php" class="btn btn-primary">+ Add New Patient</a>
</div>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-body bg-light rounded">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="small fw-bold">Search Name</label>
                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold">Min Age</label>
                <input type="number" name="min_age" class="form-control" value="<?= htmlspecialchars($min_age) ?>">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold">Max Age</label>
                <input type="number" name="max_age" class="form-control" value="<?= htmlspecialchars($max_age) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
                <a href="list.php" class="btn btn-link">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Full Age</th>
                    <th>Joined</th>
                    <th>Visits</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $stmt->fetch()): ?>
                <tr>
                    <td class="fw-bold"><?= $row['name'] ?></td>
                    <td><?= $row['age_years'] ?></td>
                    <td><small class="text-muted"><?= $row['full_age'] ?></small></td>
                    <td><?= $row['join_year'] ?>-<?= $row['join_month'] ?>-<?= $row['join_day'] ?></td>
                    <td><span class="badge bg-info text-dark"><?= $row['total_visits'] ?></span></td>
                    <td class="text-center">
                        <a href="view.php?id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-outline-info">View</a>
                        <a href="edit.php?id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): 
       
            $query_params = $_GET;
            $query_params['page'] = $i;
            $link = "?" . http_build_query($query_params);
        ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="<?= $link ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>