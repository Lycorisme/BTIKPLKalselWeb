<?php
/**
 * Report: Engagement Report
 * Laporan interaksi user dengan konten (likes, comments, views)
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../vendor/autoload.php';

if (!hasRole(['super_admin', 'admin', 'editor'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Laporan Engagement';
$db = Database::getInstance()->getConnection();

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default: awal bulan ini
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Default: hari ini
$exportPdf = $_GET['export_pdf'] ?? '';

// === 1. OVERALL ENGAGEMENT METRICS ===
$overallMetrics = [
    'total_posts' => 0,
    'total_views' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
    'avg_engagement_rate' => 0,
    'total_downloads' => 0,
    'total_messages' => 0
];

// Posts with engagement
$metricsStmt = $db->prepare("
    SELECT 
        COUNT(p.id) as total_posts,
        SUM(p.view_count) as total_views,
        (SELECT COUNT(*) FROM post_likes WHERE post_id IN (SELECT id FROM posts WHERE deleted_at IS NULL AND created_at BETWEEN ? AND ?)) as total_likes,
        (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND status = 'approved' AND created_at BETWEEN ? AND ?) as total_comments
    FROM posts p
    WHERE p.deleted_at IS NULL
    AND p.status = 'published'
    AND p.created_at BETWEEN ? AND ?
");
$metricsStmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
$metrics = $metricsStmt->fetch();

$overallMetrics['total_posts'] = $metrics['total_posts'];
$overallMetrics['total_views'] = $metrics['total_views'] ?? 0;
$overallMetrics['total_likes'] = $metrics['total_likes'] ?? 0;
$overallMetrics['total_comments'] = $metrics['total_comments'] ?? 0;

// Calculate engagement rate
if ($overallMetrics['total_views'] > 0) {
    $totalEngagements = $overallMetrics['total_likes'] + $overallMetrics['total_comments'];
    $overallMetrics['avg_engagement_rate'] = round(($totalEngagements / $overallMetrics['total_views']) * 100, 2);
}

// Downloads
$downloadsStmt = $db->query("SELECT SUM(download_count) as total FROM downloadable_files WHERE deleted_at IS NULL");
$overallMetrics['total_downloads'] = $downloadsStmt->fetch()['total'] ?? 0;

// Contact messages
$messagesStmt = $db->prepare("SELECT COUNT(*) as total FROM contact_messages WHERE created_at BETWEEN ? AND ? AND deleted_at IS NULL");
$messagesStmt->execute([$dateFrom, $dateTo]);
$overallMetrics['total_messages'] = $messagesStmt->fetch()['total'];

// === 2. TOP 10 MOST ENGAGING POSTS ===
$topEngagingStmt = $db->prepare("
    SELECT 
        p.id,
        p.title,
        p.view_count,
        c.name as category_name,
        u.name as author_name,
        p.created_at,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
        (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') as comments,
        p.view_count + (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) * 5 + (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') * 10 as engagement_score
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.deleted_at IS NULL 
    AND p.status = 'published'
    AND p.created_at BETWEEN ? AND ?
    ORDER BY engagement_score DESC
    LIMIT 10
");
$topEngagingStmt->execute([$dateFrom, $dateTo]);
$topEngagingPosts = $topEngagingStmt->fetchAll();

// === 3. ENGAGEMENT BY CATEGORY ===
$categoryEngagementStmt = $db->prepare("
    SELECT 
        c.name,
        COUNT(p.id) as total_posts,
        COALESCE(SUM(p.view_count), 0) as total_views,
        (SELECT COUNT(*) FROM post_likes pl INNER JOIN posts p2 ON pl.post_id = p2.id WHERE p2.category_id = c.id AND p2.deleted_at IS NULL) as total_likes,
        (SELECT COUNT(*) FROM comments cm WHERE cm.commentable_type = 'post' AND cm.commentable_id IN (SELECT id FROM posts WHERE category_id = c.id AND deleted_at IS NULL) AND cm.status = 'approved') as total_comments
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL AND p.created_at BETWEEN ? AND ?
    WHERE c.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY total_views DESC
");
$categoryEngagementStmt->execute([$dateFrom, $dateTo]);
$categoryEngagement = $categoryEngagementStmt->fetchAll();

// Calculate engagement rate per category
foreach ($categoryEngagement as &$cat) {
    $totalEng = ($cat['total_likes'] ?? 0) + ($cat['total_comments'] ?? 0);
    $cat['engagement_rate'] = $cat['total_views'] > 0 ? round(($totalEng / $cat['total_views']) * 100, 2) : 0;
}

// === 4. TOP COMMENTERS ===
$topCommentersStmt = $db->prepare("
    SELECT 
        name,
        email,
        COUNT(*) as total_comments
    FROM comments
    WHERE status = 'approved'
    AND created_at BETWEEN ? AND ?
    GROUP BY name, email
    ORDER BY total_comments DESC
    LIMIT 10
");
$topCommentersStmt->execute([$dateFrom, $dateTo]);
$topCommenters = $topCommentersStmt->fetchAll();

// === 5. MOST LIKED POSTS ===
$mostLikedStmt = $db->prepare("
    SELECT 
        p.title,
        c.name as category_name,
        COUNT(pl.id) as total_likes
    FROM posts p
    INNER JOIN post_likes pl ON p.id = pl.post_id
    LEFT JOIN post_categories c ON p.category_id = c.id
    WHERE p.deleted_at IS NULL
    AND pl.created_at BETWEEN ? AND ?
    GROUP BY p.id, p.title, c.name
    ORDER BY total_likes DESC
    LIMIT 10
");
$mostLikedStmt->execute([$dateFrom, $dateTo]);
$mostLikedPosts = $mostLikedStmt->fetchAll();

// === 6. MOST COMMENTED POSTS ===
$mostCommentedStmt = $db->prepare("
    SELECT 
        p.title,
        c.name as category_name,
        COUNT(cm.id) as total_comments
    FROM posts p
    INNER JOIN comments cm ON p.id = cm.commentable_id AND cm.commentable_type = 'post'
    LEFT JOIN post_categories c ON p.category_id = c.id
    WHERE p.deleted_at IS NULL
    AND cm.status = 'approved'
    AND cm.created_at BETWEEN ? AND ?
    GROUP BY p.id, p.title, c.name
    ORDER BY total_comments DESC
    LIMIT 10
");
$mostCommentedStmt->execute([$dateFrom, $dateTo]);
$mostCommentedPosts = $mostCommentedStmt->fetchAll();

// === 7. DOWNLOAD STATISTICS ===
$topDownloadsStmt = $db->query("
    SELECT 
        title,
        file_type,
        download_count
    FROM downloadable_files
    WHERE deleted_at IS NULL
    ORDER BY download_count DESC
    LIMIT 10
");
$topDownloads = $topDownloadsStmt->fetchAll();

// === 8. RECENT ENGAGEMENT ACTIVITIES (Last 10 Days) ===
$recentActivitiesStmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_activities
    FROM activity_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 10 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 10
");
$recentActivities = $recentActivitiesStmt->fetchAll();

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
                    ' . htmlspecialchars($siteName) . ' - Engagement Report
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_engagement_pdf.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output('Engagement_Report_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Analisis interaksi user dengan konten</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item">Laporan</li>
                        <li class="breadcrumb-item active">Engagement</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-funnel"></i> Filter Periode</h5>
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
                            <a href="report_engagement.php" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                            <a href="?export_pdf=1&date_from=<?= $dateFrom ?>&date_to=<?= $dateTo ?>"
                               class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overall Metrics -->
        <h5 class="mb-3">📊 Overall Engagement Metrics</h5>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-eye"></i> Total Views</h6>
                        <h2 class="mb-0"><?= formatNumber($overallMetrics['total_views']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-heart-fill"></i> Total Likes</h6>
                        <h2 class="mb-0"><?= formatNumber($overallMetrics['total_likes']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-chat-dots-fill"></i> Total Comments</h6>
                        <h2 class="mb-0"><?= formatNumber($overallMetrics['total_comments']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2"><i class="bi bi-graph-up"></i> Engagement Rate</h6>
                        <h2 class="mb-0"><?= $overallMetrics['avg_engagement_rate'] ?>%</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Downloads</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($overallMetrics['total_downloads']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Messages</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($overallMetrics['total_messages']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Active Posts</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($overallMetrics['total_posts']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Engaging Posts -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🔥 Top 10 Most Engaging Posts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">Rank</th>
                                        <th style="width: 35%;">Judul Post</th>
                                        <th style="width: 15%;">Kategori</th>
                                        <th style="width: 10%;">Views</th>
                                        <th style="width: 10%;">Likes</th>
                                        <th style="width: 10%;">Comments</th>
                                        <th style="width: 15%;">Eng. Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topEngagingPosts)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $rank = 1; foreach ($topEngagingPosts as $post): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?php if ($rank <= 3): ?>
                                                        <span class="badge bg-<?= $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'info') ?>">
                                                            <?= $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= $rank ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($post['title']) ?></td>
                                                <td><?= htmlspecialchars($post['category_name']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= formatNumber($post['view_count']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger"><?= formatNumber($post['likes']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= formatNumber($post['comments']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?= formatNumber($post['engagement_score']) ?></strong>
                                                </td>
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

        <!-- Category Engagement & Top Commenters -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📂 Engagement by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Views</th>
                                        <th class="text-end">Likes</th>
                                        <th class="text-end">Comments</th>
                                        <th class="text-end">Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryEngagement as $cat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="text-end"><?= formatNumber($cat['total_views']) ?></td>
                                            <td class="text-end"><?= formatNumber($cat['total_likes']) ?></td>
                                            <td class="text-end"><?= formatNumber($cat['total_comments']) ?></td>
                                            <td class="text-end"><strong><?= $cat['engagement_rate'] ?>%</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💬 Top Commenters</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th class="text-end">Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topCommenters)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($topCommenters as $commenter): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($commenter['name']) ?></td>
                                                <td><?= htmlspecialchars($commenter['email']) ?></td>
                                                <td class="text-end"><strong><?= formatNumber($commenter['total_comments']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Liked & Most Commented -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">❤️ Most Liked Posts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Post Title</th>
                                        <th>Category</th>
                                        <th class="text-end">Likes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($mostLikedPosts)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($mostLikedPosts as $post): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($post['title']) ?></td>
                                                <td><?= htmlspecialchars($post['category_name']) ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-danger"><?= formatNumber($post['total_likes']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💭 Most Commented Posts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Post Title</th>
                                        <th>Category</th>
                                        <th class="text-end">Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($mostCommentedPosts)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($mostCommentedPosts as $post): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($post['title']) ?></td>
                                                <td><?= htmlspecialchars($post['category_name']) ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-info"><?= formatNumber($post['total_comments']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Downloads -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📥 Top 10 Downloaded Files</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">Rank</th>
                                        <th style="width: 65%;">File Title</th>
                                        <th style="width: 15%;">Type</th>
                                        <th style="width: 15%;" class="text-end">Downloads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topDownloads)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $rank = 1; foreach ($topDownloads as $file): ?>
                                            <tr>
                                                <td class="text-center"><?= $rank++ ?></td>
                                                <td><?= htmlspecialchars($file['title']) ?></td>
                                                <td><span class="badge bg-secondary"><?= strtoupper($file['file_type']) ?></span></td>
                                                <td class="text-end">
                                                    <span class="badge bg-success"><?= formatNumber($file['download_count']) ?></span>
                                                </td>
                                            </tr>
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
