<?php
/**
 * Report: Laporan Pengguna
 * Menampilkan data statistik dan daftar pengguna sistem.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Pengguna';
 $db = Database::getInstance()->getConnection();

// Get filters - Default ke 30 hari terakhir untuk "Pengguna Baru"
 $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
 $dateTo = $_GET['date_to'] ?? date('Y-m-d');
 $roleFilter = $_GET['role'] ?? '';
 $statusFilter = $_GET['is_active'] ?? '';
 $nameFilter = $_GET['name'] ?? ''; // Filter by name or email
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get roles for filter
 $rolesStmt = $db->query("SELECT DISTINCT role FROM users WHERE deleted_at IS NULL ORDER BY role ASC");
 $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);


// === SUMMARY STATISTICS (4 Kartu) ===
 $summaryStats = [
    'total_active_users' => 0,
    'new_users_period' => 0,
    'total_admins' => 0,
    'recent_logins' => 0,
];

// Card 1: Total pengguna aktif (Global)
 $summaryStats['total_active_users'] = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND is_active = 1")->fetchColumn();

// Card 2: Pengguna baru (Periode - Sesuai Filter Tanggal)
 $paramsDateOnly = [':date_from' => $dateFrom, ':date_to' => $dateTo];
 $newUsersSql = "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN :date_from AND :date_to";
 $newUsersStmt = $db->prepare($newUsersSql);
 $newUsersStmt->execute($paramsDateOnly);
 $summaryStats['new_users_period'] = $newUsersStmt->fetchColumn();

// Card 3: Pengguna admin/superadmin (Global)
 $summaryStats['total_admins'] = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND is_active = 1 AND role IN ('super_admin', 'admin')")->fetchColumn();

// Card 4: Pengguna login terakhir 7 hari (Global)
 $summaryStats['recent_logins'] = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND is_active = 1 AND last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();


// === MAIN DATA (Tabel Utama dengan Filter) ===
 $whereConditions = ["deleted_at IS NULL"];
 $params = [];

// Filter Tanggal (berdasarkan TANGGAL DAFTAR)
 $whereConditions[] = "DATE(created_at) BETWEEN :date_from AND :date_to";
 $params[':date_from'] = $dateFrom;
 $params[':date_to'] = $dateTo;

if ($roleFilter) {
    $whereConditions[] = "role = :role";
    $params[':role'] = $roleFilter;
}

if ($statusFilter !== '') {
    $whereConditions[] = "is_active = :is_active";
    $params[':is_active'] = $statusFilter;
}

if ($nameFilter) {
    $whereConditions[] = "(name LIKE :name OR email LIKE :name)";
    $params[':name'] = "%$nameFilter%";
}

 $whereClause = implode(' AND ', $whereConditions);

// Hitung total items untuk paginasi
 $countSql = "SELECT COUNT(id) FROM users WHERE $whereClause";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($params);
 $totalItems = $countStmt->fetchColumn();
 $totalPages = ceil($totalItems / $perPage);
 $offset = ($page - 1) * $perPage;

// Get data for main table
 $sql = "
    SELECT id, name, email, role, is_active, last_login_at, created_at
    FROM users
    WHERE $whereClause
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
";

 $mainDataStmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $mainDataStmt->bindValue($key, $value);
}
 $mainDataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
 $mainDataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
 $mainDataStmt->execute();
 $mainData = $mainDataStmt->fetchAll();

// Export PDF (export all data without pagination)
if ($exportPdf === '1') {
    
    $pdfSql = "
        SELECT id, name, email, role, is_active, last_login_at, created_at
        FROM users
        WHERE $whereClause
        ORDER BY created_at DESC
    ";
    $pdfDataStmt = $db->prepare($pdfSql);
    $pdfDataStmt->execute($params);
    $mainData = $pdfDataStmt->fetchAll(); // Override mainData for PDF export
    
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L', // Landscape
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 10,
        'margin_bottom' => 20,
        'margin_header' => 0,
        'margin_footer' => 10,
    ]);
    $mpdf->SetDefaultFont('cambria');

    $footer = '
        <table width="100%" style="border-top: 1px solid #000; padding-top: 5px; font-size: 9pt;">
            <tr>
                <td width="70%" style="text-align: left;">
                    ' . htmlspecialchars($siteName) . ' - Laporan Pengguna
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_users_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Pengguna_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Statistik dan daftar pengguna sistem.</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active"><?= $pageTitle ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row mb-3">
             <div class="col-6 col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-check-fill"></i> Pengguna Aktif</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_active_users']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-plus-fill"></i> Pengguna Baru</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['new_users_period']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-badge-fill"></i> Administrator</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['total_admins']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-lines-fill"></i> Login (7 Hari)</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['recent_logins']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan (Filter Tanggal berdasarkan Tgl Daftar)</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tgl Daftar Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tgl Daftar Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Nama / Email</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($nameFilter) ?>" placeholder="Cari nama atau email...">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">-- Semua Role --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role ?>" <?= $roleFilter == $role ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="1" <?= $statusFilter == '1' ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= $statusFilter == '0' ? 'selected' : '' ?>>Non-Aktif</option>
                        </select>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <label class="form-label">Per Hal</label>
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
                <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $roleFilter || $statusFilter !== '' || $nameFilter): ?>
                    <div class="mt-2">
                        <a href="?" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset Filter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-column flex-md-row gap-2">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Laporan Detail Pengguna</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $roleFilter || $statusFilter !== '' || $nameFilter): ?>
                            <br><span class="badge bg-info mt-1">Filter Aktif</span>
                        <?php endif; ?>
                    </div>

                    <a href="?export_pdf=1<?= http_build_query(array_filter($_GET, fn($key) => $key != 'page', ARRAY_FILTER_USE_KEY)) ?>"
                       class="btn btn-danger flex-shrink-0"
                       target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-0">
                <h5 class="card-title mb-0">
                    Data Pengguna
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th>Tgl Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data pengguna</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-light-primary"><?= ucfirst(str_replace('_', ' ', $row['role'])) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['is_active']): ?>
                                                <span class="badge bg-light-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-light-danger">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <small><?= $row['last_login_at'] ? formatTanggal($row['last_login_at'], 'd/m/Y H:i') : '-' ?></small>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($row['created_at'], 'd/m/Y') ?></small>
                                        </td>
                                    </tr>
                                    <?php $no++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalItems > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div class="flex-grow-1">
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($mainData) ?> dari <?= $totalItems ?> data
                            </small>
                        </div>
                        <nav aria-label="Page navigation" class="flex-shrink-0">
                            <ul class="pagination mb-0">
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php
                                $from = max(1, $page - 2);
                                $to = min($totalPages, $page + 2);
                                for ($i = $from; $i <= $to; $i++): ?>
                                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>