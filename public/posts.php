<?php
/**
 * Posts Listing Page - Dynamic Version (Fixed Theme Consistency)
 * Fully integrated with settings table
 * Features: Pagination, Lazy loading, Animations, Sidebar
 * * PERBAIKAN:
 * - Menggunakan var(--color-background) dan var(--color-text)
 * - Breadcrumb dan Pagination mengikuti tema
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

<div style="background-color: var(--color-background); color: var(--color-text); min-height: 100vh;">

    <div class="py-4 border-b border-gray-200/50">
        <div class="container mx-auto px-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-home"></i> Beranda
                </a>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <span class="opacity-75 font-medium">Berita & Artikel</span>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <div class="mb-8" data-aos="fade-up">
                        <h1 class="text-3xl md:text-4xl font-bold mb-2" style="color: var(--color-text);">
                            <i class="fas fa-newspaper mr-2" style="color: var(--color-primary);"></i>
                            Berita & Artikel
                        </h1>
                        <p class="opacity-75">Informasi terbaru dari <?= htmlspecialchars($siteName) ?></p>
                        <?php if ($totalPosts > 0): ?>
                        <div class="text-sm opacity-60 mt-2">
                            Menampilkan <?= min($perPage, $totalPosts - $offset) ?> dari <?= number_format($totalPosts) ?> artikel
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($posts)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <?php foreach ($posts as $index => $post): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 border border-gray-100" 
                                 data-aos="fade-up" 
                                 data-aos-delay="<?= ($index % 4) * 50 ?>">
                            <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden relative group">
                                <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="w-full h-48 object-cover transform group-hover:scale-110 transition-transform duration-300"
                                     loading="lazy"
                                     onerror="this.src='<?= BASE_URL ?>assets/images/blog-default.jpg'">
                                <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                            </a>
                            
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <?php if (!empty($post['category_name'])): ?>
                                    <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                                       class="inline-block text-xs font-medium px-3 py-1 rounded-full transition hover:opacity-80"
                                       style="background-color: rgba(var(--color-primary-rgb), 0.1); color: var(--color-primary);">
                                        <i class="fas fa-folder mr-1"></i>
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="inline-block bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">
                                        Umum
                                    </span>
                                    <?php endif; ?>
                                    <span class="text-xs opacity-60">
                                        <i class="far fa-calendar mr-1"></i>
                                        <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-xl font-bold mb-2 line-clamp-2">
                                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                                       class="hover:text-blue-600 transition"
                                       style="color: var(--color-text);">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h3>
                                
                                <p class="mb-4 line-clamp-3 text-sm leading-relaxed opacity-75">
                                    <?= truncateText($post['excerpt'] ?? strip_tags($post['content']), 120) ?>
                                </p>
                                
                                <div class="flex items-center justify-between text-sm opacity-60 pt-4 border-t border-gray-100">
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
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center" data-aos="fade-up">
                        <nav class="flex items-center space-x-2" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                               style="color: var(--color-text);"
                               title="Halaman Sebelumnya">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php else: ?>
                            <span class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            // First page
                            if ($start > 1):
                            ?>
                            <a href="?page=1" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                               style="color: var(--color-text);">
                                1
                            </a>
                            <?php if ($start > 2): ?>
                            <span class="px-2 opacity-50">...</span>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php
                            for ($i = $start; $i <= $end; $i++):
                                $isActive = ($i === $page);
                            ?>
                            <a href="?page=<?= $i ?>" 
                               class="px-4 py-2 border rounded-lg transition <?= $active ? 'shadow-md' : '' ?>"
                               style="<?= $isActive ? 'background-color: var(--color-primary); color: white; border-color: var(--color-primary);' : 'background-color: white; color: var(--color-text); border-color: #e5e7eb;' ?>"
                               <?= $isActive ? 'aria-current="page"' : '' ?>>
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php
                            if ($end < $totalPages):
                                if ($end < $totalPages - 1):
                            ?>
                            <span class="px-2 opacity-50">...</span>
                            <?php endif; ?>
                            <a href="?page=<?= $totalPages ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                               style="color: var(--color-text);">
                                <?= $totalPages ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                               style="color: var(--color-text);"
                               title="Halaman Berikutnya">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php else: ?>
                            <span class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                    
                    <div class="text-center mt-4 text-sm opacity-60">
                        Halaman <?= $page ?> dari <?= $totalPages ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100" data-aos="fade-up">
                        <i class="fas fa-newspaper text-6xl mb-4 opacity-20"></i>
                        <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">Belum Ada Artikel</h3>
                        <p class="opacity-60">Artikel dari <?= htmlspecialchars($siteName) ?> akan segera ditambahkan</p>
                        <a href="<?= BASE_URL ?>" 
                           class="inline-block mt-6 px-6 py-3 text-white rounded-lg transition shadow-lg hover:-translate-y-1"
                           style="background-color: var(--color-primary);">
                            <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="lg:col-span-1">
                    <div class="sticky top-24">
                        <?php include 'templates/sidebar.php'; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

</div>

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