<?php
/**
 * Public Header Template
 * Includes: Meta tags, Navigation with Static Pages, Barba.js wrapper
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

// Get static pages for navigation
try {
    $pageModel = new Page();
    $staticPages = $pageModel->getAllPages();
    // Filter only published pages
    $staticPages = array_filter($staticPages, function($p) {
        return $p['status'] === 'published';
    });
    // Sort by display_order
    usort($staticPages, function($a, $b) {
        return ($a['display_order'] ?? 999) - ($b['display_order'] ?? 999);
    });
} catch (Exception $e) {
    $staticPages = [];
    error_log('Error loading static pages: ' . $e->getMessage());
}

// SEO defaults
$page_title = $pageTitle ?? $site_name;
$page_description = $pageDescription ?? $site_tagline;
$page_keywords = $pageKeywords ?? getSetting('site_keywords', 'btikp, kalsel, pendidikan, teknologi');
$page_image = $pageImage ?? $site_logo;
$page_url = $pageUrl ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <meta name="author" content="<?= htmlspecialchars($site_name) ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($page_url) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($page_image) ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($page_url) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="twitter:image" content="<?= htmlspecialchars($page_image) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= $site_favicon ?>">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- AOS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Line clamp utilities */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Dropdown menu */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            min-width: 200px;
            z-index: 100;
        }
        
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .dropdown-menu a:hover {
            background: #f3f4f6;
            color: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    
    <!-- Header / Navigation -->
    <header class="bg-blue-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <!-- Top Bar (Contact Info) -->
            <div class="hidden md:flex justify-between items-center py-2 text-sm border-b border-blue-800">
                <div class="flex items-center space-x-4">
                    <span><i class="fas fa-envelope mr-1"></i> <?= getSetting('contact_email', 'info@btikpkalsel.id') ?></span>
                    <span><i class="fas fa-phone mr-1"></i> <?= getSetting('contact_phone', '(0511) 1234567') ?></span>
                </div>
                <div class="flex items-center space-x-3">
                    <?php foreach ($social_media as $platform => $url): ?>
                        <?php if (!empty($url)): ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="hover:text-blue-300 transition" title="<?= ucfirst($platform) ?>">
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
            
            <!-- Main Navigation -->
            <nav class="py-4">
                <div class="flex justify-between items-center">
                    <!-- Logo -->
                    <a href="<?= BASE_URL ?>" class="flex items-center space-x-3">
                        <img src="<?= $site_logo ?>" alt="<?= $site_name ?>" class="h-12 w-auto">
                        <div class="hidden lg:block">
                            <div class="text-xl font-bold"><?= $site_name ?></div>
                            <div class="text-xs text-blue-200"><?= $site_tagline ?></div>
                        </div>
                    </a>
                    
                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="<?= BASE_URL ?>" class="hover:text-blue-300 transition">
                            <i class="fas fa-home mr-1"></i> Beranda
                        </a>
                        
                        <!-- Static Pages Dropdown -->
                        <?php if (!empty($staticPages)): ?>
                        <div class="dropdown">
                            <a href="#" class="hover:text-blue-300 transition flex items-center">
                                <i class="fas fa-info-circle mr-1"></i> Tentang
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <div class="dropdown-menu">
                                <?php foreach ($staticPages as $staticPage): ?>
                                <a href="<?= BASE_URL ?>page.php?slug=<?= $staticPage['slug'] ?>">
                                    <?= htmlspecialchars($staticPage['title']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <a href="<?= BASE_URL ?>posts.php" class="hover:text-blue-300 transition">
                            <i class="fas fa-newspaper mr-1"></i> Berita
                        </a>
                        <a href="<?= BASE_URL ?>gallery.php" class="hover:text-blue-300 transition">
                            <i class="fas fa-images mr-1"></i> Galeri
                        </a>
                        <a href="<?= BASE_URL ?>services.php" class="hover:text-blue-300 transition">
                            <i class="fas fa-cogs mr-1"></i> Layanan
                        </a>
                        <a href="<?= BASE_URL ?>files.php" class="hover:text-blue-300 transition">
                            <i class="fas fa-download mr-1"></i> Unduhan
                        </a>
                        <a href="<?= BASE_URL ?>contact.php" class="hover:text-blue-300 transition">
                            <i class="fas fa-envelope mr-1"></i> Kontak
                        </a>
                        
                        <!-- Search Button -->
                        <button onclick="openSearchModal()" class="hover:text-blue-300 transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <button onclick="toggleMobileMenu()" class="md:hidden text-white">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Mobile Menu (Hidden by default) -->
    <div id="mobileMenu" class="hidden md:hidden bg-blue-800 text-white">
        <div class="container mx-auto px-4 py-4 space-y-2">
            <a href="<?= BASE_URL ?>" class="block py-2 hover:text-blue-300 transition">
                <i class="fas fa-home mr-2"></i> Beranda
            </a>
            
            <!-- Static Pages in Mobile -->
            <?php if (!empty($staticPages)): ?>
            <div class="border-t border-blue-700 pt-2 mt-2">
                <p class="text-xs text-blue-200 mb-2 uppercase font-semibold">Tentang Kami</p>
                <?php foreach ($staticPages as $staticPage): ?>
                <a href="<?= BASE_URL ?>page.php?slug=<?= $staticPage['slug'] ?>" class="block py-2 pl-4 hover:text-blue-300 transition">
                    <?= htmlspecialchars($staticPage['title']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <a href="<?= BASE_URL ?>posts.php" class="block py-2 hover:text-blue-300 transition">
                <i class="fas fa-newspaper mr-2"></i> Berita
            </a>
            <a href="<?= BASE_URL ?>gallery.php" class="block py-2 hover:text-blue-300 transition">
                <i class="fas fa-images mr-2"></i> Galeri
            </a>
            <a href="<?= BASE_URL ?>services.php" class="block py-2 hover:text-blue-300 transition">
                <i class="fas fa-cogs mr-2"></i> Layanan
            </a>
            <a href="<?= BASE_URL ?>files.php" class="block py-2 hover:text-blue-300 transition">
                <i class="fas fa-download mr-2"></i> Unduhan
            </a>
            <a href="<?= BASE_URL ?>contact.php" class="block py-2 hover:text-blue-300 transition">
                <i class="fas fa-envelope mr-2"></i> Kontak
            </a>
            
            <!-- Social Media di Mobile -->
            <div class="pt-4 mt-4 border-t border-blue-700">
                <p class="text-xs text-blue-200 mb-2">Ikuti Kami:</p>
                <div class="flex space-x-3">
                    <?php foreach ($social_media as $platform => $url): ?>
                        <?php if (!empty($url)): ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="hover:text-blue-300 transition">
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
    
    <!-- Main Content Container -->
    <div data-barba="container" data-barba-namespace="<?= $pageNamespace ?? 'default' ?>">
