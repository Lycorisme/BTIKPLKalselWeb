<?php
/**
 * Maintenance Mode Page
 * Displayed when site is under maintenance
 */

// Start session
session_start();

// Include core configuration
require_once __DIR__ . '/../config/config.php';

// Include core classes
require_once ROOT_PATH . 'core/Database.php';
require_once ROOT_PATH . 'core/Helper.php';

// Include public functions
require_once __DIR__ . '/functions.php';

// Initialize database connection
$db = Database::getInstance()->getConnection();

// ===================================================================
// CHECK IF MAINTENANCE MODE IS ACTUALLY ACTIVE
// ===================================================================

$maintenanceMode = getSetting('site_maintenance_mode', '0');

// If maintenance mode is OFF, redirect to homepage
if ($maintenanceMode !== '1') {
    header('Location: ' . BASE_URL);
    exit;
}

// If user is admin, redirect to homepage (admins can access during maintenance)
$isAdmin = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $isAdmin = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
}

if ($isAdmin) {
    header('Location: ' . BASE_URL);
    exit;
}

// ===================================================================
// SET HTTP HEADERS
// ===================================================================

// Set HTTP 503 Service Unavailable header
http_response_code(503);
header('Retry-After: 3600'); // Retry after 1 hour

// ===================================================================
// GET ALL DYNAMIC SETTINGS
// ===================================================================

// Site Information
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteTagline = getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');
$siteLogo = get_site_logo();
$siteFavicon = get_site_favicon();

// Maintenance Message
$maintenanceMessage = getSetting('site_maintenance_message', 'Website sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi.');

// Copyright
$copyrightText = getSetting('site_copyright', '&copy; {year} BTIKP Kalimantan Selatan. All Rights Reserved.');
$copyrightText = str_replace('{year}', date('Y'), $copyrightText);

// Contact Information
$contactEmail = getSetting('contact_email', '');
$contactPhone = getSetting('contact_phone', '');

// Social Media Links
$socialFacebook = getSetting('social_facebook', '');
$socialInstagram = getSetting('social_instagram', '');
$socialTwitter = getSetting('social_twitter', '');
$socialYoutube = getSetting('social_youtube', '');
$socialLinkedin = getSetting('social_linkedin', '');
$socialTiktok = getSetting('social_tiktok', '');

// Theme Colors
$primaryColor = getSetting('public_theme_primary_color', '#1e40af');
$secondaryColor = getSetting('public_theme_secondary_color', '#3b82f6');
$accentColor = getSetting('public_theme_accent_color', '#60a5fa');
$textColor = getSetting('public_theme_text_color', '#333333');
$bgColor = getSetting('public_theme_background_color', '#ffffff');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- SEO Meta Tags -->
    <title>Maintenance Mode - <?= htmlspecialchars($siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($maintenanceMessage) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($siteFavicon) ?>">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --color-primary: <?= $primaryColor ?>;
            --color-secondary: <?= $secondaryColor ?>;
            --color-accent: <?= $accentColor ?>;
            --color-text: <?= $textColor ?>;
            --color-bg: <?= $bgColor ?>;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .maintenance-bg {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 50%, var(--color-accent) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .maintenance-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .rotate-animation {
            animation: rotate 20s linear infinite;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .social-icon {
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .contact-link {
            transition: all 0.3s ease;
        }
        
        .contact-link:hover {
            transform: translateX(5px);
        }
    </style>
</head>
<body class="maintenance-bg min-h-screen flex items-center justify-center p-4">
    
    <!-- Main Content -->
    <div class="w-full max-w-4xl relative z-10">
        
        <!-- Logo Section -->
        <div class="text-center mb-8 float-animation fade-in">
            <img src="<?= htmlspecialchars($siteLogo) ?>" 
                 alt="<?= htmlspecialchars($siteName) ?>" 
                 class="h-24 w-auto mx-auto mb-4 drop-shadow-2xl">
            <h1 class="text-white text-2xl md:text-3xl font-bold drop-shadow-lg">
                <?= htmlspecialchars($siteName) ?>
            </h1>
            <p class="text-white/90 text-sm md:text-base mt-2 drop-shadow">
                <?= htmlspecialchars($siteTagline) ?>
            </p>
        </div>
        
        <!-- Maintenance Card -->
        <div class="glass-effect rounded-2xl shadow-2xl p-6 md:p-12 fade-in" style="animation-delay: 0.2s;">
            
            <!-- Icon -->
            <div class="text-center mb-8">
                <div class="inline-block relative">
                    <div class="absolute inset-0 rounded-full blur-2xl opacity-50" 
                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-accent));"></div>
                    <svg class="w-24 h-24 md:w-32 md:h-32 mx-auto relative rotate-animation" 
                         fill="none" 
                         stroke="currentColor" 
                         viewBox="0 0 24 24"
                         style="color: var(--color-primary);">
                        <path stroke-linecap="round" 
                              stroke-linejoin="round" 
                              stroke-width="1.5" 
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" 
                              stroke-linejoin="round" 
                              stroke-width="1.5" 
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            
            <!-- Heading -->
            <h2 class="text-2xl md:text-4xl font-bold text-center mb-4" 
                style="color: var(--color-primary);">
                Website Dalam Pemeliharaan
            </h2>
            
            <!-- Message -->
            <div class="text-center mb-8">
                <p class="text-gray-700 text-base md:text-lg leading-relaxed max-w-2xl mx-auto">
                    <?= nl2br(htmlspecialchars($maintenanceMessage)) ?>
                </p>
            </div>
            
            <!-- Divider -->
            <div class="border-t border-gray-200 my-8"></div>
            
            <!-- Contact Information -->
            <?php if (!empty($contactEmail) || !empty($contactPhone)): ?>
            <div class="text-center mb-8">
                <p class="text-gray-600 text-sm mb-4 font-semibold">
                    Jika memerlukan bantuan, hubungi kami:
                </p>
                <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-4 md:gap-6">
                    <?php if (!empty($contactEmail)): ?>
                    <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" 
                       class="contact-link flex items-center justify-center text-gray-700 hover:text-blue-600 transition-all group">
                        <i class="fas fa-envelope text-xl md:text-2xl mr-3 group-hover:scale-110 transition-transform" 
                           style="color: var(--color-primary);"></i>
                        <span class="font-medium text-sm md:text-base"><?= htmlspecialchars($contactEmail) ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($contactPhone)): ?>
                    <a href="tel:<?= htmlspecialchars($contactPhone) ?>" 
                       class="contact-link flex items-center justify-center text-gray-700 hover:text-blue-600 transition-all group">
                        <i class="fas fa-phone text-xl md:text-2xl mr-3 group-hover:scale-110 transition-transform" 
                           style="color: var(--color-primary);"></i>
                        <span class="font-medium text-sm md:text-base"><?= htmlspecialchars($contactPhone) ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Social Media Links -->
            <?php 
            $socialLinks = array_filter([
                'facebook' => $socialFacebook,
                'instagram' => $socialInstagram,
                'twitter' => $socialTwitter,
                'youtube' => $socialYoutube,
                'linkedin' => $socialLinkedin,
                'tiktok' => $socialTiktok,
            ]);
            
            if (!empty($socialLinks)): 
            ?>
            <div class="text-center">
                <p class="text-gray-600 text-sm mb-4 font-semibold">Ikuti kami di:</p>
                <div class="flex flex-wrap justify-center gap-3 md:gap-4">
                    <?php
                    $socialIcons = [
                        'facebook' => 'fab fa-facebook-f',
                        'instagram' => 'fab fa-instagram',
                        'twitter' => 'fab fa-twitter',
                        'youtube' => 'fab fa-youtube',
                        'linkedin' => 'fab fa-linkedin-in',
                        'tiktok' => 'fab fa-tiktok',
                    ];
                    
                    foreach ($socialLinks as $platform => $url):
                        if (!empty($url)):
                    ?>
                    <a href="<?= htmlspecialchars($url) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="social-icon w-11 h-11 md:w-12 md:h-12 rounded-full flex items-center justify-center text-white text-lg md:text-xl shadow-lg"
                       style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));"
                       title="<?= ucfirst($platform) ?>">
                        <i class="<?= $socialIcons[$platform] ?>"></i>
                    </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Footer Copyright -->
        <div class="text-center mt-8 text-white/90 text-xs md:text-sm drop-shadow fade-in" style="animation-delay: 0.4s;">
            <p><?= $copyrightText ?></p>
        </div>
        
    </div>
    
    <!-- Background Decoration -->
    <div class="fixed top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
    <div class="fixed bottom-10 right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
    <div class="fixed top-1/2 left-1/4 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>
    <div class="fixed bottom-1/3 right-1/3 w-28 h-28 bg-white/5 rounded-full blur-2xl"></div>
    
</body>
</html>
