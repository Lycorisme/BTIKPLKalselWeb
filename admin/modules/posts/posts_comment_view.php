<?php
/**
 * View & Manage Post Comments
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Post.php';
require_once '../../../models/Comment.php';

// Hanya Super Admin dan Admin yang bisa kelola komentar
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Kelola Komentar';
 $currentPage = 'posts';

 $postModel = new Post();
 $commentModel = new Comment();
 $db = Database::getInstance()->getConnection();

// 1. Get Post ID
 $postId = $_GET['id'] ?? 0;
 $post = $postModel->getById($postId);

if (!$post) {
    setAlert('danger', 'Post tidak ditemukan');
    redirect(ADMIN_URL . 'modules/posts/posts_list.php');
}

// 2. Get filters/pagination
 $page      = max(1, (int)($_GET['page'] ?? 1));
 $perPage   = (int)($_GET['per_page'] ?? 10);
 $search    = trim($_GET['search'] ?? '');
 $dateFrom  = trim($_GET['date_from'] ?? '');
 $dateTo    = trim($_GET['date_to'] ?? '');

 $where = [];
 $params = [];

if ($search) {
    $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($dateFrom) {
    $where[] = "c.created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[] = "c.created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

 $where[] = "c.commentable_type = 'post' AND c.commentable_id = ?";
 $params[] = $postId;

 $whereSql = 'WHERE ' . implode(' AND ', $where);

// Count total comments
 $countSql = "SELECT COUNT(*) FROM comments c $whereSql";
 $stmtCount = $db->prepare($countSql);
 $stmtCount->execute($params);
 $totalItems = (int)$stmtCount->fetchColumn();
 $totalPages = max(1, ceil($totalItems / $perPage));
 $offset = ($page - 1) * $perPage;

// Get comments with pagination
 $sql = "SELECT * FROM comments c 
        $whereSql 
        ORDER BY c.created_at DESC 
        LIMIT $perPage OFFSET $offset";
 $stmt = $db->prepare($sql);
 $stmt->execute($params);
 $comments = $stmt->fetchAll();

 $perPageOps = [5, 10, 25, 50, 100];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 mb-3">
                <h3><i class=""></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12">
                <nav aria-label="breadcrumb" class="breadcrumb-header">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="posts_list.php">Post</a></li>
                        <li class="breadcrumb-item"><a href="posts_view.php?id=<?= $postId ?>">Detail</a></li>
                        <li class="breadcrumb-item active">Komentar</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Post Summary Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="card-title mb-1"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="text-muted mb-0">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($post['author_name']) ?> 
                            <span class="mx-2">•</span>
                            <i class="bi bi-calendar-event me-1"></i> <?= formatTanggal($post['published_at'] ?: $post['created_at'], 'd M Y') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="posts_view.php?id=<?= $postId ?>" class="btn btn-primary w-100 w-md-auto">
                            <i class="bi bi-eye me-1"></i> Lihat Post
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Table -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0">Daftar Komentar (<?= $totalItems ?>)</h5>
                <div class="d-flex gap-2 w-100">
                    <a href="posts_view.php?id=<?= $postId ?>" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Post
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" class="mb-3">
                    <input type="hidden" name="id" value="<?= $postId ?>">
                    <div class="row g-2">
                        <div class="col-12">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari nama, email, atau isi komentar..."
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <input type="date" name="date_from" class="form-control" 
                                   placeholder="Dari Tanggal"
                                   value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <input type="date" name="date_to" class="form-control" 
                                   placeholder="Sampai Tanggal"
                                   value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="per_page" class="form-select">
                                <?php foreach ($perPageOps as $n): ?>
                                    <option value="<?= $n ?>" <?= $perPage == $n ? "selected" : "" ?>>
                                        Tampil <?= $n ?>/hlm
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <?php if (empty($comments)): ?>
                    <div class="text-center py-5">
                        <div class="avatar avatar-lg bg-light-secondary mb-3">
                            <i class="bi bi-chat-dots fs-1"></i>
                        </div>
                        <h5>Belum ada komentar</h5>
                        <p class="text-muted">Post ini belum memiliki komentar yang sesuai dengan filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Penulis</th>
                                    <th>Komentar</th>
                                    <th class="text-center">Status</th>
                                    <th>Waktu</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): 
                                    $status = $comment['status'];
                                    $badgeClass = 'secondary';
                                    if ($status == 'approved') $badgeClass = 'success';
                                    if ($status == 'pending') $badgeClass = 'warning';
                                    if ($status == 'rejected' || $status == 'spam') $badgeClass = 'danger';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-light-primary me-2">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($comment['name']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($comment['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="comment-content">
                                            <p class="mb-1" style="white-space: pre-wrap;"><?= htmlspecialchars($comment['content']) ?></p>
                                            <small class="text-muted fst-italic">IP: <?= htmlspecialchars($comment['ip_address']) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                    </td>
                                    <td>
                                        <small><?= formatTanggalRelatif($comment['created_at']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <a href="comment_delete.php?id=<?= $comment['id'] ?>&post_id=<?= $postId ?>" 
                                           class="btn btn-danger btn-sm"
                                           data-confirm-delete
                                           data-title="Hapus Komentar Ini?"
                                           data-message="Anda yakin ingin menghapus komentar dari '<?= htmlspecialchars($comment['name']) ?>' secara permanen?"
                                           data-loading-text="Menghapus..."
                                           title="Hapus Permanen">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> | Menampilkan <?= min($perPage, $totalItems - $offset) ?> dari <?= $totalItems ?> komentar</small>
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