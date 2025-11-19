/**
 * ========================================
 * BTIKP Kalsel - Custom JavaScript (Centralized)
 * ========================================
 * All public page scripts in one file
 */

(function() {
    'use strict';
    
    // ========================================
    // 1. MOBILE MENU TOGGLE
    // ========================================
    const initMobileMenu = () => {
        const menuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                // Menggunakan class 'open' untuk transisi
                mobileMenu.classList.toggle('open');
                
                // Toggle icon
                const icon = menuBtn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                }
                
                // Mengelola overflow body untuk mencegah scroll di background
                document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
            });
            
            // Close on link click (di dalam menu utama)
            const menuLinks = mobileMenu.querySelectorAll('.container > div > a:not(.nav-link)');
            menuLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.remove('open');
                    const icon = menuBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                    document.body.style.overflow = '';
                });
            });
        }
    };
    
    // ========================================
    // 2. SEARCH MODAL
    // ========================================
    window.openSearchModal = () => {
        const modal = document.getElementById('searchModal');
        if (modal) {
            // Hapus 'hidden' dan tambahkan 'active' untuk animasi CSS
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('active'), 10);
            
            const input = modal.querySelector('input[name="q"]');
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
            document.body.style.overflow = 'hidden';
        }
    };
    
    window.closeSearchModal = () => {
        const modal = document.getElementById('searchModal');
        if (modal) {
            // Hapus 'active' untuk animasi keluar, lalu tambahkan 'hidden'
            modal.classList.remove('active');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300); // Sesuai durasi transisi CSS
        }
    };
    
    // Close modal on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSearchModal();
        }
    });
    
    // Close modal on background click
    const initSearchModalClickOutside = () => {
        const modal = document.getElementById('searchModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeSearchModal();
                }
            });
        }
    };
    
    // ========================================
    // 3. SMOOTH SCROLL TO TOP
    // ========================================
    const initScrollToTop = () => {
        // Create button if it doesn't exist
        let scrollBtn = document.getElementById('scrollToTop');
        
        if (!scrollBtn) {
            scrollBtn = document.createElement('button');
            scrollBtn.id = 'scrollToTop';
            scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollBtn.className = 'fixed bottom-8 right-8 w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all duration-300 hidden z-40 flex items-center justify-center';
            scrollBtn.style.cssText = 'background-color: var(--color-primary);';
            scrollBtn.setAttribute('aria-label', 'Scroll to top');
            document.body.appendChild(scrollBtn);
        }
        
        // Show/hide on scroll
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.remove('hidden');
            } else {
                scrollBtn.classList.add('hidden');
            }
        });
        
        // Scroll to top on click
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    };
    
    // ========================================
    // 4. LAZY LOAD IMAGES
    // ========================================
    const initLazyLoad = () => {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });
            
            const lazyImages = document.querySelectorAll('img[loading="lazy"]');
            lazyImages.forEach(img => imageObserver.observe(img));
        }
    };
    
    // ========================================
    // 5. APPLY THEME COLORS FROM SETTINGS
    // ========================================
    window.applyThemeColors = (colors) => {
        if (!colors) return;
        
        const root = document.documentElement;
        
        if (colors.primary) root.style.setProperty('--color-primary', colors.primary);
        if (colors.secondary) root.style.setProperty('--color-secondary', colors.secondary);
        if (colors.accent) root.style.setProperty('--color-accent', colors.accent);
        if (colors.text) root.style.setProperty('--color-text', colors.text);
        if (colors.background) root.style.setProperty('--color-background', colors.background);
    };
    
    // ========================================
    // 6. COPY TO CLIPBOARD UTILITY
    // ========================================
    window.copyToClipboard = (text) => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✓ Link berhasil disalin!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    };
    
    const fallbackCopyToClipboard = (text) => {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            alert('✓ Link berhasil disalin!');
        } catch (err) {
            console.error('Failed to copy:', err);
            alert('✗ Gagal menyalin link');
        }
        
        document.body.removeChild(textArea);
    };
    
    // ========================================
    // 7. FORM VALIDATION HELPER
    // ========================================
    window.validateEmail = (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };
    
    window.validatePhone = (phone) => {
        const re = /^[\d\s\-\+\(\)]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    };
    
    // ========================================
    // 8. LOADING SPINNER UTILITY
    // ========================================
    window.showLoading = (message = 'Loading...') => {
        let loader = document.getElementById('globalLoader');
        
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'globalLoader';
            loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
            loader.innerHTML = `
                <div class="bg-white rounded-lg p-8 text-center">
                    <div class="spinner mx-auto mb-4"></div>
                    <p class="text-gray-700 font-medium">${message}</p>
                </div>
            `;
            document.body.appendChild(loader);
        } else {
            loader.querySelector('p').textContent = message;
            loader.classList.remove('hidden');
        }
        
        document.body.style.overflow = 'hidden';
    };
    
    window.hideLoading = () => {
        const loader = document.getElementById('globalLoader');
        if (loader) {
            loader.classList.add('hidden');
            document.body.style.overflow = '';
        }
    };
    
    // ========================================
    // 9. NOTIFICATION UTILITY
    // ========================================
    window.showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 max-w-sm bg-white border-l-4 rounded-lg shadow-lg p-4 z-50 animate-slideIn`;
        
        const colors = {
            success: 'border-green-500',
            error: 'border-red-500',
            warning: 'border-yellow-500',
            info: 'border-blue-500'
        };
        
        const icons = {
            success: 'fa-check-circle text-green-500',
            error: 'fa-exclamation-circle text-red-500',
            warning: 'fa-exclamation-triangle text-yellow-500',
            info: 'fa-info-circle text-blue-500'
        };
        
        notification.classList.add(colors[type] || colors.info);
        
        notification.innerHTML = `
            <div class="flex items-start">
                <i class="fas ${icons[type] || icons.info} text-xl mr-3 mt-1"></i>
                <div class="flex-1">
                    <p class="text-gray-800">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    };
    
    // ========================================
    // 10. DEBOUNCE UTILITY
    // ========================================
    window.debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    // ========================================
    // 11. DROPDOWN CLICK TOGGLE (Untuk Mobile)
    // ========================================
    const initDropdownToggle = () => {
        const dropdowns = document.querySelectorAll('.dropdown');

        dropdowns.forEach(dropdown => {
            const parentLink = dropdown.querySelector('.nav-link');
            
            if (parentLink) {
                // Mengubah perilaku link parent menjadi non-navigasi dan toggle
                parentLink.addEventListener('click', (e) => {
                    // Mencegah navigasi ke halaman lain untuk semua dropdown parent (Desktop & Mobile)
                    e.preventDefault(); 
                    
                    // Toggle class 'open' hanya untuk mobile
                    if (window.innerWidth <= 768) {
                        dropdown.classList.toggle('open');
                    }
                });
            }
        });
    };
    
    // ========================================
    // 12. INITIALIZE ALL ON DOM READY
    // ========================================
    const init = () => {
        initMobileMenu();
        initSearchModalClickOutside();
        initScrollToTop();
        initLazyLoad();
        initDropdownToggle();
        
        // Initialize AOS if loaded
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 800,
                once: true,
                offset: 100,
                easing: 'ease-in-out'
            });
        }
        
        console.log('✓ BTIKP Kalsel - Custom JS Initialized');
    };
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // ========================================
    // 13. ADDITIONAL CSS ANIMATIONS
    // ========================================
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .animate-slideIn {
            animation: slideIn 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
    
})();

// ========================================
// 14. EXPORT FOR EXTERNAL USE (FIXED)
// ========================================
window.BTIKPKalsel = {
    // FIX: Export openSearchModal dan closeSearchModal
    openSearchModal: window.openSearchModal,
    closeSearchModal: window.closeSearchModal,
    
    showLoading: window.showLoading,
    hideLoading: window.hideLoading,
    showNotification: window.showNotification,
    applyThemeColors: window.applyThemeColors,
    copyToClipboard: window.copyToClipboard,
    validateEmail: window.validateEmail,
    validatePhone: window.validatePhone,
    debounce: window.debounce
};