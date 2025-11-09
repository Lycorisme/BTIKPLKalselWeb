<?php
/**
 * Homepage - BTIKP Kalimantan Selatan
 * Features:
 * - Banner slider dari admin
 * - Recent posts (6 latest)
 * - Featured services
 * - Site statistics
 * - Latest gallery albums
 */

require_once 'config.php';

// Page namespace untuk Barba.js
$pageNamespace = 'home';

// SEO Meta
$pageTitle = getSetting('site_name', 'BTIKP Kalimantan Selatan') . ' - ' . getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');
$pageDescription = getSetting('site_description', 'Portal resmi Balai Teknologi Informasi dan Komunikasi Pendidikan Kalimantan Selatan');
$pageKeywords = getSetting('site_keywords', 'btikp, kalsel, pendidikan, teknologi, informasi');
$pageImage = get_site_logo();

// Get active banners (FIXED: column name is 'ordering', not 'display_order')
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

// Get featured services (4 services) - FIXED: no 'display_order', use created_at
$stmt = $db->query("
    SELECT * FROM services 
    WHERE status = 'published' AND deleted_at IS NULL 
    ORDER BY created_at DESC 
    LIMIT 4
");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get latest gallery albums (4 albums) - FIXED: column name is 'display_order' with underscore
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

// Get site statistics
$stats = get_site_stats();

// Include header
include 'templates/header.php';
?>

<!-- Hero Section - Banner Slider -->
<?php if (!empty($banners)): ?>
<section class="relative">
    <div class="swiper heroSwiper">
        <div class="swiper-wrapper">
            <?php foreach ($banners as $banner): ?>
            <div class="swiper-slide">
                <div class="relative h-[500px] md:h-[600px] bg-cover bg-center" 
                     style="background-image: url('<?= get_banner_image($banner['image_path']) ?>');">
                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-40"></div>
                    
                    <!-- Content -->
                    <div class="relative container mx-auto px-4 h-full flex items-center">
                        <div class="max-w-2xl text-white" data-aos="fade-up">
                            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4">
                                <?= htmlspecialchars($banner['title']) ?>
                            </h1>
                            <?php if (!empty($banner['description'])): ?>
                            <p class="text-lg md:text-xl mb-6 text-gray-200">
                                <?= htmlspecialchars($banner['description']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($banner['button_text']) && !empty($banner['link_url'])): ?>
                            <a href="<?= htmlspecialchars($banner['link_url']) ?>" 
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition transform hover:scale-105">
                                <?= htmlspecialchars($banner['button_text']) ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Navigation -->
        <?php if (count($banners) > 1): ?>
        <div class="swiper-button-next text-white"></div>
        <div class="swiper-button-prev text-white"></div>
        <div class="swiper-pagination"></div>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<!-- Default Hero (no banner) -->
<section class="relative h-[500px] md:h-[600px] bg-gradient-to-r from-blue-900 to-blue-700 flex items-center">
    <div class="container mx-auto px-4 text-center text-white">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4">Selamat Datang di BTIKP Kalimantan Selatan</h1>
        <p class="text-xl mb-8"><?= htmlspecialchars(getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan')) ?></p>
        <a href="<?= BASE_URL ?>contact.php" class="inline-block bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
            Hubungi Kami
        </a>
    </div>
</section>
<?php endif; ?>

<!-- Statistics Section -->
<section class="py-12 bg-blue-900 text-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div data-aos="fade-up" data-aos-delay="0">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?= number_format($stats['posts']) ?>+</div>
                <div class="text-blue-200">Berita & Artikel</div>
            </div>
            <div data-aos="fade-up" data-aos-delay="100">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?= number_format($stats['photos']) ?>+</div>
                <div class="text-blue-200">Foto Galeri</div>
            </div>
            <div data-aos="fade-up" data-aos-delay="200">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?= number_format($stats['files']) ?>+</div>
                <div class="text-blue-200">File Unduhan</div>
            </div>
            <div data-aos="fade-up" data-aos-delay="300">
                <div class="text-4xl md:text-5xl font-bold mb-2"><?= number_format($stats['services']) ?>+</div>
                <div class="text-blue-200">Layanan</div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Posts Section -->
<?php if (!empty($recent_posts)): ?>
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Berita Terbaru</h2>
            <p class="text-gray-600">Informasi dan berita terkini dari BTIKP Kalimantan Selatan</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($recent_posts as $index => $post): ?>
            <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300" 
                     data-aos="fade-up" 
                     data-aos-delay="<?= $index * 100 ?>">
                <!-- Featured Image -->
                <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="block overflow-hidden">
                    <img src="<?= get_featured_image($post['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($post['title']) ?>"
                         class="w-full h-48 object-cover transform hover:scale-110 transition-transform duration-300">
                </a>
                
                <!-- Content -->
                <div class="p-6">
                    <!-- Category -->
                    <?php if (!empty($post['category_name'])): ?>
                    <a href="<?= BASE_URL ?>category.php?slug=<?= $post['category_slug'] ?>" 
                       class="inline-block bg-blue-100 text-blue-600 text-xs px-3 py-1 rounded-full mb-3 hover:bg-blue-200 transition">
                        <?= htmlspecialchars($post['category_name']) ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Title -->
                    <h3 class="text-xl font-bold mb-2">
                        <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" 
                           class="text-gray-900 hover:text-blue-600 transition">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h3>
                    
                    <!-- Excerpt -->
                    <p class="text-gray-600 mb-4 line-clamp-3">
                        <?= truncateText($post['excerpt'], 120) ?>
                    </p>
                    
                    <!-- Meta -->
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <?= formatTanggal($post['created_at'], 'd M Y') ?>
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <?= number_format($post['view_count']) ?>
                        </span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <!-- View All Button -->
        <div class="text-center mt-12" data-aos="fade-up">
            <a href="<?= BASE_URL ?>posts.php" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition">
                Lihat Semua Berita
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Services Section -->
<?php if (!empty($services)): ?>
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Layanan Kami</h2>
            <p class="text-gray-600">Berbagai layanan yang kami tawarkan untuk kemajuan pendidikan</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($services as $index => $service): ?>
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-xl transition-shadow duration-300 text-center" 
                 data-aos="fade-up" 
                 data-aos-delay="<?= $index * 100 ?>">
                <!-- Icon/Image -->
                <?php if (!empty($service['image_path'])): ?>
                <img src="<?= get_service_image($service['image_path']) ?>" 
                     alt="<?= htmlspecialchars($service['title']) ?>"
                     class="w-20 h-20 mx-auto mb-4 object-contain">
                <?php endif; ?>
                
                <!-- Title -->
                <h3 class="text-xl font-bold mb-3 text-gray-900">
                    <?= htmlspecialchars($service['title']) ?>
                </h3>
                
                <!-- Description -->
                <p class="text-gray-600 mb-4 line-clamp-3">
                    <?= truncateText($service['description'], 100) ?>
                </p>
                
                <!-- Read More -->
                <a href="<?= BASE_URL ?>service.php?slug=<?= $service['slug'] ?>" 
                   class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold transition">
                    Selengkapnya
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- View All Button -->
        <div class="text-center mt-12" data-aos="fade-up">
            <a href="<?= BASE_URL ?>services.php" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition">
                Lihat Semua Layanan
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Gallery Section -->
<?php if (!empty($albums)): ?>
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Galeri Foto</h2>
            <p class="text-gray-600">Dokumentasi kegiatan dan aktivitas BTIKP Kalimantan Selatan</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($albums as $index => $album): ?>
            <a href="<?= BASE_URL ?>album.php?slug=<?= $album['slug'] ?>" 
               class="group relative overflow-hidden rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300" 
               data-aos="fade-up" 
               data-aos-delay="<?= $index * 100 ?>">
                <!-- Cover Image -->
                <img src="<?= get_album_cover($album['cover_image']) ?>" 
                     alt="<?= htmlspecialchars($album['name']) ?>"
                     class="w-full h-64 object-cover transform group-hover:scale-110 transition-transform duration-300">
                
                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent opacity-70 group-hover:opacity-80 transition-opacity"></div>
                
                <!-- Info -->
                <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                    <h3 class="text-lg font-bold mb-1"><?= htmlspecialchars($album['name']) ?></h3>
                    <p class="text-sm text-gray-300">
                        <?= number_format($album['photo_count']) ?> Foto
                    </p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- View All Button -->
        <div class="text-center mt-12" data-aos="fade-up">
            <a href="<?= BASE_URL ?>gallery.php" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition">
                Lihat Semua Galeri
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="py-16 bg-blue-900 text-white">
    <div class="container mx-auto px-4 text-center" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Butuh Informasi Lebih Lanjut?</h2>
        <p class="text-xl text-blue-200 mb-8">Hubungi kami untuk pertanyaan atau konsultasi</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="<?= BASE_URL ?>contact.php" 
               class="inline-block bg-white text-blue-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                Hubungi Kami
            </a>
            <a href="<?= BASE_URL ?>files.php" 
               class="inline-block bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 transition">
                Download Dokumen
            </a>
        </div>
    </div>
</section>

<!-- Initialize Swiper & AOS -->
<script>
    // Hero Swiper
    const heroSwiper = new Swiper('.heroSwiper', {
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
    });
    
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
</script>

<?php
// Include footer
include 'templates/footer.php';
?>
