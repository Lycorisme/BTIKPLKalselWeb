<?php
/**
 * Pages - Add New Page
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Tambah Halaman Baru';
$currentPage = 'pages';

$db = Database::getInstance()->getConnection();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title'] ?? '');
    $slug = clean($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    // Validate
    if (!$title) {
        $errors[] = 'Judul halaman wajib diisi.';
    }
    if (!$slug) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }
    
    // Check slug uniqueness
    $stmt = $db->prepare("SELECT COUNT(*) FROM pages WHERE slug = ?");
    $stmt->execute([$slug]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $errors[] = 'Slug sudah digunakan, silakan gunakan slug lain.';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO pages (title, slug, content, status, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$title, $slug, $content, $status, $displayOrder]);
            
            logActivity('INSERT', "Menambah halaman baru: {$title}", 'pages', $db->lastInsertId());
            
            setAlert('success', 'Halaman berhasil ditambahkan!');
            redirect(ADMIN_URL . 'modules/pages/pages_list.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Gagal menambahkan halaman baru. Silakan coba lagi.';
        }
    }
}

include '../../includes/header.php';
?>
<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="pages_list.php">Halaman</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Halaman <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug URL</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
                        <small class="text-muted">URL unik untuk halaman ini. Diisi otomatis dari judul jika kosong.</small>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Isi Konten</label>
                        <textarea class="form-control" id="content" name="content" rows="10"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status Publikasi</label>
                        <select id="status" name="status" class="form-select">
                            <option value="published" <?= (($_POST['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= (($_POST['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Urutan Tampilan</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= (int)($_POST['display_order'] ?? 0) ?>">
                        <small class="text-muted">Urutan halaman yang akan ditampilkan (semakin kecil semakin atas)</small>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Halaman
                        </button>
                        <a href="pages_list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                    </form>
            </div>
        </div>
    </section>
</div>
<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
<script>
ClassicEditor
    .create(document.querySelector('#content'))
    .catch(error => {
        console.error(error);
    });
</script>