<?php
/**
 * Files/Downloads Page
 * File listing dengan file type filter, download counter, dan file type icons
 *
 * PERBAIKAN: Menghapus logika 'download_token' yang rusak.
 * Download link sekarang langsung ke api/download.php?id=...
 */

require_once 'config.php';

// Get file type filter
$file_type = $_GET['type'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["df.is_active = 1", "df.deleted_at IS NULL"];
$params = [];

if (!empty($file_type)) {
    $where_conditions[] = "df.file_type = ?";
    $params[] = $file_type;
}

$where_sql = implode(' AND ', $where_conditions);

// Get total count
try {
    $count_sql = "SELECT COUNT(*) as total FROM downloadable_files df WHERE {$where_sql}";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_files = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_files / $per_page);
} catch (Exception $e) {
    error_log($e->getMessage());
    $total_files = 0;
    $total_pages = 0;
}

// Get files
try {
    $stmt = $db->prepare("
        SELECT df.*, fc.name as category_name, fc.slug as category_slug, u.name as uploader_name
        FROM downloadable_files df
        LEFT JOIN file_categories fc ON df.category_id = fc.id
        LEFT JOIN users u ON df.uploaded_by = u.id
        WHERE {$where_sql}
        ORDER BY df.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $files = [];
}

// Get file types with count
try {
    $stmt = $db->query("
        SELECT 
            df.file_type,
            COUNT(*) as file_count
        FROM downloadable_files df
        WHERE df.is_active = 1 AND df.deleted_at IS NULL
        GROUP BY df.file_type
        ORDER BY file_count DESC, df.file_type ASC
    ");
    $file_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $file_types = [];
}

// Page variables
$pageNamespace = 'files';
$pageTitle = 'Unduhan File - ' . getSetting('site_name');
$pageDescription = 'Download file, dokumen, dan materi pembelajaran';

include 'templates/header.php';
?>

<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600">Unduhan</span>
        </nav>
    </div>
</div>

<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <i class="fas fa-download text-blue-600 mr-2"></i>
                Unduhan File
            </h1>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Download file, dokumen, panduan, dan materi pembelajaran
            </p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-filter text-blue-600 mr-2"></i>
                        Tipe File
                    </h3>
                    
                    <a href="<?= BASE_URL ?>files.php" 
                       class="flex justify-between items-center py-3 px-4 mb-2 rounded-lg <?= empty($file_type) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                        <span class="flex items-center">
                            <i class="fas fa-th-large mr-2"></i>
                            Semua File
                        </span>
                        <span class="<?= empty($file_type) ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-700' ?> text-xs px-2 py-1 rounded">
                            <?= $total_files ?>
                        </span>
                    </a>
                    
                    <div class="space-y-1">
                        <?php foreach ($file_types as $type): 
                            $typeIcon = getFileIcon($type['file_type']);
                            $isActive = ($file_type === $type['file_type']);
                        ?>
                        <a href="?type=<?= urlencode($type['file_type']) ?>" 
                           class="flex justify-between items-center py-3 px-4 rounded-lg <?= $isActive ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-50' ?> transition group">
                            <span class="flex items-center">
                                <i class="fas <?= $typeIcon['icon'] ?> mr-2 <?= $isActive ? '' : $typeIcon['color'] ?>"></i>
                                <span class="truncate font-medium"><?= strtoupper(htmlspecialchars($type['file_type'])) ?></span>
                            </span>
                            <span class="<?= $isActive ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-700' ?> text-xs px-2 py-1 rounded flex-shrink-0 ml-2">
                                <?= $type['file_count'] ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($file_types)): ?>
                    <div class="text-center py-4 text-gray-500 text-sm">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p>Belum ada file</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="lg:col-span-3">
                
                <?php if (!empty($file_type)): ?>
                <div class="mb-4 flex items-center gap-2">
                    <span class="text-sm text-gray-600">Filter aktif:</span>
                    <span class="inline-flex items-center bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                        <i class="fas <?= getFileIcon($file_type)['icon'] ?> mr-2"></i>
                        <?= strtoupper(htmlspecialchars($file_type)) ?>
                        <a href="<?= BASE_URL ?>files.php" class="ml-2 hover:text-blue-900">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (empty($files)): ?>
                
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Belum Ada File</h2>
                    <p class="text-gray-600">
                        <?= !empty($file_type) ? 'Tidak ada file dengan tipe ' . strtoupper($file_type) : 'File unduhan akan ditampilkan di sini' ?>
                    </p>
                    <?php if (!empty($file_type)): ?>
                    <a href="<?= BASE_URL ?>files.php" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Lihat Semua File
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php else: ?>
                
                <div class="mb-4 text-sm text-gray-600">
                    Menampilkan <?= count($files) ?> dari <?= $total_files ?> file
                </div>
                
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($files as $file): 
                        $fileIcon = getFileIcon($file['file_type']);
                        // ============= PERBAIKAN: LOGIKA TOKEN DIHAPUS DARI SINI =============
                    ?>
                    <article class="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow overflow-hidden">
                        <div class="flex flex-col md:flex-row">
                            
                            <div class="md:w-32 flex items-center justify-center bg-gray-50 p-6">
                                <i class="fas <?= $fileIcon['icon'] ?> text-6xl <?= $fileIcon['color'] ?>"></i>
                            </div>
                            
                            <div class="flex-1 p-6">
                                <?php if (!empty($file['category_name'])): ?>
                                <span class="inline-block bg-blue-100 text-blue-600 text-xs font-medium px-2 py-1 rounded mb-2">
                                    <i class="fas fa-folder mr-1"></i>
                                    <?= htmlspecialchars($file['category_name']) ?>
                                </span>
                                <?php endif; ?>
                                
                                <h3 class="text-xl font-bold text-gray-900 mb-2">
                                    <?= htmlspecialchars($file['title']) ?>
                                </h3>
                                
                                <?php if (!empty($file['description'])): ?>
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?= htmlspecialchars($file['description']) ?>
                                </p>
                                <?php endif; ?>
                                
                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-file mr-1"></i>
                                        <?= strtoupper($file['file_type']) ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-hdd mr-1"></i>
                                        <?= formatFileSize($file['file_size']) ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-download mr-1"></i>
                                        <?= number_format($file['download_count']) ?> unduhan
                                    </span>
                                    <span class="flex items-center">
                                        <i class="far fa-calendar mr-1"></i>
                                        <?= formatTanggal($file['created_at'], 'd M Y') ?>
                                    </span>
                                </div>
                                
                                <a href="<?= BASE_URL ?>api/download.php?id=<?= $file['id'] ?>" 
                                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                    <i class="fas fa-download mr-2"></i>
                                    Download File
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?type=<?= urlencode($file_type) ?>&page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?type=<?= urlencode($file_type) ?>&page=<?= $i ?>" 
                           class="px-4 py-2 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?type=<?= urlencode($file_type) ?>&page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>