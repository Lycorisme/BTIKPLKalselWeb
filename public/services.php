<?php
/**
 * Services Page
 * Click service card to directly open service URL
 */

require_once 'config.php';

// Get all active services
try {
    $stmt = $db->prepare("
        SELECT * FROM services 
        WHERE status = 'published' AND deleted_at IS NULL
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $services = [];
}

// Page variables
$pageNamespace = 'services';
$pageTitle = 'Layanan Kami - ' . getSetting('site_name');
$pageDescription = 'Berbagai layanan dan fasilitas yang kami sediakan';

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
            <span class="text-gray-600">Layanan</span>
        </nav>
    </div>
</div>

<!-- Services Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <i class="fas fa-concierge-bell text-blue-600 mr-2"></i>
                Layanan Kami
            </h1>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Kami menyediakan berbagai layanan terbaik untuk memenuhi kebutuhan Anda
            </p>
        </div>
        
        <?php if (empty($services)): ?>
        
        <!-- Empty State -->
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-concierge-bell text-6xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Belum Ada Layanan</h2>
            <p class="text-gray-600">Informasi layanan akan ditampilkan di sini</p>
        </div>
        
        <?php else: ?>
        
        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($services as $service): ?>
            <?php
            // Determine if service has URL
            $hasUrl = !empty($service['service_url']);
            $targetUrl = $hasUrl ? $service['service_url'] : '#';
            
            // **FIXED: Use image_path (with underscore) from database**
            $hasImage = !empty($service['image_path']);
            $imageUrl = $hasImage ? uploadUrl($service['image_path']) : '';
            ?>
            
            <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 group">
                
                <!-- Service Card - Clickable -->
                <a href="<?= $targetUrl ?>" 
                   <?= $hasUrl ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                   class="block">
                    
                    <!-- Service Image -->
                    <div class="h-48 overflow-hidden relative">
                        <?php if ($hasImage): ?>
                        <!-- Display uploaded image -->
                        <img src="<?= $imageUrl ?>" 
                             alt="<?= htmlspecialchars($service['title']) ?>"
                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300"
                             loading="lazy"
                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center\'><i class=\'fas fa-concierge-bell text-6xl text-white opacity-50\'></i></div>';">
                        
                        <!-- Overlay on image -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                        
                        <?php else: ?>
                        <!-- Gradient fallback if no image -->
                        <div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-concierge-bell text-6xl text-white opacity-50"></i>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <?php endif; ?>
                        
                        <!-- External Link Badge -->
                        <?php if ($hasUrl): ?>
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm text-blue-600 px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 shadow-lg">
                            <i class="fas fa-external-link-alt"></i>
                            <span>Buka Layanan</span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Service Title Overlay -->
                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <h3 class="text-white font-bold text-lg drop-shadow-lg line-clamp-2">
                                <?= htmlspecialchars($service['title']) ?>
                            </h3>
                        </div>
                    </div>
                    
                    <!-- Service Content -->
                    <div class="p-6">
                        <?php if (!empty($service['description'])): ?>
                        <p class="text-gray-600 mb-4 line-clamp-3">
                            <?= htmlspecialchars(truncateText(strip_tags($service['description']), 120)) ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Footer -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <?php if ($hasUrl): ?>
                            <!-- URL Preview -->
                            <div class="flex items-center text-sm text-blue-600 font-medium">
                                <i class="fas fa-link mr-2"></i>
                                <span class="truncate"><?= htmlspecialchars(parse_url($service['service_url'], PHP_URL_HOST)) ?></span>
                            </div>
                            <?php else: ?>
                            <!-- No URL Available -->
                            <div class="flex items-center text-sm text-gray-400">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span>Link belum tersedia</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- Call to Action -->
        <div class="mt-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-xl p-8 md:p-12 text-center text-white">
            <h2 class="text-3xl font-bold mb-4">
                Butuh Bantuan atau Informasi Lebih Lanjut?
            </h2>
            <p class="text-lg mb-6 opacity-90">
                Tim kami siap membantu Anda dengan layanan terbaik
            </p>
            <button onclick="openContactModal()" 
                    class="px-8 py-3 bg-white text-blue-600 rounded-lg hover:bg-gray-100 transition font-bold text-lg shadow-lg">
                <i class="fas fa-envelope mr-2"></i>
                Hubungi Kami
            </button>
        </div>
        
    </div>
</section>

<!-- Contact Form Modal -->
<div id="contact-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                Hubungi Kami
            </h3>
            <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="contact-form" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama *</label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Telepon</label>
                    <input type="tel" name="phone"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subjek *</label>
                    <input type="text" name="subject" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pesan *</label>
                    <textarea name="message" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </div>
            
            <button type="submit" 
                    class="mt-6 w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                <i class="fas fa-paper-plane mr-2"></i>
                Kirim Pesan
            </button>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
// Open contact modal
function openContactModal() {
    const modal = document.getElementById('contact-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

// Close contact modal
function closeContactModal() {
    const modal = document.getElementById('contact-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeContactModal();
    }
});

// Close modal on background click
document.getElementById('contact-modal').addEventListener('click', function(e) {
    if (e.target === this) closeContactModal();
});

// Contact form submission
document.getElementById('contact-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
    
    fetch('<?= BASE_URL ?>api/contact.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✓ ' + data.message);
            this.reset();
            closeContactModal();
        } else {
            alert('✗ ' + (data.message || 'Gagal mengirim pesan'));
        }
    })
    .catch(err => {
        console.error('Contact error:', err);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
    });
});
</script>

<?php include 'templates/footer.php'; ?>
