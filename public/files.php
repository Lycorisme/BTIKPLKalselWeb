<?php
/**
 * Files/Downloads Page (Fixed Database Logic & Animation)
 * * PERBAIKAN:
 * 1. Menghapus JOIN kategori karena tabel downloadable_files tidak punya category_id
 * 2. Menambahkan animasi AOS pada bagian Filter Aktif
 * 3. Memastikan data muncul sesuai struktur database yang ada
 */

require_once 'config.php';

// Helper function fallback jika tidak ada di config.php
if (!function_exists('getFileIcon')) {
    function getFileIcon($type) {
        $icons = [
            'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'text-red-500', 'color_hex' => '#ef4444'],
            'doc' => ['icon' => 'fa-file-word', 'color' => 'text-blue-500', 'color_hex' => '#3b82f6'],
            'docx' => ['icon' => 'fa-file-word', 'color' => 'text-blue-500', 'color_hex' => '#3b82f6'],
            'xls' => ['icon' => 'fa-file-excel', 'color' => 'text-green-500', 'color_hex' => '#22c55e'],
            'xlsx' => ['icon' => 'fa-file-excel', 'color' => 'text-green-500', 'color_hex' => '#22c55e'],
            'ppt' => ['icon' => 'fa-file-powerpoint', 'color' => 'text-orange-500', 'color_hex' => '#f97316'],
            'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => 'text-orange-500', 'color_hex' => '#f97316'],
            'jpg' => ['icon' => 'fa-file-image', 'color' => 'text-purple-500', 'color_hex' => '#a855f7'],
            'jpeg' => ['icon' => 'fa-file-image', 'color' => 'text-purple-500', 'color_hex' => '#a855f7'],
            'png' => ['icon' => 'fa-file-image', 'color' => 'text-purple-500', 'color_hex' => '#a855f7'],
            'zip' => ['icon' => 'fa-file-archive', 'color' => 'text-yellow-500', 'color_hex' => '#eab308'],
            'rar' => ['icon' => 'fa-file-archive', 'color' => 'text-yellow-500', 'color_hex' => '#eab308'],
        ];
        // Return default if null/empty
        if (empty($type)) return ['icon' => 'fa-file', 'color' => 'text-gray-500', 'color_hex' => '#6b7280'];
        
        return $icons[strtolower($type)] ?? ['icon' => 'fa-file', 'color' => 'text-gray-500', 'color_hex' => '#6b7280'];
    }
}

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

// Get files (FIXED QUERY: Removed category join)
try {
    $stmt = $db->prepare("
        SELECT df.*, u.name as uploader_name
        FROM downloadable_files df
        LEFT JOIN users u ON df.uploaded_by = u.id
        WHERE {$where_sql}
        ORDER BY df.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Query Error Files: " . $e->getMessage());
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

<div style="background-color: var(--color-background); color: var(--color-text); min-height: 100vh;">

    <div class="py-4 border-b border-gray-200/50">
        <div class="container mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm">
                <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-home"></i> Beranda
                </a>
                <i class="fas fa-chevron-right opacity-50 text-xs"></i>
                <span class="opacity-75 font-medium">Unduhan</span>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            
            <div class="text-center mb-12" data-aos="fade-up">
                <h1 class="text-4xl font-bold mb-4" style="color: var(--color-text);">
                    <i class="fas fa-download text-blue-600 mr-2" style="color: var(--color-primary);"></i>
                    Unduhan File
                </h1>
                <p class="text-lg max-w-2xl mx-auto opacity-75">
                    Download file, dokumen, panduan, dan materi pembelajaran
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4 border border-gray-100" data-aos="fade-right">
                        <h3 class="text-lg font-bold mb-4 flex items-center" style="color: var(--color-text);">
                            <i class="fas fa-filter mr-2" style="color: var(--color-primary);"></i>
                            Tipe File
                        </h3>
                        
                        <a href="<?= BASE_URL ?>files.php" 
                           class="flex justify-between items-center py-3 px-4 mb-2 rounded-lg transition group"
                           style="<?= empty($file_type) ? 'background-color: var(--color-primary); color: white;' : 'color: var(--color-text);' ?>">
                            <span class="flex items-center">
                                <i class="fas fa-th-large mr-2"></i>
                                Semua File
                            </span>
                            <span class="text-xs px-2 py-1 rounded" 
                                  style="<?= empty($file_type) ? 'background-color: rgba(255,255,255,0.2); color: white;' : 'background-color: #f3f4f6; color: #666;' ?>">
                                <?= $total_files ?>
                            </span>
                        </a>
                        
                        <div class="space-y-1">
                            <?php foreach ($file_types as $type): 
                                $typeIcon = getFileIcon($type['file_type']);
                                $isActive = ($file_type === $type['file_type']);
                                // Ensure icon array exists
                                $iconClass = $typeIcon['icon'] ?? 'fa-file';
                                $iconColor = $typeIcon['color_hex'] ?? '#666';
                            ?>
                            <a href="?type=<?= urlencode($type['file_type']) ?>" 
                               class="flex justify-between items-center py-3 px-4 rounded-lg transition group hover:bg-gray-50"
                               style="<?= $isActive ? 'background-color: var(--color-primary); color: white;' : 'color: var(--color-text);' ?>">
                                <span class="flex items-center">
                                    <i class="fas <?= $iconClass ?> mr-2" style="<?= $isActive ? '' : 'color: '.$iconColor ?>"></i>
                                    <span class="truncate font-medium"><?= strtoupper(htmlspecialchars($type['file_type'])) ?></span>
                                </span>
                                <span class="text-xs px-2 py-1 rounded flex-shrink-0 ml-2"
                                      style="<?= $isActive ? 'background-color: rgba(255,255,255,0.2); color: white;' : 'background-color: #f3f4f6; color: #666;' ?>">
                                    <?= $type['file_count'] ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (empty($file_types)): ?>
                        <div class="text-center py-4 opacity-60 text-sm">
                            <i class="fas fa-inbox text-2xl mb-2"></i>
                            <p>Belum ada file</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="lg:col-span-3">
                    
                    <?php if (!empty($file_type)): ?>
                    <div class="mb-4 flex items-center gap-2"
                         data-aos="fade-up"
                         data-aos-delay="120"
                         data-aos-duration="800"
                         data-aos-offset="50">
                        <span class="text-sm opacity-75">Filter aktif:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50"
                              style="color: var(--color-primary);">
                            <?php 
                                $activeIconData = getFileIcon($file_type);
                                $activeIconClass = $activeIconData['icon'] ?? 'fa-file';
                            ?>
                            <i class="fas <?= $activeIconClass ?> mr-2"></i>
                            <?= strtoupper(htmlspecialchars($file_type)) ?>
                            <a href="<?= BASE_URL ?>files.php" class="ml-2 hover:opacity-75 transition">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($files)): ?>
                    
                    <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100" data-aos="fade-up">
                        <i class="fas fa-folder-open text-6xl mb-4 opacity-20"></i>
                        <h2 class="text-2xl font-bold mb-2" style="color: var(--color-text);">Belum Ada File</h2>
                        <p class="opacity-60">
                            <?= !empty($file_type) ? 'Tidak ada file dengan tipe ' . strtoupper($file_type) : 'File unduhan akan ditampilkan di sini' ?>
                        </p>
                        <?php if (!empty($file_type)): ?>
                        <a href="<?= BASE_URL ?>files.php" 
                           class="inline-block mt-4 px-6 py-2 text-white rounded-lg transition shadow-md hover:shadow-lg"
                           style="background-color: var(--color-primary);">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Lihat Semua File
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php else: ?>
                    
                    <div class="mb-4 text-sm opacity-60"
                         data-aos="fade-up"
                         data-aos-delay="120"
                         data-aos-duration="800"
                         data-aos-offset="50">
                        Menampilkan <?= count($files) ?> dari <?= $total_files ?> file
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($files as $index => $file): 
                            $fileIcon = getFileIcon($file['file_type']);
                            $iconClass = $fileIcon['icon'] ?? 'fa-file';
                            $iconColor = $fileIcon['color_hex'] ?? 'var(--color-primary)';
                            $delay = ($index % 5) * 50; // Staggered animation delay
                        ?>
                        <article class="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:-translate-y-1" 
                                 data-aos="fade-up"
                                 data-aos-delay="<?= $delay ?>">
                            <div class="flex flex-col md:flex-row">
                                
                                <div class="md:w-32 flex items-center justify-center p-6 bg-gray-50/50 border-r border-gray-100">
                                    <i class="fas <?= $iconClass ?> text-6xl" style="color: <?= $iconColor ?>"></i>
                                </div>
                                
                                <div class="flex-1 p-6">
                                    <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">
                                        <?= htmlspecialchars($file['title']) ?>
                                    </h3>
                                    
                                    <?php if (!empty($file['description'])): ?>
                                    <p class="mb-4 line-clamp-2 text-sm opacity-75">
                                        <?= htmlspecialchars($file['description']) ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <div class="flex flex-wrap items-center gap-4 text-sm opacity-60 mb-4">
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
                                       class="inline-flex items-center px-6 py-3 text-white rounded-lg transition font-medium shadow-md hover:shadow-lg"
                                       style="background-color: var(--color-primary);">
                                        <i class="fas fa-download mr-2"></i>
                                        Download File
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center" data-aos="fade-up">
                        <nav class="flex gap-2">
                            <?php if ($page > 1): ?>
                            <a href="?type=<?= urlencode($file_type) ?>&page=<?= $page - 1 ?>" 
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
                            <a href="?type=<?= urlencode($file_type) ?>&page=<?= $i ?>" 
                               class="px-4 py-2 rounded-lg transition font-medium shadow-sm border"
                               style="<?= $isActive ? 'background-color: var(--color-primary); color: white; border-color: var(--color-primary);' : 'background-color: white; color: var(--color-text); border-color: #e5e7eb;' ?>">
                                <?= $i ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?type=<?= urlencode($file_type) ?>&page=<?= $page + 1 ?>" 
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
            </div>
        </div>
    </section>
</div>

<?php include 'templates/footer.php'; ?>