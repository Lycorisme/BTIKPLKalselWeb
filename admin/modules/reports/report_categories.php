<?php
/**
 * Report: Categories
 * Laporan kategori posts beserta statistik penggunaan
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Kategori';
$db = Database::getInstance()->getConnection();

// Get filters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query for categories with statistics
$sql = "
    SELECT 
        c.id,
        c.name,
        c.created_at,
        COUNT(p.id) as total_posts,
        (
            SELECT p2.title
            FROM posts p2
            WHERE p2.category_id = c.id
            AND p2.deleted_at IS NULL
            ORDER BY p2.created_at DESC
            LIMIT 1
        ) as latest_post,
        (
            SELECT MAX(p3.created_at)
            FROM posts p3
            WHERE p3.category_id = c.id
            AND p3.deleted_at IS NULL
        ) as last_used_date,
        SUM(p.view_count) as total_views
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    WHERE c.deleted_at IS NULL
";

$params = [];

if ($dateFrom) {
    $sql .= " AND DATE(c.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(c.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= "
    GROUP BY c.id, c.name, c.created_at
    ORDER BY total_posts DESC, c.name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Calculate statistics
$stats = [
    'total' => count($categories),
    'used' => 0,
    'unused' => 0,
    'total_posts' => 0,
    'total_views' => 0
];

foreach ($categories as $cat) {
    if ($cat['total_posts'] > 0) {
        $stats['used']++;
    } else {
        $stats['unused']++;
    }
    $stats['total_posts'] += $cat['total_posts'];
    $stats['total_views'] += (int)$cat['total_views'];
}

// Export PDF
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $siteTagline = getSetting('site_tagline', '');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'P', // Portrait
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
                    ' . htmlspecialchars($siteName) . '
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_categories_pdf.php';
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
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Kategori</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <a href="report_categories.php" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1<?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>"
                               class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Kategori</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Kategori Terpakai</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['used']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Posts</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($stats['total_posts']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Views</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($stats['total_views']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Kategori (<?= formatNumber(count($categories)) ?> data)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 20%;">Nama Kategori</th>
                                <th style="width: 12%;">Dibuat Tanggal</th>
                                <th style="width: 10%;">Total Post</th>
                                <th style="width: 30%;">Post Terbaru</th>
                                <th style="width: 13%;">Terakhir Digunakan</th>
                                <th style="width: 10%;">Total Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data kategori</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                        <td><?= formatTanggal($cat['created_at'], 'd/m/Y') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= formatNumber($cat['total_posts']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($cat['latest_post']): ?>
                                                <?= htmlspecialchars($cat['latest_post']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($cat['last_used_date']): ?>
                                                <?= formatTanggal($cat['last_used_date'], 'd/m/Y') ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatNumber($cat['total_views'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
