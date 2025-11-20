<?php
/**
 * Sidebar Template (Dynamic Theme & Layout Fixes)
 * Includes: Search, Popular Posts, Categories, Tags
 * * PERBAIKAN:
 * - Mengganti semua warna statis dengan var(--color-primary)
 * - Memperbaiki layout Popular Posts agar tidak rusak karena URL panjang
 * - Menambahkan AOS staggered delay pada setiap widget
 */

// Get popular posts
// Asumsi get_popular_posts(5) sudah mengambil data yang diperlukan
$popular_posts = get_popular_posts(5);

// Get categories
global $db;
$stmt = $db->query("
    SELECT c.*, COUNT(p.id) as post_count
    FROM post_categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'published' AND p.deleted_at IS NULL
    WHERE c.deleted_at IS NULL
    GROUP BY c.id
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular tags
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
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<aside class="space-y-6">
    
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100" data-aos="fade-up">
        <h3 class="text-lg font-bold mb-4 flex items-center" style="color: var(--color-text);">
            <i class="fas fa-search mr-2" style="color: var(--color-primary);"></i>
            Pencarian
        </h3>
        <form action="<?= BASE_URL ?>search.php" method="GET">
            <div class="flex">
                <input type="text" name="q" placeholder="Cari..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:border-transparent"
                       style="focus:ring-color: var(--color-primary);">
                <button type="submit" class="text-white px-4 py-2 rounded-r-lg hover:opacity-90 transition"
                        style="background-color: var(--color-primary);">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
    <?php if (!empty($popular_posts)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100" data-aos="fade-up" data-aos-delay="100">
        <h3 class="text-lg font-bold mb-4 flex items-center" style="color: var(--color-text);">
            <i class="fas fa-fire mr-2 text-orange-500"></i>
            Berita Populer
        </h3>
        <ul class="space-y-4">
            <?php foreach ($popular_posts as $post): ?>
            <li class="flex space-x-3 overflow-hidden max-w-full">
                <img src="<?= get_featured_image($post['featured_image']) ?>" 
                     alt="<?= htmlspecialchars($post['title']) ?>"
                     class="w-16 h-16 object-cover rounded flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                       class="text-sm font-semibold transition line-clamp-2 overflow-hidden block"
                       style="color: var(--color-text);"
                       onmouseover="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary')"
                       onmouseout="this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-text')">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                    <div class="text-xs opacity-60 mt-1 flex items-center space-x-2">
                        <span><i class="far fa-calendar mr-1"></i><?= formatTanggal($post['created_at'], 'd M Y') ?></span>
                        <span><i class="far fa-eye mr-1"></i><?= number_format($post['view_count']) ?></span>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($categories)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100" data-aos="fade-up" data-aos-delay="200">
        <h3 class="text-lg font-bold mb-4 flex items-center" style="color: var(--color-text);">
            <i class="fas fa-folder text-yellow-500 mr-2"></i>
            Kategori
        </h3>
        <ul class="space-y-2">
            <?php foreach ($categories as $cat): ?>
            <li>
                <a href="<?= BASE_URL ?>category.php?slug=<?= $cat['slug'] ?>" 
                   class="flex justify-between items-center opacity-85 hover:opacity-100 transition group"
                   style="color: var(--color-text);">
                    <span class="flex items-center group-hover:underline">
                        <i class="fas fa-chevron-right mr-2 text-xs opacity-50 group-hover:opacity-100" style="color: var(--color-primary);"></i>
                        <?= htmlspecialchars($cat['name']) ?>
                    </span>
                    <span class="bg-gray-200 text-xs px-2 py-1 rounded transition flex-shrink-0 ml-2"
                          style="color: var(--color-text);"
                          onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary'); this.style.color='white'"
                          onmouseout="this.style.backgroundColor='#E5E7EB'; this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-text')">
                        <?= $cat['post_count'] ?>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($tags)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100" data-aos="fade-up" data-aos-delay="300">
        <h3 class="text-lg font-bold mb-4 flex items-center" style="color: var(--color-text);">
            <i class="fas fa-tags text-green-500 mr-2"></i>
            Tag Populer
        </h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
            <a href="<?= BASE_URL ?>tag.php?slug=<?= $tag['slug'] ?>" 
               class="inline-block text-sm px-3 py-1 rounded-full transition"
               style="color: var(--color-text); background-color: rgba(0,0,0,0.05);"
               onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary'); this.style.color='white'"
               onmouseout="this.style.backgroundColor='rgba(0,0,0,0.05)'; this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-text')">
                <i class="fas fa-tag mr-1 text-xs"></i><?= htmlspecialchars($tag['name']) ?> (<?= $tag['post_count'] ?>)
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</aside>