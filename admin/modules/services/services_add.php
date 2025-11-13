<?php
/**
 * Services - Add New Service
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Tambah Layanan Baru';
$currentPage = 'services';

$db = Database::getInstance()->getConnection();

$errors = [];

// Folder tempat upload gambar
$uploadDir = '../../../public/uploads/services/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title'] ?? '');
    $slug = clean($_POST['slug'] ?? '');
    $description = $_POST['description'] ?? '';
    $service_url = trim($_POST['service_url'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $imagePath = null;

    if (!$title) {
        $errors[] = 'Judul layanan wajib diisi.';
    }

    if (!$slug) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }

    // Periksa slug unik
    $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
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
            // Buat folder upload jika belum ada
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = uniqid() . '.' . $ext;
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($tmpName, $destination)) {
                // Simpan relatif path untuk database
                $imagePath = 'uploads/services/' . $newFileName;
            } else {
                $errors[] = 'Gagal upload gambar.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO services (title, slug, description, service_url, image_path, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$title, $slug, $description, $service_url, $imagePath, $status]);
            logActivity('INSERT', "Menambah layanan baru: {$title}", 'services', $db->lastInsertId());
            setAlert('success', 'Layanan berhasil ditambahkan!');
            redirect(ADMIN_URL . 'modules/services/services_list.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Gagal menambahkan layanan. Silakan coba lagi.';
            // Jika gambar sudah terupload tapi gagal insert, hapus gambarnya
            if ($imagePath && file_exists($uploadDir . basename($imagePath))) {
                unlink($uploadDir . basename($imagePath));
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h3>Tambah Layanan Baru</h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="services_list.php">Layanan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah</li>
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
                        <input type="text" class="form-control" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug URL</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
                        <small class="text-muted">URL unik untuk layanan ini. Diisi otomatis jika kosong.</small>
                    </div>
                    <div class="mb-3">
                        <label for="service_url" class="form-label">URL Website Layanan</label>
                        <input type="url" class="form-control" id="service_url" name="service_url" placeholder="https://example.com" value="<?= htmlspecialchars($_POST['service_url'] ?? '') ?>">
                        <small class="text-muted">Opsional. Masukkan link website layanan yang dipromosikan.</small>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Gambar Layanan</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="text-muted">Unggah gambar untuk layanan ini (jpg, png, gif).</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status Publikasi</label>
                        <select id="status" name="status" class="form-select">
                            <option value="published" <?= (($_POST['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= (($_POST['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div> 
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-save"></i> Simpan Layanan
                        </button>
                        <a href="services_list.php" class="btn btn-secondary flex-fill">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                    </form>

            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>