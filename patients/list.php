<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/db.php';
include_once __DIR__ . '/../includes/header.php'; 

// --- Pagination Logic ---
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Filter Logic ---
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

// --- Total Count for Pagination ---
$count_sql = "SELECT COUNT(*) FROM patients $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// --- Fetch Patients with Call and Age logic ---
$sql = "SELECT *, 
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age_years,
        CONCAT(TIMESTAMPDIFF(YEAR, dob, CURDATE()), 'y ', TIMESTAMPDIFF(MONTH, dob, CURDATE()) % 12, 'm') AS full_age,
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

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Patient Directory</h2>
            <p class="text-muted mb-0">Manage patient records and communication</p>
        </div>
        <a href="add.php" class="btn btn-primary btn-lg shadow-sm w-100 w-md-auto px-4">
            <i class="bi bi-person-plus-fill me-2"></i>Add New Patient
        </a>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded shadow-inner">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="small fw-bold text-secondary">Search Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="e.g. John Doe" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-secondary">Min Age</label>
                    <input type="number" name="min_age" class="form-control" placeholder="0" value="<?= htmlspecialchars($min_age) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-secondary">Max Age</label>
                    <input type="number" name="max_age" class="form-control" placeholder="100" value="<?= htmlspecialchars($max_age) ?>">
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-dark flex-grow-1 px-4">Apply Filters</button>
                    <a href="list.php" class="btn btn-outline-danger px-3" title="Clear All"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="ps-4 py-3">Patient Profile</th>
                        <th class="d-none d-md-table-cell">Age Details</th>
                        <th>Contact</th>
                        <th class="text-center">Visits</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if ($stmt->rowCount() > 0): ?>
                        <?php while($row = $stmt->fetch()): 
                            // Clean phone for WhatsApp: removes anything that isn't a digit
                            $cleanPhone = preg_replace('/[^0-9]/', '', $row['phone']);
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-primary mb-0"><?= htmlspecialchars($row['name']) ?></div>
                                <small class="text-muted d-md-none">Joined: <?= date('M Y', strtotime($row['join_date'])) ?></small>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="fw-bold text-dark"><?= $row['age_years'] ?> Years</div>
                                <small class="text-muted"><?= $row['full_age'] ?></small>
                            </td>
                            <td>
                                <?php if(!empty($row['phone'])): ?>
                                    <div class="d-flex flex-column gap-1">
                                        <a href="tel:<?= $row['phone'] ?>" class="text-decoration-none text-success fw-bold small d-flex align-items-center">
                                            <i class="bi bi-telephone-outbound-fill me-2"></i> <?= htmlspecialchars($row['phone']) ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small italic">No contact info</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border px-3"><?= $row['total_visits'] ?></span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Options
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                        <li><a class="dropdown-item py-2" href="view.php?id=<?= $row['patient_id'] ?>"><i class="bi bi-journal-medical me-2 text-info"></i> Medical History</a></li>
                                        <li><a class="dropdown-item py-2" href="edit.php?id=<?= $row['patient_id'] ?>"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Details</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-2 text-success" href="https://wa.me/<?= $cleanPhone ?>" target="_blank">
                                            <i class="bi bi-whatsapp me-2"></i> Message Patient
                                        </a></li>
                                        <li><a class="dropdown-item py-2 text-danger" href="delete.php?id=<?= $row['patient_id'] ?>" onclick="return confirm('Archive this patient?')">
                                            <i class="bi bi-archive me-2"></i> Archive Patient
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted italic">
                                <i class="bi bi-person-exclamation display-4 d-block mb-3"></i>
                                No patients found matching your search.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center pagination-md">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>