<?php
/**
 * User Profile Page
 * View and edit current user profile
 */

require_once 'includes/auth_check.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';
require_once '../core/Validator.php';
require_once '../core/Upload.php';

 $pageTitle = 'Profile Saya';

 $db = Database::getInstance()->getConnection();
 $currentUser = getCurrentUser();
 $validator = null;

// Get user data from database
 $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$currentUser['id']]);
 $user = $stmt->fetch();

if (!$user) {
    setAlert('danger', 'User tidak ditemukan');
    redirect(ADMIN_URL);
}

// Get user statistics
 $stats = [
    'posts_created' => 0,
    'last_login' => null,
    'account_age_days' => 0
];

// Count posts created by user
 $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND deleted_at IS NULL");
 $stmt->execute([$user['id']]);
 $stats['posts_created'] = $stmt->fetchColumn();

// Get last login from activity logs
 $stmt = $db->prepare("
    SELECT created_at 
    FROM activity_logs 
    WHERE user_id = ? AND action_type = 'LOGIN' 
    ORDER BY created_at DESC 
    LIMIT 1
");
 $stmt->execute([$user['id']]);
 $lastLogin = $stmt->fetch();
 $stats['last_login'] = $lastLogin ? $lastLogin['created_at'] : null;

// Calculate account age
 $createdDate = new DateTime($user['created_at']);
 $now = new DateTime();
 $stats['account_age_days'] = $createdDate->diff($now)->days;

// Get recent activities
 $stmt = $db->prepare("
    SELECT * FROM activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
 $stmt->execute([$user['id']]);
 $recentActivities = $stmt->fetchAll();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ========================================
    // UPDATE PROFILE
    // ========================================
    if ($action === 'update_profile') {
        $validator = new Validator($_POST);
        
        $validator->required('name', 'Nama');
        $validator->email('email', 'Email');
        
        // Check if email already used by other user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$_POST['email'], $user['id']]);
        if ($stmt->fetch()) {
            $validator->addError('email', 'Email sudah digunakan user lain');
        }
        
        if ($validator->passes()) {
            try {
                $upload = new Upload();
                $photoPath = $user['photo'];
                
                // Handle photo upload
                if (!empty($_FILES['photo']['name'])) {
                    $newPhoto = $upload->upload($_FILES['photo'], 'users');
                    if ($newPhoto) {
                        // Delete old photo
                        if ($user['photo']) {
                            $upload->delete($user['photo']);
                        }
                        $photoPath = $newPhoto;
                    } else {
                        $validator->addError('photo', $upload->getError());
                    }
                }
                
                // Handle photo deletion
                if (isset($_POST['delete_photo']) && $_POST['delete_photo'] == '1') {
                    if ($user['photo']) {
                        $upload->delete($user['photo']);
                    }
                    $photoPath = null;
                }
                
                if ($validator->passes()) {
                    // Update user data
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, photo = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([
                        clean($_POST['name']),
                        clean($_POST['email']),
                        $photoPath,
                        $user['id']
                    ])) {
                        // Update session data
                        refreshUserSession($user['id']);
                        
                        logActivity('UPDATE', 'Mengupdate profil', 'users', $user['id']);
                        
                        setAlert('success', 'Profile berhasil diupdate');
                        redirect(ADMIN_URL . 'profile.php');
                    } else {
                        $validator->addError('general', 'Gagal update profile');
                    }
                }
                
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
    }
    
    // ========================================
    // CHANGE PASSWORD
    // ========================================
    elseif ($action === 'change_password') {
        $validator = new Validator($_POST);
        
        $validator->required('current_password', 'Password Lama');
        $validator->required('new_password', 'Password Baru');
        $validator->required('confirm_password', 'Konfirmasi Password');
        $validator->min('new_password', 6, 'Password Baru');
        
        // Verify current password
        if (!password_verify($_POST['current_password'], $user['password'])) {
            $validator->addError('current_password', 'Password lama tidak sesuai');
        }
        
        // Check password confirmation
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $validator->addError('confirm_password', 'Konfirmasi password tidak cocok');
        }
        
        if ($validator->passes()) {
            try {
                $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    UPDATE users 
                    SET password = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$hashedPassword, $user['id']])) {
                    logActivity('UPDATE', 'Mengubah password', 'users', $user['id']);
                    
                    setAlert('success', 'Password berhasil diubah');
                    redirect(ADMIN_URL . 'profile.php');
                } else {
                    $validator->addError('general', 'Gagal mengubah password');
                }
                
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><i class=""></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <!-- Left Column - Profile Card & Stats -->
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if ($user['photo']): ?>
                                <img src="<?= uploadUrl($user['photo']) ?>" 
                                     alt="<?= htmlspecialchars($user['name']) ?>" 
                                     class="rounded-circle" 
                                     style="width: 150px; height: 150px; object-fit: cover; border: 4px solid var(--bs-border-color);">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                                     style="width: 150px; height: 150px; font-size: 3rem; border: 4px solid var(--bs-border-color);">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                        <div class="mb-3">
                            <?= getRoleBadge($user['role']) ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="#editProfile" class="btn btn-primary" data-bs-toggle="collapse">
                                <i class="bi bi-pencil"></i> Edit Profile
                            </a>
                            <a href="#changePassword" class="btn btn-outline-primary" data-bs-toggle="collapse">
                                <i class="bi bi-key"></i> Ubah Password
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Card -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bar-chart"></i> Statistik Akun
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <small class="text-muted">Post Dibuat</small>
                                <h4 class="mb-0"><?= formatNumber($stats['posts_created']) ?></h4>
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-file-text" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-2">
                            <small class="text-muted">Akun Dibuat:</small>
                            <div class="fw-bold"><?= formatTanggal($user['created_at'], 'd F Y') ?></div>
                            <small class="text-muted">(<?= $stats['account_age_days'] ?> hari yang lalu)</small>
                        </div>
                        
                        <?php if ($stats['last_login']): ?>
                            <div class="mb-0">
                                <small class="text-muted">Login Terakhir:</small>
                                <div class="fw-bold"><?= formatTanggalRelatif($stats['last_login']) ?></div>
                                <small class="text-muted"><?= formatTanggal($stats['last_login'], 'd M Y H:i') ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Forms & Activity -->
            <div class="col-lg-8">
                <!-- Edit Profile Form -->
                <div class="collapse <?= $validator && $_POST['action'] === 'update_profile' ? 'show' : '' ?>" id="editProfile">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-pencil"></i> Edit Profile
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($validator && $_POST['action'] === 'update_profile' && $validator->getError('general')): ?>
                                <div class="alert alert-danger">
                                    <?= $validator->getError('general') ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <!-- Name -->
                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control <?= $validator && $validator->getError('name') ? 'is-invalid' : '' ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>" 
                                           required>
                                    <?php if ($validator && $validator->getError('name')): ?>
                                        <div class="invalid-feedback"><?= $validator->getError('name') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Email -->
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" 
                                           required>
                                    <?php if ($validator && $validator->getError('email')): ?>
                                        <div class="invalid-feedback"><?= $validator->getError('email') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Photo -->
                                <div class="form-group mb-3">
                                    <label for="photo" class="form-label">Foto Profile</label>
                                    
                                    <?php if ($user['photo']): ?>
                                        <div class="mb-2">
                                            <img src="<?= uploadUrl($user['photo']) ?>" 
                                                 alt="Current Photo" 
                                                 class="rounded" 
                                                 style="width: 100px; height: 100px; object-fit: cover; border: 2px solid var(--bs-border-color);">
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="delete_photo" value="1" 
                                                   class="form-check-input" id="deletePhoto">
                                            <label class="form-check-label text-danger" for="deletePhoto">
                                                <i class="bi bi-trash"></i> Hapus foto
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <input type="file" 
                                           class="form-control <?= $validator && $validator->getError('photo') ? 'is-invalid' : '' ?>" 
                                           id="photo" 
                                           name="photo" 
                                           accept="image/*">
                                    <small class="text-muted">Max <?= getSetting('upload_max_size', 5) ?>MB</small>
                                    <?php if ($validator && $validator->getError('photo')): ?>
                                        <div class="invalid-feedback"><?= $validator->getError('photo') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Role (Read-only) -->
                                <div class="form-group mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="role" 
                                           value="<?= getRoleName($user['role']) ?>" 
                                           readonly>
                                    <small class="text-muted">Role tidak bisa diubah sendiri</small>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                    <a href="#editProfile" class="btn btn-secondary" data-bs-toggle="collapse">
                                        <i class="bi bi-x"></i> Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Form -->
                <div class="collapse <?= $validator && $_POST['action'] === 'change_password' ? 'show' : '' ?>" id="changePassword">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-key"></i> Ubah Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($validator && $_POST['action'] === 'change_password' && $validator->getError('general')): ?>
                                <div class="alert alert-danger">
                                    <?= $validator->getError('general') ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <!-- Current Password -->
                                <div class="form-group mb-3">
                                    <label for="current_password" class="form-label">Password Lama <span class="text-danger">*</span></label>
                                    <input type="password" 
                                           class="form-control <?= $validator && $validator->getError('current_password') ? 'is-invalid' : '' ?>" 
                                           id="current_password" 
                                           name="current_password" 
                                           required>
                                    <?php if ($validator && $validator->getError('current_password')): ?>
                                        <div class="invalid-feedback"><?= $validator->getError('current_password') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- New Password -->
                                <div class="form-group mb-3">
                                    <label for="new_password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" 
                                           class="form-control <?= $validator && $validator->getError('new_password') ? 'is-invalid' : '' ?>" 
                                           id="new_password" 
                                           name="new_password" 
                                           minlength="6" 
                                           required>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                    <?php if ($validator && $validator->getError('new_password')): ?>
                                        <div class="invalid-feedback"><?= $validator->getError('new_password') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Confirm Password -->
                                <div class="form-group mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" 
                                           class="form-control <?= $validator && $validator->getError('confirm_password') ? 'is-invalid' : '' ?>" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required>
                                    <?php if ($validator && $validator->getError('confirm_password')): ?>
                                        <div class="invalid-feedback"><?= $validator->getError('confirm_password') ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Ubah Password
                                    </button>
                                    <a href="#changePassword" class="btn btn-secondary" data-bs-toggle="collapse">
                                        <i class="bi bi-x"></i> Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2 text-primary"></i> Aktivitas Terakhir
                        </h5>
                        <?php if (!empty($recentActivities)): ?>
                            <a href="<?= ADMIN_URL ?>modules/logs/activity_logs.php" class="btn btn-sm btn-outline-primary">
                                Lihat Semua <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Belum ada aktivitas
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentActivities as $activity): ?>
                                    
                                    <div class="list-group-item px-0 bg-transparent">
                                        <div class="d-flex align-items-start">
                                            <span class="badge bg-<?= getActionColor($activity['action_type']) ?> me-3">
                                                <?= ucfirst($activity['action_type']) ?>
                                            </span>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium"><?= htmlspecialchars($activity['description']) ?></div>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?= formatTanggalRelatif($activity['created_at']) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// Custom notification system
function showNotification(message, type = 'success') {
    // Create notification container if it doesn't exist
    let container = document.querySelector('.btikp-notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'btikp-notification-container';
        document.body.appendChild(container);
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `btikp-toast btikp-toast-${type}`;
    
    // Determine icon based on type
    let icon = 'check-circle';
    if (type === 'danger' || type === 'error') icon = 'exclamation-triangle';
    if (type === 'warning') icon = 'exclamation-triangle';
    if (type === 'info') icon = 'info-circle';
    
    notification.innerHTML = `
        <div class="btikp-toast-icon">
            <i class="bi bi-${icon}"></i>
        </div>
        <div class="btikp-toast-content">
            <div class="btikp-toast-title">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
            <div class="btikp-toast-message">${message}</div>
        </div>
        <button class="btikp-toast-close">
            <i class="bi bi-x"></i>
        </button>
    `;
    
    // Add close functionality
    notification.querySelector('.btikp-toast-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
    
    // Add to container
    container.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Custom confirmation dialog system
function showConfirmDialog(title, message, type = 'danger', confirmText = 'Hapus', cancelText = 'Batal', onConfirm) {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'btikp-alert-overlay';
    
    // Determine icon based on type
    let icon = 'exclamation-triangle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    if (type === 'info') icon = 'info-circle';
    
    // Create dialog
    const dialog = document.createElement('div');
    dialog.className = 'btikp-alert';
    dialog.innerHTML = `
        <div class="btikp-alert-icon">
            <i class="bi bi-${icon}"></i>
        </div>
        <div class="btikp-alert-title">${title}</div>
        <div class="btikp-alert-message">${message}</div>
        <div class="btikp-alert-actions">
            <button class="btikp-btn btikp-btn-secondary">${cancelText}</button>
            <button class="btikp-btn btikp-btn-${type}">${confirmText}</button>
        </div>
    `;
    
    // Add to overlay
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    
    // Add event listeners
    const confirmBtn = dialog.querySelector('.btikp-btn-' + type);
    const cancelBtn = dialog.querySelector('.btikp-btn-secondary');
    
    confirmBtn.addEventListener('click', () => {
        overlay.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(overlay);
            onConfirm();
        }, 300);
    });
    
    cancelBtn.addEventListener('click', () => {
        overlay.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(overlay);
        }, 300);
    });
    
    // Trigger animation
    setTimeout(() => overlay.classList.add('show'), 10);
}
</script>

<?php include 'includes/footer.php'; ?>