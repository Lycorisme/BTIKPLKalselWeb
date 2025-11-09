<?php
/**
 * Public Footer Template
 * Includes: Footer content, JavaScript libraries
 */

// Get footer settings
$footer_about = getSetting('footer_about', 'Balai Teknologi Informasi dan Komunikasi Pendidikan Kalimantan Selatan');
$footer_address = getSetting('footer_address', 'Jl. A. Yani KM 36, Banjarmasin, Kalimantan Selatan');
$contact_email = getSetting('contact_email', 'info@btikpkalsel.id');
$contact_phone = getSetting('contact_phone', '(0511) 1234567');
?>
        </div>
        <!-- End Barba Container -->
        
    </div>
    <!-- End Barba Wrapper -->
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- About -->
                <div>
                    <h3 class="text-xl font-bold mb-4"><?= getSetting('site_name', 'BTIKP Kalsel') ?></h3>
                    <p class="text-gray-400 mb-4"><?= $footer_about ?></p>
                    <div class="flex space-x-3">
                        <?php if ($fb = getSetting('social_facebook')): ?>
                        <a href="<?= $fb ?>" target="_blank" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-facebook text-2xl"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($ig = getSetting('social_instagram')): ?>
                        <a href="<?= $ig ?>" target="_blank" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-instagram text-2xl"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($yt = getSetting('social_youtube')): ?>
                        <a href="<?= $yt ?>" target="_blank" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-youtube text-2xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-xl font-bold mb-4">Menu Cepat</h3>
                    <ul class="space-y-2">
                        <li><a href="<?= BASE_URL ?>" class="text-gray-400 hover:text-white transition"><i class="fas fa-chevron-right mr-2 text-xs"></i>Beranda</a></li>
                        <li><a href="<?= BASE_URL ?>posts.php" class="text-gray-400 hover:text-white transition"><i class="fas fa-chevron-right mr-2 text-xs"></i>Berita</a></li>
                        <li><a href="<?= BASE_URL ?>gallery.php" class="text-gray-400 hover:text-white transition"><i class="fas fa-chevron-right mr-2 text-xs"></i>Galeri</a></li>
                        <li><a href="<?= BASE_URL ?>services.php" class="text-gray-400 hover:text-white transition"><i class="fas fa-chevron-right mr-2 text-xs"></i>Layanan</a></li>
                        <li><a href="<?= BASE_URL ?>files.php" class="text-gray-400 hover:text-white transition"><i class="fas fa-chevron-right mr-2 text-xs"></i>Unduhan</a></li>
                        <li><a href="<?= BASE_URL ?>contact.php" class="text-gray-400 hover:text-white transition"><i class="fas fa-chevron-right mr-2 text-xs"></i>Kontak</a></li>
                    </ul>
                </div>
                
                <!-- Recent Posts -->
                <div>
                    <h3 class="text-xl font-bold mb-4">Berita Terbaru</h3>
                    <ul class="space-y-3">
                        <?php
                        $recent_posts = get_recent_posts(3);
                        foreach ($recent_posts as $post):
                        ?>
                        <li>
                            <a href="<?= BASE_URL ?>post.php?slug=<?= $post['slug'] ?>" class="text-gray-400 hover:text-white transition text-sm">
                                <?= truncateText($post['title'], 50) ?>
                            </a>
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="far fa-calendar mr-1"></i><?= formatTanggal($post['created_at'], 'd M Y') ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-xl font-bold mb-4">Kontak Kami</h3>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-blue-400"></i>
                            <span class="text-sm"><?= $footer_address ?></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-blue-400"></i>
                            <a href="mailto:<?= $contact_email ?>" class="text-sm hover:text-white transition"><?= $contact_email ?></a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-blue-400"></i>
                            <a href="tel:<?= str_replace(['(', ')', ' ', '-'], '', $contact_phone) ?>" class="text-sm hover:text-white transition"><?= $contact_phone ?></a>
                        </li>
                    </ul>
                </div>
                
            </div>
            
            <!-- Copyright -->
            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> <?= getSetting('site_name', 'BTIKP Kalimantan Selatan') ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Search Modal -->
    <div id="searchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Pencarian</h3>
                <button onclick="closeSearchModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form action="<?= BASE_URL ?>search.php" method="GET">
                <input type="text" name="q" placeholder="Cari berita, layanan, atau halaman..." 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       autofocus>
                <button type="submit" class="mt-4 w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i> Cari
                </button>
            </form>
        </div>
    </div>
    
    <!-- JavaScript Libraries via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
        
        // Search modal
        function openSearchModal() {
            document.getElementById('searchModal').classList.remove('hidden');
        }
        
        function closeSearchModal() {
            document.getElementById('searchModal').classList.add('hidden');
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSearchModal();
            }
        });
        
        // Close modal on background click
        document.getElementById('searchModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSearchModal();
            }
        });
        
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>
