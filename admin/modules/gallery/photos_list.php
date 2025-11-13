<?php
/**
 * Gallery Photos - List (Full Mazer Design with Drag & Drop Reordering)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Kelola Foto';
 $currentPage = 'photos_list';

 $db = Database::getInstance()->getConnection();

// Get album ID from URL
 $albumId = (int)($_GET['album_id'] ?? 0);

if (!$albumId) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get album data - CHECK deleted_at IS NULL
 $stmt = $db->prepare("SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL");
 $stmt->execute([$albumId]);
 $album = $stmt->fetch();

if (!$album) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get all photos in this album - CHECK deleted_at IS NULL
 $stmt = $db->prepare("
    SELECT 
        p.*,
        u.name as uploader_name
    FROM gallery_photos p
    LEFT JOIN users u ON p.uploaded_by = u.id
    WHERE p.album_id = ? AND p.deleted_at IS NULL
    ORDER BY p.display_order ASC, p.created_at DESC
");
 $stmt->execute([$albumId]);
 $photos = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><i class=""></i><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Album: <strong><?= htmlspecialchars($album['name']) ?></strong></p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="albums_list.php">Albums</a></li>
                        <li class="breadcrumb-item active">Foto</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Action Buttons Card -->
        <div class="card shadow mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex gap-2">
                        <a href="albums_list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                        <a href="albums_edit.php?id=<?= $album['id'] ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit Album
                        </a>
                    </div>
                    <a href="photos_upload.php?album_id=<?= $album['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload Foto
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Album Stats Card -->
        <div class="card shadow mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Foto</h6>
                                <h4><?= count($photos) ?></h4>
                            </div>
                            <i class="bi bi-images" style="font-size: 2rem; color: #0d6efd;"></i>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Cover Photo</h6>
                                <h4><?= $album['cover_photo'] ? '<span class="badge bg-success">✓</span>' : '<span class="badge bg-secondary">-</span>' ?></h4>
                            </div>
                            <i class="bi bi-star" style="font-size: 2rem; color: #ffc107;"></i>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Status</h6>
                                <h4><?= $album['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></h4>
                            </div>
                            <i class="bi bi-toggle-on" style="font-size: 2rem; color: #198754;"></i>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Dibuat</h6>
                                <small><?= formatTanggal($album['created_at'], 'd M Y') ?></small>
                            </div>
                            <i class="bi bi-calendar-event" style="font-size: 2rem; color: #6c757d;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Photos Grid Card -->
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Daftar Foto</h5>
                    <?php if (!empty($photos)): ?>
                        <small class="text-muted">
                            <i class="bi bi-arrows-move"></i> Drag foto untuk mengubah urutan
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($photos)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Belum ada foto di album ini. 
                        <a href="photos_upload.php?album_id=<?= $album['id'] ?>">Upload foto sekarang</a>
                    </div>
                <?php else: ?>
                    <div class="row" id="photos-grid">
                        <?php foreach ($photos as $photo): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4" data-photo-id="<?= $photo['id'] ?>">
                                <div class="card h-100 photo-card shadow-sm">
                                    <!-- Drag Handle Indicator -->
                                    <div class="drag-handle" title="Drag untuk reorder">
                                        <i class="bi bi-grip-vertical"></i>
                                    </div>
                                    
                                    <!-- Photo Image -->
                                    <div style="height: 200px; overflow: hidden; background: #f5f5f5; cursor: pointer;"
                                         onclick="viewPhoto('<?= uploadUrl($photo['filename']) ?>', '<?= htmlspecialchars($photo['title'] ?? 'Photo', ENT_QUOTES) ?>')">
                                        <img src="<?= uploadUrl($photo['thumbnail'] ?? $photo['filename']) ?>" 
                                             alt="<?= htmlspecialchars($photo['title'] ?? '') ?>" 
                                             class="w-100 h-100" 
                                             style="object-fit: cover;">
                                    </div>
                                    
                                    <!-- Photo Info -->
                                    <div class="card-body p-2">
                                        <?php if ($photo['title']): ?>
                                            <h6 class="card-title mb-1 small"><?= htmlspecialchars(truncateText($photo['title'], 30)) ?></h6>
                                        <?php endif; ?>
                                        
                                        <?php if ($photo['caption']): ?>
                                            <p class="card-text text-muted small mb-2">
                                                <?= htmlspecialchars(truncateText($photo['caption'], 50)) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <small class="text-muted d-block">
                                            <i class="bi bi-eye"></i> <?= formatNumber($photo['view_count']) ?> views
                                        </small>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="card-footer bg-transparent p-2">
                                        <div class="btn-group w-100 btn-group-sm" role="group">
                                            <a href="photos_edit.php?id=<?= $photo['id'] ?>" 
                                               class="btn btn-outline-primary"
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-success"
                                                    onclick="setCover(<?= $photo['id'] ?>)"
                                                    title="Set sebagai cover">
                                                <i class="bi bi-star"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    onclick="quickDeletePhoto(<?= $photo['id'] ?>)"
                                                    title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Modal for viewing photo full size -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalLabel">Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="photoModalImage" src="" alt="" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- SortableJS Library for Drag & Drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Initialize Sortable for drag & drop reordering
const photosGrid = document.getElementById('photos-grid');
if (photosGrid && photosGrid.children.length > 0) {
    new Sortable(photosGrid, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            const photoIds = [];
            document.querySelectorAll('[data-photo-id]').forEach(el => {
                photoIds.push(el.dataset.photoId);
            });
            reorderPhotos(photoIds);
        }
    });
}

// Reorder photos via AJAX
function reorderPhotos(photoIds) {
    fetch('ajax/reorder.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            photo_ids: photoIds,
            album_id: <?= $album['id'] ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Urutan foto berhasil diupdate!', 'success');
        } else {
            showNotification('Gagal reorder: ' + data.message, 'danger');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan saat reorder', 'danger');
        location.reload();
    });
}

// Quick delete photo
function quickDeletePhoto(photoId) {
    // Use custom confirmation dialog
    showConfirmDialog(
        'Hapus Foto',
        'Apakah Anda yakin ingin menghapus foto ini? Tindakan ini tidak dapat dibatalkan.',
        'danger',
        'Hapus',
        'Batal',
        function() {
            // Find the photo card
            const cardElement = document.querySelector(`[data-photo-id="${photoId}"]`);
            const deleteBtn = cardElement.querySelector('.btn-outline-danger');
            deleteBtn.disabled = true;
            
            fetch('ajax/quick_delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({photo_id: photoId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cardElement.style.transition = 'opacity 0.3s, transform 0.3s';
                    cardElement.style.opacity = '0';
                    cardElement.style.transform = 'scale(0.8)';
                    
                    setTimeout(() => {
                        cardElement.remove();
                        showNotification('Foto berhasil dihapus!', 'success');
                        if (document.querySelectorAll('[data-photo-id]').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    deleteBtn.disabled = false;
                    showNotification('Gagal hapus foto: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                deleteBtn.disabled = false;
                showNotification('Terjadi kesalahan saat hapus foto', 'danger');
            });
        }
    );
}

// Set cover photo
function setCover(photoId) {
    // Use custom confirmation dialog
    showConfirmDialog(
        'Set sebagai Cover',
        'Apakah Anda yakin ingin menjadikan foto ini sebagai cover album?',
        'warning',
        'Set sebagai Cover',
        'Batal',
        function() {
            fetch('ajax/set_cover.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    photo_id: photoId,
                    album_id: <?= $album['id'] ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Cover album berhasil diupdate!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Gagal update cover: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat update cover', 'danger');
            });
        }
    );
}

// View photo in modal
function viewPhoto(url, title) {
    document.getElementById('photoModalImage').src = url;
    document.getElementById('photoModalLabel').textContent = title;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
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

<style>
/* Sortable drag & drop classes */
.sortable-ghost {
    opacity: 0.4;
    background: #f0f0f0;
}

.sortable-chosen {
    opacity: 0.8;
}

.sortable-drag {
    opacity: 1;
}

/* Photo card styling */
.photo-card {
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.photo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Drag handle styling */
.drag-handle {
    position: absolute;
    top: 5px;
    left: 5px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 5px 8px;
    border-radius: 4px;
    cursor: move;
    z-index: 10;
    font-size: 14px;
    transition: background 0.2s;
}

.drag-handle:hover {
    background: rgba(0,0,0,0.8);
}

.drag-handle:active {
    cursor: grabbing;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .drag-handle {
        font-size: 12px;
        padding: 4px 6px;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>