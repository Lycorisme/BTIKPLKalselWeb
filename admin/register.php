<?php
session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';

if (isLoggedIn()) {
    redirect(ADMIN_URL);
}

$siteName     = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteLogo     = getSetting('site_logo');
$siteLogoText = getSetting('site_logo_text', 'BTIKP KALSEL');
$showLogoText = getSetting('site_logo_show_text', '1');
$siteFavicon  = getSetting('site_favicon');
$bgType       = getSetting('login_background_type', 'gradient');
$bgImage      = getSetting('login_background_image');
$bgGradient   = getSetting('login_background_gradient', 'purple-pink');
$bgColor      = getSetting('login_background_color', '#667eea');
$bgOverlayText = trim(getSetting('login_background_overlay_text', ''));

// ==================================================================
// == [PERUBAHAN DIMULAI DI SINI] ==
// Logika untuk memuat file Notifikasi/Alert dinamis
// ==================================================================

// 1. Dapatkan tema yang sedang aktif dari database
$currentTheme = getSetting('notification_alert_theme', 'alecto-final-blow');

// 2. Tentukan nama file CSS dan JS berdasarkan tema yang aktif
switch ($currentTheme) {
    case 'an-eye-for-an-eye':
        $notificationCssFile = 'notifications_an_eye_for_an_eye.css';
        $notificationJsFile = 'notifications_an_eye_for_an_eye.js';
        break;
    case 'throne-of-ruin':
        $notificationCssFile = 'notifications_throne.css';
        $notificationJsFile = 'notifications_throne.js';
        break;
    case 'hoki-crossbow-of-tang':
        $notificationCssFile = 'notifications_crossbow.css';
        $notificationJsFile = 'notifications_crossbow.js';
        break;
    case 'death-sonata':
        $notificationCssFile = 'notifications_death_sonata.css';
        $notificationJsFile = 'notifications_death_sonata.js';
        break;
    case 'alecto-final-blow':
    default:
        $notificationCssFile = 'notifications.css'; // File default (Alecto)
        $notificationJsFile = 'notifications.js';  // File default (Alecto)
        break;
}
// ==================================================================
// == [PERUBAHAN SELESAI] ==
// ==================================================================

$validator   = null;
$alertJS     = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name             = clean($_POST['name'] ?? '');
    $email            = clean($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $validator = new Validator($_POST);
    $validator->required('name', 'Nama');
    $validator->required('email', 'Email');
    $validator->email('email', 'Email');
    $validator->required('password', 'Password');
    $validator->required('password_confirm', 'Konfirmasi Password');
    $validator->match('password_confirm', 'password', 'Konfirmasi Password tidak cocok dengan Password');

    if ($validator->passes()) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $alertJS .= "notify.error('Email sudah terdaftar.');";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                // is_active=3 = akun baru menunggu approval
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role, is_active, created_at) VALUES (?, ?, ?, 'editor', 3, NOW())");
                $stmt->execute([$name, $email, $password_hash]);
                $alertJS .= "
                notify.success('Pendaftaran berhasil! Tunggu persetujuan admin.', 5000);
                setTimeout(function() {
                    notify.alert({
                        type: 'info',
                        title: 'Registrasi Berhasil',
                        message: 'Akun Anda telah didaftarkan dengan status <b>menunggu persetujuan admin</b>.<br>Silakan login jika sudah di-ACC.',
                        confirmText: 'Ke Login',
                        onConfirm: function() { window.location.href = '".ADMIN_URL."login.php'; }
                    });
                }, 600);
                ";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $alertJS .= "notify.error('Terjadi kesalahan sistem.');";
        }
    } else {
        foreach (['name', 'email', 'password', 'password_confirm'] as $field)
            if ($validator->getError($field)) $alertJS .= "notify.warning('".addslashes($validator->getError($field))."', 2500);";
        if ($validator->getError('general')) $alertJS .= "notify.error('".addslashes($validator->getError('general'))."', 4000);";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - <?= htmlspecialchars($siteName) ?></title>
    <?php if ($siteFavicon): ?>
    <link rel="icon" type="image/png" href="<?= uploadUrl($siteFavicon) ?>" />
    <?php endif; ?>

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/app-dark.css" />
    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/compiled/css/auth.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />

    <link rel="stylesheet" href="<?= ADMIN_URL ?>assets/css/<?= $notificationCssFile ?>?v=<?= time() ?>" />
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
                            <img src="<?= uploadUrl($siteLogo) ?>" alt="Logo" style="height:50px" class="me-2" />
                            <?php endif; ?>
                            <?php if ($showLogoText == '1'): ?>
                            <span style="font-size: 1.5rem; font-weight: 600; color: var(--bs-primary)">
                                <?= htmlspecialchars($siteLogoText) ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <h1 class="auth-title">Register.</h1>
                    <p class="auth-subtitle mb-5">Buat akun baru untuk login ke sistem.</p>

                    <form method="POST" novalidate autocomplete="off">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" name="name"
                                class="form-control form-control-xl"
                                placeholder="Nama"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                required autofocus autocomplete="name" />
                            <div class="form-control-icon">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email"
                                class="form-control form-control-xl"
                                placeholder="Email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required autocomplete="email" />
                            <div class="form-control-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password"
                                class="form-control form-control-xl"
                                placeholder="Password"
                                required autocomplete="new-password" />
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password_confirm"
                                class="form-control form-control-xl"
                                placeholder="Konfirmasi Password"
                                required autocomplete="new-password" />
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-person-plus"></i> Daftar
                        </button>
                    </form>

                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">
                            Sudah punya akun?
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
                <div id="auth-right"
                    style="<?= generateBackgroundStyle($bgType, $bgImage, $bgGradient, $bgColor) ?>">
                    <?php if ($bgType === 'image' && !empty($bgOverlayText)): ?>
                    <div class="overlay-text"><?= nl2br(htmlspecialchars($bgOverlayText)) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= ADMIN_URL ?>assets/static/js/components/dark.js"></script>
    
    <script src="<?= ADMIN_URL ?>assets/js/<?= $notificationJsFile ?>?v=<?= time() ?>"></script>
    
    <?php if (!empty($alertJS)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?= $alertJS ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>