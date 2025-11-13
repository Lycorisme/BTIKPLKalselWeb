<?php

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
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Banner</div>
                <div>
                    <a href="banners_add.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-sm-inline">Tambah Banner</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center mb-3 filter-form">
                    <div class="col-12 col-md-3">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Judul, caption..." class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $status === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="show_deleted" class="form-select form-select-sm">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="per_page" class="form-select form-select-sm">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?>/hlm</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-sm-inline">Filter</span>
                        </button>
                    </div>
                </form>

                <?php // Tombol reset dipindah ke luar form
                $isFiltered = $search || $status !== '' || $showDeleted === '1' || (isset($_GET['per_page']) && $_GET['per_page'] != getSetting('items_per_page', 10));
                if ($isFiltered): ?>
                    <div class="mb-3">
                        <a href="banners_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (empty($banners)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-images" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="text-muted mt-3">Tidak ada banner ditemukan</h5>
                        <p class="text-muted"><a href="banners_add.php">Tambah banner baru</a></p>
                    </div>
                <?php else: ?>
                    
                    <div class="table-responsive d-none d-md-block">
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
                                            <div class="fw-semibold text-break"><?= htmlspecialchars($banner['title']) ?></div>
                                            <small class="text-muted text-break"><?= htmlspecialchars(truncateText($banner['caption'] ?? '', 50)) ?></small>
                                            <?php if ($isTrashed): ?>
                                                <span class="badge bg-secondary ms-2">Terhapus</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($banner['link_url']): ?>
                                                <a href="<?= htmlspecialchars($banner['link_url']) ?>" target="_blank" class="text-primary small text-break">
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
                                                <span class="text-danger fw-semibold text-break">
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

                    <div class="d-block d-md-none">
                        <?php foreach ($banners as $idx => $banner):
                            $isTrashed = !is_null($banner['deleted_at'] ?? null);
                        ?>
                            <div class="card mb-3 <?= $isTrashed ? 'border-danger' : '' ?>">
                                <div class="card-body pb-0">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <img src="<?= uploadUrl($banner['image_path']) ?>" 
                                                 alt="Banner" 
                                                 class="thumbnail-preview-link"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#thumbPreviewModal" 
                                                 data-img="<?= uploadUrl($banner['image_path']) ?>"
                                                 style="width: 100px; height: 60px; object-fit: cover; border-radius: 6px; cursor: pointer;">
                                        </div>
                                        <div class="flex-grow-1" style="min-width: 0;"> <h6 class="card-title mb-1 text-truncate" title="<?= htmlspecialchars($banner['title']) ?>">
                                                <?= htmlspecialchars($banner['title']) ?>
                                            </h6>
                                            <?php if ($banner['caption']): ?>
                                                <p class="card-text text-muted small mb-2 text-truncate" title="<?= htmlspecialchars($banner['caption']) ?>">
                                                    <?= htmlspecialchars($banner['caption']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <div>
                                                <?php if ($banner['is_active']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                                <?php if ($isTrashed): ?>
                                                    <span class="badge bg-danger ms-1">Terhapus</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($isTrashed): ?>
                                        <div class="text-danger small fw-semibold mt-2">
                                            <i class="bi bi-trash"></i> Deleted at <?= formatTanggal($banner['deleted_at'], 'd M Y H:i') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <ul class="list-group list-group-flush" style="font-size: 0.9rem;">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Link URL</span>
                                        <div class="text-end" style="max-width: 70%;">
                                            <?php if ($banner['link_url']): ?>
                                                <a href="<?= htmlspecialchars($banner['link_url']) ?>" target="_blank" class="text-primary text-truncate d-block">
                                                    <i class="bi bi-link-45deg"></i> <?= htmlspecialchars($banner['link_url']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Urutan</span>
                                        <span class="badge bg-light text-dark"><?= (int)($banner['ordering'] ?? 0) ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Dibuat</span>
                                        <small><?= formatTanggal($banner['created_at'], 'd M Y') ?></small>
                                    </li>
                                </ul>
                                
                                <?php if (!$isTrashed): ?>
                                    <div class="card-footer d-flex justify-content-end gap-2">
                                        <a href="banners_edit.php?id=<?= $banner['id'] ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="banners_delete.php?id=<?= $banner['id'] ?>" class="btn btn-danger btn-sm"
                                           data-confirm-delete
                                           data-title="<?= htmlspecialchars($banner['title']) ?>"
                                           data-message="Banner &quot;<?= htmlspecialchars($banner['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                           data-loading-text="Menghapus banner...">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

                <?php if ($totalBanners > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                <span class="d-none d-md-inline">Halaman <?= $page ?> dari <?= $totalPages ?> ·</span>
                                <span><?= count($banners) ?>/<?= $totalBanners ?> banner</span>
                            </small>
                        </div>
                        
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                
                                <?php // Tombol Previous ?>
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php // Nomor Halaman dengan Logika "..." ?>
                                <?php
                                $from = max(1, $page - 1);
                                $to = min($totalPages, $page + 1);
                                
                                // Show first page on mobile
                                if ($from > 1) {
                                    echo '<li class="page-item d-none d-sm-block"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'">1</a></li>';
                                    if ($from > 2) {
                                        echo '<li class="page-item disabled d-none d-sm-block"><span class="page-link">...</span></li>';
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
                                        echo '<li class="page-item disabled d-none d-sm-block"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item d-none d-sm-block"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'">'.$totalPages.'</a></li>';
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

            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="thumbPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Banner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="modal-thumb-area" style="min-height:400px; display:flex; align-items:center; justify-content:center;">
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
        var modalBody = document.getElementById("modal-thumb-area");
        
        // Tampilkan spinner dulu
        modalBody.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>`;
        
        // Buat image object untuk pre-load
        var img = new Image();
        img.onload = function() {
            // Setelah gambar selesai di-load, ganti spinner dengan gambar
            modalBody.innerHTML = `<img src="${url}" style="max-width:99%; max-height:70vh; border-radius:8px;" />`;
        };
        img.onerror = function() {
            // Jika gambar gagal di-load
            modalBody.innerHTML = `<div class="text-danger">Gagal memuat gambar.</div>`;
        }
        img.src = url; // Mulai loading gambar
    });
});
</script>

<?php include '../../includes/footer.php'; ?>