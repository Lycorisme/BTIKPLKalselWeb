<?php
/**
 * Categories List Page - Full Mazer, Soft Delete, Custom Notif/Confirm, Responsive Table
 * Default: menampilkan data aktif saja (deleted_at IS NULL)
 * Filter: show_deleted untuk menampilkan data terhapus
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/PostCategory.php';

$pageTitle = 'Kelola Kategori';
$db = Database::getInstance()->getConnection();

$itemsPerPage = (int)getSetting('items_per_page', 10);
$isActive = $_GET['is_active'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build WHERE clause - MUST INCLUDE deleted_at check
$where = [];
$params = [];

// DEFAULT: Hanya tampilkan data yang BELUM di-delete
if ($showDeleted !== '1') {
    $where[] = "pc.deleted_at IS NULL";
}

// Filter status aktif
if ($isActive !== '') {
    $where[] = "pc.is_active = ?";
    $params[] = $isActive;
}

// Filter search
if ($search) {
    $where[] = "(pc.name LIKE ? OR pc.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total
$countSql = "SELECT COUNT(*) FROM post_categories pc $whereClause";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalItems / $itemsPerPage));
$offset = ($page - 1) * $itemsPerPage;

// Get data dengan post count
$sql = "
    SELECT 
        pc.*,
        COUNT(p.id) as post_count
    FROM post_categories pc
    LEFT JOIN posts p ON pc.id = p.category_id AND p.deleted_at IS NULL
    $whereClause
    GROUP BY pc.id
    ORDER BY pc.name ASC
    LIMIT ? OFFSET ?
";

$params[] = $itemsPerPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Options for dropdown
$activeOptions = [
    '' => 'Semua Status',
    '1' => 'Aktif',
    '0' => 'Nonaktif'
];
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
                <p class="text-subtitle text-muted">Kelola kategori berita & artikel</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Kategori</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Kategori</div>
                <div>
                    <a href="categories_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline">Tambah Kategori</span>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-sm-3">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama kategori..." class="form-control">
                    </div>
                    <div class="col-6 col-sm-3">
                        <select name="is_active" class="form-select custom-dropdown">
                            <?php foreach ($activeOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $isActive === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3">
                        <select name="show_deleted" class="form-select custom-dropdown">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-3">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline">Filter</span>
                        </button>
                    </div>
                </form>

                <?php if ($isActive !== '' || $search || $showDeleted === '1'): ?>
                    <div class="mb-3">
                        <a href="categories_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Kategori</th>
                                <th class="text-center" style="width:130px;">Jumlah Post</th>
                                <th class="text-center" style="width:100px;">Status</th>
                                <th class="text-center" style="width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            <?php else: foreach ($categories as $i => $cat):
                                $isTrashed = !is_null($cat['deleted_at'] ?? null);
                            ?>
                                <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($cat['name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><code><?= $cat['slug'] ?></code></small>
                                        <?php if ($cat['description']): ?>
                                            <br>
                                            <small class="text-muted"><?= truncateText($cat['description'], 60) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="badge bg-info"><?= $cat['post_count'] ?> post</span></td>
                                    <td class="text-center">
                                        <?php if ($cat['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isTrashed): ?>
                                            <span class="text-danger fw-semibold">
                                                Deleted at <?= formatTanggal($cat['deleted_at'], 'd M Y H:i') ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>news/category.php?slug=<?= $cat['slug'] ?>"
                                                   class="btn btn-info" target="_blank" title="Lihat">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                                    <a href="categories_edit.php?id=<?= $cat['id'] ?>"
                                                       class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasRole(['super_admin', 'admin'])): ?>
                                                    <a href="categories_delete.php?id=<?= $cat['id'] ?>"
                                                       class="btn btn-danger" title="Hapus"
                                                       data-confirm-delete
                                                       data-title="<?= htmlspecialchars($cat['name']) ?>"
                                                       data-message="Kategori &quot;<?= htmlspecialchars($cat['name']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                       data-loading-text="Menghapus kategori...">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination bawah selalu tampil -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                    <div>
                        <small class="text-muted">
                            Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($categories) ?> dari <?= $totalItems ?> kategori
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
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>
