<?php
session_start();

require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';
require_once '../core/RateLimiter.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(ADMIN_URL);
}

// Get settings
$siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
$siteLogo = getSetting('site_logo');
$siteLogoText = getSetting('site_logo_text', 'BTIKP KALSEL');
$showLogoText = getSetting('site_logo_show_text', '1');
$siteFavicon = getSetting('site_favicon');

// Load background settings
$bgType = getSetting('login_background_type', 'gradient');
$bgImage = getSetting('login_background_image');
$bgGradient = getSetting('login_background_gradient', 'purple-pink');
$bgColor = getSetting('login_background_color', '#667eea');

// Load overlay text for background
$bgOverlayText = trim(getSetting('login_background_overlay_text', ''));

// Logika untuk memuat file Notifikasi/Alert dinamis
$currentTheme = getSetting('notification_alert_theme', 'alecto-final-blow');

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
        $notificationCssFile = 'notifications.css';
        $notificationJsFile = 'notifications.js';
        break;
}

$validator = null;
$alertJS = "";

// Initialize remembered email/password from cookies
$rememberedEmail = $_COOKIE['remember_email'] ?? '';
$rememberedPassword = $_COOKIE['remember_password'] ?? '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Initialize validator
    $validator = new Validator($_POST);
    
    // Rate Limiting Check
    $rateLimiter = new RateLimiter();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $rateCheck = $rateLimiter->check($ipAddress, 'login', 5, 15);
    
    if (!$rateCheck['allowed']) {
        // User is BLOCKED
        $alertJS .= "notify.error('" . addslashes($rateCheck['message']) . "', 5000);";
    } else {
        // Rate limit OK - Proceed with login
        $validator->required('email', 'Email');
        $validator->required('password', 'Password');
        $validator->email('email', 'Email');
        
        if ($validator->passes()) {
            try {
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    // User not found - Record attempt
                    $rateLimiter->record($ipAddress, 'login', 15);
                    $rateCheckAfter = $rateLimiter->check($ipAddress, 'login', 5, 15);
                    
                    $errorMsg = 'Email tidak ditemukan atau akun belum aktif.';
                    if ($rateCheckAfter['message']) {
                        $errorMsg .= ' ' . $rateCheckAfter['message'];
                    }
                    
                    $validator->addError('general', $errorMsg);
                    
                } elseif (password_verify($password, $user['password'])) {
                    // Login SUCCESS
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_photo'] = $user['photo'];

                    $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    logActivity('LOGIN', 'User login ke sistem', 'users', $user['id']);
                    
                    if ($remember) {
                        setcookie('remember_email', $email, time() + (86400 * 30), "/");
                        setcookie('remember_password', $password, time() + (86400 * 30), "/");
                    } else {
                        setcookie('remember_email', '', time() - 3600, "/");
                        setcookie('remember_password', '', time() - 3600, "/");
                    }

                    $alertJS .= "notify.success('Selamat datang, " . addslashes($user['name']) . "!');";
                    $alertJS .= "setTimeout(() => { window.location.href = '" . ADMIN_URL . "'; }, 1500);";
                    
                } else {
                    // Password WRONG - Record attempt
                    $rateLimiter->record($ipAddress, 'login', 15);
                    $rateCheckAfter = $rateLimiter->check($ipAddress, 'login', 5, 15);
                    
                    $errorMsg = 'Email atau password salah.';
                    if ($rateCheckAfter['message']) {
                        $errorMsg .= ' ' . $rateCheckAfter['message'];
                    }
                    
                    $validator->addError('general', $errorMsg);
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $validator->addError('general', 'Terjadi kesalahan sistem');
            }
        } else {
            // Validation failed
            foreach (['email','password'] as $field) {
                if ($validator->getError($field)) {
                    $alertJS .= "notify.warning('".addslashes($validator->getError($field))."', 2500);";
                }
            }
        }
    }
    
    // Display general error if exists
    if ($validator && $validator->getError('general')) {
        $alertJS .= "notify.error('".addslashes($validator->getError('general'))."', 4000);";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - <?= htmlspecialchars($siteName) ?></title>
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
                            <img src="<?= uploadUrl($siteLogo) ?>" alt="Logo" style="height: 50px;" class="me-2" />
                            <?php endif; ?>
                            <?php if ($showLogoText == '1'): ?>
                            <span style="font-size: 1.5rem; font-weight: 600; color: var(--bs-primary);">
                                <?= htmlspecialchars($siteLogoText) ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>

                    <h1 class="auth-title">Log in.</h1>
                    <p class="auth-subtitle mb-5">Masukkan email dan password Anda untuk login ke sistem.</p>

                    <form method="POST" novalidate autocomplete="off">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="email" name="email"
                                class="form-control form-control-xl <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>"
                                placeholder="Email"
                                value="<?= htmlspecialchars($_POST['email'] ?? $rememberedEmail) ?>"
                                required autofocus autocomplete="email" />
                            <div class="form-control-icon">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>

                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" name="password"
                                class="form-control form-control-xl <?= $validator && $validator->getError('password') ? 'is-invalid' : '' ?>"
                                placeholder="Password"
                                value="<?= htmlspecialchars($rememberedPassword) ?>"
                                required autocomplete="current-password" />
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>

                        <div class="form-check form-check-lg d-flex align-items-end mb-4">
                            <input class="form-check-input me-2" type="checkbox" name="remember" id="flexCheckDefault"
                                <?= $rememberedEmail ? 'checked' : '' ?> />
                            <label class="form-check-label text-gray-600" for="flexCheckDefault">
                                Ingat saya
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-3">
                            <i class="bi bi-box-arrow-in-right"></i> Log in
                        </button>
                    </form>

                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">
                            Lupa password?
                            <a href="<?= ADMIN_URL ?>forgot-password.php" class="font-bold">Reset Password</a>
                        </p>
                        <p>
                            <a href="<?= ADMIN_URL ?>register.php" class="font-bold">
                                Belum punya akun? Daftar di sini
                            </a>
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
