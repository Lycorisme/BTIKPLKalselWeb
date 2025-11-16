<?php
/**
 * Report: Laporan Layanan
 * Menampilkan data statistik dan daftar layanan.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Layanan';
 $db = Database::getInstance()->getConnection();

// Get filters
 $dateFrom = $_GET['date_from'] ?? ''; // Filter by created_at
 $dateTo = $_GET['date_to'] ?? ''; // Filter by created_at
 $statusFilter = $_GET['status'] ?? '';
 $titleFilter = $_GET['title'] ?? '';
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// === SUMMARY STATISTICS (4 Kartu) - Global Stats ===
 $summaryStats = [
    'total_active_services' => 0,
    'busiest_service_name' => 'N/A',
    'busiest_service_views' => 0,
    'total_views' => 0,
    'latest_update_name' => 'N/A',
];

// Card 1: Total layanan aktif
 $summaryStats['total_active_services'] = $db->query("SELECT COUNT(*) FROM services WHERE deleted_at IS NULL AND status = 'published'")->fetchColumn();

// Card 2: Layanan paling ramai (views terbanyak)
 $busiestSql = "
    SELECT s.title, COUNT(pv.id) as view_count
    FROM services s
    JOIN page_views pv ON s.id = pv.viewable_id AND pv.viewable_type = 'service'
    WHERE s.deleted_at IS NULL
    GROUP BY s.id, s.title
    ORDER BY view_count DESC
    LIMIT 1
";
 $busiestService = $db->query($busiestSql)->fetch();
 if ($busiestService) {
    $summaryStats['busiest_service_name'] = $busiestService['title'];
    $summaryStats['busiest_service_views'] = $busiestService['view_count'];
 }

// Card 3: Total views seluruh layanan
 $summaryStats['total_views'] = $db->query("SELECT COUNT(*) FROM page_views WHERE viewable_type = 'service'")->fetchColumn();

// Card 4: Layanan update terbaru
 $latestService = $db->query("SELECT title FROM services WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT 1")->fetch();
 if ($latestService) {
    $summaryStats['latest_update_name'] = $latestService['title'];
 }


// === MAIN DATA (Tabel Utama dengan Filter) ===
 $whereConditions = ["s.deleted_at IS NULL"];
 $params = [];

if ($dateFrom) {
    $whereConditions[] = "DATE(s.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(s.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

if ($statusFilter) {
    $whereConditions[] = "s.status = :status";
    $params[':status'] = $statusFilter;
}

if ($titleFilter) {
    $whereConditions[] = "s.title LIKE :title";
    $params[':title'] = "%$titleFilter%";
}

 $whereClause = implode(' AND ', $whereConditions);

// Hitung total items untuk paginasi
 $countSql = "SELECT COUNT(s.id) FROM services s WHERE $whereClause";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($params);
 $totalItems = $countStmt->fetchColumn();
 $totalPages = ceil($totalItems / $perPage);
 $offset = ($page - 1) * $perPage;

// Get data for main table
 $sql = "
    SELECT 
        s.id, s.title, s.status, s.updated_at, s.created_at,
        (SELECT COUNT(*) FROM page_views pv WHERE pv.viewable_type = 'service' AND pv.viewable_id = s.id) as total_views
    FROM services s
    WHERE $whereClause
    ORDER BY s.updated_at DESC
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
        SELECT 
            s.id, s.title, s.status, s.updated_at, s.created_at,
            (SELECT COUNT(*) FROM page_views pv WHERE pv.viewable_type = 'service' AND pv.viewable_id = s.id) as total_views
        FROM services s
        WHERE $whereClause
        ORDER BY s.updated_at DESC
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
                    ' . htmlspecialchars($siteName) . ' - Laporan Layanan
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_services_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Layanan_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Statistik dan data layanan terintegrasi.</p>
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
                        <h6 class="text-white mb-2"><i class="bi bi-grid-fill"></i> Total Layanan</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_active_services']) ?></h2>
                        <small>(teraktifasi)</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">
                            <i class="bi bi-bookmark-star-fill"></i> Layanan Terbaik
                        </h6>
                        <h2 class="mb-0 text-white">
                            <?= htmlspecialchars($summaryStats['busiest_service_name']) ?>
                        </h2>
                        <small>(<?= formatNumber($summaryStats['busiest_service_views']) ?> Views)</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">
                            <i class="bi bi-eye-fill"></i> Total Views
                        </h6>
                        <h2 class="mb-0">
                            <?= formatNumber($summaryStats['total_views']) ?>
                        </h2>
                        <small>(Membuka)</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">
                            <i class="bi bi-clock-history"></i> Layanan Terbaru
                        </h6>
                        <h2 class="mb-0 text-white">
                            <?= htmlspecialchars($summaryStats['latest_update_name']) ?>
                        </h2>
                        <small>(Baru Dibuat)</small>
                    </div>
                </div>
            </div>


        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tgl Dibuat Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tgl Dibuat Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Judul Layanan</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($titleFilter) ?>" placeholder="Cari judul...">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua --</option>
                            <option value="published" <?= $statusFilter == 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $statusFilter == 'draft' ? 'selected' : '' ?>>Draft</option>
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
                <?php if ($dateFrom || $dateTo || $statusFilter || $titleFilter): ?>
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
                        <h6 class="mb-1">Laporan Detail Layanan</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if ($dateFrom || $dateTo || $statusFilter || $titleFilter): ?>
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
                    Data Layanan
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Judul Layanan</th>
                                <th>Status</th>
                                <th>Total Dikunjungi</th>
                                <th>Tgl Dibuat</th>
                                <th>Update Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada data layanan</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['status'] == 'published'): ?>
                                                <span class="badge bg-light-success">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-light-secondary">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= formatNumber($row['total_views']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($row['created_at'], 'd/m/Y') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($row['updated_at'], 'd/m/Y H:i') ?></small>
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