<?php
/**
 * Report: Tags
 * Laporan penggunaan tags pada posts
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Tags';
$db = Database::getInstance()->getConnection();

// Get filters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query for tags with usage statistics
$sql = "
    SELECT 
        t.id,
        t.name,
        t.created_at,
        COUNT(DISTINCT pt.post_id) as total_used,
        (
            SELECT p.title 
            FROM posts p
            INNER JOIN post_tags pt2 ON p.id = pt2.post_id
            WHERE pt2.tag_id = t.id 
            AND p.deleted_at IS NULL
            ORDER BY p.created_at DESC
            LIMIT 1
        ) as latest_post,
        (
            SELECT MAX(p.created_at)
            FROM posts p
            INNER JOIN post_tags pt3 ON p.id = pt3.post_id
            WHERE pt3.tag_id = t.id
            AND p.deleted_at IS NULL
        ) as last_used_date,
        (
            SELECT SUM(p.view_count)
            FROM posts p
            INNER JOIN post_tags pt4 ON p.id = pt4.post_id
            WHERE pt4.tag_id = t.id
            AND p.deleted_at IS NULL
        ) as total_views
    FROM tags t
    LEFT JOIN post_tags pt ON t.id = pt.tag_id
    LEFT JOIN posts p ON pt.post_id = p.id AND p.deleted_at IS NULL
    WHERE t.deleted_at IS NULL
";

$params = [];

if ($dateFrom) {
    $sql .= " AND DATE(t.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(t.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= "
    GROUP BY t.id, t.name, t.created_at
    ORDER BY total_used DESC, t.name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tags = $stmt->fetchAll();

// Calculate statistics
$stats = [
    'total' => count($tags),
    'used' => 0,
    'unused' => 0,
    'total_views' => 0,
    'total_posts' => 0
];

foreach ($tags as $tag) {
    if ($tag['total_used'] > 0) {
        $stats['used']++;
        $stats['total_posts'] += $tag['total_used'];
    } else {
        $stats['unused']++;
    }
    $stats['total_views'] += (int)$tag['total_views'];
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
        'orientation' => 'P',
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
    include __DIR__ . '/templates/laporan_tags_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Tags_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Tags</li>
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
                            <a href="report_tags.php" class="btn btn-secondary me-2">
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
                        <h6 class="text-muted mb-2">Total Tags</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Tags Terpakai</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['used']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Tags Tidak Terpakai</h6>
                        <h3 class="mb-0 text-warning"><?= formatNumber($stats['unused']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Views</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($stats['total_views']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tags Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Tags (<?= formatNumber(count($tags)) ?> data)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 20%;">Nama Tag</th>
                                <th style="width: 12%;">Dibuat Tanggal</th>
                                <th style="width: 10%;">Total Digunakan</th>
                                <th style="width: 25%;">Post Terbaru</th>
                                <th style="width: 13%;">Terakhir Digunakan</th>
                                <th style="width: 10%;">Total Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data tags</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($tags as $tag): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= htmlspecialchars($tag['name']) ?></span>
                                        </td>
                                        <td><?= formatTanggal($tag['created_at'], 'd/m/Y') ?></td>
                                        <td class="text-center">
                                            <?php if ($tag['total_used'] > 0): ?>
                                                <span class="badge bg-success"><?= formatNumber($tag['total_used']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tag['latest_post']): ?>
                                                <?= htmlspecialchars($tag['latest_post']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tag['last_used_date']): ?>
                                                <?= formatTanggal($tag['last_used_date'], 'd/m/Y') ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatNumber($tag['total_views'] ?? 0) ?></td>
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
