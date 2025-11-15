<?php
/**
 * Report: Executive Summary (Simple Version)
 * Laporan ringkasan eksekutif sederhana dengan fokus 1 tabel
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Executive Summary';
$db = Database::getInstance()->getConnection();

$exportPdf = $_GET['export_pdf'] ?? '';

// === MAIN DATA: Top Performing Posts dengan Detail Lengkap ===
$mainDataStmt = $db->query("
    SELECT 
        p.id,
        p.title,
        p.view_count,
        p.created_at,
        c.name as category_name,
        u.name as author_name,
        u.role as author_role,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
        (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') as comments,
        ROUND(((SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) + 
               (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved')) 
               / GREATEST(p.view_count, 1) * 100, 2) as engagement_rate
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.deleted_at IS NULL 
    AND p.status = 'published'
    ORDER BY p.view_count DESC
    LIMIT 20
");
$mainData = $mainDataStmt->fetchAll();

// === SUMMARY STATISTICS ===
$summaryStats = [
    'total_posts' => 0,
    'total_views' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
    'avg_engagement' => 0
];

$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total_posts,
        SUM(view_count) as total_views
    FROM posts 
    WHERE deleted_at IS NULL AND status = 'published'
");
$stats = $statsStmt->fetch();
$summaryStats['total_posts'] = $stats['total_posts'];
$summaryStats['total_views'] = $stats['total_views'] ?? 0;

$likesStmt = $db->query("SELECT COUNT(*) as total FROM post_likes");
$summaryStats['total_likes'] = $likesStmt->fetch()['total'];

$commentsStmt = $db->query("SELECT COUNT(*) as total FROM comments WHERE status = 'approved'");
$summaryStats['total_comments'] = $commentsStmt->fetch()['total'];

$summaryStats['avg_engagement'] = $summaryStats['total_views'] > 0 ? 
    round((($summaryStats['total_likes'] + $summaryStats['total_comments']) / $summaryStats['total_views']) * 100, 2) : 0;

// Export PDF
if ($exportPdf === '1') {
    $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
    $contactPhone = getSetting('contact_phone', '');
    $contactEmail = getSetting('contact_email', '');
    $contactAddress = getSetting('contact_address', '');
    $siteLogo = getSetting('site_logo', '');

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'orientation' => 'L', // Landscape untuk tabel lebar
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
                    ' . htmlspecialchars($siteName) . ' - Executive Summary Report
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include dirname(__FILE__) . '/templates/laporan_executive_simple_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Executive_Summary_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Laporan performa website ringkas</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Executive Summary</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Export Button & Summary -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-1">Laporan Executive Summary</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                    </div>
                    <a href="?export_pdf=1" class="btn btn-danger" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>

                <!-- Quick Stats -->
                <div class="row text-center">
                    <div class="col">
                        <h4 class="mb-0 text-primary"><?= formatNumber($summaryStats['total_posts']) ?></h4>
                        <small class="text-muted">Total Posts</small>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 text-success"><?= formatNumber($summaryStats['total_views']) ?></h4>
                        <small class="text-muted">Total Views</small>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 text-danger"><?= formatNumber($summaryStats['total_likes']) ?></h4>
                        <small class="text-muted">Total Likes</small>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 text-info"><?= formatNumber($summaryStats['total_comments']) ?></h4>
                        <small class="text-muted">Total Comments</small>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 text-warning"><?= $summaryStats['avg_engagement'] ?>%</h4>
                        <small class="text-muted">Avg. Engagement</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table: Top Performing Posts -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Top 20 Performing Posts - Detail Report</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul Post</th>
                                <th>Kategori</th>
                                <th>Penulis</th>
                                <th>Role</th>
                                <th>Views</th>
                                <th>Likes</th>
                                <th>Comments</th>
                                <th>Engagement</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mainData)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($mainData as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($row['category_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['author_name']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= ucfirst($row['author_role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?= formatNumber($row['view_count']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">
                                                <?= formatNumber($row['likes']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= formatNumber($row['comments']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?= $row['engagement_rate'] ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= formatTanggal($row['created_at'], 'd/m/Y') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-muted">
                    <small>
                        <strong>Keterangan:</strong><br>
                        Engagement Rate = (Likes + Comments) / Views × 100%<br>
                        Data diurutkan berdasarkan jumlah views tertinggi<br>
                        Hanya menampilkan post dengan status "published"
                    </small>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>