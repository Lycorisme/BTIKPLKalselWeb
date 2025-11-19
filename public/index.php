<?php

require_once 'config.php';

// Page namespace
$pageNamespace = 'home';

// Dynamic SEO Meta from Settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteTagline = getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');
$siteDescription = getSetting('site_description', 'Portal resmi Balai Teknologi Informasi dan Komunikasi Pendidikan Kalimantan Selatan');
$siteKeywords = getSetting('site_keywords', 'btikp, kalsel, pendidikan, teknologi, informasi');

$pageTitle = $siteName . ' - ' . $siteTagline;
$pageDescription = $siteDescription;
$pageKeywords = $siteKeywords;
$pageImage = get_site_logo();

// Get active banners
$stmt = $db->query("
    SELECT * FROM banners 
    WHERE is_active = 1 AND deleted_at IS NULL 
    ORDER BY ordering ASC, created_at DESC
    LIMIT 5
");
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent posts (6 latest)
$stmt = $db->query("
    SELECT p.*, c.name as category_name, c.slug as category_slug
    FROM posts p
    LEFT JOIN post_categories c ON p.category_id = c.id
    WHERE p.status = 'published' AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT 6
");
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured services
// Kita ambil lebih banyak limit agar slider terlihat fungsinya
$stmt = $db->query("
    SELECT * FROM services 
    WHERE status = 'published' AND deleted_at IS NULL 
    ORDER BY created_at DESC 
    LIMIT 9
");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get latest gallery albums (4 albums)
$stmt = $db->query("
    SELECT a.*, 
           a.photo_count,
           a.cover_photo as cover_image
    FROM gallery_albums a
    WHERE a.deleted_at IS NULL
    ORDER BY a.created_at DESC
    LIMIT 4
");
$albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include 'templates/header.php';
?>

<div style="background-color: var(--color-background); color: var(--color-text); min-height: 100vh;">

    <?php if (!empty($banners)): ?>
    <section class="relative overflow-hidden">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                <?php foreach ($banners as $index => $banner): ?>
                <div class="swiper-slide group cursor-pointer">
                    <div class="relative h-[500px] md:h-[600px] bg-cover bg-center transition-transform duration-700 group-hover:scale-105" 
                         style="background-image: url('<?= get_banner_image($banner['image_path']) ?>');">
                        
                        <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        
                        <div class="relative container mx-auto px-4 h-full flex items-center opacity-0 group-hover:opacity-100 translate-y-8 group-hover:translate-y-0 transition-all duration-500 delay-100">
                            <div class="max-w-3xl text-white pl-4 md:pl-0 border-l-4 md:border-l-0 border-blue-500 md:border-none">
                                
                                <h1 class="text-3xl md:text-5xl lg:text-6xl font-bold mb-4 leading-tight drop-shadow-lg">
                                    <?= htmlspecialchars($banner['title']) ?>
                                </h1>
                                
                                <?php if (!empty($banner['description'])): ?>
                                <p class="text-lg md:text-xl mb-8 text-gray-200 leading-relaxed max-w-2xl drop-shadow-md">
                                    <?= htmlspecialchars($banner['description']) ?>
                                </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($banner['link_url'])): ?>
                                <a href="<?= htmlspecialchars($banner['link_url']) ?>" 
                                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-lg font-bold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-blue-500/50"
                                   style="background-color: var(--color-primary);">
                                    <span><?= !empty($banner['button_text']) ? htmlspecialchars($banner['button_text']) : 'Selengkapnya' ?></span>
                                    <i class="fas fa-arrow-right text-sm"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next text-white/0 group-hover:text-white/70 transition-colors after:text-2xl"></div>
            <div class="swiper-button-prev text-white/0 group-hover:text-white/70 transition-colors after:text-2xl"></div>
            <div class="swiper-pagination"></div>
        </div>
    </section>
    <?php else: ?>
    <section class="relative h-[400px] flex items-center overflow-hidden"
             style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMiIgZmlsbD0iI2ZmZmZmZiIvPjwvc3ZnPg==')]"></div>
        <div class="container mx-auto px-4 text-center text-white relative z-10">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4" data-aos="fade-up">
                Selamat Datang di <?= htmlspecialchars($siteName) ?>
            </h1>
            <p class="text-xl mb-8 text-blue-100" data-aos="fade-up" data-aos-delay="100">
                <?= htmlspecialchars($siteTagline) ?>
            </p>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($recent_posts)): ?>
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12" data-aos="fade-up">
                <div class="text-center md:text-left w-full md:w-auto">
                    <h2 class="text-3xl md:text-4xl font-bold mb-3" style="color: var(--color-text);">Berita Terbaru</h2>
                    <p class="text-gray-500">Informasi dan kegiatan terkini dari <?= htmlspecialchars($siteName) ?></p>
                </div>
                <a href="<?= BASE_URL ?>posts.php" class="hidden md:inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold transition mt-4 md:mt-0" style="color: var(--color-primary);">
                    Lihat Semua <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($recent_posts as $index => $post): ?>
                <article class="bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden border border-gray-100 h-full flex flex-col" 
                         data-aos="fade-up" 
                         data-aos-delay="<?= $index * 100 ?>">
                    <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden relative h-56">
                        <img src="<?= get_featured_image($post['featured_image']) ?>" 
                             alt="<?= htmlspecialchars($post['title']) ?>"
                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                             loading="lazy">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                        <?php if (!empty($post['category_name'])): ?>
                        <div class="absolute top-4 left-4">
                            <span class="bg-white/90 backdrop-blur text-xs font-bold px-3 py-1.5 rounded-full shadow-sm" style="color: var(--color-primary);">
                                <?= htmlspecialchars($post['category_name']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </a>
                    
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="text-xs text-gray-400 mb-3 flex items-center gap-3">
                            <span class="flex items-center"><i class="far fa-calendar-alt mr-1.5"></i> <?= formatTanggal($post['created_at'], 'd M Y') ?></span>
                            <span class="flex items-center"><i class="far fa-eye mr-1.5"></i> <?= number_format($post['view_count']) ?></span>
                        </div>

                        <h3 class="text-xl font-bold mb-3 leading-snug group-hover:text-blue-600 transition-colors">
                            <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" style="color: var(--color-text);">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h3>
                        
                        <p class="text-gray-500 text-sm line-clamp-3 mb-4 flex-1">
                            <?= truncateText($post['excerpt'], 100) ?>
                        </p>
                        
                        <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                           class="inline-flex items-center text-sm font-semibold mt-auto"
                           style="color: var(--color-primary);">
                            Baca Selengkapnya <i class="fas fa-chevron-right ml-1 text-xs transition-transform group-hover:translate-x-1"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-8 md:hidden">
                <a href="<?= BASE_URL ?>posts.php" class="btn-primary w-full justify-center">
                    Lihat Semua Berita
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="py-20 bg-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-blue-50 opacity-50 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-purple-50 opacity-50 blur-3xl"></div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16 max-w-3xl mx-auto" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--color-text);">
                    <span style="color: var(--color-primary);">Layanan</span> Kami
                </h2>
                <p class="text-gray-500 text-lg">
                    Kami berdedikasi untuk menyediakan layanan teknologi informasi dan komunikasi terbaik guna mendukung kemajuan pendidikan.
                </p>
            </div>
            
            <?php if (!empty($services)): ?>
            <div class="swiper servicesSwiper pb-10 px-2">
                <div class="swiper-wrapper">
                    <?php foreach ($services as $index => $service): ?>
                    <?php
                        $isExternal = !empty($service['service_url']);
                        $targetUrl = $isExternal ? $service['service_url'] : BASE_URL . 'service.php?slug=' . $service['slug'];
                        $hasImage = !empty($service['image_path']);
                        $imageUrl = $hasImage ? get_service_image($service['image_path']) : '';
                    ?>
                    
                    <div class="swiper-slide h-auto" data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>">
                        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 group h-full flex flex-col overflow-hidden border border-gray-100 relative transform hover:-translate-y-1">
                            
                            <a href="<?= $targetUrl ?>" <?= $isExternal ? 'target="_blank"' : '' ?> class="block relative h-52 overflow-hidden bg-gray-100">
                                <?php if ($hasImage): ?>
                                    <img src="<?= $imageUrl ?>" 
                                         alt="<?= htmlspecialchars($service['title']) ?>"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                         loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60 group-hover:opacity-40 transition-opacity"></div>
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center relative overflow-hidden"
                                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                                        <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMiIgZmlsbD0iI2ZmZmZmZiIvPjwvc3ZnPg==')]"></div>
                                        <i class="fas fa-concierge-bell text-6xl text-white/80 transform group-hover:scale-110 transition-transform duration-500"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="absolute top-4 right-4">
                                    <?php if ($isExternal): ?>
                                        <span class="bg-white/90 backdrop-blur text-xs font-bold px-3 py-1.5 rounded-full shadow-sm flex items-center gap-1 text-blue-600">
                                            <span>Aplikasi</span> <i class="fas fa-external-link-alt text-[10px]"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-white/90 backdrop-blur text-xs font-bold px-3 py-1.5 rounded-full shadow-sm flex items-center gap-1 text-purple-600">
                                            <span>Informasi</span> <i class="fas fa-info-circle text-[10px]"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <div class="p-6 flex-1 flex flex-col">
                                <h3 class="text-xl font-bold mb-3 line-clamp-2 group-hover:text-blue-600 transition-colors" 
                                    style="color: var(--color-text);">
                                    <?= htmlspecialchars($service['title']) ?>
                                </h3>
                                
                                <p class="text-sm text-gray-500 mb-6 line-clamp-3 flex-1 leading-relaxed">
                                    <?= htmlspecialchars(truncateText(strip_tags($service['description']), 120)) ?>
                                </p>
                                
                                <div class="pt-4 mt-auto border-t border-gray-100 flex justify-between items-center">
                                    <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Lihat Detail</span>
                                    
                                    <a href="<?= $targetUrl ?>" 
                                       <?= $isExternal ? 'target="_blank"' : '' ?>
                                       class="inline-flex items-center text-sm font-bold hover:underline decoration-2 underline-offset-4 transition w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center"
                                       style="color: var(--color-primary);">
                                        <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination !relative !bottom-0 mt-8"></div>
            </div>
            
            <div class="text-center mt-8" data-aos="fade-up">
                <a href="<?= BASE_URL ?>services.php" 
                   class="inline-block bg-transparent border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300"
                   style="border-color: var(--color-primary); color: var(--color-primary);"
                   onmouseover="this.style.backgroundColor=getComputedStyle(document.documentElement).getPropertyValue('--color-primary'); this.style.color='white'"
                   onmouseout="this.style.backgroundColor='transparent'; this.style.color=getComputedStyle(document.documentElement).getPropertyValue('--color-primary')">
                    Lihat Semua Layanan
                </a>
            </div>
            <?php else: ?>
                <div class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-300" data-aos="fade-up">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Belum ada layanan yang ditampilkan saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($albums)): ?>
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--color-text);">Galeri Foto</h2>
                <p class="text-gray-500">Dokumentasi kegiatan dan aktivitas terbaru</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($albums as $index => $album): ?>
                <a href="<?= BASE_URL ?>album.php?slug=<?= $album['slug'] ?>" 
                   class="group relative overflow-hidden rounded-xl shadow-lg aspect-[4/3] cursor-pointer" 
                   data-aos="fade-up" 
                   data-aos-delay="<?= $index * 100 ?>">
                    <img src="<?= get_album_cover($album['cover_image']) ?>" 
                         alt="<?= htmlspecialchars($album['name']) ?>"
                         class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                         loading="lazy">
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>
                    
                    <div class="absolute bottom-0 left-0 right-0 p-6 text-white translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                        <h3 class="text-lg font-bold mb-1 line-clamp-1"><?= htmlspecialchars($album['name']) ?></h3>
                        <div class="flex items-center justify-between text-sm text-gray-300">
                            <span><?= number_format($album['photo_count']) ?> Foto</span>
                            <span class="bg-white/20 p-1.5 rounded-full hover:bg-white/30 transition"><i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-10" data-aos="fade-up">
                <a href="<?= BASE_URL ?>gallery.php" class="text-blue-600 hover:text-blue-800 font-semibold transition" style="color: var(--color-primary);">
                    Lihat Galeri Lainnya <i class="fas fa-long-arrow-alt-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="py-10 px-4">
        <div class="container mx-auto">
            <div class="relative rounded-3xl overflow-hidden shadow-2xl" 
                 data-aos="fade-up" 
                 data-aos-offset="0">
                
                <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);"></div>
                
                <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMiIgZmlsbD0iI2ZmZmZmZiIvPjwvc3ZnPg==')]"></div>

                <div class="relative z-10 p-10 md:p-16 text-center text-white">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">Butuh Informasi Lebih Lanjut?</h2>
                    <p class="text-lg text-white/90 mb-8 max-w-2xl mx-auto leading-relaxed">
                        Jangan ragu untuk menghubungi tim kami. Kami siap membantu menjawab pertanyaan seputar layanan dan informasi pendidikan yang tersedia.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <button onclick="openContactModal()" 
                                class="px-8 py-3.5 bg-white rounded-lg font-bold text-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition duration-300 flex items-center justify-center"
                                style="color: var(--color-primary);">
                            <i class="fas fa-envelope mr-2"></i> Hubungi Kami
                        </button>
                        <a href="<?= BASE_URL ?>contact.php" 
                           class="px-8 py-3.5 bg-transparent border-2 border-white text-white rounded-lg font-bold text-lg hover:bg-white/10 transition duration-300 flex items-center justify-center">
                            <i class="fas fa-map-marker-alt mr-2"></i> Lokasi Kantor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<div id="contact-modal" 
     class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 transition-all duration-300"
     aria-hidden="true">
    
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity opacity-0" 
         id="modal-backdrop"
         onclick="closeContactModal()"></div>
         
    <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh]" 
         id="modal-panel">
        
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-2xl">
            <h3 class="text-xl font-bold flex items-center" style="color: var(--color-text);">
                <span class="w-8 h-8 rounded-full flex items-center justify-center mr-3 text-white text-sm shadow-sm" 
                      style="background: var(--color-primary);">
                    <i class="fas fa-envelope"></i>
                </span>
                Kirim Pesan
            </h3>
            <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto custom-scrollbar">
            <form id="contact-form" class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition outline-none">
                    </div>
                    
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition outline-none">
                    </div>
                    
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">No. Telepon</label>
                        <input type="tel" name="phone" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Subjek</label>
                        <input type="text" name="subject" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Pesan</label>
                        <textarea name="message" rows="4" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition outline-none"></textarea>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="p-6 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl flex justify-end gap-3">
            <button type="button" onclick="closeContactModal()" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-600 font-medium hover:bg-gray-100 transition">
                Batal
            </button>
            <button type="submit" form="contact-form" 
                    class="px-6 py-2.5 rounded-lg text-white font-medium shadow-lg hover:opacity-90 transition transform hover:-translate-y-0.5"
                    style="background: var(--color-primary);">
                <i class="fas fa-paper-plane mr-2"></i> Kirim
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Hero Swiper (BANNER)
    <?php if (!empty($banners)): ?>
    const heroSwiper = new Swiper('.heroSwiper', {
        loop: <?= count($banners) > 1 ? 'true' : 'false' ?>,
        autoplay: {
            delay: 6000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        speed: 1000,
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
    <?php endif; ?>

    // 2. Services Swiper (DIGESER-GESER)
    <?php if (!empty($services)): ?>
    const servicesSwiper = new Swiper('.servicesSwiper', {
        slidesPerView: 1,
        spaceBetween: 20,
        // Free mode agar bisa digeser halus
        grabCursor: true,
        pagination: {
            el: '.servicesSwiper .swiper-pagination',
            clickable: true,
            dynamicBullets: true,
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        }
    });
    <?php endif; ?>
    
    // 3. Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
            easing: 'ease-out-cubic'
        });
    }

    // 4. MODAL LOGIC (Contact Modal)
    const modal = document.getElementById('contact-modal');
    const backdrop = document.getElementById('modal-backdrop');
    const panel = document.getElementById('modal-panel');

    window.openContactModal = function() {
        if(!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('scale-95', 'opacity-0');
            panel.classList.add('scale-100', 'opacity-100');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    window.closeContactModal = function() {
        if(!modal) return;
        backdrop.classList.add('opacity-0');
        panel.classList.remove('scale-100', 'opacity-100');
        panel.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }, 300);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeContactModal();
        }
    });

    // 5. CONTACT FORM SUBMISSION (AJAX)
    const form = document.getElementById('contact-form');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.querySelector('button[type="submit"][form="contact-form"]');
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim...';
            const formData = new FormData(this);

            fetch('<?= BASE_URL ?>api/contact.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.BTIKPKalsel && window.BTIKPKalsel.showNotification) {
                        window.BTIKPKalsel.showNotification(data.message, 'success');
                    } else {
                        alert('Pesan berhasil dikirim!');
                    }
                    form.reset();
                    closeContactModal();
                } else {
                    throw new Error(data.message || 'Gagal mengirim pesan');
                }
            })
            .catch(err => {
                console.error(err);
                if (window.BTIKPKalsel && window.BTIKPKalsel.showNotification) {
                    window.BTIKPKalsel.showNotification(err.message, 'error');
                } else {
                    alert('Gagal mengirim pesan: ' + err.message);
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            });
        });
    }
});
</script>

<?php include 'templates/footer.php'; ?>