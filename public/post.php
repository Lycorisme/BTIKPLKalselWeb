<?php
/**
 * Single Post Detail Page - Dynamic Version v2.0
 * Fully integrated with settings table
 * Features: Comments, Likes, Related Posts, Complete Sidebar, Share Buttons
 */

require_once 'config.php';

// Dynamic settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteTagline = getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . BASE_URL . 'posts.php');
    exit;
}

// Get post details
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               c.name as category_name, 
               c.slug as category_slug,
               u.name as author_name
        FROM posts p
        LEFT JOIN post_categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.slug = ? AND p.status = 'published' AND p.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Post Query Error: " . $e->getMessage());
    $post = null;
}

// 404 if post not found
if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include 'templates/header.php';
    ?>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Artikel Tidak Ditemukan</h2>
            <p class="text-gray-600 mb-8">Artikel yang Anda cari tidak tersedia atau telah dihapus.</p>
            <a href="<?= BASE_URL ?>posts.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar Berita
            </a>
        </div>
    </div>
    <?php
    include 'templates/footer.php';
    exit;
}

// Format content dengan word-break
if (!empty($post['content'])) {
    if (strip_tags($post['content']) === $post['content']) {
        $post['content'] = '<div style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.8;">' 
                         . htmlspecialchars($post['content']) 
                         . '</div>';
    }
}

// Increment view count
try {
    increment_post_views($post['id']);
} catch (Exception $e) {
    error_log("Increment Views Error: " . $e->getMessage());
}

// Get post tags
try {
    $stmt = $db->prepare("
        SELECT t.* 
        FROM tags t
        INNER JOIN post_tags pt ON pt.tag_id = t.id
        WHERE pt.post_id = ? AND t.deleted_at IS NULL
    ");
    $stmt->execute([$post['id']]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Tags Query Error: " . $e->getMessage());
    $tags = [];
}

// Get related posts
try {
    $related_posts = get_related_posts($post['id'], $post['category_id'], 3);
} catch (Exception $e) {
    error_log("Related Posts Error: " . $e->getMessage());
    $related_posts = [];
}

// Get likes and comments count
try {
    $likes_count = get_post_likes($post['id']);
    $has_liked = has_user_liked_post($post['id']);
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM comments WHERE commentable_type = 'post' AND commentable_id = ? AND status = 'approved'");
    $stmt->execute([$post['id']]);
    $comments_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $likes_count = 0;
    $has_liked = false;
    $comments_count = 0;
    error_log("Likes/Comments Error: " . $e->getMessage());
}

// Page variables
$pageNamespace = 'post-detail';
$pageTitle = $post['title'] . ' - ' . $siteName;
$pageDescription = truncateText($post['excerpt'] ?? strip_tags($post['content']), 160);
$pageKeywords = !empty($tags) ? implode(', ', array_column($tags, 'name')) : '';
$pageImage = get_featured_image($post['featured_image']);
$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Include header
include 'templates/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center flex-wrap gap-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700 transition">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?= BASE_URL ?>posts.php" class="text-blue-600 hover:text-blue-700 transition">Berita</a>
            <?php if (!empty($post['category_name'])): ?>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" class="text-blue-600 hover:text-blue-700 transition">
                <?= htmlspecialchars($post['category_name']) ?>
            </a>
            <?php endif; ?>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600 truncate"><?= truncateText($post['title'], 50) ?></span>
        </nav>
    </div>
</div>

<!-- Main Content -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Post Content (2/3 width) -->
            <div class="lg:col-span-2">
                <article class="bg-white rounded-lg shadow-lg overflow-hidden" data-aos="fade-up">
                    
                    <!-- Featured Image -->
                    <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= uploadUrl($post['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($post['title']) ?>"
                         class="w-full h-64 md:h-96 object-cover"
                         loading="eager"
                         onerror="this.src='<?= BASE_URL ?>assets/images/blog-default.jpg'">
                    <?php endif; ?>
                    
                    <!-- Post Body -->
                    <div class="p-6 md:p-8">
                        
                        <!-- Category & Date -->
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <?php if (!empty($post['category_name'])): ?>
                            <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                               class="inline-block bg-blue-100 text-blue-600 text-sm font-medium px-3 py-1 rounded-full hover:bg-blue-200 transition">
                                <i class="fas fa-folder mr-1"></i><?= htmlspecialchars($post['category_name']) ?>
                            </a>
                            <?php endif; ?>
                            <span class="text-sm text-gray-500">
                                <i class="far fa-calendar mr-1"></i>
                                <?= formatTanggal($post['created_at'], 'd F Y') ?>
                            </span>
                        </div>
                        
                        <!-- Title -->
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6 leading-tight break-words">
                            <?= htmlspecialchars($post['title']) ?>
                        </h1>
                        
                        <!-- Meta Info -->
                        <div class="flex flex-wrap items-center gap-4 py-4 border-y border-gray-200 mb-6 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="far fa-user mr-2 text-blue-600"></i>
                                <span><?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="far fa-eye mr-2 text-blue-600"></i>
                                <span><?= number_format($post['view_count']) ?> kali dibaca</span>
                            </div>
                            <div class="flex items-center">
                                <i class="far fa-comments mr-2 text-blue-600"></i>
                                <span><?= $comments_count ?> komentar</span>
                            </div>
                            
                            <!-- Share Buttons -->
                            <div class="flex items-center gap-2 lg:ml-auto">
                                <span class="text-xs font-medium">Bagikan:</span>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" 
                                   target="_blank" rel="noopener noreferrer" title="Bagikan ke Facebook"
                                   class="w-8 h-8 flex items-center justify-center bg-blue-600 text-white rounded-full hover:bg-blue-700 transition">
                                    <i class="fab fa-facebook-f text-xs"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($post['title']) ?>" 
                                   target="_blank" rel="noopener noreferrer" title="Bagikan ke Twitter"
                                   class="w-8 h-8 flex items-center justify-center bg-sky-500 text-white rounded-full hover:bg-sky-600 transition">
                                    <i class="fab fa-twitter text-xs"></i>
                                </a>
                                <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' - ' . $pageUrl) ?>" 
                                   target="_blank" rel="noopener noreferrer" title="Bagikan ke WhatsApp"
                                   class="w-8 h-8 flex items-center justify-center bg-green-500 text-white rounded-full hover:bg-green-600 transition">
                                    <i class="fab fa-whatsapp text-xs"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Post Content -->
                        <div class="mb-8">
                            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed" style="word-break: break-word; overflow-wrap: break-word;">
                                <?= $post['content'] ?>
                            </div>
                        </div>
                        
                        <!-- Tags -->
                        <?php if (!empty($tags)): ?>
                        <div class="flex flex-wrap items-center gap-2 py-4 border-t border-gray-200">
                            <span class="text-sm font-semibold text-gray-700">
                                <i class="fas fa-tags mr-1"></i> Tags:
                            </span>
                            <?php foreach ($tags as $tag): ?>
                            <a href="<?= BASE_URL ?>tag.php?slug=<?= $tag['slug'] ?>" 
                               class="inline-block bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-700 text-sm px-3 py-1 rounded-full transition">
                                <?= htmlspecialchars($tag['name']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Like Button -->
                        <div class="py-6 border-t border-gray-200">
                            <button onclick="toggleLike(<?= $post['id'] ?>)" 
                                    id="like-btn"
                                    class="flex items-center gap-2 px-6 py-3 <?= $has_liked ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700' ?> rounded-lg font-medium hover:bg-red-500 hover:text-white transition transform hover:scale-105">
                                <i class="<?= $has_liked ? 'fas' : 'far' ?> fa-heart"></i>
                                <span id="like-count" class="font-bold"><?= $likes_count ?></span>
                                <span>Suka</span>
                            </button>
                        </div>
                        
                    </div>
                </article>
                
                <!-- Related Posts -->
                <?php if (!empty($related_posts)): ?>
                <div class="mt-12" data-aos="fade-up">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-newspaper text-blue-600 mr-2"></i>
                        Artikel Terkait
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($related_posts as $index => $related): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition"
                                 data-aos="fade-up"
                                 data-aos-delay="<?= $index * 100 ?>">
                            <a href="<?= BASE_URL ?>post.php?slug=<?= $related['slug'] ?>" class="block overflow-hidden">
                                <img src="<?= get_featured_image($related['featured_image']) ?>" 
                                     alt="<?= htmlspecialchars($related['title']) ?>"
                                     class="w-full h-40 object-cover transform hover:scale-110 transition-transform duration-300"
                                     loading="lazy">
                            </a>
                            <div class="p-4">
                                <h3 class="font-bold mb-2 line-clamp-2">
                                    <a href="<?= BASE_URL ?>post.php?slug=<?= $related['slug'] ?>" 
                                       class="text-gray-900 hover:text-blue-600 transition">
                                        <?= htmlspecialchars($related['title']) ?>
                                    </a>
                                </h3>
                                <div class="text-xs text-gray-500">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= formatTanggal($related['created_at'], 'd M Y') ?>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Comments Section -->
                <div class="mt-12 bg-white rounded-lg shadow-md p-6 md:p-8" data-aos="fade-up">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="far fa-comments text-blue-600 mr-2"></i>
                        Komentar (<?= $comments_count ?>)
                    </h2>
                    
                    <!-- Comment Form -->
                    <form id="comment-form" class="mb-8 bg-gray-50 p-6 rounded-lg">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama *</label>
                                <input type="text" name="author_name" required
                                       placeholder="Masukkan nama Anda"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                <input type="email" name="author_email" required
                                       placeholder="contoh@email.com"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Komentar *</label>
                                <textarea name="content" rows="4" required
                                          placeholder="Tulis komentar Anda di sini..."
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                        <button type="submit" 
                                class="mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-paper-plane mr-2"></i>Kirim Komentar
                        </button>
                    </form>
                    
                    <!-- Comments List -->
                    <div id="comments-list">
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT * FROM comments 
                                WHERE commentable_type = 'post' AND commentable_id = ? AND status = 'approved' AND parent_id IS NULL
                                ORDER BY created_at DESC
                            ");
                            $stmt->execute([$post['id']]);
                            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($comments)):
                                foreach ($comments as $comment):
                        ?>
                        <div class="border-b border-gray-200 pb-6 mb-6 last:border-0">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                    <?= strtoupper(substr($comment['name'], 0, 1)) ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($comment['name']) ?></span>
                                        <span class="text-xs text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?= formatTanggal($comment['created_at'], 'd M Y H:i') ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <i class="far fa-comments text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
                        </div>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Comments Query Error: " . $e->getMessage());
                            echo '<p class="text-center text-gray-500 py-8">Gagal memuat komentar</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar (1/3 width) -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Search Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6" data-aos="fade-up">
                        <h3 class="text-lg font-bold mb-4 flex items-center">
                            <i class="fas fa-search text-blue-600 mr-2"></i>
                            Pencarian
                        </h3>
                        <form action="<?= BASE_URL ?>search.php" method="GET">
                            <div class="flex">
                                <input type="text" name="q" placeholder="Cari artikel..." 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Popular Posts Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6" data-aos="fade-up" data-aos-delay="100">
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
                            echo '<p class="text-gray-500 text-sm text-center py-4">Gagal memuat berita</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Categories Widget -->
                    <div class="bg-white rounded-lg shadow-md p-6" data-aos="fade-up" data-aos-delay="200">
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

<!-- JavaScript -->
<script>
function toggleLike(postId) {
    const btn = document.getElementById('like-btn');
    const icon = btn.querySelector('i');
    const countSpan = document.getElementById('like-count');
    
    btn.disabled = true;
    
    fetch('<?= BASE_URL ?>api/like.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(res => res.ok ? res.json() : Promise.reject('Network error'))
    .then(data => {
        if (data.success) {
            countSpan.textContent = data.likes_count;
            
            if (data.action === 'liked') {
                btn.classList.remove('bg-gray-200', 'text-gray-700');
                btn.classList.add('bg-red-500', 'text-white');
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.classList.remove('bg-red-500', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
        } else {
            alert('Error: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        console.error('Like error:', err);
        alert('Gagal memproses like. Silakan coba lagi.');
    })
    .finally(() => {
        btn.disabled = false;
    });
}

// Comment form submission
document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('comment-form');
    if (!commentForm) return;
    
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalHTML = submitBtn.innerHTML;
        
        // Validate
        const name = this.querySelector('[name="author_name"]').value.trim();
        const email = this.querySelector('[name="author_email"]').value.trim();
        const content = this.querySelector('[name="content"]').value.trim();
        
        if (name.length < 2) {
            alert('Nama minimal 2 karakter');
            return;
        }
        
        if (!email.includes('@')) {
            alert('Email tidak valid');
            return;
        }
        
        if (content.length < 10) {
            alert('Komentar minimal 10 karakter');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
        
        fetch('<?= BASE_URL ?>api/comment.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.ok ? response.json() : Promise.reject('HTTP error'))
        .then(data => {
            if (data.success) {
                alert('✓ ' + data.message);
                if (data.reload) {
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    commentForm.reset();
                }
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Comment error:', error);
            alert('✗ Gagal mengirim komentar: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        });
    });
    
    // Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            once: true,
            offset: 100
        });
    }
});
</script>

<?php include 'templates/footer.php'; ?>
