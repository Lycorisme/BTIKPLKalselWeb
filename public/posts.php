<?php
/**
 * Posts Listing Page - Dynamic Version
 * Fully integrated with settings table
 * Features: Pagination, Lazy loading, Animations, Sidebar
 */

require_once 'config.php';

// Dynamic settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteDescription = getSetting('site_description', 'Portal resmi Balai Teknologi Informasi dan Komunikasi Pendidikan Kalimantan Selatan');

// Page namespace
$pageNamespace = 'posts';

// Pagination settings
$perPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

// Get total posts count
try {
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM posts 
        WHERE status = 'published' AND deleted_at IS NULL
    ");
    $totalPosts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalPosts / $perPage);
} catch (Exception $e) {
    error_log("Posts Count Error: " . $e->getMessage());
    $totalPosts = 0;
    $totalPages = 0;
}

// Get posts with pagination
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               c.name as category_name, 
               c.slug as category_slug,
               u.name as author_name
        FROM posts p
        LEFT JOIN post_categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.status = 'published' AND p.deleted_at IS NULL
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Posts Query Error: " . $e->getMessage());
    $posts = [];
}

// SEO Meta
$pageTitle = 'Berita & Artikel - ' . $siteName;
$pageDescription = 'Berita terbaru dan artikel dari ' . $siteName;
$pageKeywords = 'berita, artikel, ' . strtolower($siteName);

// Include header
include 'templates/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center space-x-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700 transition">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600">Berita & Artikel</span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Posts Grid -->
            <div class="lg:col-span-2">
                <!-- Page Header -->
                <div class="mb-8" data-aos="fade-up">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-newspaper text-blue-600 mr-2"></i>
                        Berita & Artikel
                    </h1>
                    <p class="text-gray-600">Informasi terbaru dari <?= htmlspecialchars($siteName) ?></p>
                    <?php if ($totalPosts > 0): ?>
                    <div class="text-sm text-gray-500 mt-2">
                        Menampilkan <?= min($perPage, $totalPosts - $offset) ?> dari <?= number_format($totalPosts) ?> artikel
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Posts Grid -->
                <?php if (!empty($posts)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php foreach ($posts as $index => $post): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300" 
                             data-aos="fade-up" 
                             data-aos-delay="<?= ($index % 4) * 50 ?>">
                        <!-- Featured Image -->
                        <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden">
                            <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="w-full h-48 object-cover transform hover:scale-110 transition-transform duration-300"
                                 loading="lazy"
                                 onerror="this.src='<?= BASE_URL ?>assets/images/blog-default.jpg'">
                        </a>
                        
                        <!-- Content -->
                        <div class="p-6">
                            <!-- Category & Date -->
                            <div class="flex items-center justify-between mb-3">
                                <?php if (!empty($post['category_name'])): ?>
                                <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                                   class="inline-block bg-blue-100 text-blue-600 text-xs font-medium px-3 py-1 rounded-full hover:bg-blue-200 transition">
                                    <i class="fas fa-folder mr-1"></i>
                                    <?= htmlspecialchars($post['category_name']) ?>
                                </a>
                                <?php else: ?>
                                <span class="inline-block bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                                    Umum
                                </span>
                                <?php endif; ?>
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                </span>
                            </div>
                            
                            <!-- Title -->
                            <h3 class="text-xl font-bold mb-2 line-clamp-2">
                                <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                                   class="text-gray-900 hover:text-blue-600 transition">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h3>
                            
                            <!-- Excerpt -->
                            <p class="text-gray-600 mb-4 line-clamp-3 text-sm leading-relaxed">
                                <?= truncateText($post['excerpt'] ?? strip_tags($post['content']), 120) ?>
                            </p>
                            
                            <!-- Meta Footer -->
                            <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t border-gray-200">
                                <span class="flex items-center">
                                    <i class="far fa-user mr-1"></i>
                                    <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="far fa-eye mr-1"></i>
                                    <?= number_format($post['view_count']) ?>
                                </span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center" data-aos="fade-up">
                    <nav class="flex items-center space-x-2" aria-label="Pagination">
                        <!-- Previous -->
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                           title="Halaman Sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        // First page
                        if ($start > 1):
                        ?>
                        <a href="?page=1" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            1
                        </a>
                        <?php if ($start > 2): ?>
                        <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Middle pages -->
                        <?php
                        for ($i = $start; $i <= $end; $i++):
                            $active = ($i === $page) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
                        ?>
                        <a href="?page=<?= $i ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg transition <?= $active ?>"
                           <?= $i === $page ? 'aria-current="page"' : '' ?>>
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <!-- Last page -->
                        <?php
                        if ($end < $totalPages):
                            if ($end < $totalPages - 1):
                        ?>
                        <span class="px-2 text-gray-500">...</span>
                        <?php endif; ?>
                        <a href="?page=<?= $totalPages ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <?= $totalPages ?>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Next -->
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                           title="Halaman Berikutnya">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                        <?php endif; ?>
                    </nav>
                </div>
                
                <!-- Pagination Info -->
                <div class="text-center mt-4 text-sm text-gray-600">
                    Halaman <?= $page ?> dari <?= $totalPages ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- No Posts -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center" data-aos="fade-up">
                    <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Belum Ada Artikel</h3>
                    <p class="text-gray-500">Artikel dari <?= htmlspecialchars($siteName) ?> akan segera ditambahkan</p>
                    <a href="<?= BASE_URL ?>" 
                       class="inline-block mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-24">
                    <!-- Search Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6" data-aos="fade-up">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class="fas fa-search text-blue-600 mr-2"></i>
                            Pencarian
                        </h3>
                        <form action="<?= BASE_URL ?>search.php" method="GET">
                            <div class="flex">
                                <input type="text" 
                                       name="q" 
                                       placeholder="Cari artikel..." 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                                <button type="submit" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Popular Posts Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class="fas fa-fire text-orange-500 mr-2"></i>
                            Berita Populer
                        </h3>
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT id, title, slug, featured_image, created_at, view_count 
                                FROM posts 
                                WHERE status = 'published' AND deleted_at IS NULL 
                                ORDER BY view_count DESC 
                                LIMIT 5
                            ");
                            $popular = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($popular)):
                                foreach ($popular as $p):
                        ?>
                        <div class="flex gap-3 mb-4 pb-4 border-b last:border-0 last:mb-0 last:pb-0">
                            <img src="<?= get_featured_image($p['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($p['title']) ?>"
                                 class="w-16 h-16 object-cover rounded flex-shrink-0"
                                 loading="lazy">
                            <div class="flex-1 min-w-0">
                                <a href="<?= BASE_URL ?>post.php?slug=<?= $p['slug'] ?>" 
                                   class="font-semibold text-sm text-gray-900 hover:text-blue-600 line-clamp-2 block">
                                    <?= htmlspecialchars($p['title']) ?>
                                </a>
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                                    <span><i class="far fa-calendar mr-1"></i><?= formatTanggal($p['created_at'], 'd M Y') ?></span>
                                    <span><i class="far fa-eye mr-1"></i><?= number_format($p['view_count']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <p class="text-gray-500 text-sm text-center py-4">Belum ada berita populer</p>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Popular Posts Error: " . $e->getMessage());
                            echo '<p class="text-gray-500 text-sm text-center py-4">Gagal memuat berita populer</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Categories Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6" data-aos="fade-up" data-aos-delay="200">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class="fas fa-folder text-yellow-500 mr-2"></i>
                            Kategori
                        </h3>
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT c.*, COUNT(p.id) as post_count
                                FROM post_categories c
                                LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' AND p.deleted_at IS NULL
                                WHERE c.deleted_at IS NULL
                                GROUP BY c.id
                                ORDER BY c.name ASC
                            ");
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($categories)):
                        ?>
                        <div class="space-y-1">
                            <?php foreach ($categories as $cat): ?>
                            <a href="<?= BASE_URL ?>category.php?slug=<?= $cat['slug'] ?>" 
                               class="flex justify-between items-center py-2 px-3 rounded hover:bg-gray-50 transition group">
                                <span class="text-gray-700 group-hover:text-blue-600 flex items-center">
                                    <i class="fas fa-chevron-right mr-2 text-xs"></i>
                                    <span class="truncate"><?= htmlspecialchars($cat['name']) ?></span>
                                </span>
                                <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded group-hover:bg-blue-600 group-hover:text-white transition flex-shrink-0 ml-2">
                                    <?= $cat['post_count'] ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php 
                            else:
                        ?>
                        <p class="text-gray-500 text-sm text-center py-4">Belum ada kategori</p>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Categories Error: " . $e->getMessage());
                            echo '<p class="text-gray-500 text-sm text-center py-4">Gagal memuat kategori</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Tags Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class="fas fa-tags text-green-500 mr-2"></i>
                            Tag Populer
                        </h3>
                        <?php
                        try {
                            $stmt = $db->query("
                                SELECT t.*, COUNT(pt.post_id) as post_count
                                FROM tags t
                                INNER JOIN post_tags pt ON pt.tag_id = t.id
                                INNER JOIN posts p ON p.id = pt.post_id AND p.status = 'published' AND p.deleted_at IS NULL
                                WHERE t.deleted_at IS NULL
                                GROUP BY t.id
                                ORDER BY post_count DESC
                                LIMIT 20
                            ");
                            $sidebar_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($sidebar_tags)):
                        ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($sidebar_tags as $t): ?>
                            <a href="<?= BASE_URL ?>tag.php?slug=<?= $t['slug'] ?>" 
                               class="inline-block bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-700 text-xs px-3 py-1 rounded-full transition">
                                <i class="fas fa-tag mr-1"></i><?= htmlspecialchars($t['name']) ?> 
                                <span class="font-semibold">(<?= $t['post_count'] ?>)</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php 
                            else:
                        ?>
                        <p class="text-gray-500 text-sm text-center py-4">Belum ada tag</p>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Tags Error: " . $e->getMessage());
                            echo '<p class="text-gray-500 text-sm text-center py-4">Gagal memuat tag</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</section>

<!-- Initialize AOS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            once: true,
            offset: 100,
            easing: 'ease-in-out'
        });
    }
});
</script>

<?php include 'templates/footer.php'; ?>
