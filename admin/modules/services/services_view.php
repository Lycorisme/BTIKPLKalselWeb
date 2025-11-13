<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    setAlert('danger', 'Layanan tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    setAlert('danger', 'Layanan tidak ditemukan atau sudah dihapus.');
    redirect(ADMIN_URL . 'modules/services/services_list.php');
}

$pageTitle = 'Detail Layanan'; // Menambahkan pageTitle
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
                        <li class="breadcrumb-item"><a href="services_list.php">Layanan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

    <section class="section">
        <div class="card shadow-sm">
            <div class="card-body">
                
                <h4 class="card-title text-break"><?= htmlspecialchars($service['title']) ?></h4>
                <?php if ($service['image_path']): ?>
                    <img src="<?= BASE_URL . $service['image_path'] ?>" 
                         alt="Gambar Layanan" 
                         class="img-fluid rounded mb-3" 
                         style="max-height:300px; object-fit: cover; width: 100%;">
                <?php endif; ?>

                <div class="mb-3">
                    <strong>URL Website:</strong>
                    <?php if ($service['service_url']): ?>
                        <a href="<?= htmlspecialchars($service['service_url']) ?>" 
                           target="_blank" 
                           class="text-break">
                           <?= htmlspecialchars($service['service_url']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">(Tidak ada URL)</span>
                    <?php endif; ?>
                </div>

                <div classm"mb-3">
                    <strong>Deskripsi:</strong>
                    <p class="mb-0 text-break">
                        <?= $service['description'] ? nl2br(htmlspecialchars($service['description'])) : '<span class="text-muted">Tidak ada deskripsi.</span>' ?>
                    </p>
                    </div>

                <div class="mb-4 mt-3">
                    <strong>Status:</strong> <?= getStatusBadge($service['status']) ?>
                </div>

                <hr>
                
                <div class="d-flex gap-2 flex-wrap">
                    <a href="services_edit.php?id=<?= $service['id'] ?>" class="btn btn-warning flex-fill">
                        <i class="bi bi-pencil"></i> Edit Layanan
                    </a>
                    <a href="services_delete.php?id=<?= $service['id'] ?>" 
                       class="btn btn-danger flex-fill"
                       data-confirm-delete
                       data-title="<?= htmlspecialchars($service['title']) ?>"
                       data-message="Layanan &quot;<?= htmlspecialchars($service['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                       data-loading-text="Menghapus layanan..."
                       title="Hapus">
                        <i class="bi bi-trash"></i> Hapus
                    </a>
                    <a href="services_list.php" class="btn btn-secondary flex-fill">
                        <i class="bi bi-list"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>