<?php
/**
 * Contact Page - Dynamic Version
 * Fully integrated with settings table
 * Features: Contact form, Info cards, Social media (no username), Google Maps
 */

require_once 'config.php';

// Dynamic settings
 $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
 $siteTagline = getSetting('site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan');

// Page variables
 $pageNamespace = 'contact';
 $pageTitle = 'Hubungi Kami - ' . $siteName;
 $pageDescription = 'Hubungi ' . $siteName . ' untuk informasi, pertanyaan, atau kerjasama';

include 'templates/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-gray-100 py-4">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-sm">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:text-blue-700 transition">
                <i class="fas fa-home"></i> Beranda
            </a>
            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
            <span class="text-gray-600">Kontak</span>
        </nav>
    </div>
</div>

<!-- Contact Section -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="text-center mb-12" data-aos="fade-up">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                Hubungi Kami
            </h1>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                Kami siap membantu Anda. Silakan hubungi kami melalui form di bawah atau informasi kontak yang tersedia.
            </p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Contact Info Cards (1/3 width) -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Address -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow" data-aos="fade-up">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-map-marker-alt text-2xl text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Alamat</h3>
                            <p class="text-gray-600">
                                <?= nl2br(htmlspecialchars(getSetting('contact_address', 'Jl. Pendidikan No. 123, Banjarmasin, Kalimantan Selatan'))) ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-phone-alt text-2xl text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Telepon</h3>
                            <?php 
                            $phone = getSetting('contact_phone');
                            $whatsapp = getSetting('contact_whatsapp');
                            
                            if (!empty($phone)): 
                            ?>
                            <a href="tel:<?= htmlspecialchars($phone) ?>" 
                               class="text-gray-600 hover:text-blue-600 transition block">
                                <i class="fas fa-phone mr-2"></i><?= htmlspecialchars($phone) ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($whatsapp)): ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>" 
                               target="_blank" rel="noopener noreferrer"
                               class="text-gray-600 hover:text-green-600 transition block mt-2">
                                <i class="fab fa-whatsapp mr-2"></i><?= htmlspecialchars($whatsapp) ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (empty($phone) && empty($whatsapp)): ?>
                            <p class="text-gray-500 text-sm">Nomor telepon akan segera tersedia</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Email -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-envelope text-2xl text-red-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Email</h3>
                            <?php 
                            $email = getSetting('contact_email');
                            if (!empty($email)): 
                            ?>
                            <a href="mailto:<?= htmlspecialchars($email) ?>" 
                               class="text-gray-600 hover:text-blue-600 transition break-all">
                                <i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($email) ?>
                            </a>
                            <?php else: ?>
                            <p class="text-gray-500 text-sm">Email akan segera tersedia</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Office Hours -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-clock text-2xl text-purple-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Jam Operasional</h3>
                            <div class="text-gray-600 space-y-1">
                                <div class="flex justify-between">
                                    <span>Senin - Jumat</span>
                                    <span class="font-semibold">08:00 - 16:00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Sabtu</span>
                                    <span class="font-semibold">08:00 - 12:00</span>
                                </div>
                                <div class="flex justify-between text-red-600">
                                    <span>Minggu</span>
                                    <span class="font-semibold">Tutup</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media - WITHOUT USERNAME -->
                <?php
                $social_platforms = [
                    'facebook' => [
                        'url' => getSetting('social_facebook'),
                        'icon' => 'fab fa-facebook-f',
                        'name' => 'Facebook',
                        'gradient' => 'from-blue-600 via-blue-700 to-blue-800'
                    ],
                    'instagram' => [
                        'url' => getSetting('social_instagram'),
                        'icon' => 'fab fa-instagram',
                        'name' => 'Instagram',
                        'gradient' => 'from-purple-600 via-pink-600 to-orange-500'
                    ],
                    'youtube' => [
                        'url' => getSetting('social_youtube'),
                        'icon' => 'fab fa-youtube',
                        'name' => 'YouTube',
                        'gradient' => 'from-red-600 via-red-700 to-red-800'
                    ],
                    'twitter' => [
                        'url' => getSetting('social_twitter'),
                        'icon' => 'fab fa-twitter',
                        'name' => 'Twitter',
                        'gradient' => 'from-sky-400 via-blue-500 to-blue-600'
                    ],
                    'linkedin' => [
                        'url' => getSetting('social_linkedin'),
                        'icon' => 'fab fa-linkedin-in',
                        'name' => 'LinkedIn',
                        'gradient' => 'from-blue-700 via-blue-800 to-blue-900'
                    ],
                    'tiktok' => [
                        'url' => getSetting('social_tiktok'),
                        'icon' => 'fab fa-tiktok',
                        'name' => 'TikTok',
                        'gradient' => 'from-gray-900 via-black to-gray-800'
                    ],
                ];

                $active_socials = array_filter($social_platforms, function($social) {
                    return !empty($social['url']);
                });

                if (!empty($active_socials)):
                ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow" data-aos="fade-up" data-aos-delay="400">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4">
                        <h3 class="text-lg font-bold text-white flex items-center">
                            <i class="fas fa-share-alt mr-2"></i>
                            Ikuti Kami
                        </h3>
                        <p class="text-white/80 text-sm mt-1">Tetap terhubung dengan kami</p>
                    </div>
                    
                    <div class="p-4 space-y-2">
                        <?php foreach ($active_socials as $platform => $social): ?>
                        <a href="<?= htmlspecialchars($social['url']) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="block relative overflow-hidden rounded-lg group">
                            <div class="absolute inset-0 bg-gradient-to-r <?= $social['gradient'] ?> opacity-10 group-hover:opacity-20 transition"></div>
                            <div class="relative flex items-center justify-between p-3 border border-gray-200 rounded-lg group-hover:border-transparent group-hover:shadow-md transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br <?= $social['gradient'] ?> rounded-lg flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition">
                                        <i class="<?= $social['icon'] ?> text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900"><?= $social['name'] ?></div>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 group-hover:text-gray-600 group-hover:translate-x-1 transition"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Right Column with Contact Form and Google Maps (2/3 width) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Contact Form -->
                <div class="bg-white rounded-lg shadow-md p-8" data-aos="fade-up">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        Kirim Pesan
                    </h2>
                    
                    <!-- Success Message -->
                    <div id="success-message" class="hidden mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span id="success-text"></span>
                    </div>
                    
                    <!-- Error Message -->
                    <div id="error-message" class="hidden mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="error-text"></span>
                    </div>
                    
                    <form id="contact-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nama Lengkap *
                                </label>
                                <input type="text" name="name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Masukkan nama lengkap">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Email *
                                </label>
                                <input type="email" name="email" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="email@example.com">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Nomor Telepon
                                </label>
                                <input type="tel" name="phone"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="08xx-xxxx-xxxx">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Subjek *
                                </label>
                                <input type="text" name="subject" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Perihal pesan">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Pesan *
                            </label>
                            <textarea name="message" rows="6" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Tulis pesan Anda di sini..."></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="w-full px-8 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-bold text-lg shadow-lg hover:shadow-xl">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Kirim Pesan
                        </button>
                        
                        <p class="text-sm text-gray-500 mt-4 text-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Kami akan merespon pesan Anda dalam 1×24 jam
                        </p>
                    </form>
                </div>
                
                <!-- Google Maps -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                    <h2 class="text-2xl font-bold text-gray-900 p-6 border-b">
                        <i class="fas fa-map-marked-alt text-blue-600 mr-2"></i>
                        Lokasi Kami
                    </h2>
                    <div class="aspect-video w-full">
                        <?php
                        $maps_embed = getSetting('contact_maps_embed');
                        if (!empty($maps_embed)):
                        ?>
                            <?= $maps_embed ?>
                        <?php else: ?>
                        <!-- Default Google Maps Embed -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.1234567890123!2d114.5910!3d-3.3190!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM8KwMTknMDguNCJTIDExNMKwMzUnMjcuNiJF!5e0!3m2!1sid!2sid!4v1234567890123"
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade"
                            class="w-full h-full">
                        </iframe>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
</section>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalHTML = submitBtn.innerHTML;
        const successMsg = document.getElementById('success-message');
        const errorMsg = document.getElementById('error-message');
        
        // Hide previous messages
        successMsg.classList.add('hidden');
        errorMsg.classList.add('hidden');
        
        // Validate
        const name = this.querySelector('[name="name"]').value.trim();
        const email = this.querySelector('[name="email"]').value.trim();
        const subject = this.querySelector('[name="subject"]').value.trim();
        const message = this.querySelector('[name="message"]').value.trim();
        
        if (name.length < 2) {
            document.getElementById('error-text').textContent = 'Nama minimal 2 karakter';
            errorMsg.classList.remove('hidden');
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        if (!email.includes('@') || !email.includes('.')) {
            document.getElementById('error-text').textContent = 'Email tidak valid';
            errorMsg.classList.remove('hidden');
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        if (subject.length < 3) {
            document.getElementById('error-text').textContent = 'Subjek minimal 3 karakter';
            errorMsg.classList.remove('hidden');
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        if (message.length < 10) {
            document.getElementById('error-text').textContent = 'Pesan minimal 10 karakter';
            errorMsg.classList.remove('hidden');
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
        
        fetch('<?= BASE_URL ?>api/contact.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                document.getElementById('success-text').textContent = data.message || 'Pesan berhasil dikirim! Kami akan segera menghubungi Anda.';
                successMsg.classList.remove('hidden');
                
                // Reset form
                contactForm.reset();
                
                // Scroll to success message
                successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                throw new Error(data.message || 'Gagal mengirim pesan');
            }
        })
        .catch(err => {
            console.error('Contact form error:', err);
            // Show error message
            document.getElementById('error-text').textContent = err.message || 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.';
            errorMsg.classList.remove('hidden');
            
            // Scroll to error message
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        });
    });
    
    // Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 600,
            once: true,
            offset: 100
        });
    }
});
</script>

<?php include 'templates/footer.php'; ?>