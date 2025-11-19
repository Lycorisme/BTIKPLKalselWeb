<?php
/**
 * Search Results Page (Fixed Theme Consistency)
 * Full-text search dengan filter & sorting
 * * PERBAIKAN:
 * - Wrapper background & text color dinamis
 * - Tombol dan elemen aksen mengikuti var(--color-primary)
 * - Breadcrumb dan Pagination konsisten
 */

require_once 'config.php';

// Get search query
$query = trim($_GET['q'] ?? '');
$category_filter = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'relevance'; // relevance, date, views
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Sanitize query
$query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

// Build search query
$where_conditions = ["p.status = 'published'", "p.deleted_at IS NULL"];
$params = [];

// Search in title, content, and excerpt
if (!empty($query)) {
    $search_term = "%{$query}%";
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Category filter
if (!empty($category_filter) && is_numeric($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

// Build WHERE clause
$where_sql = implode(' AND ', $where_conditions);

// Sorting
$order_by = match($sort) {
    'date' => 'p.created_at DESC',
    'views' => 'p.view_count DESC',
    'title' => 'p.title ASC',
    default => 'relevance DESC, p.created_at DESC' // Relevance = keyword match count
};

// Get total count
try {
    $count_sql = "
        SELECT COUNT(*) as total
        FROM posts p
        WHERE {$where_sql}
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_results = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_results / $per_page);
} catch (Exception $e) {
    error_log($e->getMessage());
    $total_results = 0;
    $total_pages = 0;
}

// Get search results
try {
    // Calculate relevance score if searching
    if (!empty($query)) {
        $relevance_sql = "
            (
                (CASE WHEN p.title LIKE ? THEN 3 ELSE 0 END) +
                (CASE WHEN p.excerpt LIKE ? THEN 2 ELSE 0 END) +
                (CASE WHEN p.content LIKE ? THEN 1 ELSE 0 END)
            ) as relevance
        ";
        $relevance_params = [$search_term, $search_term, $search_term];
    } else {
        $relevance_sql = "0 as relevance";
        $relevance_params = [];
    }
    
    $sql = "
        SELECT p.*,
               c.name as category_name,
               c.slug as category_slug,
               u.name as author_name,
               {$relevance_sql}
        FROM posts p
        LEFT JOIN post_categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE {$where_sql}
        ORDER BY {$order_by}
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($relevance_params, $params));
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $results = [];
}

// Get categories for filter
try {
    $stmt = $db->query("
        SELECT id, name, slug 
        FROM post_categories 
        WHERE deleted_at IS NULL 
        ORDER BY name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Page variables
$pageNamespace = 'search';
$pageTitle = !empty($query) ? "Hasil Pencarian: {$query}" : "Pencarian";
$pageDescription = "Hasil pencarian untuk: {$query}";

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
                <span class="opacity-75 font-medium">Pencarian</span>
                <?php if (!empty($query)): ?>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <span class="font-semibold">"<?= htmlspecialchars($query) ?>"</span>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 border border-gray-100" data-aos="fade-up">
                <form action="<?= BASE_URL ?>search.php" method="GET" class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" 
                                       name="q" 
                                       value="<?= htmlspecialchars($query) ?>" 
                                       placeholder="Cari artikel, berita, informasi..." 
                                       class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                       style="focus:ring-color: var(--color-primary);"
                                       required>
                                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 opacity-50"></i>
                            </div>
                        </div>
                        
                        <div class="md:w-48">
                            <select name="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                    style="focus:ring-color: var(--color-primary);">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" 
                                class="px-8 py-3 text-white rounded-lg transition font-medium shadow-md hover:opacity-90"
                                style="background-color: var(--color-primary);">
                            <i class="fas fa-search mr-2"></i>Cari
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($query)): ?>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--color-text);">
                        <?php if ($total_results > 0): ?>
                            Ditemukan <?= number_format($total_results) ?> hasil
                        <?php else: ?>
                            Tidak ada hasil
                        <?php endif; ?>
                    </h1>
                    <p class="opacity-75 mt-1">
                        untuk pencarian: <strong>"<?= htmlspecialchars($query) ?>"</strong>
                    </p>
                </div>
                
                <?php if ($total_results > 0): ?>
                <div class="flex items-center gap-2">
                    <span class="text-sm opacity-75">Urutkan:</span>
                    <select onchange="window.location.href=this.value" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:border-transparent"
                            style="color: var(--color-text);">
                        <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>
                            Relevansi
                        </option>
                        <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=date" <?= $sort === 'date' ? 'selected' : '' ?>>
                            Terbaru
                        </option>
                        <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=views" <?= $sort === 'views' ? 'selected' : '' ?>>
                            Terpopuler
                        </option>
                        <option value="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=title" <?= $sort === 'title' ? 'selected' : '' ?>>
                            Judul (A-Z)
                        </option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2">
                    <?php if (empty($query)): ?>
                    
                    <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100">
                        <i class="fas fa-search text-6xl mb-4 opacity-20"></i>
                        <h2 class="text-2xl font-bold mb-2" style="color: var(--color-text);">Cari Artikel</h2>
                        <p class="opacity-60">Gunakan form di atas untuk mencari artikel, berita, atau informasi</p>
                    </div>
                    
                    <?php elseif (empty($results)): ?>
                    
                    <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100">
                        <i class="fas fa-search-minus text-6xl mb-4 opacity-20"></i>
                        <h2 class="text-2xl font-bold mb-2" style="color: var(--color-text);">Tidak Ada Hasil</h2>
                        <p class="mb-4 opacity-75">
                            Tidak ditemukan hasil untuk "<strong><?= htmlspecialchars($query) ?></strong>"
                        </p>
                        <div class="space-y-2 text-sm opacity-60">
                            <p>Saran:</p>
                            <ul class="list-disc list-inside inline-block text-left">
                                <li>Periksa ejaan kata kunci</li>
                                <li>Gunakan kata kunci yang lebih umum</li>
                                <li>Coba kata kunci yang berbeda</li>
                                <li>Kurangi jumlah kata kunci</li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    
                    <div class="space-y-6">
                        <?php foreach ($results as $post): ?>
                        <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow border border-gray-100">
                            <div class="md:flex">
                                <div class="md:w-64 md:flex-shrink-0">
                                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block h-48 md:h-full overflow-hidden relative group">
                                        <img src="<?= get_featured_image($post['featured_image']) ?>" 
                                             alt="<?= htmlspecialchars($post['title']) ?>"
                                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300">
                                        <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                                    </a>
                                </div>
                                
                                <div class="p-6 flex-1">
                                    <?php if (!empty($post['category_name'])): ?>
                                    <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                                       class="inline-block text-xs font-medium px-2 py-1 rounded mb-2 hover:opacity-80 transition"
                                       style="background-color: rgba(var(--color-primary-rgb), 0.1); color: var(--color-primary);">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <h2 class="text-xl font-bold mb-2 transition">
                                        <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                                           class="hover:opacity-80"
                                           style="color: var(--color-text);">
                                            <?php
                                            // Highlight search term in title
                                            $title = htmlspecialchars($post['title']);
                                            if (!empty($query)) {
                                                $title = preg_replace(
                                                    '/(' . preg_quote($query, '/') . ')/i',
                                                    '<mark class="bg-yellow-200 px-1 rounded">$1</mark>',
                                                    $title
                                                );
                                            }
                                            echo $title;
                                            ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="mb-4 line-clamp-2 opacity-75">
                                        <?php
                                        $excerpt = htmlspecialchars(truncateText($post['excerpt'] ?? strip_tags($post['content']), 150));
                                        if (!empty($query)) {
                                            $excerpt = preg_replace(
                                                '/(' . preg_quote($query, '/') . ')/i',
                                                '<mark class="bg-yellow-200 px-1 rounded">$1</mark>',
                                                $excerpt
                                            );
                                        }
                                        echo $excerpt;
                                        ?>
                                    </p>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm opacity-60">
                                        <span class="flex items-center">
                                            <i class="far fa-user mr-1"></i>
                                            <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?>
                                        </span>
                                        <span class="flex items-center">
                                            <i class="far fa-calendar mr-1"></i>
                                            <?= formatTanggal($post['created_at'], 'd M Y') ?>
                                        </span>
                                        <span class="flex items-center">
                                            <i class="far fa-eye mr-1"></i>
                                            <?= number_format($post['view_count']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <nav class="flex gap-2">
                            <?php if ($page > 1): ?>
                            <a href="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                               style="color: var(--color-text);">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $isActive = ($i === $page);
                            ?>
                            <a href="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>&page=<?= $i ?>" 
                               class="px-4 py-2 border rounded-lg transition <?= $active ? 'shadow-md' : '' ?>"
                               style="<?= $isActive ? 'background-color: var(--color-primary); color: white; border-color: var(--color-primary);' : 'background-color: white; color: var(--color-text); border-color: #e5e7eb;' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?q=<?= urlencode($query) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>" 
                               class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition shadow-sm"
                               style="color: var(--color-text);">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
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