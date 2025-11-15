<?php
/**
 * Admin Footer Template
 * With Dynamic Copyright from Settings & Dynamic Notification Theme Loading
 */

// Get copyright from settings
$copyright = getSetting('site_copyright', 'Â© {year} BTIKP Kalimantan Selatan. All Rights Reserved.');
$copyright = str_replace('{year}', date('Y'), $copyright);

// Get site name
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');

// ===================================
// DYNAMIC NOTIFICATION THEME LOADING
// ===================================
$notification_theme = getSetting('notification_alert_theme', 'alecto-final-blow');

// Map theme names to JS file names
$themeFiles = [
    'alecto-final-blow' => 'notifications.js',
    'an-eye-for-an-eye' => 'notifications_an_eye_for_an_eye.js',
    'throne-of-ruin' => 'notifications_throne.js',
    'hoki-crossbow-of-tang' => 'notifications_crossbow.js',
    'death-sonata' => 'notifications_death_sonata.js'
];

// Get current theme JS file or fallback to default
$themeJsFile = $themeFiles[$notification_theme] ?? $themeFiles['alecto-final-blow'];
?>
            </div> <!-- End #main-content -->
            
            <!-- Footer -->
            <footer>
                <div class="container-fluid">
                    <div class="footer clearfix mb-0 text-muted">
                        <div class="float-start">
                            <p><?= $copyright ?></p>
                        </div>
                        <div class="float-end">
                            <p>Powered by <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                                <a href="<?= BASE_URL ?>" target="_blank"><?= $siteName ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Mazer Core JS -->
    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
    <script src="<?= ADMIN_URL ?>assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= ADMIN_URL ?>assets/compiled/js/app.js"></script>
    
    <!-- DYNAMIC NOTIFICATION THEME JAVASCRIPT -->
    <script src="<?= ADMIN_URL ?>assets/js/<?= $themeJsFile ?>?v=<?= time() ?>" 
            data-notification-theme="<?= $notification_theme ?>"></script>
    
    <!-- Auto show alert from PHP session (using custom toast) -->
    <?php if ($alert = getAlert()): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for notify object to be loaded
        if (typeof notify !== 'undefined') {
            notify.<?= $alert['type'] === 'danger' ? 'error' : $alert['type'] ?>('<?= addslashes($alert['message']) ?>');
        } else {
            // Fallback if notify not loaded yet
            console.error('Notify object not loaded. Alert message: <?= addslashes($alert['message']) ?>');
        }
    });
    </script>
    <?php endif; ?>
    
    <!-- Additional Scripts from Pages -->
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>

</body>
</html>