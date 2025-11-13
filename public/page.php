<?php
/**
 * Static Page Viewer
 * Display single static page by slug
 */

require_once 'config.php';

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
    <body class="bg-gray-50">
        <div class="min-h-screen flex items-center justify-center px-4">
            <div class="text-center">
                <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
                <p class="text-xl text-gray-600 mb-8">Halaman tidak ditemukan</p>
                <a href="<?= BASE_URL ?>" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-home mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Increment view count (only if column exists)
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

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600"><?= htmlspecialchars($page['title']) ?></span>
        </nav>
    </div>
</div>

<!-- Page Content -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4">
        <article class="max-w-4xl mx-auto">
            
            <!-- Page Header -->
            <header class="mb-8">
                <!-- Featured Image (if exists and column exists) -->
                <?php if (isset($page['featured_image']) && !empty($page['featured_image'])): ?>
                <div class="mb-6 rounded-lg overflow-hidden shadow-lg">
                    <img src="<?= uploadUrl($page['featured_image']) ?>" 
                         alt="<?= htmlspecialchars($page['title']) ?>"
                         class="w-full h-auto"
                         loading="lazy">
                </div>
                <?php endif; ?>
                
                <!-- Page Title -->
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    <?= htmlspecialchars($page['title']) ?>
                </h1>
                
                <!-- Subtitle (if exists) -->
                <?php if (isset($page['subtitle']) && !empty($page['subtitle'])): ?>
                <p class="text-xl text-gray-600 mb-6">
                    <?= htmlspecialchars($page['subtitle']) ?>
                </p>
                <?php endif; ?>
                
                <!-- Meta Info -->
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 pb-4 border-b border-gray-200">
                    <!-- Show view count only if column exists -->
                    <?php if (isset($page['view_count'])): ?>
                    <span class="flex items-center">
                        <i class="fas fa-eye mr-2"></i>
                        <?= number_format($page['view_count']) ?> kali dilihat
                    </span>
                    <?php endif; ?>
                    
                    <span class="flex items-center">
                        <i class="far fa-calendar mr-2"></i>
                        Dipublikasi: <?= formatTanggal($page['created_at'], 'd F Y') ?>
                    </span>
                    
                    <?php if (isset($page['updated_at']) && $page['updated_at'] && $page['updated_at'] !== $page['created_at']): ?>
                    <span class="flex items-center">
                        <i class="fas fa-sync mr-2"></i>
                        Diperbarui: <?= formatTanggal($page['updated_at'], 'd F Y') ?>
                    </span>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Page Content (Rich Text from Editor) -->
            <div class="prose prose-lg max-w-none">
                <!-- Custom Prose Styling -->
                <style>
                    .prose {
                        color: #374151;
                        max-width: none;
                    }
                    .prose h2 {
                        font-size: 1.875rem;
                        font-weight: 700;
                        color: #1f2937;
                        margin-top: 2rem;
                        margin-bottom: 1rem;
                    }
                    .prose h3 {
                        font-size: 1.5rem;
                        font-weight: 600;
                        color: #1f2937;
                        margin-top: 1.5rem;
                        margin-bottom: 0.75rem;
                    }
                    .prose p {
                        margin-bottom: 1rem;
                        line-height: 1.75;
                    }
                    .prose ul, .prose ol {
                        margin: 1rem 0;
                        padding-left: 2rem;
                    }
                    .prose li {
                        margin-bottom: 0.5rem;
                    }
                    .prose img {
                        border-radius: 0.5rem;
                        margin: 1.5rem 0;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    }
                    .prose a {
                        color: #2563eb;
                        text-decoration: underline;
                    }
                    .prose a:hover {
                        color: #1d4ed8;
                    }
                    .prose blockquote {
                        border-left: 4px solid #2563eb;
                        padding-left: 1rem;
                        font-style: italic;
                        color: #4b5563;
                        margin: 1.5rem 0;
                    }
                    .prose table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 1.5rem 0;
                    }
                    .prose th {
                        background-color: #f3f4f6;
                        padding: 0.75rem;
                        text-align: left;
                        font-weight: 600;
                        border: 1px solid #e5e7eb;
                    }
                    .prose td {
                        padding: 0.75rem;
                        border: 1px solid #e5e7eb;
                    }
                </style>
                
                <?= $page['content'] ?>
            </div>
            
            <!-- Share Buttons -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Bagikan Halaman Ini:</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($page_url ?? '') ?>" 
                       target="_blank" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fab fa-facebook"></i>
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($page_url ?? '') ?>&text=<?= urlencode($page['title']) ?>" 
                       target="_blank"
                       class="px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600 transition flex items-center gap-2">
                        <i class="fab fa-twitter"></i>
                        Twitter
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($page['title'] . ' - ' . ($page_url ?? '')) ?>" 
                       target="_blank"
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <i class="fab fa-whatsapp"></i>
                        WhatsApp
                    </a>
                    <button onclick="copyToClipboard('<?= $page_url ?? '' ?>')" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                        <i class="fas fa-link"></i>
                        Salin Link
                    </button>
                </div>
            </div>
            
        </article>
    </div>
</section>

<!-- Copy to Clipboard Script -->
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('✓ Link berhasil disalin!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('✗ Gagal menyalin link');
    });
}
</script>

<?php include 'templates/footer.php'; ?>
