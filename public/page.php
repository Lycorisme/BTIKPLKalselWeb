<?php
/**
 * Static Page Viewer (Fixed Theme Consistency)
 * Display single static page by slug
 * * PERBAIKAN:
 * - Background & Text Color dinamis
 * - Prose/Konten Artikel mengikuti warna tema (Heading, Link, dll)
 * - Elemen UI menggunakan opacity bukan hardcoded color
 */

require_once 'config.php';
// [TRACKING] Load Tracker Class
require_once '../core/PageViewTracker.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . BASE_URL);
    exit;
}

// Get page from database
$pageModel = new Page();
$page = $pageModel->getBySlug($slug);

// Check if page exists and is published
if (!$page || $page['status'] !== 'published') {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Halaman Tidak Ditemukan</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 flex items-center justify-center min-h-screen text-gray-800">
        <div class="text-center px-4">
            <h1 class="text-6xl font-bold mb-4 text-blue-600">404</h1>
            <p class="text-xl mb-8 opacity-75">Halaman tidak ditemukan</p>
            <a href="<?= BASE_URL ?>" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg">
                <i class="fas fa-home mr-2"></i>
                Kembali ke Beranda
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// [TRACKING] Implement Page View Tracker
if ($page) {
    $tracker = new PageViewTracker();
    $tracker->track('page', $page['id']);
}

// Increment view count (only if column exists)
// Note: Tetap dijalankan karena trigger SQL saat ini hanya menangani 'post' dan 'service', belum 'page'
try {
    $pageModel->incrementViewCount($page['id']);
} catch (Exception $e) {
    // Skip if view_count column doesn't exist
    error_log('View count increment failed: ' . $e->getMessage());
}

// Page variables for SEO
$pageNamespace = 'page';
$pageTitle = $page['title'] . ' - ' . getSetting('site_name');
$pageDescription = truncateText(strip_tags($page['content']), 160);
$pageKeywords = isset($page['meta_keywords']) ? $page['meta_keywords'] : getSetting('site_keywords');
$pageImage = !empty($page['featured_image']) ? uploadUrl($page['featured_image']) : get_site_logo();

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
                <span class="opacity-75 font-medium"><?= htmlspecialchars($page['title']) ?></span>
            </nav>
        </div>
    </div>

    <section class="py-12">
        <div class="container mx-auto px-4">
            <article class="max-w-4xl mx-auto bg-white/50 rounded-xl p-4 md:p-0 backdrop-blur-sm">
                
                <header class="mb-8">
                    <?php if (isset($page['featured_image']) && !empty($page['featured_image'])): ?>
                    <div class="mb-8 rounded-xl overflow-hidden shadow-lg border border-gray-100 relative group">
                        <img src="<?= uploadUrl($page['featured_image']) ?>" 
                             alt="<?= htmlspecialchars($page['title']) ?>"
                             class="w-full h-auto object-cover"
                             loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                    <?php endif; ?>
                    
                    <h1 class="text-3xl md:text-5xl font-bold mb-4 leading-tight" style="color: var(--color-text);">
                        <?= htmlspecialchars($page['title']) ?>
                    </h1>
                    
                    <?php if (isset($page['subtitle']) && !empty($page['subtitle'])): ?>
                    <p class="text-xl mb-6 opacity-75 font-light">
                        <?= htmlspecialchars($page['subtitle']) ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="flex flex-wrap items-center gap-6 text-sm opacity-60 pb-6 border-b border-gray-200/50">
                        <span class="flex items-center">
                            <i class="far fa-calendar mr-2"></i>
                            Dipublikasi: <?= formatTanggal($page['created_at'], 'd F Y') ?>
                        </span>
                        
                        <?php if (isset($page['view_count'])): ?>
                        <span class="flex items-center">
                            <i class="fas fa-eye mr-2"></i>
                            <?= number_format($page['view_count']) ?> kali dilihat
                        </span>
                        <?php endif; ?>
                        
                        <?php if (isset($page['updated_at']) && $page['updated_at'] && $page['updated_at'] !== $page['created_at']): ?>
                        <span class="flex items-center">
                            <i class="fas fa-sync mr-2"></i>
                            Diperbarui: <?= formatTanggal($page['updated_at'], 'd F Y') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </header>
                
                <div class="prose prose-lg max-w-none">
                    <style>
                        .prose {
                            color: var(--color-text);
                            max-width: none;
                        }
                        .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
                            color: var(--color-text);
                            font-weight: 700;
                            margin-top: 2rem;
                            margin-bottom: 1rem;
                        }
                        .prose h2 { font-size: 1.875rem; border-bottom: 2px solid rgba(0,0,0,0.05); padding-bottom: 0.5rem; }
                        .prose h3 { font-size: 1.5rem; }
                        
                        .prose p {
                            margin-bottom: 1.25rem;
                            line-height: 1.8;
                            opacity: 0.9;
                        }
                        
                        .prose ul, .prose ol {
                            margin: 1.25rem 0;
                            padding-left: 1.5rem;
                        }
                        
                        .prose ul { list-style-type: disc; }
                        .prose ol { list-style-type: decimal; }
                        
                        .prose li {
                            margin-bottom: 0.5rem;
                            padding-left: 0.5rem;
                        }
                        
                        .prose img {
                            border-radius: 0.75rem;
                            margin: 2rem auto;
                            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                            max-width: 100%;
                            height: auto;
                        }
                        
                        .prose a {
                            color: var(--color-primary);
                            text-decoration: none;
                            border-bottom: 1px solid transparent;
                            transition: all 0.2s;
                            font-weight: 500;
                        }
                        
                        .prose a:hover {
                            border-bottom-color: var(--color-primary);
                            opacity: 0.8;
                        }
                        
                        .prose blockquote {
                            border-left: 4px solid var(--color-primary);
                            background-color: rgba(0,0,0,0.02);
                            padding: 1rem 1.5rem;
                            font-style: italic;
                            color: var(--color-text);
                            opacity: 0.8;
                            margin: 2rem 0;
                            border-radius: 0 0.5rem 0.5rem 0;
                        }
                        
                        .prose table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 2rem 0;
                            font-size: 0.95rem;
                        }
                        
                        .prose th {
                            background-color: rgba(0,0,0,0.03);
                            padding: 1rem;
                            text-align: left;
                            font-weight: 600;
                            border: 1px solid rgba(0,0,0,0.1);
                            color: var(--color-text);
                        }
                        
                        .prose td {
                            padding: 1rem;
                            border: 1px solid rgba(0,0,0,0.1);
                            color: var(--color-text);
                        }

                        .prose strong, .prose b {
                            font-weight: 700;
                            color: var(--color-text);
                        }
                        
                        /* Fix for embedded iframes (maps, videos) */
                        .prose iframe {
                            width: 100%;
                            max-width: 100%;
                            border-radius: 0.5rem;
                            margin: 1.5rem 0;
                        }
                    </style>
                    
                    <?= $page['content'] ?>
                </div>
                
                <div class="mt-16 pt-8 border-t border-gray-200/50">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Bagikan Halaman Ini:</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($page_url ?? '') ?>" 
                           target="_blank" 
                           class="px-4 py-2 bg-[#1877F2] text-white rounded-lg hover:opacity-90 transition flex items-center gap-2 shadow-sm">
                            <i class="fab fa-facebook"></i>
                            Facebook
                        </a>
                        
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($page_url ?? '') ?>&text=<?= urlencode($page['title']) ?>" 
                           target="_blank"
                           class="px-4 py-2 bg-black text-white rounded-lg hover:opacity-80 transition flex items-center gap-2 shadow-sm">
                            <i class="fab fa-twitter"></i>
                            Twitter
                        </a>
                        
                        <a href="https://wa.me/?text=<?= urlencode($page['title'] . ' - ' . ($page_url ?? '')) ?>" 
                           target="_blank"
                           class="px-4 py-2 bg-[#25D366] text-white rounded-lg hover:opacity-90 transition flex items-center gap-2 shadow-sm">
                            <i class="fab fa-whatsapp"></i>
                            WhatsApp
                        </a>
                        
                        <button onclick="copyToClipboard('<?= $page_url ?? '' ?>')" 
                                class="px-4 py-2 text-white rounded-lg transition flex items-center gap-2 shadow-sm hover:opacity-90"
                                style="background-color: var(--color-primary);">
                            <i class="fas fa-link"></i>
                            Salin Link
                        </button>
                    </div>
                </div>
                
            </article>
        </div>
    </section>
</div>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✓ Link berhasil disalin!');
        }).catch(err => {
            console.error('Failed to copy:', err);
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        alert('✓ Link berhasil disalin!');
    } catch (err) {
        alert('✗ Gagal menyalin link');
    }
    document.body.removeChild(textArea);
}
</script>

<?php include 'templates/footer.php'; ?>