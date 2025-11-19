<?php
/**
 * Gallery Page - Photo Albums (Fixed Theme Consistency)
 * Fully integrated with settings table
 * Features: Album listing, Photo grid, Lightbox, Pagination
 */

require_once 'config.php';

// Dynamic settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteDescription = getSetting('site_description', 'Portal resmi Balai Teknologi Informasi dan Komunikasi Pendidikan Kalimantan Selatan');

// Get album filter
$album_id = $_GET['album'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Page variables
$pageNamespace = 'gallery';
$pageTitle = 'Galeri Foto - ' . $siteName;
$pageDescription = 'Galeri foto dokumentasi kegiatan dan acara ' . $siteName;

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
                <span class="opacity-75 font-medium">Galeri</span>
            </nav>
        </div>
    </div>

    <?php if (empty($album_id)): ?>
        <?php
        // Get all active albums
        try {
            $stmt = $db->prepare("
                SELECT * FROM gallery_albums 
                WHERE is_active = 1 AND deleted_at IS NULL
                ORDER BY display_order ASC, created_at DESC
            ");
            $stmt->execute();
            $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Gallery Albums Error: " . $e->getMessage());
            $albums = [];
        }
        ?>
        
        <section class="py-12">
            <div class="container mx-auto px-4">
                <div class="text-center mb-12" data-aos="fade-up">
                    <h1 class="text-4xl font-bold mb-4" style="color: var(--color-text);">
                        <i class="fas fa-images mr-2" style="color: var(--color-primary);"></i>
                        Galeri Foto
                    </h1>
                    <p class="text-lg opacity-75">Dokumentasi kegiatan dan acara <?= htmlspecialchars($siteName) ?></p>
                </div>
                
                <?php if (empty($albums)): ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100" data-aos="fade-up">
                    <i class="fas fa-images text-6xl mb-4 opacity-20"></i>
                    <h2 class="text-2xl font-bold mb-2" style="color: var(--color-text);">Belum Ada Album</h2>
                    <p class="opacity-60">Album galeri foto <?= htmlspecialchars($siteName) ?> akan ditampilkan di sini</p>
                </div>
                
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($albums as $index => $album): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow group border border-gray-100"
                             data-aos="fade-up" 
                             data-aos-delay="<?= $index * 100 ?>">
                        <a href="?album=<?= $album['id'] ?>" class="block relative overflow-hidden">
                            <div class="h-64 overflow-hidden bg-gray-100">
                                <?php if (!empty($album['cover_photo'])): ?>
                                <img src="<?= uploadUrl($album['cover_photo']) ?>" 
                                     alt="<?= htmlspecialchars($album['name']) ?>"
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300"
                                     loading="lazy"
                                     onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><i class=\'fas fa-image text-6xl opacity-20\'></i></div>'">
                                <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-image text-6xl opacity-20"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-6">
                                <div class="text-white">
                                    <p class="flex items-center gap-2 text-sm font-medium">
                                        <i class="fas fa-images"></i>
                                        <?= number_format($album['photo_count']) ?> Foto
                                    </p>
                                </div>
                            </div>
                        </a>
                        
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2 transition">
                                <a href="?album=<?= $album['id'] ?>" 
                                   class="hover:text-blue-600"
                                   style="color: var(--color-text);">
                                    <?= htmlspecialchars($album['name']) ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($album['description'])): ?>
                            <p class="text-sm mb-4 line-clamp-2 opacity-75">
                                <?= htmlspecialchars($album['description']) ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between text-sm opacity-60 pt-4 border-t border-gray-100">
                                <span>
                                    <i class="far fa-images mr-1"></i>
                                    <?= number_format($album['photo_count']) ?> Foto
                                </span>
                                <span>
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= formatTanggal($album['created_at'], 'd M Y') ?>
                                </span>
                            </div>
                            
                            <a href="?album=<?= $album['id'] ?>" 
                               class="mt-4 inline-flex items-center font-medium transition hover:translate-x-1"
                               style="color: var(--color-primary);">
                                Lihat Album
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

    <?php else: ?>
        <?php
        // Get album info
        try {
            $stmt = $db->prepare("
                SELECT * FROM gallery_albums 
                WHERE id = ? AND is_active = 1 AND deleted_at IS NULL
            ");
            $stmt->execute([$album_id]);
            $album = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$album) {
                header('Location: ' . BASE_URL . 'gallery.php');
                exit;
            }
            
            // Get photos count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM gallery_photos WHERE album_id = ? AND deleted_at IS NULL");
            $stmt->execute([$album_id]);
            $total_photos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $total_pages = ceil($total_photos / $per_page);
            
            // Get photos
            $stmt = $db->prepare("
                SELECT * FROM gallery_photos 
                WHERE album_id = ? AND deleted_at IS NULL
                ORDER BY display_order ASC, created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$album_id, $per_page, $offset]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Gallery Photos Error: " . $e->getMessage());
            $album = null;
            $photos = [];
            $total_photos = 0;
        }
        ?>
        
        <section class="py-12">
            <div class="container mx-auto px-4">
                <div class="mb-8" data-aos="fade-up">
                    <a href="<?= BASE_URL ?>gallery.php" 
                       class="inline-flex items-center mb-4 transition hover:-translate-x-1"
                       style="color: var(--color-primary);">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali ke Galeri
                    </a>
                    
                    <div class="bg-white rounded-lg shadow-md p-8 border-l-4" style="border-left-color: var(--color-primary);">
                        <h1 class="text-3xl font-bold mb-2" style="color: var(--color-text);">
                            <?= htmlspecialchars($album['name']) ?>
                        </h1>
                        
                        <?php if (!empty($album['description'])): ?>
                        <p class="mb-4 opacity-75"><?= htmlspecialchars($album['description']) ?></p>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-6 text-sm opacity-60">
                            <span>
                                <i class="far fa-images mr-1"></i>
                                <?= number_format($total_photos) ?> Foto
                            </span>
                            <span>
                                <i class="far fa-calendar mr-1"></i>
                                <?= formatTanggal($album['created_at'], 'd F Y') ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($photos)): ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center border border-gray-100" data-aos="fade-up">
                    <i class="fas fa-image text-6xl mb-4 opacity-20"></i>
                    <h2 class="text-2xl font-bold mb-2" style="color: var(--color-text);">Belum Ada Foto</h2>
                    <p class="opacity-60">Foto dalam album ini akan ditampilkan di sini</p>
                </div>
                
                <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($photos as $index => $photo): ?>
                    <div class="group relative overflow-hidden rounded-lg shadow-md hover:shadow-xl transition-shadow cursor-pointer"
                         onclick="openLightbox(<?= $index ?>)"
                         data-aos="fade-up"
                         data-aos-delay="<?= ($index % 8) * 50 ?>">
                        <div class="aspect-square overflow-hidden bg-gray-100">
                            <img src="<?= uploadUrl($photo['thumbnail'] ?? $photo['filename']) ?>" 
                                 data-full="<?= uploadUrl($photo['filename']) ?>"
                                 alt="<?= htmlspecialchars($photo['title'] ?? $album['name']) ?>"
                                 class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300"
                                 loading="lazy"
                                 onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center\'><i class=\'fas fa-image text-4xl opacity-20\'></i></div>'">
                        </div>
                        
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <i class="fas fa-search-plus text-white text-3xl"></i>
                        </div>
                        
                        <?php if (!empty($photo['title']) || !empty($photo['caption'])): ?>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                            <?php if (!empty($photo['title'])): ?>
                            <p class="font-semibold text-sm truncate"><?= htmlspecialchars($photo['title']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($photo['caption'])): ?>
                            <p class="text-xs mt-1 line-clamp-2 opacity-90"><?= htmlspecialchars($photo['caption']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="mt-12 flex justify-center" data-aos="fade-up">
                    <nav class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?album=<?= $album_id ?>&page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                           title="Halaman Sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                            $isActive = ($i === $page);
                        ?>
                        <a href="?album=<?= $album_id ?>&page=<?= $i ?>" 
                           class="px-4 py-2 rounded-lg transition font-medium shadow-sm border"
                           style="<?= $isActive ? 'background-color: var(--color-primary); color: white; border-color: var(--color-primary);' : 'background-color: white; color: var(--color-text); border-color: #e5e7eb;' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?album=<?= $album_id ?>&page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition"
                           title="Halaman Berikutnya">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <div id="lightbox" class="fixed inset-0 bg-black/95 z-[60] hidden items-center justify-center p-4">
            <button onclick="closeLightbox()" 
                    class="absolute top-4 right-4 text-white hover:text-gray-300 text-4xl z-10 transition"
                    title="Tutup (ESC)">
                <i class="fas fa-times"></i>
            </button>
            
            <button onclick="prevPhoto()" 
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-4xl z-10 transition"
                    title="Foto Sebelumnya (←)">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <button onclick="nextPhoto()" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-4xl z-10 transition"
                    title="Foto Selanjutnya (→)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="max-w-6xl max-h-full flex flex-col items-center">
                <img id="lightbox-img" 
                     src="" 
                     alt="" 
                     class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl">
                <div id="lightbox-caption" class="text-white text-center mt-4 max-w-2xl"></div>
            </div>
            
            <div id="lightbox-loading" class="absolute inset-0 flex items-center justify-center hidden">
                <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
            </div>
        </div>
        
        <script>
        // Prepare photos data
        const photos = [
            <?php foreach ($photos as $photo): ?>
            {
                filename: '<?= uploadUrl($photo['filename']) ?>',
                title: <?= json_encode($photo['title'] ?? '') ?>,
                caption: <?= json_encode($photo['caption'] ?? '') ?>
            },
            <?php endforeach; ?>
        ];

        let currentPhotoIndex = 0;

        function openLightbox(index) {
            currentPhotoIndex = index;
            showPhoto(index);
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
            document.body.style.overflow = '';
        }

        function showPhoto(index) {
            const photo = photos[index];
            const img = document.getElementById('lightbox-img');
            const caption = document.getElementById('lightbox-caption');
            const loading = document.getElementById('lightbox-loading');
            
            // Show loading
            loading.classList.remove('hidden');
            img.style.opacity = '0';
            
            // Preload image
            const tempImg = new Image();
            tempImg.onload = function() {
                img.src = photo.filename;
                img.alt = photo.title || 'Foto';
                
                // Hide loading
                loading.classList.add('hidden');
                img.style.opacity = '1';
            };
            tempImg.onerror = function() {
                loading.classList.add('hidden');
                alert('Gagal memuat foto');
                closeLightbox();
            };
            tempImg.src = photo.filename;
            
            // Update caption
            let captionHtml = '';
            if (photo.title || photo.caption) {
                if (photo.title) {
                    captionHtml += '<div class="text-lg font-semibold mb-1">' + escapeHtml(photo.title) + '</div>';
                }
                if (photo.caption) {
                    captionHtml += '<div class="text-sm text-gray-300">' + escapeHtml(photo.caption) + '</div>';
                }
            }
            captionHtml += '<div class="text-sm text-gray-400 mt-2">Foto ' + (index + 1) + ' dari ' + photos.length + '</div>';
            
            caption.innerHTML = captionHtml;
        }

        function nextPhoto() {
            currentPhotoIndex = (currentPhotoIndex + 1) % photos.length;
            showPhoto(currentPhotoIndex);
        }

        function prevPhoto() {
            currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length;
            showPhoto(currentPhotoIndex);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightbox');
            if (!lightbox.classList.contains('hidden')) {
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowRight') nextPhoto();
                if (e.key === 'ArrowLeft') prevPhoto();
            }
        });

        // Close on background click
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });

        // Initialize AOS
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 600,
                    once: true,
                    offset: 100
                });
            }
        });
        </script>

    <?php endif; ?>

</div>

<?php include 'templates/footer.php'; ?>