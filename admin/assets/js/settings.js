/**
 * settings.js
 *
 * Versi ini DIDESAIN UNTUK BEKERJA DENGAN SISTEM BTIKPNotification KUSTOM.
 *
 * PERBAIKAN:
 * - Menambahkan logika "REVERT" (mengembalikan).
 * - Setelah cooldown berakhir (onConfirm) atau saat klik Batal (onCancel),
 * stylesheet akan dikembalikan ke 'CURRENT_THEME' (tema yang tersimpan).
 *
 * ASUMSI:
 * 1. File 'notifications.js' (atau variannya) SUDAH dimuat SEBELUM file ini.
 * 2. Variabel global 'ADMIN_URL' dan 'CURRENT_THEME' sudah didefinisikan di 'settings.php'.
 */

"use strict";

(function () {
    
    /**
     * ------------------------------------------------------------------------
     * Helper Function: Memuat Tema CSS Notifikasi
     * ------------------------------------------------------------------------
     */
    function loadNotificationTheme(themeName) {
        if (typeof ADMIN_URL === 'undefined') {
            console.error('Global variable ADMIN_URL is not defined.');
            return Promise.reject('ADMIN_URL not defined');
        }

        // Peta dari 'key' di PHP ke 'nama file' CSS Anda
        const themeMap = {
            'alecto-final-blow': 'notifications.css',
            'an-eye-for-an-eye': 'notifications_an_eye_for_an_eye.css',
            'throne-of-ruin': 'notifications_throne.css',
            'hoki-crossbow-of-tang': 'notifications_crossbow.css',
            'death-sonata': 'notifications_death_sonata.css'
        };

        const cssFile = themeMap[themeName] || 'notifications.css';
        const themeId = 'notification-theme-style';
        const themePath = `${ADMIN_URL}assets/css/${cssFile}`;

        let linkElement = document.getElementById(themeId);

        // Langsung resolve jika CSS yang diminta sudah aktif
        if (linkElement && linkElement.getAttribute('href') === themePath) {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            if (linkElement) {
                linkElement.setAttribute('href', themePath);
            } else {
                linkElement = document.createElement('link');
                linkElement.setAttribute('rel', 'stylesheet');
                linkElement.setAttribute('type', 'text/css');
                linkElement.setAttribute('id', themeId);
                linkElement.setAttribute('href', themePath);
                document.head.appendChild(linkElement);
            }
            linkElement.onload = () => resolve();
            linkElement.onerror = () => {
                console.error(`Failed to load theme CSS: ${themePath}`);
                reject(`Failed to load ${cssFile}`);
            };
        });
    }

    /**
     * ------------------------------------------------------------------------
     * Logika Cooldown
     * ------------------------------------------------------------------------
     */
    let isPreviewOnCooldown = false;
    const COOLDOWN_SECONDS = 8;

    /**
     * ------------------------------------------------------------------------
     * Definisi BTIKPSettings Object
     * ------------------------------------------------------------------------
     */
    window.BTIKPSettings = {

        /**
         * Fungsi untuk menampilkan preview tema notifikasi dan alert
         * @param {string} themeName - Nama file tema (cth: 'alecto-final-blow')
         * @param {string} themeLabel - Nama tema yang akan ditampilkan (cth: 'Alecto: Final Blow')
         */
        previewTheme: function (themeName, themeLabel) {
            
            if (typeof notify === 'undefined' || typeof notify.confirm === 'undefined') {
                console.error("Sistem 'notify' kustom (BTIKPNotification) belum dimuat.");
                alert("Error: Sistem notifikasi kustom belum dimuat.");
                return;
            }

            // 1. Cek Cooldown
            if (isPreviewOnCooldown) {
                notify.warning(`Harap tunggu ${COOLDOWN_SECONDS} detik sebelum preview lagi.`);
                return;
            }

            // 2. Nonaktifkan tombol sementara
            const btnPreview = document.getElementById('btnPreview');
            const originalBtnText = btnPreview.innerHTML;
            btnPreview.disabled = true;
            btnPreview.innerHTML = `<i class="bi bi-arrow-repeat"></i> Memuat...`;
            
            // 3. Muat CSS Tema (Preview)
            loadNotificationTheme(themeName)
                .then(() => {
                    // 4. CSS Preview berhasil dimuat, panggil ALERT KONFIRMASI
                    notify.confirm({
                        type: 'info',
                        title: `Preview Tema: ${themeLabel}`,
                        message: `Anda akan melihat preview untuk tema <strong>${themeLabel}</strong>.<br><br>Gaya alert ini dan notifikasi (jika Anda klik 'Lanjutkan') akan menggunakan tema yang dipilih.`,
                        confirmText: 'Ya, Lanjutkan',
                        cancelText: 'Batal',
                        
                        onConfirm: () => {
                            // 5. PENGGUNA KLIK "YA" -> Mulai Cooldown
                            isPreviewOnCooldown = true;
                            
                            let countdown = COOLDOWN_SECONDS;
                            btnPreview.innerHTML = `<i class="bi bi-stopwatch"></i> Cooldown (${countdown}s)`;
                            
                            const interval = setInterval(() => {
                                countdown--;
                                if (countdown > 0) {
                                    btnPreview.innerHTML = `<i class="bi bi-stopwatch"></i> Cooldown (${countdown}s)`;
                                } else {
                                    clearInterval(interval);
                                }
                            }, 1000);

                            // Atur timer untuk mengakhiri cooldown
                            setTimeout(() => {
                                isPreviewOnCooldown = false;
                                btnPreview.disabled = false;
                                btnPreview.innerHTML = originalBtnText;

                                // ==============================================
                                // == [LOGIKA REVERT] SAAT COOLDOWN SELESAI ==
                                // Kembalikan CSS ke tema yang tersimpan
                                loadNotificationTheme(CURRENT_THEME);
                                // ==============================================

                            }, COOLDOWN_SECONDS * 1000);

                            // 6. Tampilkan Rangkaian TOAST (masih dalam gaya preview)
                            setTimeout(() => { notify.success('Ini adalah notifikasi <strong>Success</strong>.'); }, 500);
                            setTimeout(() => { notify.error('Ini adalah notifikasi <strong>Error</strong>.'); }, 1500);
                            setTimeout(() => { notify.warning('Ini adalah notifikasi <strong>Warning</strong>.'); }, 2500);
                            setTimeout(() => { notify.info('Ini adalah notifikasi <strong>Info</strong>.'); }, 3500);
                        },

                        onCancel: () => {
                            // 7. PENGGUNA KLIK "BATAL"
                            isPreviewOnCooldown = false;
                            btnPreview.disabled = false;
                            btnPreview.innerHTML = originalBtnText;

                            // ==============================================
                            // == [LOGIKA REVERT] SAAT BATAL ==
                            // Kembalikan CSS ke tema yang tersimpan
                            loadNotificationTheme(CURRENT_THEME);
                            // ==============================================
                        }
                    });
                })
                .catch((errorMsg) => {
                    // 8. GAGAL MEMUAT CSS PREVIEW
                    notify.error(`Gagal memuat preview: ${errorMsg}`);
                    isPreviewOnCooldown = false;
                    btnPreview.disabled = false;
                    btnPreview.innerHTML = originalBtnText;

                    // ==============================================
                    // == [LOGIKA REVERT] SAAT GAGAL LOAD ==
                    // Kembalikan CSS ke tema yang tersimpan (aman)
                    loadNotificationTheme(CURRENT_THEME);
                    // ==============================================
                });
        }
    };

    // Muat tema CSS yang sedang aktif (CURRENT_THEME) saat halaman dimuat
    // Ini memastikan halaman selalu dimulai dengan gaya yang benar.
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof CURRENT_THEME !== 'undefined') {
            loadNotificationTheme(CURRENT_THEME);
        } else {
            console.warn('Variabel CURRENT_THEME tidak didefinisikan. Menggunakan tema default.');
            loadNotificationTheme('alecto-final-blow');
        }
    });

})(); // Akhir dari IIFE