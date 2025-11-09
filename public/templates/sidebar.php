<?php
/**
 * Sidebar Template
 * Includes: Search, Popular Posts, Categories, Tags
 */

// Get popular posts
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
    
    <!-- Search Widget -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <i class="fas fa-search text-blue-600 mr-2"></i>
            Pencarian
        </h3>
        <form action="<?= BASE_URL ?>search.php" method="GET">
            <div class="flex">
                <input type="text" name="q" placeholder="Cari..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Popular Posts Widget -->
    <?php if (!empty($popular_posts)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <i class="fas fa-fire text-orange-500 mr-2"></i>
            Berita Populer
        </h3>
        <ul class="space-y-4">
            <?php foreach ($popular_posts as $post): ?>
            <li class="flex space-x-3">
                <img src="<?= get_featured_image($post['featured_image']) ?>" 
                     alt="<?= htmlspecialchars($post['title']) ?>"
                     class="w-16 h-16 object-cover rounded">
                <div class="flex-1">
                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                       class="text-sm font-semibold hover:text-blue-600 transition line-clamp-2">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                    <div class="text-xs text-gray-500 mt-1 flex items-center space-x-2">
                        <span><i class="far fa-calendar mr-1"></i><?= formatTanggal($post['created_at'], 'd M Y') ?></span>
                        <span><i class="far fa-eye mr-1"></i><?= number_format($post['view_count']) ?></span>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Categories Widget -->
    <?php if (!empty($categories)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <i class="fas fa-folder text-yellow-500 mr-2"></i>
            Kategori
        </h3>
        <ul class="space-y-2">
            <?php foreach ($categories as $cat): ?>
            <li>
                <a href="<?= BASE_URL ?>category.php?slug=<?= $cat['slug'] ?>" 
                   class="flex justify-between items-center text-gray-700 hover:text-blue-600 transition group">
                    <span class="flex items-center">
                        <i class="fas fa-chevron-right mr-2 text-xs text-gray-400 group-hover:text-blue-600"></i>
                        <?= htmlspecialchars($cat['name']) ?>
                    </span>
                    <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded group-hover:bg-blue-600 group-hover:text-white transition">
                        <?= $cat['post_count'] ?>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Tags Widget -->
    <?php if (!empty($tags)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <i class="fas fa-tags text-green-500 mr-2"></i>
            Tag Populer
        </h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
            <a href="<?= BASE_URL ?>tag.php?slug=<?= $tag['slug'] ?>" 
               class="inline-block bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-700 text-sm px-3 py-1 rounded-full transition">
                <i class="fas fa-tag mr-1 text-xs"></i><?= htmlspecialchars($tag['name']) ?> (<?= $tag['post_count'] ?>)
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</aside>
