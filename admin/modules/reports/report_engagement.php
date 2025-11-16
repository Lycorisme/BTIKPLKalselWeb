<?php
/**
 * Report: Laporan Engagement
 * Fokus pada aktivitas kunjungan (page_views) dan interaksi (likes, comments)
 * Sesuai standar laporan executive
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Laporan Engagement';
 $db = Database::getInstance()->getConnection();

// Get filters
 $dateFrom = $_GET['date_from'] ?? '';
 $dateTo = $_GET['date_to'] ?? '';
 $ipFilter = $_GET['ip_filter'] ?? ''; // Filter untuk IP
 $typeFilter = $_GET['type_filter'] ?? ''; // Filter untuk Tipe Konten
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
 $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
 $exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
 $perPageOptions = [10, 25, 50, 100, 200];

// Get viewable types for filter (dari tabel page_views)
 $typesStmt = $db->query("SELECT DISTINCT viewable_type FROM page_views WHERE viewable_type IS NOT NULL AND viewable_type != '' ORDER BY viewable_type ASC");
 $viewableTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

// === Build WHERE conditions for Page Views (Untuk Kartu 1, 2, dan Tabel Utama) ===
 $whereConditions = ["1=1"];
 $params = [];

if ($dateFrom) {
    $whereConditions[] = "DATE(pv.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(pv.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

if ($ipFilter) {
    $whereConditions[] = "pv.ip_address = :ip_address";
    $params[':ip_address'] = $ipFilter;
}

if ($typeFilter) {
    $whereConditions[] = "pv.viewable_type = :viewable_type";
    $params[':viewable_type'] = $typeFilter;
}

 $whereClause = implode(' AND ', $whereConditions);

// === SUMMARY STATISTICS (4 Kartu) ===
 $summaryStats = [
    'total_views' => 0,
    'unique_visitors' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
];

// Card 1: Total Page Views (berdasarkan filter)
 $countSql = "SELECT COUNT(*) as total FROM page_views pv WHERE $whereClause";
 $countStmt = $db->prepare($countSql);
 $countStmt->execute($params);
 $totalItems = $countStmt->fetch()['total'];
 $summaryStats['total_views'] = $totalItems;
 $totalPages = ceil($totalItems / $perPage);

// Card 2: Sesi Visitor Unik (berdasarkan filter)
 $uniqueIpSql = "SELECT COUNT(DISTINCT pv.ip_address) FROM page_views pv WHERE $whereClause";
 $uniqueIpStmt = $db->prepare($uniqueIpSql);
 $uniqueIpStmt->execute($params);
 $summaryStats['unique_visitors'] = $uniqueIpStmt->fetchColumn();

// Build separate date-only filters for Likes & Comments (Kartu 3 & 4)
 $dateConditions = [];
 $dateParams = [];
if ($dateFrom) {
    $dateConditions[] = "DATE(created_at) >= :date_from";
    $dateParams[':date_from'] = $dateFrom;
}
if ($dateTo) {
    $dateConditions[] = "DATE(created_at) <= :date_to";
    $dateParams[':date_to'] = $dateTo;
}
 $dateWhereClause = empty($dateConditions) ? "1=1" : implode(' AND ', $dateConditions);

// Card 3: Total Like Seluruh Platform (hanya filter tanggal)
 $likesSql = "SELECT COUNT(*) FROM post_likes WHERE $dateWhereClause";
 $likesStmt = $db->prepare($likesSql);
 $likesStmt->execute($dateParams);
 $summaryStats['total_likes'] = $likesStmt->fetchColumn();

// Card 4: Total Komentar Seluruh Platform (hanya filter tanggal)
 $commentsSql = "SELECT COUNT(*) FROM comments WHERE status = 'approved' AND $dateWhereClause";
 $commentsStmt = $db->prepare($commentsSql);
 $commentsStmt->execute($dateParams);
 $summaryStats['total_comments'] = $commentsStmt->fetchColumn();


// === MAIN DATA (Paginated) ===
 $offset = ($page - 1) * $perPage;
 $sql = "
    SELECT 
        pv.id,
        pv.created_at, 
        pv.ip_address, 
        pv.viewable_type, 
        pv.viewable_id,
        pv.user_agent,
        CASE 
            WHEN pv.viewable_type = 'post' THEN (SELECT title FROM posts WHERE id = pv.viewable_id)
            WHEN pv.viewable_type = 'service' THEN (SELECT title FROM services WHERE id = pv.viewable_id)
            WHEN pv.viewable_type = 'page' THEN (SELECT title FROM pages WHERE id = pv.viewable_id)
            ELSE CONCAT('ID: ', pv.viewable_id)
        END as content_title
    FROM page_views pv
    WHERE $whereClause
    ORDER BY pv.created_at DESC
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
    // Get all data for PDF (tanpa limit)
    $pdfSql = "
        SELECT 
            pv.id,
            pv.created_at, 
            pv.ip_address, 
            pv.viewable_type, 
            pv.viewable_id,
            pv.user_agent,
            CASE 
                WHEN pv.viewable_type = 'post' THEN (SELECT title FROM posts WHERE id = pv.viewable_id)
                WHEN pv.viewable_type = 'service' THEN (SELECT title FROM services WHERE id = pv.viewable_id)
                WHEN pv.viewable_type = 'page' THEN (SELECT title FROM pages WHERE id = pv.viewable_id)
                ELSE CONCAT('ID: ', pv.viewable_id)
            END as content_title
        FROM page_views pv
        WHERE $whereClause
        ORDER BY pv.created_at DESC
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
                    ' . htmlspecialchars($siteName) . ' - Laporan Engagement
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    // Menggunakan template PDF yang baru
    include dirname(__FILE__) . '/templates/laporan_engagement_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Engagement_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Analisis aktivitas kunjungan dan interaksi pengguna.</p>
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
                        <h6 class="text-white mb-2"><i class="bi bi-eye"></i> Total Page Views</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_views']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-people-fill"></i> IP Visitor Unik</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['unique_visitors']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-heart-fill"></i> Total Likes</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_likes']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-chat-dots-fill"></i> Total Komentar</h6>
                        <h2 class="mb-0"><?= formatNumber($summaryStats['total_comments']) ?></h2>
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
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Tipe Konten</label>
                        <select name="type_filter" class="form-select">
                            <option value="">-- Semua --</option>
                            <?php foreach ($viewableTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $typeFilter == $type ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_filter" class="form-control" value="<?= htmlspecialchars($ipFilter) ?>" placeholder="Filter by IP">
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
                <?php if ($dateFrom || $dateTo || $ipFilter || $typeFilter): ?>
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
                        <h6 class="mb-1">Laporan Detail Engagement</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if ($dateFrom || $dateTo || $ipFilter || $typeFilter): ?>
                            <br><span class="badge bg-info mt-1">Filter Aktif</span>
                        <?php endif; ?>
                    </div>

                    <a href="?export_pdf=1<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?><?= $typeFilter ? '&type_filter='.$typeFilter : '' ?><?= $ipFilter ? '&ip_filter='.$ipFilter : '' ?>"
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
                    Rekap Detail Kunjungan
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Konten yang Dilihat</th>
                                <th>Tipe</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = $offset + 1; 
                                foreach ($mainData as $row): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['content_title'] ?? 'N/A') ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-secondary"><?= htmlspecialchars($row['viewable_type']) ?></span>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($row['ip_address']) ?></td>
                                        <td>
                                            <small title="<?= htmlspecialchars($row['user_agent']) ?>">
                                                <?= htmlspecialchars(substr($row['user_agent'], 0, 50)) . '...' ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($row['created_at'], 'd/m/Y H:i') ?></small>
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