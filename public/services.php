<?php
/**
 * Services Page - Redesigned & Animation Fixed
 * * PERBAIKAN UTAMA:
 * 1. Struktur HTML disederhanakan untuk kompatibilitas AOS yang lebih baik.
 * 2. Script pemaksa refresh AOS ditambahkan di footer.
 * 3. Empty state menggunakan animasi 'zoom-in' yang lebih jelas.
 * 4. Sistem delay animasi dihitung otomatis.
 */

require_once 'config.php';
// [TRACKING] Load Tracker Class (Disiapkan jika ingin tracking halaman ini sebagai 'page' umum nanti)
require_once '../core/PageViewTracker.php';

// 1. Ambil Data Layanan
try {
    $stmt = $db->prepare("
        SELECT * FROM services 
        WHERE status = 'published' AND deleted_at IS NULL
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Services Error: " . $e->getMessage());
    $services = [];
}

// 2. Setup Variabel Halaman
$pageNamespace = 'services';
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$pageTitle = 'Layanan Kami - ' . $siteName;
$pageDescription = 'Berbagai layanan dan fasilitas yang kami sediakan';

include 'templates/header.php';
?>

<div style="background-color: var(--color-background); color: var(--color-text); min-height: 100vh; display: flex; flex-direction: column;">

    <div class="py-4 border-b border-gray-200/50">
        <div class="container mx-auto px-4">
            <nav class="flex items-center gap-2 text-sm">
                <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-home"></i> Beranda
                </a>
                <i class="fas fa-chevron-right opacity-30 text-xs"></i>
                <span class="opacity-70 font-medium">Layanan</span>
            </nav>
        </div>
    </div>

    <section class="flex-grow py-16">
        <div class="container mx-auto px-4">
            
            <div class="text-center mb-16 max-w-3xl mx-auto" data-aos="fade-down" data-aos-duration="800">
                <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight" style="color: var(--color-text);">
                    <span style="color: var(--color-primary);">Layanan</span> Kami
                </h1>
                <p class="text-lg opacity-70 leading-relaxed">
                    Kami berdedikasi untuk menyediakan layanan teknologi informasi dan komunikasi terbaik guna mendukung kemajuan pendidikan di Kalimantan Selatan.
                </p>
            </div>
            
            <?php if (empty($services)): ?>
            
                <div class="max-w-2xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center border border-gray-100" 
                         data-aos="zoom-in" 
                         data-aos-duration="600">
                        
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center"
                             style="background-color: rgba(var(--color-primary-rgb), 0.1);">
                            <i class="fas fa-concierge-bell text-5xl" style="color: var(--color-primary);"></i>
                        </div>
                        
                        <h2 class="text-2xl font-bold mb-3" style="color: var(--color-text);">
                            Layanan Belum Tersedia
                        </h2>
                        <p class="opacity-60 mb-8">
                            Saat ini kami sedang memperbarui daftar layanan. Silakan kembali lagi nanti atau hubungi kami untuk informasi lebih lanjut.
                        </p>
                        
                        <button onclick="openContactModal()" 
                                class="inline-flex items-center px-8 py-3 rounded-lg text-white font-medium transition shadow-md hover:shadow-lg hover:-translate-y-1"
                                style="background-color: var(--color-primary);">
                            <i class="fas fa-envelope mr-2"></i> Hubungi Kami
                        </button>
                    </div>
                </div>
            
            <?php else: ?>
            
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($services as $index => $service): ?>
                    <?php
                        // Setup Data
                        $hasUrl = !empty($service['service_url']);
                        $targetUrl = $hasUrl ? $service['service_url'] : '#';
                        $hasImage = !empty($service['image_path']);
                        $imageUrl = $hasImage ? uploadUrl($service['image_path']) : '';
                        
                        // Hitung Delay Animasi (Staggered Effect)
                        // Kartu 1: 0ms, Kartu 2: 100ms, Kartu 3: 200ms, dst.
                        $animDelay = ($index % 6) * 100; 
                    ?>
                    
                    <div data-aos="fade-up" 
                         data-aos-delay="<?= $animDelay ?>"
                         data-aos-duration="800"
                         data-aos-anchor-placement="top-bottom">
                         
                        <div class="bg-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 group h-full flex flex-col overflow-hidden border border-gray-100 relative">
                            
                            <a href="<?= $targetUrl ?>" <?= $hasUrl ? 'target="_blank"' : '' ?> class="block relative h-52 overflow-hidden bg-gray-100">
                                <?php if ($hasImage): ?>
                                    <img src="<?= $imageUrl ?>" 
                                         alt="<?= htmlspecialchars($service['title']) ?>"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                         loading="lazy">
                                    <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center"
                                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                                        <i class="fas fa-concierge-bell text-6xl text-white/50"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasUrl): ?>
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur text-xs font-bold px-3 py-1.5 rounded-full shadow-sm flex items-center gap-1"
                                     style="color: var(--color-primary);">
                                    <span>Buka</span> <i class="fas fa-external-link-alt text-[10px]"></i>
                                </div>
                                <?php endif; ?>
                            </a>

                            <div class="p-6 flex-1 flex flex-col">
                                <h3 class="text-xl font-bold mb-3 line-clamp-2 group-hover:text-blue-600 transition-colors" 
                                    style="color: var(--color-text);">
                                    <?= htmlspecialchars($service['title']) ?>
                                </h3>
                                
                                <?php if (!empty($service['description'])): ?>
                                <p class="text-sm opacity-70 mb-4 line-clamp-3 flex-1 leading-relaxed">
                                    <?= htmlspecialchars(truncateText(strip_tags($service['description']), 120)) ?>
                                </p>
                                <?php else: ?>
                                <div class="flex-1"></div>
                                <?php endif; ?>
                                
                                <div class="pt-4 mt-auto border-t border-gray-100 flex justify-between items-center">
                                    <span class="text-xs opacity-50 uppercase tracking-wider font-semibold">Info Layanan</span>
                                    
                                    <a href="<?= $targetUrl ?>" 
                                       <?= $hasUrl ? 'target="_blank"' : '' ?>
                                       class="inline-flex items-center text-sm font-semibold hover:underline decoration-2 underline-offset-4 transition"
                                       style="color: var(--color-primary);">
                                        <?= $hasUrl ? 'Akses Sekarang' : 'Detail' ?>
                                        <i class="fas fa-arrow-right ml-2 text-xs transition-transform group-hover:translate-x-1"></i>
                                    </a>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            <?php endif; ?>
            
            <div class="mt-20 relative rounded-2xl overflow-hidden shadow-2xl" 
                 data-aos="fade-up" 
                 data-aos-delay="200"
                 data-aos-offset="0"> <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);"></div>
                
                <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIyIiBjeT0iMiIgcj0iMiIgZmlsbD0iI2ZmZmZmZiIvPjwvc3ZnPg==')]"></div>

                <div class="relative z-10 p-10 md:p-16 text-center text-white">
                    <h2 class="text-3xl md:text-4xl font-bold mb-6">Butuh Informasi Lebih Lanjut?</h2>
                    <p class="text-lg text-white/90 mb-8 max-w-2xl mx-auto">
                        Jangan ragu untuk menghubungi tim kami. Kami siap membantu menjawab pertanyaan seputar layanan yang tersedia.
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <button onclick="openContactModal()" 
                                class="px-8 py-3.5 bg-white rounded-lg font-bold text-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition duration-300"
                                style="color: var(--color-primary);">
                            <i class="fas fa-envelope mr-2"></i> Hubungi Kami
                        </button>
                        <a href="<?= BASE_URL ?>contact.php" 
                           class="px-8 py-3.5 bg-transparent border-2 border-white text-white rounded-lg font-bold text-lg hover:bg-white/10 transition duration-300">
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
                        <input type="text" name="name" required class="form-input w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                    </div>
                    
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Email</label>
                        <input type="email" name="email" required class="form-input w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                    </div>
                    
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">No. Telepon</label>
                        <input type="tel" name="phone" class="form-input w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Subjek</label>
                        <input type="text" name="subject" required class="form-input w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1.5 opacity-80">Pesan</label>
                        <textarea name="message" rows="4" required class="form-textarea w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition"></textarea>
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
    if (typeof AOS !== 'undefined') {
        setTimeout(function() {
            AOS.refresh();
        }, 500); // Refresh ulang setelah 500ms
    }

    // 2. LOGIKA MODAL
    const modal = document.getElementById('contact-modal');
    const backdrop = document.getElementById('modal-backdrop');
    const panel = document.getElementById('modal-panel');

    window.openContactModal = function() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // Animasi Masuk
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('scale-95', 'opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    window.closeContactModal = function() {
        // Animasi Keluar
        backdrop.classList.add('opacity-0');
        panel.classList.remove('scale-100', 'opacity-100');
        panel.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }, 300); // Sesuaikan durasi transition CSS
    }

    // Close on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeContactModal();
        }
    });

    // 3. SUBMIT FORM AJAX
    const form = document.getElementById('contact-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ambil tombol submit dari footer (karena tombol diluar form tag tapi terhubung via form attribute)
        // Atau cari tombol di dalam dokumen yang punya attribute form="contact-form"
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
                // Tampilkan notifikasi sukses (menggunakan fungsi global jika ada, atau alert)
                if (window.BTIKPKalsel && window.BTIKPKalsel.showNotification) {
                    window.BTIKPKalsel.showNotification(data.message, 'success');
                } else {
                    alert(data.message);
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
                alert(err.message);
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
    });
});
</script>

<?php include 'templates/footer.php'; ?>