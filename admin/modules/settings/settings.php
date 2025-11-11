<?php
/**
 * Settings Management - Complete with Logo Text & Copyright
 * WITH CUSTOM NOTIFICATIONS SYSTEM
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';

$pageTitle = 'Pengaturan Website';

// Only admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();
$validator = null;

// Get all settings
$stmt = $db->query("SELECT * FROM settings ORDER BY `group`, `key`");
$allSettings = $stmt->fetchAll();

// Convert to key-value array
$settings = [];
foreach ($allSettings as $setting) {
    $settings[$setting['key']] = $setting['value'];
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($_POST);
    
    // Verify CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $validator->addError('general', 'Invalid CSRF token');
    }
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $updated = 0;
            
            // Process checkbox values
            $checkboxFields = ['site_logo_show_text'];
            foreach ($checkboxFields as $field) {
                if (!isset($_POST[$field])) {
                    $_POST[$field] = '0';
                }
            }
            
            // Process each posted setting
            foreach ($_POST as $key => $value) {
                if ($key === 'csrf_token') continue;
                
                // Clean value
                $cleanValue = is_array($value) ? implode(',', $value) : clean($value);
                
                // Update or insert setting
                $stmt = $db->prepare("
                    INSERT INTO settings (`key`, `value`, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    `value` = VALUES(`value`), 
                    updated_at = NOW()
                ");
                if ($stmt->execute([$key, $cleanValue])) {
                    $updated++;
                }
            }
            
            // Handle file uploads (logo & favicon)
            if (!empty($_FILES['site_logo']['name'])) {
                $logo = $upload->upload($_FILES['site_logo'], 'settings');
                if ($logo) {
                    // Delete old logo
                    $oldLogo = getSetting('site_logo');
                    if ($oldLogo) $upload->delete($oldLogo);
                    
                    setSetting('site_logo', $logo);
                    $updated++;
                }
            }
            
            if (!empty($_FILES['site_favicon']['name'])) {
                $favicon = $upload->upload($_FILES['site_favicon'], 'settings');
                if ($favicon) {
                    // Delete old favicon
                    $oldFavicon = getSetting('site_favicon');
                    if ($oldFavicon) $upload->delete($oldFavicon);
                    
                    setSetting('site_favicon', $favicon);
                    $updated++;
                }
            }
            
            // Handle login background image upload
            if (!empty($_FILES['login_background_image']['name'])) {
                $loginBg = $upload->upload($_FILES['login_background_image'], 'backgrounds');
                if ($loginBg) {
                    $oldBg = getSetting('login_background_image');
                    if ($oldBg) $upload->delete($oldBg);
                    
                    setSetting('login_background_image', $loginBg);
                    $updated++;
                }
            }
            
            // Log activity
            logActivity('UPDATE', "Mengupdate settings website ($updated items)", 'settings');
            
            setAlert('success', "Settings berhasil diupdate ($updated items)");
            redirect(ADMIN_URL . 'modules/settings/settings.php');
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- ERROR HANDLING - Hanya tampilkan jika ada error validator -->
        <?php if ($validator && $validator->getError('general')): ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                notify.error('<?= addslashes($validator->getError('general')) ?>');
            });
            </script>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="settingsForm">
            <?= csrfField() ?>
            
            <div class="row">
                <!-- General Settings -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-gear"></i> Pengaturan Umum
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Site Name -->
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Website</label>
                                <input type="text" name="site_name" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                            </div>
                            
                            <!-- Site Tagline -->
                            <div class="form-group mb-3">
                                <label class="form-label">Tagline/Slogan</label>
                                <input type="text" name="site_tagline" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>">
                            </div>
                            
                            <!-- Site Description -->
                            <div class="form-group mb-3">
                                <label class="form-label">Deskripsi Website</label>
                                <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                <small class="text-muted">Untuk SEO meta description</small>
                            </div>
                            
                            <!-- Keywords -->
                            <div class="form-group mb-3">
                                <label class="form-label">Keywords (SEO)</label>
                                <input type="text" name="site_keywords" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>">
                                <small class="text-muted">Pisahkan dengan koma</small>
                            </div>
                            
                            <!-- Logo -->
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Website</label>
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['site_logo']) ?>" 
                                             alt="Logo" style="max-height: 60px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_logo" class="form-control" accept="image/*">
                                <small class="text-muted">PNG/JPG, max 2MB. Rekomendasi: 200x60px</small>
                            </div>
                            
                            <!-- Logo Text -->
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Text</label>
                                <input type="text" name="site_logo_text" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_logo_text'] ?? 'BTIKP KALSEL') ?>"
                                       placeholder="Text yang muncul di sebelah logo">
                                <small class="text-muted">Akan muncul di sebelah logo di header</small>
                            </div>
                            
                            <!-- Show Logo Text -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="site_logo_show_text" id="site_logo_show_text" 
                                           class="form-check-input" 
                                           value="1" <?= ($settings['site_logo_show_text'] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="site_logo_show_text">
                                        Tampilkan text di sebelah logo
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Favicon -->
                            <div class="form-group mb-3">
                                <label class="form-label">Favicon</label>
                                <?php if (!empty($settings['site_favicon'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['site_favicon']) ?>" 
                                             alt="Favicon" style="max-height: 32px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_favicon" class="form-control" accept="image/*">
                                <small class="text-muted">ICO/PNG, 32x32px atau 64x64px</small>
                            </div>
                            
                            <!-- Copyright Text -->
                            <div class="form-group mb-0">
                                <label class="form-label">Copyright Text</label>
                                <input type="text" name="site_copyright" class="form-control" 
                                       value="<?= htmlspecialchars($settings['site_copyright'] ?? '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.') ?>">
                                <small class="text-muted">Text copyright di footer. Gunakan <code>{year}</code> untuk tahun otomatis.</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-cloud-upload"></i> Pengaturan Upload
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Max Upload Size -->
                            <div class="form-group mb-3">
                                <label class="form-label">Max File Size (MB)</label>
                                <input type="number" name="upload_max_size" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_max_size'] ?? '5') ?>" min="1" max="50">
                            </div>
                            
                            <!-- Allowed Images -->
                            <div class="form-group mb-3">
                                <label class="form-label">Format Gambar Diizinkan</label>
                                <input type="text" name="upload_allowed_images" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_allowed_images'] ?? '') ?>">
                                <small class="text-muted">Pisahkan dengan koma. Contoh: jpg,png,gif</small>
                            </div>
                            
                            <!-- Allowed Docs -->
                            <div class="form-group mb-3">
                                <label class="form-label">Format Dokumen Diizinkan</label>
                                <input type="text" name="upload_allowed_docs" class="form-control" 
                                       value="<?= htmlspecialchars($settings['upload_allowed_docs'] ?? '') ?>">
                                <small class="text-muted">Contoh: pdf,doc,docx,xls,xlsx</small>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="form-group mb-0">
                                <label class="form-label">Items Per Page (Admin)</label>
                                <input type="number" name="items_per_page" class="form-control" 
                                       value="<?= htmlspecialchars($settings['items_per_page'] ?? '10') ?>" min="5" max="100">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact & Social -->
                <div class="col-lg-6">
                    <!-- Contact Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-telephone"></i> Informasi Kontak
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Phone -->
                            <div class="form-group mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="contact_phone" class="form-control" 
                                       value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="contact_email" class="form-control" 
                                       value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="contact_address" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Maps Embed -->
                            <div class="form-group mb-0">
                                <label class="form-label">Google Maps Embed Code</label>
                                <textarea name="contact_maps_embed" class="form-control" rows="3" placeholder="<iframe src=...></iframe>"><?= htmlspecialchars($settings['contact_maps_embed'] ?? '') ?></textarea>
                                <small class="text-muted">Paste iframe embed code dari Google Maps</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-share"></i> Social Media
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Facebook -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-facebook text-primary"></i> Facebook URL
                                </label>
                                <input type="url" name="social_facebook" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>"
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            
                            <!-- Instagram -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-instagram text-danger"></i> Instagram URL
                                </label>
                                <input type="url" name="social_instagram" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>"
                                       placeholder="https://instagram.com/yourprofile">
                            </div>
                            
                            <!-- YouTube -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-youtube text-danger"></i> YouTube URL
                                </label>
                                <input type="url" name="social_youtube" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_youtube'] ?? '') ?>"
                                       placeholder="https://youtube.com/@yourchannel">
                            </div>
                            
                            <!-- Twitter -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-twitter text-info"></i> Twitter/X URL
                                </label>
                                <input type="url" name="social_twitter" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>"
                                       placeholder="https://twitter.com/yourprofile">
                            </div>
                            
                            <!-- TikTok -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-tiktok text-dark"></i> TikTok URL
                                </label>
                                <input type="url" name="social_tiktok" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_tiktok'] ?? '') ?>"
                                       placeholder="https://tiktok.com/@yourprofile">
                            </div>
                            
                            <!-- LinkedIn -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <i class="bi bi-linkedin text-primary"></i> LinkedIn URL
                                </label>
                                <input type="url" name="social_linkedin" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_linkedin'] ?? '') ?>"
                                       placeholder="https://linkedin.com/in/yourprofile">
                            </div>
                            
                            <!-- WhatsApp -->
                            <div class="form-group mb-0">
                                <label class="form-label">
                                    <i class="bi bi-whatsapp text-success"></i> WhatsApp Link/Number
                                </label>
                                <input type="text" name="social_whatsapp" class="form-control" 
                                       value="<?= htmlspecialchars($settings['social_whatsapp'] ?? '') ?>"
                                       placeholder="https://wa.me/628xxxxxxx atau +62 812-xxxx-xxxx">
                                <small class="text-muted">Format: https://wa.me/628xxxxxxxxx (tanpa +, -, atau spasi)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appearance Settings -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-palette"></i> Appearance (Background)
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Login Background Type -->
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Login Page Background</label>
                                
                                <div class="form-check">
                                    <input type="radio" name="login_background_type" id="login_bg_gradient" 
                                           class="form-check-input" value="gradient" 
                                           <?= ($settings['login_background_type'] ?? 'gradient') === 'gradient' ? 'checked' : '' ?>
                                           onchange="toggleBackgroundFields('login')">
                                    <label class="form-check-label" for="login_bg_gradient">
                                        Gradient
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="radio" name="login_background_type" id="login_bg_image" 
                                           class="form-check-input" value="image" 
                                           <?= ($settings['login_background_type'] ?? 'gradient') === 'image' ? 'checked' : '' ?>
                                           onchange="toggleBackgroundFields('login')">
                                    <label class="form-check-label" for="login_bg_image">
                                        Custom Image
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="radio" name="login_background_type" id="login_bg_solid" 
                                           class="form-check-input" value="solid" 
                                           <?= ($settings['login_background_type'] ?? 'gradient') === 'solid' ? 'checked' : '' ?>
                                           onchange="toggleBackgroundFields('login')">
                                    <label class="form-check-label" for="login_bg_solid">
                                        Solid Color
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Login Gradient Preset -->
                            <div id="login_gradient_field" class="form-group mb-3" 
                                 style="display: <?= ($settings['login_background_type'] ?? 'gradient') === 'gradient' ? 'block' : 'none' ?>;">
                                <label class="form-label">Gradient Style</label>
                                <select name="login_background_gradient" class="form-select">
                                    <?php 
                                    $currentGradient = $settings['login_background_gradient'] ?? 'purple-pink';
                                    foreach (getGradientOptions() as $key => $label): 
                                    ?>
                                        <option value="<?= $key ?>" <?= $currentGradient === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Login Image Upload -->
                            <div id="login_image_field" class="form-group mb-3" 
                                 style="display: <?= ($settings['login_background_type'] ?? 'gradient') === 'image' ? 'block' : 'none' ?>;">
                                <label class="form-label">Background Image</label>
                                <?php if (!empty($settings['login_background_image'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= uploadUrl($settings['login_background_image']) ?>" 
                                             alt="Login BG" style="max-height: 100px; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="login_background_image" class="form-control" accept="image/*">
                                <small class="text-muted">JPG/PNG, max 2MB. Rekomendasi: 1920x1080px</small>
                            </div>
                            
                            <!-- Login Solid Color -->
                            <div id="login_color_field" class="form-group mb-3" 
                                 style="display: <?= ($settings['login_background_type'] ?? 'gradient') === 'solid' ? 'block' : 'none' ?>;">
                                <label class="form-label">Background Color</label>
                                <input type="color" name="login_background_color" class="form-control" 
                                       value="<?= htmlspecialchars($settings['login_background_color'] ?? '#667eea') ?>"
                                       style="height: 50px;">
                            </div>
                            
                            <!-- Overlay Text Input -->
                            <div class="form-group mb-3">
                                <label class="form-label fw-bold">Teks Overlay di Background</label>
                                <textarea class="form-control" name="login_background_overlay_text" rows="3" placeholder="Masukkan teks yang akan muncul di atas background"><?= htmlspecialchars($settings['login_background_overlay_text'] ?? '') ?></textarea>
                                <small class="text-muted">Kosongkan jika tidak ingin menampilkan teks overlay</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="button" class="btn btn-primary btn-lg flex-grow-1" onclick="confirmSaveSettings()">
                                    <i class="bi bi-save"></i> 
                                    <span class="d-none d-sm-inline">Simpan Semua Pengaturan</span>
                                    <span class="d-inline d-sm-none">Simpan</span>
                                </button>
                                <button type="button" class="btn btn-secondary btn-lg" onclick="confirmBack()">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<script>
function toggleBackgroundFields(type) {
    if (type === 'login') {
        const bgType = document.querySelector('input[name="login_background_type"]:checked').value;
        
        document.getElementById('login_gradient_field').style.display = bgType === 'gradient' ? 'block' : 'none';
        document.getElementById('login_image_field').style.display = bgType === 'image' ? 'block' : 'none';
        document.getElementById('login_color_field').style.display = bgType === 'solid' ? 'block' : 'none';
    }
}

// Initialize toggle states on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleBackgroundFields('login');
});

/**
 * CUSTOM NOTIFICATIONS IMPLEMENTATION
 */

// Confirm before save
function confirmSaveSettings() {
    notify.confirm({
        type: 'warning',
        title: 'Simpan Pengaturan?',
        message: 'Anda akan menyimpan semua perubahan pengaturan website. Lanjutkan?',
        confirmText: 'Ya, Simpan',
        cancelText: 'Batal',
        onConfirm: function() {
            // Show loading
            notify.loading('Menyimpan pengaturan...');
            
            // Submit form
            document.getElementById('settingsForm').submit();
        }
    });
}

// Confirm before back
function confirmBack() {
    notify.confirm({
        type: 'info',
        title: 'Kembali ke Dashboard?',
        message: 'Perubahan yang belum disimpan akan hilang. Yakin ingin kembali?',
        confirmText: 'Ya, Kembali',
        cancelText: 'Batal',
        onConfirm: function() {
            window.location.href = '<?= ADMIN_URL ?>';
        }
    });
}

// Show info alert on page load (optional demo)
<?php if (isset($_GET['demo'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Demo notification
    setTimeout(function() {
        notify.info('Halaman pengaturan siap digunakan!', 3000);
    }, 500);
});
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>