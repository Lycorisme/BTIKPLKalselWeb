<?php
/**
 * Report: Laporan Harian (Executive Summary)
 * Fokus pada ringkasan aktivitas global harian.
 * Sesuai standar laporan executive (versi baru).
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Harian';
 $db = Database::getInstance()->getConnection();

// Get filters - Default ke HARI INI
 $today = date('Y-m-d');
 $dateFrom = $_GET['date_from'] ?? $today;
 $dateTo = $_GET['date_to'] ?? $today;
 $actionType = $_GET['action_type'] ?? '';
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get action types for filter (dari activity_logs)
 $actionTypesStmt = $db->query("SELECT DISTINCT action_type FROM activity_logs WHERE action_type IS NOT NULL AND action_type != '' ORDER BY action_type ASC");
 $actionTypes = $actionTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// === SUMMARY STATISTICS (4 Kartu) - Berdasarkan Filter Tanggal ===
 $summaryStats = [
    'posts_today' => 0,
    'views_today' => 0,
    'downloads_today' => 0,
    'messages_today' => 0,
];

// Build date-only params
 $paramsDateOnly = [
    ':date_from' => $dateFrom,
    ':date_to' => $dateTo
];

// 1. Total Post Terbit
 $postsSql = "SELECT COUNT(*) FROM posts WHERE status = 'published' AND DATE(published_at) >= :date_from AND DATE(published_at) <= :date_to";
 $postsStmt = $db->prepare($postsSql);
 $postsStmt->execute($paramsDateOnly);
 $summaryStats['posts_today'] = $postsStmt->fetchColumn();

// 2. Total Views
 $viewsSql = "SELECT COUNT(*) FROM page_views WHERE DATE(created_at) >= :date_from AND DATE(created_at) <= :date_to";
 $viewsStmt = $db->prepare($viewsSql);
 $viewsStmt->execute($paramsDateOnly);
 $summaryStats['views_today'] = $viewsStmt->fetchColumn();

// 3. Total Download File
 $downloadsSql = "SELECT COUNT(*) FROM activity_logs WHERE action_type = 'DOWNLOAD' AND DATE(created_at) >= :date_from AND DATE(created_at) <= :date_to";
 $downloadsStmt = $db->prepare($downloadsSql);
 $downloadsStmt->execute($paramsDateOnly);
 $summaryStats['downloads_today'] = $downloadsStmt->fetchColumn();

// 4. Total Pesan Masuk
 $messagesSql = "SELECT COUNT(*) FROM contact_messages WHERE DATE(created_at) >= :date_from AND DATE(created_at) <= :date_to";
 $messagesStmt = $db->prepare($messagesSql);
 $messagesStmt->execute($paramsDateOnly);
 $summaryStats['messages_today'] = $messagesStmt->fetchColumn();


// === MAIN DATA (Activity Logs) - Based on Filters ===
 $whereConditions = ["DATE(al.created_at) >= :date_from", "DATE(al.created_at) <= :date_to"];
 $params = [
    ':date_from' => $dateFrom,
    ':date_to' => $dateTo
];

if ($actionType) {
    $whereConditions[] = "al.action_type = :action_type";
    $params[':action_type'] = $actionType;
}

 $whereClause = implode(' AND ', $whereConditions);

// Count total items
 $countSql = "SELECT COUNT(*) as total FROM activity_logs al WHERE $whereClause";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($params);
 $totalItems = $countStmt->fetch()['total'];
 $totalPages = ceil($totalItems / $perPage);

// Calculate offset for pagination
 $offset = ($page - 1) * $perPage;

// Get data for main table
 $sql = "
    SELECT al.* FROM activity_logs al
    WHERE $whereClause
    ORDER BY al.created_at DESC
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
    // Get all data for PDF
    $pdfSql = "
        SELECT al.* FROM activity_logs al
        WHERE $whereClause
        ORDER BY al.created_at DESC
    ";
    $pdfDataStmt = $db->prepare($pdfSql);
    $pdfDataStmt->execute($params);
    $mainData = $pdfDataStmt->fetchAll(); // Override mainData for PDF export
    
    // Siapkan variabel untuk template
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    // Buat Filter Text untuk PDF
    $filterInfo = [];
    $filterInfo[] = "Dari: " . formatTanggal($dateFrom, 'd/m/Y');
    $filterInfo[] = "Sampai: " . formatTanggal($dateTo, 'd/m/Y');
    if ($actionType) $filterInfo[] = "Aksi: " . $actionType;
    $filterText = implode(' | ', $filterInfo);


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
                    ' . htmlspecialchars($siteName) . ' - Laporan Harian
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    // Menggunakan template PDF yang di-override
    include dirname(__FILE__) . '/templates/laporan_executive_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Harian_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Ringkasan global aktivitas utama portal.</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Laporan Harian</li>
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
                        <h6 class="text-white mb-2"><i class="bi bi-file-earmark-post"></i> Post Terbit</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['posts_today']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-eye"></i> Total Views</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['views_today']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-download"></i> Total Download</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['downloads_today']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-envelope"></i> Pesan Masuk</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['messages_today']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Tipe Aksi</label>
                        <select name="action_type" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $actionType == $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label">Per Hal</label>
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </form>
                <?php if ($dateFrom != $today || $dateTo != $today || $actionType): ?>
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
                        <h6 class="mb-1">Detail Log Aktivitas</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if ($dateFrom || $dateTo || $actionType): ?>
                            <br><span class="badge bg-info mt-1">Filter Aktif</span>
                        <?php endif; ?>
                    </div>

                    <a href="?export_pdf=1<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?><?= $actionType ? '&action_type='.$actionType : '' ?>"
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
                    Log Aktivitas
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
                                <th>User</th>
                                <th>Aksi</th>
                                <th>Tipe</th>
                                <th>Deskripsi</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td><small><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></small></td>
                                        <td><?= htmlspecialchars($row['user_name'] ?? 'Guest') ?></td>
                                        <td>
                                            <span class="badge bg-light-primary"><?= htmlspecialchars($row['action_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['model_type'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['description']) ?></td>
                                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
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