<?php
session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';

$siteName   = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteLogo   = getSetting('site_logo');
$siteLogoText = getSetting('site_logo_text', 'BTIKP KALSEL');
$showLogoText = getSetting('site_logo_show_text', '1');
$siteFavicon = getSetting('site_favicon');
$bgType     = getSetting('login_background_type', 'gradient');
$bgImage    = getSetting('login_background_image');
$bgGradient = getSetting('login_background_gradient', 'purple-pink');
$bgColor    = getSetting('login_background_color', '#667eea');
$bgOverlayText = trim(getSetting('login_background_overlay_text', ''));

$validator = null;
$alertJS = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');

    $validator = new Validator($_POST);
    $validator->required('email', 'Email');
    $validator->email('email', 'Email');

    if ($validator->passes()) {
        try {
            $db = Database::getInstance()->getConnection();

            // Cek user berdasarkan email
            $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND deleted_at IS NULL AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate token reset (36 char random)
                $token = bin2hex(random_bytes(18));

                // Insert token ke tabel password_resets, hapus token lama jika ada
                $del = $db->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->execute([$email]);

                $stmt = $db->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$email, $token]);

                // Kirim email reset (gunakan function helper Anda, email template dsb)
                sendPasswordResetEmail($email, $token);

                // Notifikasi sukses
                $alertJS .= "notify.success('Email reset password telah dikirim. Periksa inbox Anda.', 5000);";
                $alertJS .= "notify.alert({ type: 'info', title: 'Permintaan Reset Terkirim', message: 'Silakan cek email Anda untuk instruksi reset password.', confirmText: 'Tutup' });";

            } else {
                $alertJS .= "notify.error('Email tidak ditemukan atau akun belum aktif.');";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $alertJS .= "notify.error('Terjadi kesalahan sistem.');";
        }
    } else {
        // Invalid form input show warnings
        if ($validator->getError('email'))
            $alertJS .= "notify.warning('".addslashes($validator->getError('email'))."');";
        if ($validator->getError('general'))
            $alertJS .= "notify.error('".addslashes($validator->getError('general'))."');";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - <?= htmlspecialchars($siteName) ?></title>

    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Custom Notification Styles -->
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/css/notifications.css?v=<?= time() ?>">
</head>
<body>
    <script src="<?= ADMIN_URL ?>assets/static/js/initTheme.js"></script>

    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <div class="auth-logo mb-4">
                        <a href="<?= BASE_URL ?>" class="d-flex align-items-center">
                            <?php if ($siteLogo): ?>
                                <img src="<?= uploadUrl($siteLogo) ?>" alt="Logo" style="height: 50px;" class="me-2">
                            <?php endif; ?>
                            <?php if ($showLogoText == '1'): ?>
                                <span style="font-size: 1.5rem; font-weight: 600; color: var(--bs-primary);">
                                    <?= htmlspecialchars($siteLogoText) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <h1 class="auth-title">Lupa Password.</h1>
                    <p class="auth-subtitle mb-5">Masukkan email Anda untuk reset password.</p>

                    <form method="POST" novalidate autocomplete="off">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email"
                                class="form-control form-control-xl <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>"
                                placeholder="Email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required autocomplete="email" autofocus>
                            <div class="form-control-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <?php if ($validator && $validator->getError('email')): ?>
                                <div class="invalid-feedback"><?= $validator->getError('email') ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-envelope"></i> Kirim Link Reset
                        </button>
                    </form>

                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">
                            Ingat password? 
                            <a href="<?= ADMIN_URL ?>login.php" class="font-bold">Login di sini</a>
                        </p>
                        <p>
                            <a href="<?= BASE_URL ?>" class="font-bold">
                                <i class="bi bi-arrow-left"></i> Kembali ke Website
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right" style="<?= generateBackgroundStyle($bgType, $bgImage, $bgGradient, $bgColor) ?>">
                    <?php if ($bgType === 'image' && !empty($bgOverlayText)): ?>
                        <div class="overlay-text"><?= nl2br(htmlspecialchars($bgOverlayText)) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
    <script src="<?= ADMIN_URL ?>assets/js/notifications.js?v=<?= time() ?>"></script>
    <?php if (!empty($alertJS)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?= $alertJS ?>
    });
    </script>
    <?php endif; ?>
</body>
</html>
