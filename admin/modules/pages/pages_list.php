<?php
/**
 * Pages List - Full Mazer Design with Soft Delete, Pagination, Search
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/Page.php';

$pageTitle = 'Kelola Halaman';
$currentPage = 'pages';

$db = Database::getInstance()->getConnection();
$pageModel = new Page();

// Pagination & Filter
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 15);
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build filters
$filters = [];
if ($search) $filters['search'] = $search;
if ($status) $filters['status'] = $status;
if ($showDeleted === '1') $filters['show_deleted'] = true;

// Get paginated data
$result = $pageModel->getPaginated($page, $perPage, $filters);
$pages = $result['data'];
$totalItems = $result['total'];
$totalPages = $result['last_page'];
$offset = ($page - 1) * $perPage;

// Options for dropdown
$statusOptions = [
    '' => 'Semua Status',
    'draft' => 'Draft',
    'published' => 'Published'
];
$perPageOptions = [10, 15, 25, 50];
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
                <p class="text-subtitle text-muted">Kelola semua halaman statis di website</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Halaman</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Halaman</div>
                <div>
                    <a href="pages_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline">Tambah Halaman</span>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-sm-4">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari halaman..." class="form-control">
                    </div>
                    <div class="col-6 col-sm-3">
                        <select name="status" class="form-select custom-dropdown">
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $status === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="show_deleted" class="form-select custom-dropdown">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="per_page" class="form-select custom-dropdown">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?>/hlm</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-1">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i> <span class="d-none d-md-inline"></span>
                        </button>
                    </div>
                </form>

                <?php if ($search || $status || $showDeleted === '1'): ?>
                    <div class="mb-3">
                        <a href="pages_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Judul</th>
                                <th>Slug</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Urutan</th>
                                <th class="text-center">Views</th>
                                <th class="text-center">Dibuat</th>
                                <th class="text-center" style="width:120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pages)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            <?php else: foreach ($pages as $i => $p):
                                $isTrashed = !is_null($p['deleted_at'] ?? null);
                            ?>
                                <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <strong class="text-break"><?= htmlspecialchars($p['title']) ?></strong>
                                        <?php if ($isTrashed): ?>
                                            <span class="badge bg-secondary ms-2">Terhapus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="text-break"><?= htmlspecialchars($p['slug']) ?></code>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($p['status'] === 'published'): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark"><?= (int)$p['display_order'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= formatNumber($p['view_count'] ?? 0) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <small><?= formatTanggal($p['created_at'], 'd M Y') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isTrashed): ?>
                                            <span class="text-danger fw-semibold text-break">
                                                Deleted at <?= formatTanggal($p['deleted_at'], 'd M Y H:i') ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="pages_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="pages_delete.php?id=<?= $p['id'] ?>" class="btn btn-danger"
                                                   data-confirm-delete
                                                   data-title="<?= htmlspecialchars($p['title']) ?>"
                                                   data-message="Halaman &quot;<?= htmlspecialchars($p['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                   data-loading-text="Menghapus halaman..."
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalItems > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($pages) ?> dari <?= $totalItems ?> halaman
                            </small>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php
                                $from = max(1, $page - 2);
                                $to = min($totalPages, $page + 2);
                                for ($i = $from; $i <= $to; $i++): ?>
                                    <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
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

<?php include '../../includes/footer.php'; ?>