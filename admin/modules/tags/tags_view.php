<?php
/**
 * View Posts by Tag
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Post.php';
// Tidak perlu memuat model Tag karena kita akan query langsung

 $pageTitle = 'Tag View';
 $currentPage = 'tags';

 $postModel = new Post();
 $db = Database::getInstance()->getConnection();

// Get tag ID
 $tagId = $_GET['id'] ?? 0;

// Direct query to get tag data since Tag model might not have getById method
 $stmt = $db->prepare("SELECT * FROM tags WHERE id = ? AND deleted_at IS NULL");
 $stmt->execute([$tagId]);
 $tag = $stmt->fetch();

if (!$tag) {
    setAlert('danger', 'Tag tidak ditemukan');
    redirect(ADMIN_URL . 'modules/tags/tags_list.php');
}

// Get filters/pagination
 $page      = max(1, (int)($_GET['page'] ?? 1));
 $perPage   = (int)($_GET['per_page'] ?? 10);
 $search    = trim($_GET['search'] ?? '');
 $status    = trim($_GET['status'] ?? '');

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

// Always filter by this tag
 $where[] = "pt.tag_id = ?";
 $params[] = $tagId;

// Don't show deleted posts
 $where[] = "p.deleted_at IS NULL";

 $whereSql = 'WHERE ' . implode(' AND ', $where);

// Count total posts
 $countSql = "SELECT COUNT(*) FROM posts p
            JOIN post_tags pt ON p.id = pt.post_id
            $whereSql";
 $stmtCount = $db->prepare($countSql);
 $stmtCount->execute($params);
 $totalItems = (int)$stmtCount->fetchColumn();
 $totalPages = max(1, ceil($totalItems / $perPage));
 $offset = ($page - 1) * $perPage;

// Get posts with pagination
 $sql = "SELECT p.*, c.name AS category_name, u.name as author_name 
        FROM posts p 
        JOIN post_tags pt ON p.id = pt.post_id
        LEFT JOIN post_categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id
        $whereSql 
        ORDER BY p.created_at DESC 
        LIMIT $perPage OFFSET $offset";
 $stmt = $db->prepare($sql);
 $stmt->execute($params);
 $posts = $stmt->fetchAll();

 $statusOps = [
    '' => 'Semua Status',
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived'
];
 $perPageOps = [5, 10, 25, 50, 100];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-8 mb-3 mb-md-0">
                <h3><i class=""></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-4">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-md-end">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="tags_list.php">Tags</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($tag['name']) ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Tag Summary Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-light text-dark border me-3 p-2 fs-5">#<?= htmlspecialchars($tag['name']) ?></span>
                            <div>
                                <h5 class="card-title mb-1">Tag: <?= htmlspecialchars($tag['name']) ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-file-text me-1"></i> <?= $totalItems ?> post menggunakan tag ini
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="tags_list.php" class="btn btn-primary w-100 w-md-auto">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Tags
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Post dengan Tag "<?= htmlspecialchars($tag['name']) ?>" (<?= $totalItems ?>)</h5>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <input type="hidden" name="id" value="<?= $tagId ?>">
                    <div class="col-sm-4 col-12">
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari judul, excerpt..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-sm-2 col-6">
                        <select name="status" class="form-select">
                            <?php foreach ($statusOps as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $status === $val ? "selected" : "" ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-2 col-6">
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOps as $n): ?>
                                <option value="<?= $n ?>" <?= $perPage == $n ? "selected" : "" ?>>
                                    Tampil <?= $n ?>/hlm
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4 col-12">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <div class="avatar avatar-lg bg-light-secondary mb-3">
                            <i class="bi bi-file-text fs-1"></i>
                        </div>
                        <h5>Tidak ada post</h5>
                        <p class="text-muted">Tidak ada post dengan tag ini yang sesuai dengan filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
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
                            <?php foreach ($posts as $i => $post): ?>
                                <tr>
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
                                        <br><small class="text-muted"><?= truncateText(strip_tags($post['excerpt'] ?? ''), 60) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($post['category_name'] ?? '-') ?></span></td>
                                    <td><small><?= htmlspecialchars($post['author_name'] ?? '-') ?></small></td>
                                    <td class="text-center"><?= getStatusBadge($post['status']) ?></td>
                                    <td class="text-center"><small><?= formatTanggal($post['published_at'] ?: $post['created_at'], 'd M Y') ?></small></td>
                                    <td class="text-center"><span class="badge bg-info"><?= formatNumber($post['view_count'] ?? 0) ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../posts/posts_view.php?id=<?= $post['id'] ?>" class="btn btn-info" title="Lihat"><i class="bi bi-eye"></i></a>
                                            <?php if (hasRole(['super_admin', 'admin', 'editor'])): ?>
                                                <a href="../posts/posts_edit.php?id=<?= $post['id'] ?>" class="btn btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
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
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>