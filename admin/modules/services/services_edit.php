<?php
/**
 * Services - Edit Service
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Edit Layanan';
$currentPage = 'services';

$db = Database::getInstance()->getConnection();

$id = $_GET['id'] ?? null;
if (!$id) {
    setAlert('danger', 'Layanan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

$stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    setAlert('danger', 'Layanan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

$errors = [];

$uploadDir = '../../../public/uploads/services/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title'] ?? '');
    $slug = clean($_POST['slug'] ?? '');
    $description = $_POST['description'] ?? '';
    $service_url = trim($_POST['service_url'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    if (!$title) {
        $errors[] = 'Judul layanan wajib diisi.';
    }

    if (!$slug) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }

    // Cek slug unik selain service ini
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM services WHERE slug = ? AND id != ?");
    $stmtCheck->execute([$slug, $id]);
    if ($stmtCheck->fetchColumn() > 0) {
        $errors[] = 'Slug sudah digunakan, gunakan slug lain.';
    }

    if ($service_url && !filter_var($service_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'URL layanan tidak valid.';
    }

    // Handle upload gambar
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $allowedExt)) {
            $errors[] = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau GIF.';
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = uniqid() . '.' . $ext;
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($tmpName, $destination)) {
                // Hapus gambar lama jika ada
                if ($service['image_path'] && is_file('../../../public/' . $service['image_path'])) {
                    unlink('../../../public/' . $service['image_path']);
                }
                $service['image_path'] = 'uploads/services/' . $newFileName;
            } else {
                $errors[] = 'Gagal upload gambar.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmtUpdate = $db->prepare("UPDATE services SET title = ?, slug = ?, description = ?, service_url = ?, image_path = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$title, $slug, $description, $service_url, $service['image_path'], $status, $id]);
            logActivity('UPDATE', "Mengubah layanan: {$title}", 'services', $id);
            setAlert('success', 'Layanan berhasil diupdate!');
            redirect(ADMIN_URL . 'modules/services/services_list.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Gagal mengupdate layanan. Silakan coba lagi.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h3>Edit Layanan</h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="services_list.php">Layanan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

    <section class="section">
        <div class="card">
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Layanan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($service['title']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug URL</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($service['slug']) ?>">
                        <small class="text-muted">URL unik untuk layanan ini. Diisi otomatis jika kosong.</small>
                    </div>
                    <div class="mb-3">
                        <label for="service_url" class="form-label">URL Website Layanan</label>
                        <input type="url" class="form-control" id="service_url" name="service_url" placeholder="https://example.com" value="<?= htmlspecialchars($service['service_url']) ?>">
                        <small class="text-muted">Opsional. Masukkan link website layanan yang dipromosikan.</small>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Gambar Layanan</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <?php if ($service['image_path']): ?>
                            <img src="<?= BASE_URL . $service['image_path'] ?>" 
                                 alt="Gambar Layanan" 
                                 class="img-thumbnail mt-2 mw-100" 
                                 style="max-height: 150px;">
                            <?php endif; ?>
                        <small class="text-muted">Unggah gambar untuk layanan ini (.jpg, .png, .gif). Kosongkan jika tidak ingin mengubah gambar.</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($service['description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status Publikasi</label>
                        <select id="status" name="status" class="form-select">
                            <option value="published" <?= ($service['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= ($service['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <a href="services_list.php" class="btn btn-secondary flex-fill">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                    </form>

            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>