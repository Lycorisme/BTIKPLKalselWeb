<?php
/**
 * Report: Laporan File Download
 * Menampilkan data statistik dan daftar file yang dapat diunduh.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan File Download';
 $db = Database::getInstance()->getConnection();

// Get filters
 $dateFrom = $_GET['date_from'] ?? '';
 $dateTo = $_GET['date_to'] ?? '';
 $uploaderId = $_GET['uploader_id'] ?? '';
 $fileType = $_GET['file_type'] ?? '';
 $isActive = $_GET['is_active'] ?? ''; // Filter status
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get uploaders for filter
 $authorsStmt = $db->query("SELECT id, name FROM users WHERE deleted_at IS NULL AND role IN ('super_admin', 'admin', 'editor', 'author') ORDER BY name ASC");
 $uploaders = $authorsStmt->fetchAll();

// Get file types for filter
 $typesStmt = $db->query("SELECT DISTINCT file_type FROM downloadable_files WHERE deleted_at IS NULL AND file_type IS NOT NULL ORDER BY file_type ASC");
 $fileTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

// Build WHERE conditions
 $whereConditions = ["df.deleted_at IS NULL"];
 $params = [];

if ($dateFrom) {
    $whereConditions[] = "DATE(df.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(df.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

if ($uploaderId) {
    $whereConditions[] = "df.uploaded_by = :uploader_id";
    $params[':uploader_id'] = $uploaderId;
}

if ($fileType) {
    $whereConditions[] = "df.file_type = :file_type";
    $params[':file_type'] = $fileType;
}

if ($isActive !== '') {
    $whereConditions[] = "df.is_active = :is_active";
    $params[':is_active'] = $isActive;
}

 $whereClause = implode(' AND ', $whereConditions);

// === MAIN DATA QUERY (untuk statistik dan tabel) ===
 $sqlBase = "
    FROM downloadable_files df
    LEFT JOIN users u ON df.uploaded_by = u.id
    WHERE $whereClause
";

// Hitung total items untuk paginasi
 $countSql = "SELECT COUNT(df.id) $sqlBase";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($params);
 $totalItems = $countStmt->fetchColumn();
 $totalPages = ceil($totalItems / $perPage);
 $offset = ($page - 1) * $perPage;

// === SUMMARY STATISTICS (4 Kartu) ===
 $summaryStats = [
    'total_active_files' => 0,
    'top_file_name' => 'N/A',
    'top_file_downloads' => 0,
    'total_downloads' => 0,
    'new_files_monthly' => 0,
];

// Card 1: Total file aktif (Sesuai Filter)
 $activeWhere = $whereClause . " AND df.is_active = 1";
 $activeSql = "SELECT COUNT(df.id) FROM downloadable_files df WHERE $activeWhere";
 $activeStmt = $db->prepare($activeSql);
 $activeStmt->execute($params);
 $summaryStats['total_active_files'] = $activeStmt->fetchColumn();

// Card 3: Total download semua file (Sesuai Filter)
 $downloadsSql = "SELECT SUM(df.download_count) $sqlBase";
 $downloadsStmt = $db->prepare($downloadsSql);
 $downloadsStmt->execute($params);
 $summaryStats['total_downloads'] = $downloadsStmt->fetchColumn() ?? 0;

// Card 2: Download terbanyak (Sesuai Filter)
 $topFileSql = "SELECT df.title, df.download_count $sqlBase ORDER BY df.download_count DESC LIMIT 1";
 $topFileStmt = $db->prepare($topFileSql);
 $topFileStmt->execute($params);
 $topFile = $topFileStmt->fetch();
 if ($topFile) {
    $summaryStats['top_file_name'] = $topFile['title'];
    $summaryStats['top_file_downloads'] = $topFile['download_count'];
 }

// Card 4: File baru upload (30 hari terakhir) - Statis, tidak terpengaruh filter
 $newFilesSql = "SELECT COUNT(*) FROM downloadable_files WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
 $summaryStats['new_files_monthly'] = $db->query($newFilesSql)->fetchColumn();


// === MAIN DATA (Paginated) ===
 $sql = "
    SELECT 
        df.id, df.title, df.file_type, df.download_count, df.created_at, df.is_active, df.file_size,
        u.name as uploader_name
    $sqlBase
    ORDER BY df.created_at DESC
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
        SELECT 
            df.id, df.title, df.file_type, df.download_count, df.created_at, df.is_active, df.file_size,
            u.name as uploader_name
        $sqlBase
        ORDER BY df.created_at DESC
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
                    ' . htmlspecialchars($siteName) . ' - Laporan File Download
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_files_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_File_Download_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Statistik dan data file yang dapat diunduh.</p>
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
                        <h6 class="text-white mb-2"><i class="bi bi-file-earmark-check"></i> Total File Aktif</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_active_files']) ?></h2>
                        <small>(Sesuai Filter)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center" style="padding-bottom: 1.2rem;">
                        <h6 class="text-white mb-2"><i class="bi bi-bookmark-star-fill"></i> Download Terbanyak</h6>
                        <h4 class="mb-0 text-white" style="font-size: 1.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($summaryStats['top_file_name']) ?>">
                            <?= htmlspecialchars($summaryStats['top_file_name']) ?>
                        </h4>
                        <small>(<?= formatNumber($summaryStats['top_file_downloads']) ?> Unduhan)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-download"></i> Total Download</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['total_downloads']) ?></h2>
                         <small>(Sesuai Filter)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-calendar-plus"></i> File Baru (30 Hari)</h6>
                         <h2 class="mb-0"><?= formatNumber($summaryStats['new_files_monthly']) ?></h2>
                         <small>(Statis)</small>
                    </div>
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
                        <label class="form-label">Tgl Upload Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tgl Upload Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Uploader</label>
                        <select name="uploader_id" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php foreach ($uploaders as $uploader): ?>
                                <option value="<?= $uploader['id'] ?>" <?= $uploaderId == $uploader['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($uploader['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tipe File</label>
                        <select name="file_type" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php foreach ($fileTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $fileType == $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">-- Semua --</option>
                            <option value="1" <?= $isActive == '1' ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= $isActive == '0' ? 'selected' : '' ?>>Non-Aktif</option>
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
                         <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
                <?php if ($dateFrom || $dateTo || $uploaderId || $fileType || $isActive !== ''): ?>
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
                        <h6 class="mb-1">Laporan Detail File Download</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if ($dateFrom || $dateTo || $uploaderId || $fileType || $isActive !== ''): ?>
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
                    Data File
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama File</th>
                                <th>Tipe</th>
                                <th>Uploader</th>
                                <th>Status</th>
                                <th>Downloads</th>
                                <th>Ukuran</th>
                                <th>Tgl Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data file</td>
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
                                            <span class="badge bg-light-secondary"><?= htmlspecialchars($row['file_type']) ?></span>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($row['uploader_name'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php if ($row['is_active']): ?>
                                                <span class="badge bg-light-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-light-danger">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= formatNumber($row['download_count']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatUkuranFile($row['file_size']) ?></small>
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