<?php
/**
 * Gallery Photos - Edit
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

 $pageTitle = 'Edit Foto';
 $currentPage = 'photos_edit';

 $db = Database::getInstance()->getConnection();

// Get photo ID
 $photoId = $_GET['id'] ?? null;

if (!$photoId) {
    setAlert('danger', 'Foto tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get photo data with album info
 $stmt = $db->prepare("
    SELECT p.*, a.name as album_name 
    FROM gallery_photos p
    LEFT JOIN gallery_albums a ON p.album_id = a.id
    WHERE p.id = ? AND p.deleted_at IS NULL
");
 $stmt->execute([$photoId]);
 $photo = $stmt->fetch();

if (!$photo) {
    setAlert('danger', 'Foto tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title'] ?? '');
    $description = clean($_POST['description'] ?? '');
    $caption = clean($_POST['caption'] ?? '');
    $takenAt = $_POST['taken_at'] ?? null;
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    try {
        $stmt = $db->prepare("
            UPDATE gallery_photos 
            SET title = ?, 
                description = ?, 
                caption = ?, 
                taken_at = ?,
                display_order = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title,
            $description,
            $caption,
            $takenAt,
            $displayOrder,
            $photoId
        ]);
        
        // Log activity
        logActivity('UPDATE', "Mengupdate foto: {$title}", 'gallery_photos', $photoId);
        
        setAlert('success', 'Foto berhasil diupdate!');
        redirect(ADMIN_URL . 'modules/gallery/photos_list.php?album_id=' . $photo['album_id']);
        
    } catch (PDOException $e) {
        error_log($e->getMessage());
        setAlert('danger', 'Gagal mengupdate foto. Silakan coba lagi.');
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><i class="bi bi-image me-2"></i><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Album: <strong><?= htmlspecialchars($photo['album_name']) ?></strong></p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="albums_list.php">Albums</a></li>
                        <li class="breadcrumb-item"><a href="photos_list.php?album_id=<?= $photo['album_id'] ?>">Foto</a></li>
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
                        <h5 class="card-title mb-0">Edit Detail Foto</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrfField() ?>
                            
                            <!-- Photo Preview -->
                            <div class="form-group mb-3">
                                <label class="form-label">Preview Foto</label>
                                <div>
                                    <img src="<?= uploadUrl($photo['filename']) ?>" 
                                         alt="<?= htmlspecialchars($photo['title'] ?? '') ?>" 
                                         class="img-fluid rounded"
                                         style="max-height: 400px;">
                                </div>
                            </div>
                            
                            <!-- Title -->
                            <div class="form-group mb-3">
                                <label for="title" class="form-label">Judul Foto</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       value="<?= htmlspecialchars($photo['title'] ?? '') ?>"
                                       placeholder="Contoh: Pelatihan Guru TIK 2024">
                                <small class="text-muted">Judul foto yang akan ditampilkan</small>
                            </div>
                            
                            <!-- Caption -->
                            <div class="form-group mb-3">
                                <label for="caption" class="form-label">Caption</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="caption" 
                                       name="caption" 
                                       value="<?= htmlspecialchars($photo['caption'] ?? '') ?>"
                                       placeholder="Caption singkat untuk foto">
                                <small class="text-muted">Caption pendek yang muncul di bawah foto</small>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4"
                                          placeholder="Deskripsi lengkap tentang foto ini..."><?= htmlspecialchars($photo['description'] ?? '') ?></textarea>
                                <small class="text-muted">Deskripsi detail tentang foto (optional)</small>
                            </div>
                            
                            <!-- Taken At -->
                            <div class="form-group mb-3">
                                <label for="taken_at" class="form-label">Tanggal Pengambilan</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="taken_at" 
                                       name="taken_at" 
                                       value="<?= $photo['taken_at'] ?>">
                                <small class="text-muted">Kapan foto ini diambil?</small>
                            </div>
                            
                            <!-- Display Order -->
                            <div class="form-group mb-3">
                                <label for="display_order" class="form-label">Urutan Tampilan</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="display_order" 
                                       name="display_order" 
                                       value="<?= $photo['display_order'] ?>"
                                       min="0">
                                <small class="text-muted">Semakin kecil angka, semakin awal ditampilkan</small>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <a href="photos_list.php?album_id=<?= $photo['album_id'] ?>" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Kembali
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Foto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Info Foto</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Filename:</strong><br>
                                <code><?= basename($photo['filename']) ?></code>
                            </li>
                            <li class="mb-2">
                                <strong>Ukuran File:</strong><br>
                                <?= formatFileSize($photo['file_size']) ?>
                            </li>
                            <li class="mb-2">
                                <strong>Dimensi:</strong><br>
                                <?= $photo['width'] ?> x <?= $photo['height'] ?> px
                            </li>
                            <li class="mb-2">
                                <strong>Views:</strong><br>
                                <?= formatNumber($photo['view_count']) ?> kali
                            </li>
                            <li class="mb-2">
                                <strong>Diupload:</strong><br>
                                <?= formatTanggal($photo['created_at'], 'd F Y H:i') ?>
                            </li>
                        </ul>
                        
                        <hr>
                        
                        <a href="photos_delete.php?id=<?= $photo['id'] ?>&album_id=<?= $photo['album_id'] ?>" 
                           class="btn btn-danger w-100"
                           data-confirm-delete
                           data-title="Hapus Foto"
                           data-message="Apakah Anda yakin ingin menghapus foto ini? Tindakan ini tidak dapat dibatalkan."
                           data-loading-text="Menghapus...">
                            <i class="bi bi-trash"></i> Hapus Foto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>