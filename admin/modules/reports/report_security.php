<?php
/**
 * Report: Laporan Keamanan
 * Menampilkan data log dari tabel rate_limits dan data keamanan lainnya.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Keamanan';
 $db = Database::getInstance()->getConnection();

// Get filters - Default ke 30 hari terakhir
 $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
 $dateTo = $_GET['date_to'] ?? date('Y-m-d');
 $actionType = $_GET['action_type'] ?? '';
 $ipFilter = $_GET['ip_address'] ?? '';
 $statusFilter = $_GET['status'] ?? ''; // 0=All, 1=Blocked, 2=Not Blocked
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get action types for filter (dari rate_limits)
 $actionTypesStmt = $db->query("SELECT DISTINCT action_type FROM rate_limits WHERE action_type IS NOT NULL AND action_type != '' ORDER BY action_type ASC");
 $actionTypes = $actionTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// === SUMMARY STATISTICS (4 Kartu) ===
 $summaryStats = [
    'failed_logins' => 0, // Akan diubah ke Akun Terkunci
    'blocked_ips' => 0,
    'security_events_period' => 0,
    'password_resets_period' => 0,
];

// Card 1: Jumlah Akun Terkunci (Global, Saat Ini)
// Menggunakan 'login_attempts' > 0 ATAU 'locked_until' di masa depan
 $summaryStats['failed_logins'] = $db->query("
    SELECT COUNT(*) FROM users 
    WHERE deleted_at IS NULL 
    AND (login_attempts > 0 OR (locked_until IS NOT NULL AND locked_until > NOW()))
 ")->fetchColumn();

// Card 2: Jumlah IP Diblokir (Global, Saat Ini)
 $summaryStats['blocked_ips'] = $db->query("
    SELECT COUNT(*) FROM rate_limits 
    WHERE is_blocked = 1 AND (blocked_until IS NOT NULL AND blocked_until > NOW())
 ")->fetchColumn();
 
// Build date-only params for cards 3 & 4
 $paramsDateOnly = [':date_from' => $dateFrom, ':date_to' => $dateTo];

// Card 3: Jumlah event keamanan bulan ini (Sesuai Filter Tanggal)
 $eventsSql = "SELECT COUNT(*) FROM rate_limits WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
 $eventsStmt = $db->prepare($eventsSql);
 $eventsStmt->execute($paramsDateOnly);
 $summaryStats['security_events_period'] = $eventsStmt->fetchColumn();

// Card 4: Total reset password (Sesuai Filter Tanggal)
 $resetsSql = "SELECT COUNT(*) FROM password_resets WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
 $resetsStmt = $db->prepare($resetsSql);
 $resetsStmt->execute($paramsDateOnly);
 $summaryStats['password_resets_period'] = $resetsStmt->fetchColumn();


// === MAIN DATA (Tabel Utama dari rate_limits) ===
 $whereConditions = ["DATE(created_at) BETWEEN :date_from AND :date_to"];
 $params = [
    ':date_from' => $dateFrom,
    ':date_to' => $dateTo
];

if ($actionType) {
    $whereConditions[] = "action_type = :action_type";
    $params[':action_type'] = $actionType;
}

if ($ipFilter) {
    $whereConditions[] = "ip_address = :ip_address";
    $params[':ip_address'] = $ipFilter;
}

if ($statusFilter === '1') { // Sedang diblokir
    $whereConditions[] = "is_blocked = 1 AND (blocked_until IS NOT NULL AND blocked_until > NOW())";
} elseif ($statusFilter === '2') { // Tidak diblokir / sudah kadaluarsa
    $whereConditions[] = "(is_blocked = 0 OR (blocked_until IS NOT NULL AND blocked_until <= NOW()))";
}

 $whereClause = implode(' AND ', $whereConditions);

// Hitung total items untuk paginasi
 $countSql = "SELECT COUNT(*) FROM rate_limits WHERE $whereClause";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($params);
 $totalItems = $countStmt->fetchColumn();
 $totalPages = ceil($totalItems / $perPage);
 $offset = ($page - 1) * $perPage;

// Get data for main table
 $sql = "
    SELECT *
    FROM rate_limits
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
        SELECT *
        FROM rate_limits
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
        'margin_left' => 10,
        'margin_right' => 10,
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
                    ' . htmlspecialchars($siteName ?? '') . ' - Laporan Keamanan
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_security_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Keamanan_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= htmlspecialchars($pageTitle) ?></h3>
                <p class="text-subtitle text-muted">Statistik dan log event keamanan sistem.</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle) ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row mb-3">
             <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-person-x-fill"></i> Akun Terkunci</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['failed_logins']) ?></h2>
                        <small>(Global Saat Ini)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-shield-slash-fill"></i> IP Terblokir</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['blocked_ips']) ?></h2>
                        <small>(Global Saat Ini)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-calendar-event"></i> Event Keamanan</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['security_events_period']) ?></h2>
                         <small>(Sesuai Filter Tanggal)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-key-fill"></i> Total Reset Password</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['password_resets_period']) ?></h2>
                         <small>(Sesuai Filter Tanggal)</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan (Tabel Event Keamanan)</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-control" value="<?= htmlspecialchars($ipFilter) ?>" placeholder="Cari IP Address...">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tipe Event</label>
                        <select name="action_type" class="form-select">
                            <option value="">-- Semua Tipe --</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $actionType == $type ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status Blokir</label>
                        <select name="status" class="form-select">
                            <option value="0">-- Semua Status --</option>
                            <option value="1" <?= $statusFilter == '1' ? 'selected' : '' ?>>Sedang Diblokir</option>
                            <option value="2" <?= $statusFilter == '2' ? 'selected' : '' ?>>Tidak Diblokir</option>
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
                
                <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $statusFilter || $actionType || $ipFilter): ?>
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
                        <h6 class="mb-1">Laporan Detail Event Keamanan</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if (($dateFrom != date('Y-m-d', strtotime('-30 days'))) || ($dateTo != date('Y-m-d')) || $statusFilter || $actionType || $ipFilter): ?>
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
                    Rekap Event Keamanan (Rate Limits)
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Waktu</th>
                                <th>IP Address</th>
                                <th>Tipe Event</th>
                                <th>User/Target</th>
                                <th>Status Blokir</th>
                                <th>Deskripsi</th>
                                <th>Detail Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data event keamanan</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                $now = new DateTime();
                                foreach ($mainData as $row): 
                                    $isBlocked = false;
                                    if ($row['is_blocked'] && $row['blocked_until']) {
                                        $blockedUntil = new DateTime($row['blocked_until']);
                                        if ($blockedUntil > $now) {
                                            $isBlocked = true;
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td><small><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></small></td>
                                        <td><?= htmlspecialchars($row['ip_address'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-light-primary"><?= htmlspecialchars($row['action_type'] ?? '') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['identifier'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php if ($isBlocked): ?>
                                                <span class="badge bg-danger">Diblokir</span>
                                            <?php else: ?>
                                                <span class="badge bg-light-secondary">Tidak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['block_reason'] ?? '-') ?></td>
                                        <td>
                                            <small title="<?= htmlspecialchars($row['user_agent'] ?? '') ?>">
                                                <?= htmlspecialchars(substr($row['user_agent'] ?? '', 0, 40)) . '...' ?>
                                            </small>
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