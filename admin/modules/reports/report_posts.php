<?php
/**
 * Report: Posts/Artikel
 * Laporan posts beserta statistik & export PDF
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Posts';
$db = Database::getInstance()->getConnection();

// Get filters
$categoryId = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$authorId = $_GET['author_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$exportPdf = $_GET['export_pdf'] ?? '';

// Build query dengan like & comment count - FIXED: using comments table with polymorphic relation
$sql = "SELECT p.*, 
        c.name as category_name, 
        u.name as author_name,
        (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM comments cm WHERE cm.commentable_type = 'post' AND cm.commentable_id = p.id AND cm.status = 'approved') as comment_count
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.deleted_at IS NULL";

$params = [];

if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($status) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

if ($authorId) {
    $sql .= " AND p.author_id = ?";
    $params[] = $authorId;
}

if ($dateFrom) {
    $sql .= " AND DATE(p.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(p.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => count($posts),
    'published' => 0,
    'draft' => 0,
    'archived' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
    'total_views' => 0
];

foreach ($posts as $post) {
    if ($post['status'] === 'published') $stats['published']++;
    if ($post['status'] === 'draft') $stats['draft']++;
    if ($post['status'] === 'archived') $stats['archived']++;
    $stats['total_likes'] += $post['like_count'];
    $stats['total_comments'] += $post['comment_count'];
    $stats['total_views'] += $post['view_count'];
}

// Get category statistics
$categoryStatsStmt = $db->query("
    SELECT c.name, COUNT(p.id) as total
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY total DESC
");
$categoryStats = $categoryStatsStmt->fetchAll();

// Get author statistics - Limit to top 10
$authorStatsStmt = $db->query("
    SELECT u.name, COUNT(p.id) as total
    FROM users u
    LEFT JOIN posts p ON u.id = p.author_id AND p.deleted_at IS NULL
    GROUP BY u.id, u.name
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
");
$authorStats = $authorStatsStmt->fetchAll();

// Get data for filters
$categoriesStmt = $db->query("SELECT * FROM post_categories WHERE deleted_at IS NULL ORDER BY name");
$categories = $categoriesStmt->fetchAll();

$authorsStmt = $db->query("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");
$authors = $authorsStmt->fetchAll();

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
    include __DIR__ . '/templates/laporan_posts_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Laporan_Posts_' . date('Ymd_His') . '.pdf', 'I');
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
                        <li class="breadcrumb-item active">Posts</li>
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
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="published" <?= $status == 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $status == 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= $status == 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Penulis</label>
                        <select name="author_id" class="form-select">
                            <option value="">Semua Penulis</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= $author['id'] ?>" <?= $authorId == $author['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($author['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                        <a href="report_posts.php" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                        <a href="?export_pdf=1<?= $categoryId ? '&category_id='.$categoryId : '' ?><?= $status ? '&status='.$status : '' ?><?= $authorId ? '&author_id='.$authorId : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?>"
                           class="btn btn-danger" target="_blank">
                            <i class="bi bi-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Post</h6>
                        <h3 class="mb-0"><?= formatNumber($stats['total']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Published</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($stats['published']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Draft</h6>
                        <h3 class="mb-0 text-warning"><?= formatNumber($stats['draft']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Likes</h6>
                        <h3 class="mb-0 text-danger"><?= formatNumber($stats['total_likes']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Komentar</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($stats['total_comments']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Views</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($stats['total_views']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Daftar Post (<?= formatNumber(count($posts)) ?> data)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 4%;">No</th>
                                <th style="width: 28%;">Judul</th>
                                <th style="width: 10%;">Tanggal Post</th>
                                <th style="width: 12%;">Kategori</th>
                                <th style="width: 12%;">Penulis</th>
                                <th style="width: 9%;">Status</th>
                                <th style="width: 7%;">Likes</th>
                                <th style="width: 8%;">Komentar</th>
                                <th style="width: 7%;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada data post</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <?= htmlspecialchars($post['title']) ?>
                                            <?php if ($post['is_featured']): ?>
                                                <i class="bi bi-star-fill text-warning ms-1" title="Featured"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatTanggal($post['created_at'], 'd/m/Y') ?></td>
                                        <td><?= htmlspecialchars($post['category_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($post['author_name'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($post['status'] === 'published'): ?>
                                                <span class="badge bg-success">Published</span>
                                            <?php elseif ($post['status'] === 'draft'): ?>
                                                <span class="badge bg-warning">Draft</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">
                                                <i class="bi bi-heart-fill"></i> <?= formatNumber($post['like_count']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">
                                                <i class="bi bi-chat-dots-fill"></i> <?= formatNumber($post['comment_count']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">
                                                <i class="bi bi-eye-fill"></i> <?= formatNumber($post['view_count']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Category & Author Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Post per Kategori</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th class="text-end" style="width: 100px;">Jumlah</th>
                                        <th class="text-end" style="width: 100px;">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalPosts = array_sum(array_column($categoryStats, 'total'));
                                    foreach ($categoryStats as $cat): 
                                        $percentage = $totalPosts > 0 ? ($cat['total'] / $totalPosts * 100) : 0;
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($cat['total']) ?></strong></td>
                                            <td class="text-end"><?= number_format($percentage, 1) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <?php if ($totalPosts > 0): ?>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end"><?= formatNumber($totalPosts) ?></td>
                                        <td class="text-end">100%</td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Top 10 Penulis Teraktif</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Rank</th>
                                        <th>Penulis</th>
                                        <th class="text-end" style="width: 100px;">Jumlah Post</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($authorStats)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $rank = 1; foreach ($authorStats as $author): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?php if ($rank == 1): ?>
                                                        <span class="badge bg-warning">🥇</span>
                                                    <?php elseif ($rank == 2): ?>
                                                        <span class="badge bg-secondary">🥈</span>
                                                    <?php elseif ($rank == 3): ?>
                                                        <span class="badge bg-info">🥉</span>
                                                    <?php else: ?>
                                                        <?= $rank ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($author['name']) ?></td>
                                                <td class="text-end"><strong><?= formatNumber($author['total']) ?></strong></td>
                                            </tr>
                                            <?php $rank++; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
