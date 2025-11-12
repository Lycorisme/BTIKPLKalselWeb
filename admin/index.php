<?php
/**
 * Admin Dashboard
 * Enhanced version dengan chart & real-time statistics
 */

require_once 'includes/auth_check.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../models/Post.php';

$pageTitle = 'Dashboard';

// Initialize models
$postModel = new Post();
$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [
    'posts' => [
        'total' => 0,
        'published' => 0,
        'draft' => 0,
        'archived' => 0,
        'total_views' => 0
    ],
    'categories' => 0,
    'tags' => 0,
    'users' => 0
];

// Posts stats
$postStats = $postModel->getStats();
$stats['posts'] = $postStats;

// Categories count - FIXED: removed deleted_at check
$stmt = $db->query("SELECT COUNT(*) FROM post_categories");
$stats['categories'] = $stmt->fetchColumn();

// Tags count - FIXED: no deleted_at in tags table
$stmt = $db->query("SELECT COUNT(*) FROM tags");
$stats['tags'] = $stmt->fetchColumn();

// Users count - FIXED: check if column exists first
try {
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
    $stats['users'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    // If deleted_at doesn't exist, just count all
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['users'] = $stmt->fetchColumn();
}

// Recent posts
$recentPosts = $postModel->getPaginated(1, 5);

// Posts per month (last 6 months)
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM posts
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
      AND deleted_at IS NULL
    GROUP BY month
    ORDER BY month ASC
");
$postsPerMonth = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Popular posts (top 5 by views)
$stmt = $db->query("
    SELECT p.*, pc.name as category_name
    FROM posts p
    JOIN post_categories pc ON p.category_id = pc.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.view_count DESC
    LIMIT 5
");
$popularPosts = $stmt->fetchAll();

// Recent activities
$stmt = $db->query("
    SELECT *
    FROM activity_logs
    ORDER BY created_at DESC
    LIMIT 10
");
$recentActivities = $stmt->fetchAll();

// Posts by category
$stmt = $db->query("
    SELECT 
        pc.name,
        COUNT(p.id) as count
    FROM post_categories pc
    LEFT JOIN posts p ON pc.id = p.category_id AND p.deleted_at IS NULL
    WHERE pc.is_active = 1
    GROUP BY pc.id, pc.name
    ORDER BY count DESC
");
$postsByCategory = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
    .truncate-text-title {
        white-space: nowrap;      /* Mencegah teks pindah baris */
        overflow: hidden;         /* Sembunyikan teks yang berlebih */
        text-overflow: ellipsis;  /* Tampilkan "..." */
        max-width: 95%;           /* Beri sedikit ruang untuk badge */
    }

    /* Memastikan card "Recent Posts" memiliki tinggi yang konsisten */
    .list-group-item-action {
        min-height: 62px; /* Sesuaikan nilainya jika perlu */
    }
</style>
<div class="page-heading">
    <h3>Dashboard Overview</h3>
</div>

<section class="row">
    <div class="col-12 col-lg-12">
        <div class="row">
            <div class="col-6 col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body px-4 py-4-5">
                        <div class="row">
                            <div class="col-md-4 d-flex justify-content-start">
                                <div class="stats-icon purple mb-2">
                                    <i class="iconly-boldDocument"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-muted font-semibold">Total Posts</h6>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($stats['posts']['total']) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body px-4 py-4-5">
                        <div class="row">
                            <div class="col-md-4 d-flex justify-content-start">
                                <div class="stats-icon green mb-2">
                                    <i class="iconly-boldShow"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-muted font-semibold">Published</h6>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($stats['posts']['published']) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body px-4 py-4-5">
                        <div class="row">
                            <div class="col-md-4 d-flex justify-content-start">
                                <div class="stats-icon blue mb-2">
                                    <i class="iconly-boldChart"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-muted font-semibold">Total Views</h6>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($stats['posts']['total_views']) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3 col-md-6">
                <div class="card">
                    <div class="card-body px-4 py-4-5">
                        <div class="row">
                            <div class="col-md-4 d-flex justify-content-start">
                                <div class="stats-icon red mb-2">
                                    <i class="iconly-boldEdit"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-muted font-semibold">Draft</h6>
                                <h6 class="font-extrabold mb-0"><?= formatNumber($stats['posts']['draft']) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Posts per Month (Last 6 Months)</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="postsChart" height="100"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h4>Posts by Category</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Posts</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($recentPosts['data'])): ?>
                                <?php foreach ($recentPosts['data'] as $post): ?>
                                    <a href="<?= ADMIN_URL ?>modules/posts/posts_edit.php?id=<?= $post['id'] ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            
                                            <h6 class="mb-1 truncate-text-title" title="<?= htmlspecialchars($post['title']) ?>">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </h6>
                                            <?= getStatusBadge($post['status']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">Belum ada post</p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3">
                            <a href="<?= ADMIN_URL ?>modules/posts/posts_list.php" class="btn btn-primary btn-block w-100">
                                View All Posts
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h4>Most Popular Posts</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php if (!empty($popularPosts)): ?>
                                <?php foreach ($popularPosts as $post): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1" style="overflow: hidden;"> <h6 class="mb-1 truncate-text-title" title="<?= htmlspecialchars($post['title']) ?>">
                                                    <?= htmlspecialchars($post['title']) ?>
                                                </h6>
                                                <small class="text-muted"><?= $post['category_name'] ?></small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill ms-2"> <?= formatNumber($post['view_count']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">Belum ada post</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Activities</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentActivities)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($activity['user_name']) ?></td>
                                                <td>
                                                    <?php
                                                    $badges = [
                                                        'CREATE' => 'success',
                                                        'UPDATE' => 'primary',
                                                        'DELETE' => 'danger',
                                                        'LOGIN' => 'info'
                                                    ];
                                                    $badgeClass = $badges[$activity['action_type']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $badgeClass ?>">
                                                        <?= $activity['action_type'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($activity['description']) ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= formatTanggalRelatif($activity['created_at']) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Belum ada aktivitas</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Posts per Month Chart
const postsChartCtx = document.getElementById('postsChart').getContext('2d');
const postsPerMonthData = <?= json_encode($postsPerMonth) ?>;

// Prepare data for last 6 months
const months = [];
const counts = [];
const now = new Date();

for (let i = 5; i >= 0; i--) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const monthKey = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
    const monthName = date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' });
    
    months.push(monthName);
    counts.push(postsPerMonthData[monthKey] || 0);
}

new Chart(postsChartCtx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Posts Created',
            data: counts,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Posts by Category Chart
const categoryChartCtx = document.getElementById('categoryChart').getContext('2d');
const categoryData = <?= json_encode($postsByCategory) ?>;

const categoryLabels = categoryData.map(item => item.name);
const categoryCounts = categoryData.map(item => parseInt(item.count));

new Chart(categoryChartCtx, {
    // ===== DIPERBAIKI: Ubah ke bar horizontal =====
    type: 'bar',
    // ============================================
    data: {
        labels: categoryLabels,
        datasets: [{
            label: 'Number of Posts',
            data: categoryCounts,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        // ===== DIPERBAIKI: Tambahkan indexAxis =====
        indexAxis: 'y', // Ini yang mengubahnya jadi horizontal
        // ==========================================
        responsive: true,
        plugins: {
            legend: { display: false } // Sembunyikan legenda, tidak perlu
        },
        scales: {
            // ===== DIPERBAIKI: Tukar x dan y =====
            x: { // Sumbu X sekarang adalah 'count'
                beginAtZero: true,
                ticks: { stepSize: 1 }
            },
            y: { // Sumbu Y sekarang adalah 'kategori'
                // tidak perlu 'beginAtZero'
            }
            // ====================================
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>