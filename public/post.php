<?php
/**
 * Single Post Detail Page - Dynamic Version (Fixed Theme Consistency)
 * Features: Comments, Likes, Related Posts, Complete Sidebar, Share Buttons
 * * PERBAIKAN:
 * - Menggunakan var(--color-background) dan var(--color-text)
 * - Prose styling dinamis mengikuti tema
 * - Elemen UI (tombol, link) mengikuti var(--color-primary)
 */

require_once 'config.php';
// [TRACKING] Load Tracker Class
require_once '../core/PageViewTracker.php';

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

// [TRACKING] Implement Page View Tracker
// Note: Trigger database akan otomatis mengupdate view_count di tabel posts
if ($post) {
    $tracker = new PageViewTracker();
    $tracker->track('post', $post['id']);
    
    // Optional: Debug stats (bisa dihapus di production)
    // $viewStats = $tracker->getStats('post', $post['id'], 30);
}

// Format content dengan word-break
if (!empty($post['content'])) {
    if (strip_tags($post['content']) === $post['content']) {
        $post['content'] = '<div style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.8;">' 
                         . htmlspecialchars($post['content']) 
                         . '</div>';
    }
}

// [OLD LOGIC] Disabled to prevent double counting because SQL Trigger handles it now
/*
try {
    increment_post_views($post['id']);
} catch (Exception $e) {
    error_log("Increment Views Error: " . $e->getMessage());
}
*/

// Get post tags
try {
    $stmt = $db->prepare("
        SELECT t.* FROM tags t
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

<div style="background-color: var(--color-background); color: var(--color-text); min-height: 100vh;">

    <div class="py-4 border-b border-gray-200/50">
        <div class="container mx-auto px-4">
            <nav class="flex items-center flex-wrap gap-2 text-sm">
                <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-home"></i> Beranda
                </a>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <a href="<?= BASE_URL ?>posts.php" class="text-blue-600 hover:text-blue-800 transition">Berita</a>
                <?php if (!empty($post['category_name'])): ?>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" class="text-blue-600 hover:text-blue-800 transition">
                    <?= htmlspecialchars($post['category_name']) ?>
                </a>
                <?php endif; ?>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <span class="opacity-75 truncate"><?= truncateText($post['title'], 50) ?></span>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <article class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100" data-aos="fade-up">
                        
                        <?php if (!empty($post['featured_image'])): ?>
                        <div class="relative group">
                            <img src="<?= uploadUrl($post['featured_image']) ?>" 
                                 alt="<?= htmlspecialchars($post['title']) ?>"
                                 class="w-full h-64 md:h-96 object-cover"
                                 loading="eager"
                                 onerror="this.src='<?= BASE_URL ?>assets/images/blog-default.jpg'">
                             <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-60"></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="p-6 md:p-8">
                            
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <?php if (!empty($post['category_name'])): ?>
                                <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                                   class="inline-flex items-center text-xs font-medium px-3 py-1 rounded-full transition hover:opacity-80"
                                   style="background-color: rgba(var(--color-primary-rgb), 0.1); color: var(--color-primary);">
                                    <i class="fas fa-folder mr-1"></i><?= htmlspecialchars($post['category_name']) ?>
                                </a>
                                <?php endif; ?>
                                <span class="text-sm opacity-60 flex items-center">
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= formatTanggal($post['created_at'], 'd F Y') ?>
                                </span>
                            </div>
                            
                            <h1 class="text-3xl md:text-4xl font-bold mb-6 leading-tight break-words" style="color: var(--color-text);">
                                <?= htmlspecialchars($post['title']) ?>
                            </h1>
                            
                            <div class="flex flex-wrap items-center gap-4 py-4 border-y border-gray-100 mb-6 text-sm opacity-75">
                                <div class="flex items-center">
                                    <i class="far fa-user mr-2" style="color: var(--color-primary);"></i>
                                    <span><?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="far fa-eye mr-2" style="color: var(--color-primary);"></i>
                                    <span><?= number_format($post['view_count']) ?> kali dibaca</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="far fa-comments mr-2" style="color: var(--color-primary);"></i>
                                    <span><?= $comments_count ?> komentar</span>
                                </div>
                                
                                <div class="flex items-center gap-2 lg:ml-auto">
                                    <span class="text-xs font-medium">Bagikan:</span>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($pageUrl) ?>" 
                                       target="_blank" rel="noopener noreferrer" title="Bagikan ke Facebook"
                                       class="w-8 h-8 flex items-center justify-center text-white rounded-full hover:opacity-80 transition"
                                       style="background-color: #1877F2;">
                                        <i class="fab fa-facebook-f text-xs"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($pageUrl) ?>&text=<?= urlencode($post['title']) ?>" 
                                       target="_blank" rel="noopener noreferrer" title="Bagikan ke Twitter"
                                       class="w-8 h-8 flex items-center justify-center text-white rounded-full hover:opacity-80 transition"
                                       style="background-color: #000000;">
                                        <i class="fab fa-twitter text-xs"></i>
                                    </a>
                                    <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' - ' . $pageUrl) ?>" 
                                       target="_blank" rel="noopener noreferrer" title="Bagikan ke WhatsApp"
                                       class="w-8 h-8 flex items-center justify-center text-white rounded-full hover:opacity-80 transition"
                                       style="background-color: #25D366;">
                                        <i class="fab fa-whatsapp text-xs"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="mb-8">
                                <div class="prose prose-lg max-w-none leading-relaxed" style="word-break: break-word; overflow-wrap: break-word; color: var(--color-text);">
                                    <style>
                                        .prose h2, .prose h3, .prose h4 { color: var(--color-text); font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; }
                                        .prose a { color: var(--color-primary); text-decoration: underline; }
                                        .prose blockquote { border-left-color: var(--color-primary); color: var(--color-text); opacity: 0.8; }
                                        .prose ul { list-style-type: disc; padding-left: 1.5em; }
                                        .prose ol { list-style-type: decimal; padding-left: 1.5em; }
                                        .prose img { border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin: 1.5em 0; }
                                    </style>
                                    <?= $post['content'] ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($tags)): ?>
                            <div class="flex flex-wrap items-center gap-2 py-4 border-t border-gray-100">
                                <span class="text-sm font-semibold opacity-75">
                                    <i class="fas fa-tags mr-1"></i> Tags:
                                </span>
                                <?php foreach ($tags as $tag): ?>
                                <a href="<?= BASE_URL ?>tag.php?slug=<?= $tag['slug'] ?>" 
                                   class="inline-block bg-gray-100 hover:text-white text-sm px-3 py-1 rounded-full transition"
                                   onmouseover="this.style.backgroundColor='var(--color-primary)'"
                                   onmouseout="this.style.backgroundColor='#f3f4f6'"
                                   style="color: var(--color-text);">
                                    <?= htmlspecialchars($tag['name']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="py-6 border-t border-gray-100">
                                <button onclick="toggleLike(<?= $post['id'] ?>)" 
                                        id="like-btn"
                                        class="flex items-center gap-2 px-6 py-3 rounded-lg font-medium transition transform hover:scale-105 shadow-sm"
                                        style="<?= $has_liked ? 'background-color: #EF4444; color: white;' : 'background-color: #f3f4f6; color: var(--color-text);' ?>">
                                    <i class="<?= $has_liked ? 'fas' : 'far' ?> fa-heart"></i>
                                    <span id="like-count" class="font-bold"><?= $likes_count ?></span>
                                    <span>Suka</span>
                                </button>
                            </div>
                            
                        </div>
                    </article>
                    
                    <?php if (!empty($related_posts)): ?>
                    <div class="mt-12" data-aos="fade-up">
                        <h2 class="text-2xl font-bold mb-6 flex items-center" style="color: var(--color-text);">
                            <i class="fas fa-newspaper mr-2" style="color: var(--color-primary);"></i>
                            Artikel Terkait
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <?php foreach ($related_posts as $index => $related): ?>
                            <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition border border-gray-100 group"
                                     data-aos="fade-up"
                                     data-aos-delay="<?= $index * 100 ?>">
                                <a href="<?= BASE_URL ?>post.php?slug=<?= $related['slug'] ?>" class="block overflow-hidden relative">
                                    <img src="<?= get_featured_image($related['featured_image']) ?>" 
                                         alt="<?= htmlspecialchars($related['title']) ?>"
                                         class="w-full h-40 object-cover transform group-hover:scale-110 transition-transform duration-300"
                                         loading="lazy">
                                    <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                                </a>
                                <div class="p-4">
                                    <h3 class="font-bold mb-2 line-clamp-2">
                                        <a href="<?= BASE_URL ?>post.php?slug=<?= $related['slug'] ?>" 
                                           class="transition hover:opacity-80"
                                           style="color: var(--color-text);">
                                            <?= htmlspecialchars($related['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="text-xs opacity-60">
                                        <i class="far fa-calendar mr-1"></i>
                                        <?= formatTanggal($related['created_at'], 'd M Y') ?>
                                    </div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-12 bg-white rounded-lg shadow-md p-6 md:p-8 border border-gray-100" data-aos="fade-up">
                        <h2 class="text-2xl font-bold mb-6 flex items-center" style="color: var(--color-text);">
                            <i class="far fa-comments mr-2" style="color: var(--color-primary);"></i>
                            Komentar (<?= $comments_count ?>)
                        </h2>
                        
                        <form id="comment-form" class="mb-8 bg-gray-50 p-6 rounded-lg border border-gray-100">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text);">Nama *</label>
                                    <input type="text" name="author_name" required
                                           placeholder="Masukkan nama Anda"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                           style="focus:ring-color: var(--color-primary);">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text);">Email *</label>
                                    <input type="email" name="author_email" required
                                           placeholder="contoh@email.com"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                           style="focus:ring-color: var(--color-primary);">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text);">Komentar *</label>
                                    <textarea name="content" rows="4" required
                                              placeholder="Tulis komentar Anda di sini..."
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                              style="focus:ring-color: var(--color-primary);"></textarea>
                                </div>
                            </div>
                            <button type="submit" 
                                    class="mt-4 px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition shadow-md"
                                    style="background-color: var(--color-primary);">
                                <i class="fas fa-paper-plane mr-2"></i>Kirim Komentar
                            </button>
                        </form>
                        
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
                            <div class="border-b border-gray-100 pb-6 mb-6 last:border-0 last:pb-0 last:mb-0">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-sm"
                                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                                        <?= strtoupper(substr($comment['name'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="font-semibold" style="color: var(--color-text);"><?= htmlspecialchars($comment['name']) ?></span>
                                            <span class="text-xs opacity-60">
                                                <i class="far fa-clock mr-1"></i>
                                                <?= formatTanggal($comment['created_at'], 'd M Y H:i') ?>
                                            </span>
                                        </div>
                                        <p class="leading-relaxed opacity-80" style="color: var(--color-text);"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php 
                                    endforeach;
                                else:
                            ?>
                            <div class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                <i class="far fa-comments text-5xl mb-4 opacity-30"></i>
                                <p class="opacity-60">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
                            </div>
                            <?php 
                                endif;
                            } catch (Exception $e) {
                                error_log("Comments Query Error: " . $e->getMessage());
                                echo '<p class="text-center opacity-60 py-8">Gagal memuat komentar</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        <?php include 'templates/sidebar.php'; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </section>
</div>

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
                btn.style.backgroundColor = '#EF4444';
                btn.style.color = 'white';
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                btn.style.backgroundColor = '#f3f4f6';
                btn.style.color = 'var(--color-text)';
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