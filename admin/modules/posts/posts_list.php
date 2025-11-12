<?php
/**
 * Posts List Page (Full Mazer, soft delete fixed, custom notif/swal)
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/Post.php';
require_once '../../../models/PostCategory.php';

$pageTitle = 'Kelola Post';
$db = Database::getInstance()->getConnection();
$postModel = new Post();
$categoryModel = new PostCategory();

// Get filters/pagination
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = (int)($_GET['per_page'] ?? 10);
$search    = trim($_GET['search'] ?? '');
$categoryId= trim($_GET['category_id'] ?? '');
$status    = trim($_GET['status'] ?? '');
$showTrashed = ($_GET['show_deleted'] ?? '0') === '1';

$where = [];
$params = [];

if ($search) {
    $where[] = "(p.title LIKE ? OR p.excerpt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status) {
    $where[] = "p.status = ?";
    $params[] = $status;
}
if ($categoryId) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}
if (!$showTrashed) {
    $where[] = "p.deleted_at IS NULL";
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) FROM posts p $whereSql";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*, c.name AS category_name, u.name as author_name 
        FROM posts p 
        LEFT JOIN post_categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id
        $whereSql 
        ORDER BY p.created_at DESC 
        LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categories = $categoryModel->getActive();
$statusOps = [
    '' => 'Semua Status',
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived'
];
$perPageOps = [5, 10, 25, 50, 100];
?>

<?php include '../../includes/header.php'; ?>

<style>
.truncate-title {
    display: inline-block;
    max-width: 400px; /* Anda bisa sesuaikan lebar maksimum judul di sini */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: top; /* Agar sejajar dengan badge 'Featured' jika ada */
}
</style>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola berita, artikel, dan pengumuman</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Post</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
    <div class="card shadow">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="card-title m-0">Daftar Post</div>
            <div class="action-btn-group d-flex flex-row gap-2">
                <a href="posts_add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> <span class="d-none d-md-inline">Tambah Post</span>
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-center mb-3">
                <div class="col-sm-3 col-12">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari judul, excerpt..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-sm-2 col-6">
                    <select name="category_id" class="form-select custom-dropdown">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-2 col-6">
                    <select name="status" class="form-select custom-dropdown">
                        <?php foreach ($statusOps as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $status === $val ? "selected" : "" ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-2 col-6">
                    <select name="per_page" class="form-select custom-dropdown">
                        <?php foreach ($perPageOps as $n): ?>
                            <option value="<?= $n ?>" <?= $perPage == $n ? "selected" : "" ?>>
                                Tampil <?= $n ?>/hlm
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-2 col-6">
                    <select name="show_deleted" class="form-select custom-dropdown">
                        <option value="0" <?= !$showTrashed ? "selected" : "" ?>>Hide Soft Deleted</option>
                        <option value="1" <?= $showTrashed ? "selected" : "" ?>>Show Soft Deleted</option>
                    </select>
                </div>
                <div class="col-sm-1 col-12">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search"></i> <span class="d-none d-md-inline"></span>
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="posts-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">No</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Tanggal</th>
                            <th class="text-center">Views</th>
                            <th class="text-center" style="width:180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Data tidak ditemukan.
                            </td>
                        </tr>
                    <?php else:
                        foreach ($posts as $i => $post):
                            $isTrashed = !is_null($post['deleted_at'] ?? null); ?>
                        <tr <?= $isTrashed ? 'class="table-danger text-muted"' : '' ?>>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <strong class="truncate-title" title="<?= htmlspecialchars($post['title']) ?>">
                                    <?= htmlspecialchars($post['title']) ?>
                                </strong>
                                <?php if (!empty($post['is_featured'])): ?>
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="bi bi-star-fill"></i> Featured
                                    </span>
                                <?php endif; ?>
                                <?php if ($isTrashed): ?>
                                    <span class="badge bg-secondary ms-2">Terhapus</span>
                                <?php endif; ?>
                                <br><small class="text-muted"><?= truncateText(strip_tags($post['excerpt'] ?? ''), 60) ?></small>
                            </td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($post['category_name'] ?? '-') ?></span></td>
                            <td><small><?= htmlspecialchars($post['author_name'] ?? '-') ?></small></td>
                            <td class="text-center"><?= getStatusBadge($post['status']) ?></td>
                            <td class="text-center"><small><?= formatTanggal($post['published_at'] ?: $post['created_at'], 'd M Y') ?></small></td>
                            <td class="text-center"><span class="badge bg-info"><?= formatNumber($post['view_count'] ?? 0) ?></span></td>
                            <td class="text-center">
                                <?php if ($isTrashed): ?>
                                    <span class="text-danger">
                                        <strong>Deleted at <?= formatTanggal($post['deleted_at'], 'd M Y H:i') ?></strong>
                                    </span>
                                <?php else: ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="posts_view.php?id=<?= $post['id'] ?>" class="btn btn-info" title="Lihat"><i class="bi bi-eye"></i></a>
                                        <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                            <a href="posts_edit.php?id=<?= $post['id'] ?>" class="btn btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <a href="posts_delete.php?id=<?= $post['id'] ?>" class="btn btn-danger"
                                                data-confirm-delete
                                                data-title="<?= htmlspecialchars($post['title']) ?>"
                                                data-message="Post &quot;<?= htmlspecialchars($post['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                data-loading-text="Menghapus post..."
                                                title="Hapus"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                <div>
                    <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> | Menampilkan <?= min($perPage, $totalItems - $offset) ?> dari <?= $totalItems ?> post</small>
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php
                        $from = max(1, $page - 2);
                        $to = min($totalPages, $page + 2);
                        for ($i = $from; $i <= $to; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
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