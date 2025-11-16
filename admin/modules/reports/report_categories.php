<?php
/**
 * Report: Laporan Kategori
 * Menampilkan data agregat untuk setiap kategori post.
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Kategori';
$db = Database::getInstance()->getConnection();

// Get filters
$categoryName = $_GET['category_name'] ?? '';
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$exportPdf = $_GET['export_pdf'] ?? '';

// Per page options
$perPageOptions = [10, 25, 50, 100, 200];

// Build WHERE conditions
$whereConditions = ["c.deleted_at IS NULL"];
$params = [];

if ($categoryName) {
    $whereConditions[] = "c.name LIKE :category_name";
    $params[':category_name'] = "%$categoryName%";
}

$whereClause = implode(' AND ', $whereConditions);

// === MAIN DATA QUERY (Agregat) ===
// Query ini mengambil semua data untuk kartu dan tabel
$sqlAll = "
    SELECT 
        c.id, 
        c.name, 
        c.slug,
        COUNT(DISTINCT p.id) as post_count,
        COALESCE(SUM(p.view_count), 0) as total_views,
        COALESCE(SUM(p.like_count), 0) as total_likes,
        COALESCE(SUM(p.comment_count), 0) as total_comments,
        
        -- Engagement Rate per Kategori
        COALESCE(
            ROUND(
                ( (COALESCE(SUM(p.like_count), 0) + COALESCE(SUM(p.comment_count), 0)) / GREATEST(COALESCE(SUM(p.view_count), 0), 1) ) * 100
            , 2), 0) as engagement_rate
            
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id
                       AND p.status = 'published' 
                       AND p.deleted_at IS NULL
    WHERE $whereClause
    GROUP BY c.id, c.name, c.slug
    ORDER BY post_count DESC
";

$allDataStmt = $db->prepare($sqlAll);
$allDataStmt->execute($params);
$allData = $allDataStmt->fetchAll(PDO::FETCH_ASSOC);

// === SUMMARY STATISTICS (4 Kartu) ===
$summaryStats = [
    'total_categories' => 0,
    'top_cat_posts_name' => 'N/A',
    'top_cat_posts_count' => 0,
    'top_cat_views_name' => 'N/A',
    'top_cat_views_count' => 0,
    'top_cat_eng_name' => 'N/A',
    'top_cat_eng_rate' => 0,
];

$summaryStats['total_categories'] = count($allData);

if (!empty($allData)) {
    // Card 2: Kategori dengan post terbanyak
    // Data sudah di-sort by post_count DESC
    $summaryStats['top_cat_posts_name'] = $allData[0]['name'];
    $summaryStats['top_cat_posts_count'] = $allData[0]['post_count'];

    // Card 3: Kategori dengan views paling tinggi
    $sortedByViews = $allData;
    usort($sortedByViews, function($a, $b) { return $b['total_views'] <=> $a['total_views']; });
    $summaryStats['top_cat_views_name'] = $sortedByViews[0]['name'];
    $summaryStats['top_cat_views_count'] = $sortedByViews[0]['total_views'];
    
    // Card 4: Kategori engagement tertinggi
    $sortedByEng = $allData;
    usort($sortedByEng, function($a, $b) { return $b['engagement_rate'] <=> $a['engagement_rate']; });
    $summaryStats['top_cat_eng_name'] = $sortedByEng[0]['name'];
    $summaryStats['top_cat_eng_rate'] = $sortedByEng[0]['engagement_rate'];
}


// === Pagination Logic ===
$totalItems = $summaryStats['total_categories'];
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

// $mainData adalah versi paginasi dari $allData (yang sudah di-sort by post_count)
$mainData = array_slice($allData, $offset, $perPage);

// Export PDF (export all data without pagination)
if ($exportPdf === '1') {
    
    $mainData = $allData; // Override $mainData dengan semua data untuk PDF
    
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
                    ' . htmlspecialchars($siteName) . ' - Laporan Kategori
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    // Menggunakan template PDF yang baru
    include dirname(__FILE__) . '/templates/laporan_categories_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Kategori_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Laporan agregat performa semua kategori post.</p>
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
                        <h6 class="text-white mb-2">
                            <i class="bi bi-folder-fill"></i> Kategori Aktif
                        </h6>
                        <h5 class="mb-0"><?= formatNumber($summaryStats['total_categories']) ?></h5>
                        <small>(Terdata)</small>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-bookmark-star-fill"></i> Kategori Terbanyak</h6>
                        <h4 class="mb-0 text-white" style="font-size: 1.2rem;"><?= htmlspecialchars($summaryStats['top_cat_posts_name']) ?></h4>
                        <small>(<?= formatNumber($summaryStats['top_cat_posts_count']) ?> Posts)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-eye-fill"></i> Dilihat Terbanyak</h6>
                         <h4 class="mb-0 text-white" style="font-size: 1.2rem;"><?= htmlspecialchars($summaryStats['top_cat_views_name']) ?></h4>
                        <small>(<?= formatNumber($summaryStats['top_cat_views_count']) ?> Views)</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-graph-up"></i> Katgori Populer</h6>
                         <h4 class="mb-0 text-white" style="font-size: 1.2rem;"><?= htmlspecialchars($summaryStats['top_cat_eng_name']) ?></h4>
                        <small>(<?= $summaryStats['top_cat_eng_rate'] ?>%)</small>
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
                    <div class="col-12 col-md-9">
                        <label class="form-label">Cari Nama Kategori</label>
                        <input type="text" name="category_name" class="form-control" value="<?= htmlspecialchars($categoryName) ?>" placeholder="Masukkan nama kategori...">
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label">Per Hal</label>
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                         <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
                <?php if ($categoryName): ?>
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
                        <h6 class="mb-1">Laporan Detail Performa Kategori</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                        <?php if ($categoryName): ?>
                            <br><span class="badge bg-info mt-1">Filter Aktif</span>
                        <?php endif; ?>
                    </div>

                    <a href="?export_pdf=1<?= $categoryName ? '&category_name='.$categoryName : '' ?>"
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
                    Data Performa Kategori
                    <span class="badge bg-primary"><?= $totalItems ?> Data</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Nama Kategori</th>
                                <th>Jml Post</th>
                                <th>Total Views</th>
                                <th>Total Likes</th>
                                <th>Total Comments</th>
                                <th>Engagement</th>
                                <th>Content Gap</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data kategori</td>
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
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= formatNumber($row['post_count']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= formatNumber($row['total_views']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= formatNumber($row['total_likes']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= formatNumber($row['total_comments']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= $row['engagement_rate'] ?>%</span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['post_count'] == 0): ?>
                                                <span class="badge bg-danger">Ya</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
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
                                Halaman <?= $page ?> dari <?= $totalPages ?> &middot; Menampilkan <?= count($mainData) ?> dari <?= $totalItems ?> data
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