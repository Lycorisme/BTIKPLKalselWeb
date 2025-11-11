<?php
/**
 * Tags List Page - Full Mazer, Soft Delete, Custom Notif/Confirm, Responsive Table
 * Default: menampilkan tags aktif saja (deleted_at IS NULL)
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/Tag.php';

$pageTitle = 'Kelola Tags';
$db = Database::getInstance()->getConnection();

$itemsPerPage = (int)getSetting('items_per_page', 10);
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build WHERE clause - MUST INCLUDE deleted_at check
$where = [];
$params = [];

// DEFAULT: Hanya tampilkan data yang BELUM di-delete
if ($showDeleted !== '1') {
    $where[] = "t.deleted_at IS NULL";
}

// Filter search
if ($search) {
    $where[] = "(t.name LIKE ? OR t.slug LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total
$countSql = "SELECT COUNT(*) FROM tags t $whereClause";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalItems / $itemsPerPage));
$offset = ($page - 1) * $itemsPerPage;

// Get data dengan post count
$sql = "
    SELECT 
        t.*,
        COUNT(pt.post_id) as post_count
    FROM tags t
    LEFT JOIN post_tags pt ON t.id = pt.tag_id
    $whereClause
    GROUP BY t.id
    ORDER BY t.name ASC
    LIMIT ? OFFSET ?
";

$params[] = $itemsPerPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tags = $stmt->fetchAll();

// Options for dropdown
$showDeletedOptions = [
    '0' => 'Tampilkan Tags Aktif',
    '1' => 'Tampilkan Tags Terhapus'
];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola tags untuk berita & artikel</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tags</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Tags</div>
                <div>
                    <a href="tags_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline">Tambah Tag</span>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Filter Panel -->
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-sm-6">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau slug tag..." class="form-control">
                    </div>
                    <div class="col-6 col-sm-3">
                        <select name="show_deleted" class="form-select custom-dropdown">
                            <?php foreach ($showDeletedOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $showDeleted === $val ? ' selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline">Filter</span>
                        </button>
                    </div>
                </form>

                <?php if ($search || $showDeleted === '1'): ?>
                    <div class="mb-3">
                        <a href="tags_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Tags Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Nama Tag</th>
                                <th>Slug</th>
                                <th class="text-center" style="width:130px;">Jumlah Post</th>
                                <th class="text-center" style="width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            <?php else: foreach ($tags as $i => $tag):
                                $isTrashed = !is_null($tag['deleted_at'] ?? null);
                            ?>
                                <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">#<?= htmlspecialchars($tag['name']) ?></span>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($tag['slug']) ?></code>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= formatNumber($tag['post_count']) ?> post</span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isTrashed): ?>
                                            <span class="text-danger fw-semibold">
                                                Deleted at <?= formatTanggal($tag['deleted_at'], 'd M Y H:i') ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>news/tag.php?slug=<?= $tag['slug'] ?>"
                                                   class="btn btn-info" target="_blank" title="Lihat">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                                    <a href="tags_edit.php?id=<?= $tag['id'] ?>"
                                                       class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasRole(['super_admin', 'admin'])): ?>
                                                    <a href="tags_delete.php?id=<?= $tag['id'] ?>"
                                                       class="btn btn-danger" title="Hapus"
                                                       data-confirm-delete
                                                       data-title="<?= htmlspecialchars($tag['name']) ?>"
                                                       data-message="Tag &quot;<?= htmlspecialchars($tag['name']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                       data-loading-text="Menghapus tag...">
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
                <?php if ($totalItems > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($tags) ?> dari <?= $totalItems ?> tags
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
