<?php
/**
 * Public Header Template (Full Animation Version)
 * Includes: Meta tags, Navigation with Static Pages, AOS Animations, and ANIMATED SEARCH MODAL
 */

// Get site settings
$site_name = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$site_tagline = getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');
$site_logo = get_site_logo();
$site_favicon = get_site_favicon();

// Get all social media links
$social_media = [
    'facebook' => getSetting('social_facebook'),
    'twitter' => getSetting('social_twitter'),
    'instagram' => getSetting('social_instagram'),
    'youtube' => getSetting('social_youtube'),
    'linkedin' => getSetting('social_linkedin'),
    'tiktok' => getSetting('social_tiktok'),
];

// Get static pages for navigation (Menu 'Profil')
try {
    $pageModel = new Page();
    $staticPages = $pageModel->getAllPages();
    $staticPages = array_filter($staticPages, function($p) {
        return $p['status'] === 'published';
    });
    usort($staticPages, function($a, $b) {
        return ($a['display_order'] ?? 999) - ($b['display_order'] ?? 999);
    });
} catch (Exception $e) {
    $staticPages = [];
    error_log('Error loading static pages: ' . $e->getMessage());
}

// Get post categories for navigation (Menu 'Berita')
try {
    // KOREKSI ERROR: Menggunakan method getActive() yang tersedia di class PostCategory
    $categoryModel = new PostCategory();
    $categories = $categoryModel->getActive(); 
    // Filter/sorting jika diperlukan
} catch (Exception $e) {
    $categories = [];
    error_log('Error loading post categories: ' . $e->getMessage());
}

// SEO defaults
$page_title = $pageTitle ?? $site_name;
$page_description = $pageDescription ?? $site_tagline;
$page_keywords = $pageKeywords ?? getSetting('site_keywords', 'btikp, kalsel, pendidikan, teknologi');
$page_image = $pageImage ?? $site_logo;
$page_url = $pageUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Get theme colors from settings
$primaryColor = getSetting('public_theme_primary_color', '#667eea');
$secondaryColor = getSetting('public_theme_secondary_color', '#764ba2');
$accentColor = getSetting('public_theme_accent_color', '#f093fb');
$textColor = getSetting('public_theme_text_color', '#333333');
$bgColor = getSetting('public_theme_background_color', '#ffffff');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>" />
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>" />
    <meta name="author" content="<?= htmlspecialchars($site_name) ?>" />
    
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= htmlspecialchars($page_url) ?>" />
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>" />
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>" />
    <meta property="og:image" content="<?= htmlspecialchars($page_image) ?>" />
    
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="<?= htmlspecialchars($page_url) ?>" />
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>" />
    <meta property="twitter:description" content="<?= htmlspecialchars($page_description) ?>" />
    <meta property="twitter:image" content="<?= htmlspecialchars($page_image) ?>" />
    
    <link rel="icon" type="image/x-icon" href="<?= $site_favicon ?>" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?= $primaryColor ?>',
                        secondary: '<?= $secondaryColor ?>',
                        accent: '<?= $accentColor ?>',
                    }
                }
            }
        }
    </script>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/custom.css?v=1.0" />
    
    <style>
        :root {
            --color-primary: <?= $primaryColor ?>;
            --color-secondary: <?= $secondaryColor ?>;
            --color-accent: <?= $accentColor ?>;
            --color-text: <?= $textColor ?>;
            --color-background: <?= $bgColor ?>;
        }
        
        /* Mobile Menu Animation */
        #mobileMenu {
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            visibility: hidden; /* Hide completely when closed */
        }
        /* MENGGUNAKAN CLASS 'open' DARI JS UNTUK TRANSISI */
        #mobileMenu.open { 
            opacity: 1;
            visibility: visible;
        }
        
        /* Nav Link Hover Animation */
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: white;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }

        /* Search Modal Overlay */
        .search-modal-overlay.hidden {
            display: none;
        }
        .search-modal-overlay {
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .search-modal-overlay.active {
            opacity: 1;
        }

        /* Search Modal Panel */
        .search-modal-panel {
            transform: translateY(-20px) scale(0.95);
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .search-modal-overlay.active .search-modal-panel {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    </style>
    
</head>
<body class="bg-gray-50 font-sans antialiased">

    <?php if (isset($_SESSION['maintenance_mode_active']) && $_SESSION['maintenance_mode_active']): ?>
    <div class="bg-yellow-500 text-white px-4 py-2 text-center font-semibold animate-pulse">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Mode Maintenance Aktif - Pengunjung tidak dapat mengakses website
    </div>
    <?php endif; ?>

    <header class="text-white shadow-lg sticky top-0 z-50 backdrop-blur-sm bg-opacity-95" 
            style="background-color: var(--color-primary);">
        
        <div class="container mx-auto px-4">
            
            <div class="hidden md:flex justify-between items-center py-2 text-sm" 
                 style="border-bottom: 1px solid rgba(255,255,255,0.2);"
                 data-aos="fade-down" 
                 data-aos-duration="600"
                 data-aos-once="true">
                <div class="flex items-center space-x-4">
                    <span class="hover:text-blue-200 transition"><i class="fas fa-envelope mr-1"></i> <?= getSetting('contact_email', 'info@btikpkalsel.id') ?></span>
                    <span class="hover:text-blue-200 transition"><i class="fas fa-phone mr-1"></i> <?= getSetting('contact_phone', '(0511) 1234567') ?></span>
                </div>
                <div class="flex items-center space-x-3">
                    <?php 
                    $social_delay = 0;
                    foreach ($social_media as $platform => $url): 
                        if (!empty($url)): 
                            $social_delay += 50;
                    ?>
                            <a href="<?= htmlspecialchars($url) ?>" 
                               target="_blank" 
                               rel="noopener" 
                               class="hover:text-blue-300 transition transform hover:scale-110 inline-block" 
                               title="<?= ucfirst($platform) ?>"
                               data-aos="fade-left"
                               data-aos-delay="<?= $social_delay ?>"
                               data-aos-duration="400"
                               data-aos-once="true">
                                <?php
                                $icons = [
                                    'facebook' => 'fab fa-facebook',
                                    'twitter' => 'fab fa-twitter',
                                    'instagram' => 'fab fa-instagram',
                                    'youtube' => 'fab fa-youtube',
                                    'linkedin' => 'fab fa-linkedin',
                                    'tiktok' => 'fab fa-tiktok',
                                ];
                                ?>
                                <i class="<?= $icons[$platform] ?? 'fas fa-link' ?> text-lg"></i>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <nav class="py-4">
                <div class="flex justify-between items-center">
                    
                    <a href="<?= BASE_URL ?>" class="flex items-center space-x-3 group"
                       data-aos="fade-right" 
                       data-aos-duration="800"
                       data-aos-once="true">
                        <img src="<?= $site_logo ?>" alt="<?= $site_name ?>" class="h-12 w-auto transform group-hover:scale-105 transition duration-300" />
                        <div class="lg:block hidden">
                            <div class="text-xl font-bold tracking-wide"><?= $site_name ?></div>
                            <div class="text-xs text-blue-100 opacity-90"><?= $site_tagline ?></div>
                        </div>
                        <div class="md:hidden">
                            <div class="text-base font-semibold tracking-wide leading-none"><?= $site_name ?></div>
                            <div class="text-xs text-blue-100 opacity-80 leading-none"><?= $site_tagline ?></div>
                        </div>
                    </a>

                    <div class="hidden md:flex items-center space-x-6">
                        
                        <a href="<?= BASE_URL ?>" class="nav-link hover:text-blue-200 transition font-medium"
                           data-aos="fade-down" data-aos-delay="100" data-aos-once="true">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>

                        <?php if (!empty($staticPages)): ?>
                        <div class="dropdown" data-aos="fade-down" data-aos-delay="200" data-aos-once="true">
                            <a class="nav-link hover:text-blue-200 transition flex items-center font-medium cursor-pointer">
                                <i class="fas fa-file-alt mr-1"></i> Profil
                                <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-300"></i>
                            </a>
                            <div class="dropdown-menu transform origin-top-left" style="border-top: 4px solid var(--color-secondary);">
                                <?php foreach ($staticPages as $staticPage): ?>
                                <a href="<?= BASE_URL ?>page.php?slug=<?= $staticPage['slug'] ?>" class="hover:pl-4 transition-all">
                                    <?= htmlspecialchars($staticPage['title']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="dropdown" data-aos="fade-down" data-aos-delay="300" data-aos-once="true">
                            <a class="nav-link hover:text-blue-200 transition flex items-center font-medium cursor-pointer">
                                <i class="fas fa-newspaper mr-1"></i> Berita
                                <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-300"></i>
                            </a>
                            <div class="dropdown-menu transform origin-top-left" style="border-top: 4px solid var(--color-secondary);">
                                <a href="<?= BASE_URL ?>posts.php" class="hover:pl-4 transition-all font-semibold">
                                    Semua Berita
                                </a>
                                <?php if (!empty($categories)): ?>
                                <div class="py-1 border-t border-gray-100 mt-1"></div>
                                <div class="px-4 py-2 text-xs uppercase font-bold text-gray-400">Kategori</div>
                                    <?php foreach ($categories as $cat): ?>
                                    <a href="<?= BASE_URL ?>category.php?slug=<?= $cat['slug'] ?>" class="hover:pl-4 transition-all text-sm">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>services.php" class="nav-link hover:text-blue-200 transition font-medium"
                           data-aos="fade-down" data-aos-delay="400" data-aos-once="true">
                            <i class="fas fa-cogs mr-1"></i> Layanan
                        </a>
                        
                        <a href="<?= BASE_URL ?>gallery.php" class="nav-link hover:text-blue-200 transition font-medium"
                           data-aos="fade-down" data-aos-delay="500" data-aos-once="true">
                            <i class="fas fa-images mr-1"></i> Galeri
                        </a>
                        
                        <a href="<?= BASE_URL ?>files.php" class="nav-link hover:text-blue-200 transition font-medium"
                           data-aos="fade-down" data-aos-delay="600" data-aos-once="true">
                            <i class="fas fa-download mr-1"></i> Unduhan
                        </a>
                        
                        <a href="<?= BASE_URL ?>contact.php" class="nav-link hover:text-blue-200 transition font-medium"
                           data-aos="fade-down" data-aos-delay="700" data-aos-once="true">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>

                        <button onclick="window.BTIKPKalsel.openSearchModal()" class="hover:text-blue-200 transition transform hover:scale-110 bg-white/10 p-2 rounded-full w-10 h-10 flex items-center justify-center"
                                data-aos="zoom-in" data-aos-delay="800" data-aos-once="true">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="md:hidden flex items-center space-x-3">
                        <button onclick="window.BTIKPKalsel.openSearchModal()" class="hover:text-blue-200 transition transform hover:scale-110 bg-white/10 p-2 rounded-full w-9 h-9 flex items-center justify-center">
                            <i class="fas fa-search text-lg"></i>
                        </button>
                        <button id="mobileMenuBtn" class="text-white focus:outline-none bg-white/10 p-2 rounded-full w-9 h-9 flex items-center justify-center">
                            <i class="fas fa-bars text-xl transition-transform duration-300" id="menuIcon"></i>
                        </button>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <div id="mobileMenu" class="md:hidden text-white shadow-inner" style="background-color: var(--color-secondary);">
        <div class="container mx-auto px-4 py-4 space-y-2">
            
            <a href="<?= BASE_URL ?>" class="block py-3 border-b border-white/10 hover:bg-white/10 px-2 rounded transition">
                <i class="fas fa-home mr-3 w-5 text-center"></i> Beranda
            </a>

            <div class="border-b border-white/10 dropdown"> 
                <a class="nav-link text-white block py-3 hover:bg-white/10 px-2 rounded transition flex justify-between items-center font-medium">
                    <span><i class="fas fa-file-alt mr-3 w-5 text-center"></i> Profil / Halaman</span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300"></i>
                </a>
                
                <div class="dropdown-menu"> 
                    <?php if (!empty($staticPages)): ?>
                    <?php foreach ($staticPages as $staticPage): ?>
                    <a href="<?= BASE_URL ?>page.php?slug=<?= $staticPage['slug'] ?>" class="block py-2 pl-8 hover:bg-white/10 rounded transition text-sm">
                        <?= htmlspecialchars($staticPage['title']) ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="border-b border-white/10 dropdown">
                <a class="nav-link text-white block py-3 hover:bg-white/10 px-2 rounded transition flex justify-between items-center font-medium">
                    <span><i class="fas fa-newspaper mr-3 w-5 text-center"></i> Berita & Artikel</span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300"></i>
                </a>
                
                <div class="dropdown-menu"> 
                    <a href="<?= BASE_URL ?>posts.php" class="block py-2 pl-8 hover:bg-white/10 rounded transition text-sm font-bold">
                        Semua Berita
                    </a>
                    <?php if (!empty($categories)): ?>
                        <p class="text-xs text-blue-200 mt-2 mb-1 px-2 uppercase opacity-70 pl-8">Kategori</p>
                        <?php foreach ($categories as $cat): ?>
                        <a href="<?= BASE_URL ?>category.php?slug=<?= $cat['slug'] ?>" class="block py-1 pl-12 hover:bg-white/10 rounded transition text-xs">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?= BASE_URL ?>services.php" class="block py-3 border-b border-white/10 hover:bg-white/10 px-2 rounded transition">
                <i class="fas fa-cogs mr-3 w-5 text-center"></i> Layanan
            </a>
            
            <a href="<?= BASE_URL ?>gallery.php" class="block py-3 border-b border-white/10 hover:bg-white/10 px-2 rounded transition">
                <i class="fas fa-images mr-3 w-5 text-center"></i> Galeri
            </a>
            
            <a href="<?= BASE_URL ?>files.php" class="block py-3 border-b border-white/10 hover:bg-white/10 px-2 rounded transition">
                <i class="fas fa-download mr-3 w-5 text-center"></i> Unduhan
            </a>
            
            <a href="<?= BASE_URL ?>contact.php" class="block py-3 border-b border-white/10 hover:bg-white/10 px-2 rounded transition">
                <i class="fas fa-envelope mr-3 w-5 text-center"></i> Kontak
            </a>
            
            <div class="pt-6 pb-2 text-center">
                <p class="text-xs text-blue-200 mb-3 uppercase tracking-widest">Ikuti Kami</p>
                <div class="flex justify-center space-x-6">
                    <?php foreach ($social_media as $platform => $url): ?>
                        <?php if (!empty($url)): ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="hover:text-blue-300 transition transform hover:scale-125">
                                <?php
                                $icons = [
                                    'facebook' => 'fab fa-facebook',
                                    'twitter' => 'fab fa-twitter',
                                    'instagram' => 'fab fa-instagram',
                                    'youtube' => 'fab fa-youtube',
                                    'linkedin' => 'fab fa-linkedin',
                                    'tiktok' => 'fab fa-tiktok',
                                ];
                                ?>
                                <i class="<?= $icons[$platform] ?? 'fas fa-link' ?> text-xl"></i>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="searchModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 search-modal-overlay hidden" aria-hidden="true">
        <div class="relative bg-white w-full max-w-lg md:max-w-xl rounded-xl shadow-2xl flex flex-col search-modal-panel" role="dialog" aria-modal="true" aria-labelledby="searchModalTitle">
            <div class="p-4 md:p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h3 id="searchModalTitle" class="text-lg md:text-xl font-bold flex items-center" style="color: var(--color-text);">
                    <span class="w-7 h-7 md:w-8 md:h-8 rounded-full flex items-center justify-center mr-2 text-white text-sm shadow-sm" 
                          style="background: var(--color-primary);">
                        <i class="fas fa-search"></i>
                    </span>
                    Pencarian
                </h3>
                <button onclick="window.BTIKPKalsel.closeSearchModal()" class="text-gray-400 hover:text-gray-600 w-7 h-7 md:w-8 md:h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <form id="searchForm" action="<?= BASE_URL ?>search.php" method="GET" class="p-4 md:p-6">
                <div class="relative mb-4">
                    <input type="text" name="q" placeholder="Cari berita, layanan, atau halaman..." 
                           class="w-full pl-10 pr-4 py-2 md:pl-12 md:pr-4 md:py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring focus:ring-blue-200 text-base md:text-lg transition"
                           style="color: var(--color-text);">
                    <i class="fas fa-search absolute left-3 md:left-4 top-1/2 -translate-y-1/2 text-gray-400 text-base md:text-lg"></i>
                </div>
                <button type="submit" 
                        class="w-full px-6 py-2 md:py-3 text-white rounded-lg font-medium shadow-md hover:shadow-lg hover:-translate-y-0.5 transition"
                        style="background: var(--color-primary);">
                    <i class="fas fa-search mr-2"></i> Cari
                </button>
            </form>
        </div>
    </div>