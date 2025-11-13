<?php
/**
 * Report: Executive Summary
 * Laporan ringkasan eksekutif untuk manajemen
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

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // Default: awal bulan ini
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Default: hari ini
$exportPdf = $_GET['export_pdf'] ?? '';

// === 1. OVERVIEW STATISTICS ===
$overviewStats = [
    'total_posts' => 0,
    'total_published' => 0,
    'total_users' => 0,
    'total_services' => 0,
    'total_files' => 0,
    'total_galleries' => 0,
    'total_views' => 0,
    'total_likes' => 0,
    'total_comments' => 0,
    'total_downloads' => 0,
    'total_messages' => 0
];

// Posts
$postsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
        SUM(view_count) as total_views
    FROM posts 
    WHERE deleted_at IS NULL
");
$postsData = $postsStmt->fetch();
$overviewStats['total_posts'] = $postsData['total'];
$overviewStats['total_published'] = $postsData['published'];
$overviewStats['total_views'] = $postsData['total_views'] ?? 0;

// Users
$usersStmt = $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
$overviewStats['total_users'] = $usersStmt->fetch()['total'];

// Services
$servicesStmt = $db->query("SELECT COUNT(*) as total FROM services WHERE deleted_at IS NULL AND status = 'published'");
$overviewStats['total_services'] = $servicesStmt->fetch()['total'];

// Files
$filesStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(download_count) as downloads
    FROM downloadable_files 
    WHERE deleted_at IS NULL
");
$filesData = $filesStmt->fetch();
$overviewStats['total_files'] = $filesData['total'];
$overviewStats['total_downloads'] = $filesData['downloads'] ?? 0;

// Galleries
$galleriesStmt = $db->query("SELECT COUNT(*) as total FROM gallery_albums WHERE deleted_at IS NULL");
$overviewStats['total_galleries'] = $galleriesStmt->fetch()['total'];

// Likes
$likesStmt = $db->query("SELECT COUNT(*) as total FROM post_likes");
$overviewStats['total_likes'] = $likesStmt->fetch()['total'];

// Comments
$commentsStmt = $db->query("SELECT COUNT(*) as total FROM comments WHERE status = 'approved'");
$overviewStats['total_comments'] = $commentsStmt->fetch()['total'];

// Messages
$messagesStmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE deleted_at IS NULL");
$overviewStats['total_messages'] = $messagesStmt->fetch()['total'];

// === 2. TOP 10 PERFORMING POSTS ===
$topPostsStmt = $db->query("
    SELECT 
        p.title,
        p.view_count,
        c.name as category_name,
        u.name as author_name,
        p.created_at,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
        (SELECT COUNT(*) FROM comments WHERE commentable_type = 'post' AND commentable_id = p.id AND status = 'approved') as comments
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.deleted_at IS NULL 
    AND p.status = 'published'
    ORDER BY p.view_count DESC
    LIMIT 10
");
$topPosts = $topPostsStmt->fetchAll();

// === 3. CONTENT GROWTH (Last 6 Months) ===
$growthStmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total
    FROM posts
    WHERE deleted_at IS NULL
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$contentGrowth = $growthStmt->fetchAll();

// === 4. USER ACTIVITY (Last 30 Days) ===
$activityStmt = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_activities
    FROM activity_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 10
");
$recentActivities = $activityStmt->fetchAll();

// === 5. TOP CONTRIBUTORS ===
$contributorsStmt = $db->query("
    SELECT 
        u.name,
        u.role,
        COUNT(p.id) as total_posts,
        SUM(p.view_count) as total_views
    FROM users u
    LEFT JOIN posts p ON u.id = p.author_id AND p.deleted_at IS NULL
    WHERE u.deleted_at IS NULL
    GROUP BY u.id, u.name, u.role
    HAVING total_posts > 0
    ORDER BY total_posts DESC, total_views DESC
    LIMIT 10
");
$topContributors = $contributorsStmt->fetchAll();

// === 6. ENGAGEMENT METRICS ===
$engagementMetrics = [
    'avg_views_per_post' => $overviewStats['total_published'] > 0 ? 
        round($overviewStats['total_views'] / $overviewStats['total_published'], 2) : 0,
    'avg_likes_per_post' => $overviewStats['total_published'] > 0 ? 
        round($overviewStats['total_likes'] / $overviewStats['total_published'], 2) : 0,
    'avg_comments_per_post' => $overviewStats['total_published'] > 0 ? 
        round($overviewStats['total_comments'] / $overviewStats['total_published'], 2) : 0,
    'engagement_rate' => $overviewStats['total_views'] > 0 ? 
        round((($overviewStats['total_likes'] + $overviewStats['total_comments']) / $overviewStats['total_views']) * 100, 2) : 0
];

// === 7. CATEGORY DISTRIBUTION ===
$categoryDistStmt = $db->query("
    SELECT 
        c.name,
        COUNT(p.id) as total_posts,
        SUM(p.view_count) as total_views
    FROM post_categories c
    LEFT JOIN posts p ON c.id = p.category_id AND p.deleted_at IS NULL
    WHERE c.deleted_at IS NULL
    GROUP BY c.id, c.name
    ORDER BY total_posts DESC
");
$categoryDistribution = $categoryDistStmt->fetchAll();

// === 8. RECENT HIGHLIGHTS ===
$highlightsStmt = $db->query("
    SELECT 
        description,
        created_at,
        action_type
    FROM activity_logs
    WHERE action_type IN ('CREATE', 'UPDATE', 'DELETE')
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
    LIMIT 10
");
$recentHighlights = $highlightsStmt->fetchAll();

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
                    ' . htmlspecialchars($siteName) . ' - Executive Summary Report
                </td>
                <td width="30%" style="text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>';
    $mpdf->SetHTMLFooter($footer);

    ob_start();
    include __DIR__ . '/templates/laporan_executive_pdf.php';
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
                <p class="text-subtitle text-muted">Overview lengkap performa website</p>
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
        <!-- Export Button -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">📊 Laporan Periode</h6>
                        <small class="text-muted">Tanggal Generate: <?= date('d F Y, H:i') ?> WIB</small>
                    </div>
                    <a href="?export_pdf=1" class="btn btn-danger" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- Overview Statistics -->
        <h5 class="mb-3">📈 Overview Statistik</h5>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">Total Posts</h6>
                        <h2 class="mb-0"><?= formatNumber($overviewStats['total_posts']) ?></h2>
                        <small><?= formatNumber($overviewStats['total_published']) ?> Published</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">Total Views</h6>
                        <h2 class="mb-0"><?= formatNumber($overviewStats['total_views']) ?></h2>
                        <small>Avg: <?= formatNumber($engagementMetrics['avg_views_per_post']) ?>/post</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">Total Users</h6>
                        <h2 class="mb-0"><?= formatNumber($overviewStats['total_users']) ?></h2>
                        <small>Active Contributors</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h6 class="text-white mb-2">Engagement Rate</h6>
                        <h2 class="mb-0"><?= $engagementMetrics['engagement_rate'] ?>%</h2>
                        <small>Likes + Comments / Views</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Likes</h6>
                        <h3 class="mb-0 text-danger"><?= formatNumber($overviewStats['total_likes']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Comments</h6>
                        <h3 class="mb-0 text-info"><?= formatNumber($overviewStats['total_comments']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Downloads</h6>
                        <h3 class="mb-0 text-success"><?= formatNumber($overviewStats['total_downloads']) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">Total Messages</h6>
                        <h3 class="mb-0 text-primary"><?= formatNumber($overviewStats['total_messages']) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 10 Posts -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🏆 Top 10 Performing Posts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 5%;">Rank</th>
                                        <th style="width: 35%;">Judul Post</th>
                                        <th style="width: 15%;">Kategori</th>
                                        <th style="width: 15%;">Penulis</th>
                                        <th style="width: 10%;">Views</th>
                                        <th style="width: 10%;">Likes</th>
                                        <th style="width: 10%;">Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($topPosts)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $rank = 1; foreach ($topPosts as $post): ?>
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
                                                <td><?= htmlspecialchars($post['title']) ?></td>
                                                <td><?= htmlspecialchars($post['category_name']) ?></td>
                                                <td><?= htmlspecialchars($post['author_name']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= formatNumber($post['view_count']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger"><?= formatNumber($post['likes']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= formatNumber($post['comments']) ?></span>
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

        <!-- Contributors & Distribution -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">👥 Top Contributors</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th class="text-end">Posts</th>
                                        <th class="text-end">Views</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topContributors as $contributor): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($contributor['name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst($contributor['role']) ?></span></td>
                                            <td class="text-end"><strong><?= formatNumber($contributor['total_posts']) ?></strong></td>
                                            <td class="text-end"><?= formatNumber($contributor['total_views']) ?></td>
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
                        <h5 class="card-title mb-0">📂 Category Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Posts</th>
                                        <th class="text-end">Views</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoryDistribution as $category): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                            <td class="text-end"><strong><?= formatNumber($category['total_posts']) ?></strong></td>
                                            <td class="text-end"><?= formatNumber($category['total_views']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🕐 Recent Activities (Last 10 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Total Activities</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td><?= formatTanggal($activity['date'], 'd M Y') ?></td>
                                            <td class="text-end"><strong><?= formatNumber($activity['total_activities']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
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
