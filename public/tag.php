<?php
/**
 * Tag Page - Posts by Tag (Fixed Theme Consistency)
 * Features:
 * - List posts filtered by tag
 * - Tag info with dynamic styling
 * - Pagination
 * * PERBAIKAN:
 * - Wrapper background & text color dinamis
 * - Ikon tag mengikuti var(--color-primary)
 * - Layout konsisten dengan category.php
 */

require_once 'config.php';

// Get tag slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'posts.php');
    exit;
}

// Get tag details
$stmt = $db->prepare("
    SELECT t.*, COUNT(pt.post_id) as post_count
    FROM tags t
    LEFT JOIN post_tags pt ON pt.tag_id = t.id
    LEFT JOIN posts p ON p.id = pt.post_id AND p.status = 'published' AND p.deleted_at IS NULL
    WHERE t.slug = ? AND t.deleted_at IS NULL
    GROUP BY t.id
");
$stmt->execute([$slug]);
$tag = $stmt->fetch(PDO::FETCH_ASSOC);

// 404 if tag not found
if (!$tag) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Pagination settings
$perPage = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $perPage;

// Get total posts with this tag
$totalPosts = $tag['post_count'];
$totalPages = ceil($totalPosts / $perPage);

// Get posts with this tag
$stmt = $db->prepare("
    SELECT p.*, 
           c.name as category_name, 
           c.slug as category_slug,
           u.name as author_name
    FROM posts p
    INNER JOIN post_tags pt ON pt.post_id = p.id
    LEFT JOIN post_categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE pt.tag_id = ? AND p.status = 'published' AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$tag['id'], $perPage, $offset]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page namespace
$pageNamespace = 'tag';

// SEO Meta
$pageTitle = 'Tag: ' . $tag['name'] . ' - ' . getSetting('site_name');
$pageDescription = 'Artikel dengan tag ' . $tag['name'];
$pageKeywords = $tag['name'] . ', berita, artikel, btikp kalsel';

// Include header
include 'templates/header.php';
?>

<div style="background-color: var(--color-background); color: var(--color-text); min-height: 100vh;">

    <div class="py-4 border-b border-gray-200/50">
        <div class="container mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm">
                <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-home"></i> Beranda
                </a>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <a href="<?= BASE_URL ?>posts.php" class="text-blue-600 hover:text-blue-800 transition">Berita</a>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <span class="opacity-75 font-medium">Tag: <?= htmlspecialchars($tag['name']) ?></span>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8 border-l-4" 
                         style="border-left-color: var(--color-primary);" 
                         data-aos="fade-up">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center bg-gray-50"
                                 style="color: var(--color-primary);">
                                <i class="fas fa-tag text-3xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm opacity-60 mb-1 font-semibold tracking-wide">TAG</div>
                                <h1 class="text-3xl font-bold mb-2" style="color: var(--color-text);">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </h1>
                                <div class="text-sm opacity-70">
                                    <i class="fas fa-newspaper mr-1"></i>
                                    <?= number_format($totalPosts) ?> artikel
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($posts)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <?php foreach ($posts as $index => $post): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 border border-gray-100 flex flex-col h-full" 
                                 data-aos="fade-up" 
                                 data-aos-delay="<?= $index * 50 ?>">
                            <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden h-48 relative group">
                                <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300">
                                <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                            </a>
                            
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="flex items-center justify-between mb-3">
                                    <?php if (!empty($post['category_name'])): ?>
                                    <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                                       class="inline-block text-xs font-medium px-3 py-1 rounded-full transition hover:opacity-80"
                                       style="background-color: rgba(var(--color-primary-rgb), 0.1); color: var(--color-primary);">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                    <?php endif; ?>
                                    <span class="text-xs opacity-60">
                                        <i class="far fa-calendar mr-1"></i>
                                        <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-xl font-bold mb-2 line-clamp-2 flex-1">
                                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                                       class="transition hover:opacity-80"
                                       style="color: var(--color-text);">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </a>
                                </h3>
                                
                                <p class="mb-4 line-clamp-3 text-sm opacity-75 leading-relaxed">
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
                               class="px-4 py-2 border rounded-lg transition font-medium shadow-sm"
                               style="<?= $isActive ? 'background-color: var(--color-primary); color: white; border-color: var(--color-primary);' : 'background-color: white; color: var(--color-text); border-color: #e5e7eb;' ?>">
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
                    <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100" data-aos="fade-up">
                        <i class="fas fa-tags text-6xl mb-4 opacity-20"></i>
                        <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">Belum Ada Artikel</h3>
                        <p class="opacity-60 mb-6">Tag ini belum memiliki artikel yang terkait</p>
                        <a href="<?= BASE_URL ?>posts.php" 
                           class="inline-flex items-center px-6 py-3 text-white rounded-lg transition shadow-md hover:-translate-y-1"
                           style="background-color: var(--color-primary);">
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