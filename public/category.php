<?php
/**
 * Category Page - Posts by Category (Fixed Theme Consistency)
 * * PERBAIKAN:
 * - Background color sekarang menggunakan var(--color-background)
 * - Text color mengikuti var(--color-text)
 * - Elemen aksen (tombol/link) menggunakan class 'blue' yang sudah di-override
 * oleh custom.css menjadi Primary Color tema.
 */

require_once 'config.php';

// Get category slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'posts.php');
    exit;
}

// Get category details
$stmt = $db->prepare("
    SELECT c.*, COUNT(p.id) as post_count
    FROM post_categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' AND p.deleted_at IS NULL
    WHERE c.slug = ? AND c.deleted_at IS NULL
    GROUP BY c.id
");
$stmt->execute([$slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

// 404 if category not found
if (!$category) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Pagination settings
$perPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

// Get total posts in this category
$totalPosts = $category['post_count'];
$totalPages = ceil($totalPosts / $perPage);

// Get posts in this category
$stmt = $db->prepare("
    SELECT p.*, 
           c.name as category_name, 
           c.slug as category_slug,
           u.name as author_name
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.category_id = ? AND p.status = 'published' AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$category['id'], $perPage, $offset]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page namespace
$pageNamespace = 'category';

// SEO Meta
$pageTitle = $category['name'] . ' - Berita & Artikel - ' . getSetting('site_name');
$pageDescription = $category['description'] ?? 'Artikel dalam kategori ' . $category['name'];
$pageKeywords = $category['name'] . ', berita, artikel, btikp kalsel';

// Include header (Header sets up the CSS Variables for colors)
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
                <a href="<?= BASE_URL ?>posts.php" class="text-blue-600 hover:text-blue-800 transition">Berita</a>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <span class="opacity-75 font-medium"><?= htmlspecialchars($category['name']) ?></span>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8 border-l-4 border-blue-600" data-aos="fade-up">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center bg-blue-50">
                                <i class="fas fa-folder text-3xl text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-3xl font-bold mb-2" style="color: var(--color-text);">
                                    <?= htmlspecialchars($category['name']) ?>
                                </h1>
                                <?php if (!empty($category['description'])): ?>
                                <p class="opacity-75"><?= htmlspecialchars($category['description']) ?></p>
                                <?php endif; ?>
                                <div class="text-sm opacity-60 mt-2">
                                    <i class="fas fa-newspaper mr-1"></i>
                                    <?= number_format($totalPosts) ?> artikel
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($posts)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <?php foreach ($posts as $index => $post): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 flex flex-col h-full" 
                                 data-aos="fade-up" 
                                 data-aos-delay="<?= $index * 50 ?>">
                            
                            <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden h-48 relative group">
                                <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300"
                                     loading="lazy">
                                <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                            </a>
                            
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="mb-3 flex items-center text-xs opacity-60">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                </div>
                                
                                <h3 class="text-xl font-bold mb-2 line-clamp-2 flex-1">
                                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                                       class="hover:text-blue-600 transition"
                                       style="color: var(--color-text);">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h3>
                                
                                <p class="mb-4 line-clamp-3 text-sm opacity-70">
                                    <?= truncateText($post['excerpt'], 100) ?>
                                </p>
                                
                                <div class="flex items-center justify-between text-sm opacity-60 pt-4 border-t border-gray-100 mt-auto">
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
                    <div class="flex justify-center mt-8" data-aos="fade-up">
                        <nav class="flex items-center gap-2">
                            <?php if ($page > 1): ?>
                            <a href="?slug=<?= $slug ?>&page=<?= $page - 1 ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                               style="color: var(--color-text);">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                                $isActive = ($i === $page);
                            ?>
                            <a href="?slug=<?= $slug ?>&page=<?= $i ?>" 
                               class="px-4 py-2 border rounded-lg transition font-medium shadow-sm <?= $isActive ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200 hover:bg-gray-50' ?>"
                               style="<?= !$isActive ? 'color: var(--color-text);' : '' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?slug=<?= $slug ?>&page=<?= $page + 1 ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                               style="color: var(--color-text);">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-folder-open text-4xl opacity-30"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">Belum Ada Artikel</h3>
                        <p class="opacity-60 mb-6">Kategori ini belum memiliki artikel yang dipublikasikan.</p>
                        <a href="<?= BASE_URL ?>posts.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md hover:shadow-lg">
                            <i class="fas fa-arrow-left mr-2"></i>Lihat Semua Artikel
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="lg:col-span-1">
                    <?php include 'templates/sidebar.php'; ?>
                </div>
                
            </div>
        </div>
    </section>

</div>

<?php include 'templates/footer.php'; ?>