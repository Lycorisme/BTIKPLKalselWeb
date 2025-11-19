/**
 * settings.js
 *
 * Versi ini DIDESAIN UNTUK BEKERJA DENGAN SISTEM BTIKPNotification KUSTOM.
 *
 * PERBAIKAN (Bug Alecto):
 * - Fungsi loadNotificationTheme() diubah.
 * - Sekarang MENGHAPUS <link> lama dan MEMBUAT <link> baru,
 * bukan hanya mengganti href. Ini MENCEGAH konflik cache browser
 * saat mem-preview tema default (Alecto).
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

        const themeMap = {
            'alecto-final-blow': 'notifications.css',
            'an-eye-for-an-eye': 'notifications_an_eye_for_an_eye.css',
            'throne-of-ruin': 'notifications_throne.css',
            'hoki-crossbow-of-tang': 'notifications_crossbow.css',
            'death-sonata': 'notifications_death_sonata.css'
        };

        const cssFile = themeMap[themeName] || 'notifications.css';
        const themeId = 'notification-theme-style'; // ID unik untuk link kita
        const themePath = `${ADMIN_URL}assets/css/${cssFile}`;

        // Cari <link> yang lama (jika ada)
        let oldLink = document.getElementById(themeId);

        // ==================================================================
        // === PERBAIKAN LOGIKA 'HAPUS-DAN-BUAT-BARU' DIMULAI DI SINI ===
        // ==================================================================
        
        return new Promise((resolve, reject) => {
            
            // Jika link lama ada DAN href-nya sudah benar, kita tidak perlu
            // melakukan apa-apa. Langsung resolve.
            if (oldLink && oldLink.getAttribute('href') === themePath) {
                return resolve();
            }

            // Jika link lama ada (apapun href-nya), HAPUS dari DOM.
            if (oldLink) {
                oldLink.parentNode.removeChild(oldLink);
            }

            // Buat elemen <link> BARU
            const newLink = document.createElement('link');
            newLink.setAttribute('rel', 'stylesheet');
            newLink.setAttribute('type', 'text/css');
            newLink.setAttribute('id', themeId); // Beri ID yang sama agar bisa ditemukan lagi
            newLink.setAttribute('href', themePath);
            
            // Pasang listener HANYA di link baru
            newLink.onload = () => resolve();
            
            newLink.onerror = () => {
                console.error(`Failed to load theme CSS: ${themePath}`);
                reject(`Failed to load ${cssFile}`);
            };

            // Tambahkan link baru ke <head>
            document.head.appendChild(newLink);
        });
        // ==================================================================
        // === PERBAIKAN LOGIKA SELESAI ===
        // ==================================================================
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

            if (isPreviewOnCooldown) {
                notify.warning(`Harap tunggu ${COOLDOWN_SECONDS} detik sebelum preview lagi.`);
                return;
            }

            const btnPreview = document.getElementById('btnPreview');
            const originalBtnText = btnPreview.innerHTML;
            btnPreview.disabled = true;
            
            // ================================================================
            // == [PERUBAHAN 1] Menggunakan Mazer Spinner untuk "Memuat..."
            // ================================================================
            btnPreview.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...`;
            // ================================================================

            loadNotificationTheme(themeName)
                .then(() => {
                    notify.confirm({
                        type: 'info',
                        title: `Preview Tema: ${themeLabel}`,
                        message: `Anda akan melihat preview untuk tema <strong>${themeLabel}</strong>.<br><br>Gaya alert ini dan notifikasi (jika Anda klik 'Lanjutkan') akan menggunakan tema yang dipilih.`,
                        confirmText: 'Ya, Lanjutkan',
                        cancelText: 'Batal',
                        
                        onConfirm: () => {
                            isPreviewOnCooldown = true;
                            
                            let countdown = COOLDOWN_SECONDS;
                            
                            // ================================================================
                            // == [PERUBAHAN 2] Menggunakan Mazer Spinner untuk "Cooldown"
                            // ================================================================
                            btnPreview.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cooldown (${countdown}s)`;
                            // ================================================================

                            const interval = setInterval(() => {
                                countdown--;
                                if (countdown > 0) {
                                    // ================================================================
                                    // == [PERUBAHAN 3] Update Teks Cooldown dengan Mazer Spinner
                                    // ================================================================
                                    btnPreview.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cooldown (${countdown}s)`;
                                } else {
                                    clearInterval(interval);
                                }
                            }, 1000);

                            setTimeout(() => {
                                isPreviewOnCooldown = false;
                                btnPreview.disabled = false;
                                btnPreview.innerHTML = originalBtnText; // Restore ke teks asli
                                // REVERT: Kembalikan ke tema yang tersimpan
                                loadNotificationTheme(CURRENT_THEME);
                            }, COOLDOWN_SECONDS * 1000);

                            // Tampilkan Rangkaian TOAST
                            setTimeout(() => { notify.success('Ini adalah notifikasi <strong>Success</strong>.'); }, 500);
                            setTimeout(() => { notify.error('Ini adalah notifikasi <strong>Error</strong>.'); }, 1500);
                            setTimeout(() => { notify.warning('Ini adalah notifikasi <strong>Warning</strong>.'); }, 2500);
                            setTimeout(() => { notify.info('Ini adalah notifikasi <strong>Info</strong>.'); }, 3500);
                        },

                        onCancel: () => {
                            isPreviewOnCooldown = false;
                            btnPreview.disabled = false;
                            btnPreview.innerHTML = originalBtnText; // Restore ke teks asli
                            // REVERT: Kembalikan ke tema yang tersimpan
                            loadNotificationTheme(CURRENT_THEME);
                        }
                    });
                })
                .catch((errorMsg) => {
                    notify.error(`Gagal memuat preview: ${errorMsg}`);
                    isPreviewOnCooldown = false;
                    btnPreview.disabled = false;
                    btnPreview.innerHTML = originalBtnText; // Restore ke teks asli
                    // REVERT: Kembalikan ke tema yang tersimpan
                    loadNotificationTheme(CURRENT_THEME);
                });
        }
    };

    // Muat tema CSS yang sedang aktif (CURRENT_THEME) saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof CURRENT_THEME !== 'undefined') {
            loadNotificationTheme(CURRENT_THEME);
        } else {
            console.warn('Variabel CURRENT_THEME tidak didefinisikan. Menggunakan tema default.');
            loadNotificationTheme('alecto-final-blow');
        }
    });

})(); // Akhir dari IIFE