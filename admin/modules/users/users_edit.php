<?php
/**
 * Edit User Page - WITH SESSION REFRESH
 * Update user information and role
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../core/Validator.php';
require_once '../../../core/Upload.php';
require_once '../../../models/User.php';

// Only super_admin and admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Edit Pengguna';

$userModel = new User();
$validator = null;

// Get user ID
$userId = $_GET['id'] ?? 0;
$user = $userModel->find($userId);

if (!$user) {
    setAlert('danger', 'Pengguna tidak ditemukan');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

// Prevent editing own super_admin role (security)
$isSelfEdit = ($user['id'] == getCurrentUser()['id']);
$canEditRole = !($isSelfEdit && $user['role'] == 'super_admin');

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST Data for Edit User ID {$userId}: " . print_r($_POST, true));
    
    $validator = new Validator($_POST);
    
    // Validation rules
    $validator->required('name', 'Nama');
    $validator->required('email', 'Email');
    $validator->email('email', 'Email');
    $validator->required('role', 'Role');
    
    // Password validation (only if filled)
    if (!empty($_POST['password'])) {
        $validator->minLength('password', 6, 'Password');
    }
    
    // Check if email already exists (exclude current user) BEFORE passes()
    if (isset($_POST['email']) && $userModel->emailExists($_POST['email'], $userId)) {
        $validator->addError('email', 'Email sudah digunakan');
    }
    
    if ($validator->passes()) {
        try {
            $upload = new Upload();
            $photoPath = $user['photo']; // Keep existing photo
            
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
            
            if ($validator->passes()) {
                // Prepare data
                $data = [
                    'name' => clean($_POST['name']),
                    'email' => clean($_POST['email']),
                    'phone' => clean($_POST['phone'] ?? ''),
                    'address' => clean($_POST['address'] ?? ''),
                    'role' => clean($_POST['role']),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'photo' => $photoPath
                ];
                
                // Only add updated_by if column exists
                try {
                    $data['updated_by'] = getCurrentUser()['id'];
                } catch (Exception $e) {
                    error_log("updated_by column not found, skipping...");
                }
                
                // Add password if provided
                if (!empty($_POST['password'])) {
                    $data['password'] = $_POST['password'];
                }
                
                // Debug: Log data to update
                error_log("Updating user data: " . print_r($data, true));
                
                if ($userModel->update($userId, $data)) {
                    // ✅ REFRESH SESSION if editing own profile
                    refreshUserSession($userId);
                    
                    // Log activity
                    try {
                        logActivity('UPDATE', "Mengupdate pengguna: {$data['name']}", 'users', $userId);
                    } catch (Exception $e) {
                        error_log("Activity log failed: " . $e->getMessage());
                    }
                    
                    setAlert('success', 'Pengguna berhasil diupdate');
                    redirect(ADMIN_URL . 'modules/users/users_list.php');
                } else {
                    $validator->addError('general', 'Gagal menyimpan data');
                }
            }
            
        } catch (PDOException $e) {
            error_log("PDO Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan database: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $validator->addError('general', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    } else {
        // Debug: Show validation errors
        error_log("Validation failed: " . print_r($validator->getErrors(), true));
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
                        <li class="breadcrumb-item"><a href="users_list.php">Pengguna</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Form Edit Pengguna</h5>
            </div>
            
            <div class="card-body">
                <?php if ($validator && $validator->getError('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Error:</strong> <?= $validator->getError('general') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($validator && $validator->hasErrors()): ?>
                    <div class="alert alert-warning">
                        <strong>Validation Errors:</strong>
                        <ul class="mb-0">
                            <?php foreach ($validator->getErrors() as $field => $error): ?>
                                <?php if ($field !== 'general'): ?>
                                    <li><?= $field ?>: <?= $error ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-8">
                            <!-- Name -->
                            <div class="form-group mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" 
                                       class="form-control <?= $validator && $validator->getError('name') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>" required>
                                <?php if ($validator && $validator->getError('name')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('name') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" 
                                       class="form-control <?= $validator && $validator->getError('email') ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
                                <?php if ($validator && $validator->getError('email')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('email') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Password -->
                            <div class="form-group mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" 
                                       class="form-control <?= $validator && $validator->getError('password') ? 'is-invalid' : '' ?>">
                                <small class="text-muted">Kosongkan jika tidak ingin mengubah password. Minimal 6 karakter.</small>
                                <?php if ($validator && $validator->getError('password')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('password') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Phone -->
                            <div class="form-group mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-4">
                            <!-- Current Photo -->
                            <?php if (!empty($user['photo'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Foto Saat Ini</label>
                                    <div>
                                        <img src="<?= uploadUrl($user['photo']) ?>" 
                                             alt="Photo" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Photo Upload -->
                            <div class="form-group mb-3">
                                <label class="form-label">
                                    <?= !empty($user['photo']) ? 'Foto Profil Baru' : 'Foto Profil' ?>
                                </label>
                                <input type="file" name="photo" 
                                       class="form-control <?= $validator && $validator->getError('photo') ? 'is-invalid' : '' ?>" 
                                       accept="image/*">
                                <small class="text-muted">Max <?= getSetting('upload_max_size', 5) ?>MB. Format: JPG, PNG, GIF, WebP</small>
                                <?php if ($validator && $validator->getError('photo')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('photo') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Role -->
                            <div class="form-group mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" 
                                        class="form-select <?= $validator && $validator->getError('role') ? 'is-invalid' : '' ?>" 
                                        required <?= !$canEditRole ? 'disabled' : '' ?>>
                                    <?php if (hasRole('super_admin')): ?>
                                        <option value="super_admin" <?= ($_POST['role'] ?? $user['role']) == 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                    <?php endif; ?>
                                    <option value="admin" <?= ($_POST['role'] ?? $user['role']) == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="editor" <?= ($_POST['role'] ?? $user['role']) == 'editor' ? 'selected' : '' ?>>Editor</option>
                                    <option value="author" <?= ($_POST['role'] ?? $user['role']) == 'author' ? 'selected' : '' ?>>Author</option>
                                </select>
                                <?php if ($validator && $validator->getError('role')): ?>
                                    <div class="invalid-feedback"><?= $validator->getError('role') ?></div>
                                <?php endif; ?>
                                <?php if (!$canEditRole): ?>
                                    <small class="text-muted">Tidak bisa mengubah role sendiri</small>
                                    <input type="hidden" name="role" value="super_admin">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="is_active" id="is_active" 
                                           class="form-check-input" value="1" 
                                           <?= ($_POST['is_active'] ?? $user['is_active']) == '1' ? 'checked' : '' ?>
                                           <?= $isSelfEdit ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Status Aktif
                                    </label>
                                    <?php if ($isSelfEdit): ?>
                                        <input type="hidden" name="is_active" value="1">
                                        <small class="text-muted d-block">Tidak bisa menonaktifkan akun sendiri</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Info Card -->
                            <div class="alert alert-info">
                                <h6 class="mb-2">Informasi:</h6>
                                <small>
                                    <strong>User ID:</strong> <?= $user['id'] ?><br>
                                    <strong>Dibuat:</strong> <?= formatTanggal($user['created_at'], 'd M Y H:i') ?><br>
                                    <?php if (!empty($user['updated_at'])): ?>
                                        <strong>Diupdate:</strong> <?= formatTanggal($user['updated_at'], 'd M Y H:i') ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($user['last_login_at'])): ?>
                                        <strong>Login Terakhir:</strong> <?= formatTanggal($user['last_login_at'], 'd M Y H:i') ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <?php if ($isSelfEdit): ?>
                                <div class="alert alert-warning">
                                    <small><i class="bi bi-info-circle"></i> Anda sedang mengedit profil sendiri</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Pengguna
                        </button>
                        <a href="users_list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
