<?php
/**
 * Banners List - Full Mazer Design with Soft Delete, Search
 * Layout disamakan dengan files_list.php (Single Card Layout)
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/Banner.php';

$pageTitle = 'Manajemen Banner';
$currentPage = 'banners';

$db = Database::getInstance()->getConnection();
$bannerModel = new Banner();

// Pagination & Filter
$page = max(1, (int)($_GET['page'] ?? 1));
// Ambil perPage dari settings, atau default ke 10
$perPage = (int)($_GET['per_page'] ?? getSetting('items_per_page', 10));
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build filters
$filters = [];
if ($search) $filters['search'] = $search;
if ($status !== '') $filters['status'] = $status;
if ($showDeleted === '1') $filters['show_deleted'] = true;

// Get paginated data
$result = $bannerModel->getPaginated($page, $perPage, $filters);
$banners = $result['data'];
$totalBanners = $result['total'];
$totalPages = $result['last_page'];
$offset = ($page - 1) * $perPage;

// Options for dropdown
$statusOptions = [
    '' => 'Semua Status',
    '1' => 'Aktif',
    '0' => 'Nonaktif'
];
$perPageOptions = [10, 20, 50, 100];
// Perbarui nilai perPage default di dropdown jika tidak ada di list standar
if (!in_array($perPage, $perPageOptions) && $perPage > 0) {
    $perPageOptions[] = $perPage;
    sort($perPageOptions);
}

$showDeletedOptions = [
    '0' => 'Tampilkan Data Aktif',
    '1' => 'Tampilkan Data Terhapus'
];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola semua banner di website</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Banners</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title">Daftar Banner</h5>
                    <a href="banners_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Banner
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Cari Banner</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Judul, caption..." class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $status === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Tampilkan</label>
                        <select name="show_deleted" class="form-select form-select-sm">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Per Page</label>
                        <select name="per_page" class="form-select form-select-sm">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?>/hlm</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-search"></i> Cari
                        </button>
                        <?php if ($search || $status !== '' || $showDeleted === '1' || (isset($_GET['per_page']) && $_GET['per_page'] != getSetting('items_per_page', 10))): ?>
                            <a href="banners_list.php" class="btn btn-secondary btn-sm">
                                <i class="bi bi-x-circle"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <?php if (empty($banners)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-images" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="text-muted mt-3">Tidak ada banner ditemukan</h5>
                        <p class="text-muted"><a href="banners_add.php">Tambah banner baru</a></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th style="width:100px;">Gambar</th>
                                    <th>Judul & Caption</th>
                                    <th>Link</th>
                                    <th class="text-center" style="width:80px;">Status</th>
                                    <th class="text-center" style="width:80px;">Urutan</th>
                                    <th class="text-center" style="width:120px;">Dibuat</th>
                                    <th class="text-center" style="width:130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $idx => $banner):
                                    $isTrashed = !is_null($banner['deleted_at'] ?? null);
                                ?>
                                    <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                        <td><?= $offset + $idx + 1 ?></td>
                                        <td>
                                            <div style="width:90px; height:54px; overflow:hidden; border-radius:5px; background:#f5f5f5; cursor:pointer;" 
                                                 class="thumbnail-preview-link" 
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#thumbPreviewModal" 
                                                 data-img="<?= uploadUrl($banner['image_path']) ?>">
                                                <img src="<?= uploadUrl($banner['image_path']) ?>" alt="Banner" style="width:100%; height:100%; object-fit:cover;">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($banner['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(truncateText($banner['caption'] ?? '', 50)) ?></small>
                                            <?php if ($isTrashed): ?>
                                                <span class="badge bg-secondary ms-2">Terhapus</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($banner['link_url']): ?>
                                                <a href="<?= htmlspecialchars($banner['link_url']) ?>" target="_blank" class="text-primary small">
                                                    <i class="bi bi-link-45deg"></i> <?= truncateText($banner['link_url'], 40) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($banner['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark"><?= (int)($banner['ordering'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <small><?= formatTanggal($banner['created_at'], 'd M Y') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isTrashed): ?>
                                                <span class="text-danger fw-semibold">
                                                    Deleted at <?= formatTanggal($banner['deleted_at'], 'd M Y') ?>
                                                </span>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="banners_edit.php?id=<?= $banner['id'] ?>" class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="banners_delete.php?id=<?= $banner['id'] ?>" class="btn btn-danger"
                                                       data-confirm-delete
                                                       data-title="<?= htmlspecialchars($banner['title']) ?>"
                                                       data-message="Banner &quot;<?= htmlspecialchars($banner['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                       data-loading-text="Menghapus banner..."
                                                       title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($totalBanners > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($banners) ?> dari <?= $totalBanners ?> banner
                            </small>
                        </div>
                        
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                
                                <?php // Tombol Previous ?>
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php // Nomor Halaman dengan Logika "..." ?>
                                <?php
                                $from = max(1, $page - 2);
                                $to = min($totalPages, $page + 2);
                                
                                if ($from > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'">1</a></li>';
                                    if ($from > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $from; $i <= $to; $i++): ?>
                                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; 
                                
                                if ($to < $totalPages) {
                                    if ($to < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'">'.$totalPages.'</a></li>';
                                }
                                ?>
                                
                                <?php // Tombol Next ?>
                                <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div> </div> </section>
</div>

<div class="modal fade" id="thumbPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="modal-thumb-area" style="min-height:400px; display:flex; align-items-center; justify-content:center;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll(".thumbnail-preview-link").forEach(function(link) {
    link.addEventListener("click", function(e) {
        e.preventDefault();
        var url = this.dataset.img;
        document.getElementById("modal-thumb-area").innerHTML = `<img src="${url}" style="max-width:99%; max-height:70vh; border-radius:8px;" />`;
    });
});
</script>

<?php include '../../includes/footer.php'; ?>