/**
 * Simple Page Transition & Smooth Scroll Handler (CRUD safe + Konfirmasi)
 * v3.6 (Fixed Scroll To Top) - Perbaikan tombol Scroll To Top yang hilang
 */
(function() {
    'use strict';

    // ========= SMOOTH SCROLL =========
    function smoothScrollTo(element, duration = 700) {
        const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        let startTime = null;
        function easeInOut(t) {
            return t < .5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        }
        function animate(currentTime) {
            if (startTime === null) startTime = currentTime;
            let timeElapsed = currentTime - startTime;
            let progress = Math.min(timeElapsed / duration, 1);
            let ease = easeInOut(progress);
            window.scrollTo(0, startPosition + distance * ease);
            if (timeElapsed < duration) requestAnimationFrame(animate);
        }
        requestAnimationFrame(animate);
    }
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                let href = this.getAttribute('href');
                if (!href || href === '#') return;
                let target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    smoothScrollTo(target);
                    if (history.pushState) history.pushState(null, null, href);
                }
            });
        });
    }

    // ========= PAGE TRANSITIONS (CRUD & confirm safe) =========
    function initPageTransitions() {
        const internalLinks = document.querySelectorAll(
            'a[href]:not([href^="#"]):not([href^="http"]):not([target="_blank"])'
        );
        internalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                let href = this.getAttribute('href');
                
                const isExcluded = 
                    e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1 ||
                    this.hasAttribute('data-confirm') || 
                    this.classList.contains('no-transition') || 
                    this.hasAttribute('data-confirm-delete') || 
                    this.hasAttribute('data-confirm-logout') || 
                    this.hasAttribute('data-confirm-restore') || 
                    (this.getAttribute('onclick') || '').toLowerCase().match(/confirm|swal|bootbox/) || 
                    (href && href.match(/\/(edit|add|tambah|create|view|show)\.php/i));
                
                if (isExcluded) {
                    return; 
                }
                
                let pageDelay = 300; 

                e.preventDefault();
                document.body.classList.add('page-transitioning');
                setTimeout(() => {
                    window.location.href = href;
                }, pageDelay);
            });
        });
    }

    // ========= SCROLL TO TOP (FIXED) =========
    function initScrollToTop() {
        // Cek jika tombol sudah ada
        let scrollBtn = document.getElementById('scrollToTop');
        
        // Jika tombol belum ada, buat tombol baru
        if (!scrollBtn) {
            scrollBtn = document.createElement('button');
            scrollBtn.id = 'scrollToTop';
            scrollBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
            scrollBtn.className = 'scroll-to-top-btn';
            scrollBtn.setAttribute('aria-label', 'Scroll to top');
            document.body.appendChild(scrollBtn);
        }

        // Fungsi untuk menampilkan/menyembunyikan tombol
        function toggleScrollButton() {
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollPosition > 300) {
                scrollBtn.classList.add('show');
                scrollBtn.classList.remove('hide');
            } else {
                scrollBtn.classList.add('hide');
                scrollBtn.classList.remove('show');
            }
        }
        
        // Event listener untuk scroll
        window.addEventListener('scroll', toggleScrollButton);
        
        // Event listener untuk klik
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            
            // Tambahkan animasi rotasi saat diklik
            this.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                this.style.transform = '';
            }, 500);
        });
        
        // Inisialisasi status tombol
        toggleScrollButton();
    }

    // ========= LAZY ANIMATION =========
    function initLazyAnimations() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const observerOpts = { threshold: .15, rootMargin: '0px 0px -60px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOpts);
        document.querySelectorAll('.lazy-animate').forEach(el => observer.observe(el));
    }

    // ========= BACK BUTTON FIX =========
    function handleBackButton() {
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.body.classList.remove('page-transitioning');
                let main = document.getElementById('main-content');
                if (main) { 
                    main.style.animation = 'none'; 
                    setTimeout(() => main.style.animation = '', 10); 
                }
            }
        });
    }

    // ========= INISIALISASI =========
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        initSmoothScroll();
        initPageTransitions();
        initScrollToTop();
        initLazyAnimations();
        handleBackButton();
    }
    
    init();
})();