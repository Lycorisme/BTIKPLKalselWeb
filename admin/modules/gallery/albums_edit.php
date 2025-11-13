<?php
/**
 * Gallery Albums - Edit
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Edit Album';
 $currentPage = 'albums_list';

 $db = Database::getInstance()->getConnection();

// Get album ID
 $albumId = $_GET['id'] ?? null;

if (!$albumId) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get album data
 $stmt = $db->prepare("SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL");
 $stmt->execute([$albumId]);
 $album = $stmt->fetch();

if (!$album) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $description = clean($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Generate slug if name changed
    $slug = $album['slug'];
    if ($name !== $album['name']) {
        $slug = generateSlug($name);
        
        // Check if new slug already exists
        $checkStmt = $db->prepare("SELECT id FROM gallery_albums WHERE slug = ? AND id != ? AND deleted_at IS NULL");
        $checkStmt->execute([$slug, $albumId]);
        
        if ($checkStmt->fetch()) {
            $slug = $slug . '-' . time();
        }
    }
    
    // Handle cover photo upload
    $coverPhoto = $album['cover_photo'];
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_photo'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowedTypes)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'cover_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadPath = '../../../public/uploads/gallery/albums/';
            
            // Create directory if not exists
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                // Delete old cover photo
                if ($coverPhoto && file_exists($uploadPath . $coverPhoto)) {
                    unlink($uploadPath . $coverPhoto);
                }
                $coverPhoto = 'gallery/albums/' . $filename;
            }
        }
    }
    
    // Handle remove cover photo
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
        if ($coverPhoto && file_exists($uploadPath . $coverPhoto)) {
            unlink($uploadPath . $coverPhoto);
        }
        $coverPhoto = null;
    }
    
    try {
        $stmt = $db->prepare("
            UPDATE gallery_albums 
            SET name = ?, 
                slug = ?, 
                description = ?, 
                cover_photo = ?, 
                display_order = ?, 
                is_active = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, 
            $slug, 
            $description, 
            $coverPhoto, 
            $displayOrder, 
            $isActive,
            $albumId
        ]);
        
        // Log activity
        logActivity('UPDATE', "Mengupdate album gallery: {$name}", 'gallery_albums', $albumId);
        
        setAlert('success', 'Album berhasil diupdate!');
        redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        setAlert('danger', 'Gagal mengupdate album. Silakan coba lagi.');
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><i class="bi bi-images me-2"></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="albums_list.php">Gallery Albums</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Edit Informasi Album</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="editForm">
                            <?= csrfField() ?>
                            <input type="hidden" name="remove_cover" id="remove_cover" value="0">
                            
                            <!-- Nama Album -->
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Nama Album <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       required
                                       value="<?= htmlspecialchars($album['name']) ?>">
                            </div>
                            
                            <!-- Deskripsi -->
                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4"><?= htmlspecialchars($album['description']) ?></textarea>
                            </div>
                            
                            <!-- Current Cover Photo -->
                            <?php if ($album['cover_photo']): ?>
                                <div class="form-group mb-3">
                                    <label class="form-label">Cover Photo Saat Ini</label>
                                    <div class="position-relative" style="display: inline-block;">
                                        <img src="<?= uploadUrl($album['cover_photo']) ?>" 
                                             alt="Cover" 
                                             class="img-thumbnail" 
                                             style="max-height:200px;"
                                             id="current-cover">
                                        <button type="button" 
                                                class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2"
                                                onclick="removeCover()"
                                                id="remove-btn">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- New Cover Photo -->
                            <div class="form-group mb-3">
                                <label for="cover_photo" class="form-label">
                                    <?= $album['cover_photo'] ? 'Ganti Cover Photo' : 'Cover Photo' ?>
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="cover_photo" 
                                       name="cover_photo" 
                                       accept="image/*"
                                       onchange="previewImage(this, 'cover-preview')">
                                <small class="text-muted">Format: JPG, PNG, GIF. Maks: 5MB</small>
                                
                                <!-- Preview -->
                                <div id="cover-preview" class="mt-3" style="display: none;">
                                    <img src="" alt="Preview" class="img-thumbnail" style="max-height:200px;">
                                </div>
                            </div>
                            
                            <!-- Display Order -->
                            <div class="form-group mb-3">
                                <label for="display_order" class="form-label">Urutan Tampilan</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="display_order" 
                                       name="display_order" 
                                       value="<?= $album['display_order'] ?>"
                                       min="0">
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           <?= $album['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Aktif (ditampilkan di website)
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <a href="albums_list.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Album
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Stats Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Album</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Slug:</strong><br>
                                <code><?= $album['slug'] ?></code>
                            </li>
                            <li class="mb-2">
                                <strong>Dibuat:</strong><br>
                                <?= formatTanggal($album['created_at'], 'd F Y H:i') ?>
                            </li>
                            <li class="mb-2">
                                <strong>Diupdate:</strong><br>
                                <?= formatTanggal($album['updated_at'], 'd F Y H:i') ?>
                            </li>
                        </ul>
                        
                        <hr>
                        
                        <a href="photos_list.php?album_id=<?= $album['id'] ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-images"></i> Kelola Foto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const img = preview.querySelector('img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

function removeCover() {
    // Use custom confirmation dialog
    showConfirmDialog(
        'Hapus Cover Photo',
        'Apakah Anda yakin ingin menghapus cover photo ini?',
        'danger',
        'Hapus',
        'Batal',
        function() {
            document.getElementById('remove_cover').value = '1';
            document.getElementById('current-cover').style.display = 'none';
            document.getElementById('remove-btn').style.display = 'none';
        }
    );
}

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

<?php include '../../includes/footer.php'; ?>