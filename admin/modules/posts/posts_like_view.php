<?php
/**
 * View Post Likes
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/Post.php';

// Hanya Super Admin dan Admin yang bisa melihat ini
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Detail Likes';
 $currentPage = 'posts';

 $postModel = new Post();
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
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR pl.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($dateFrom) {
    $where[] = "pl.created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[] = "pl.created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

 $where[] = "pl.post_id = ?";
 $params[] = $postId;

 $whereSql = 'WHERE ' . implode(' AND ', $where);

// Count total likes
 $countSql = "SELECT COUNT(*) FROM post_likes pl
             LEFT JOIN users u ON pl.user_id = u.id
             $whereSql";
 $stmtCount = $db->prepare($countSql);
 $stmtCount->execute($params);
 $totalItems = (int)$stmtCount->fetchColumn();
 $totalPages = max(1, ceil($totalItems / $perPage));
 $offset = ($page - 1) * $perPage;

// Get likes with pagination
 $sql = "SELECT pl.*, u.name as user_name, u.email as user_email 
        FROM post_likes pl
        LEFT JOIN users u ON pl.user_id = u.id
        $whereSql 
        ORDER BY pl.created_at DESC 
        LIMIT $perPage OFFSET $offset";
 $stmt = $db->prepare($sql);
 $stmt->execute($params);
 $likes = $stmt->fetchAll();

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
                        <li class="breadcrumb-item active">Likes</li>
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

        <!-- Likes Table -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0">Daftar Likes (<?= $totalItems ?>)</h5>
                <div class="d-flex gap-2">
                    <a href="posts_view.php?id=<?= $postId ?>" class="btn btn-secondary">
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
                                placeholder="Cari nama, email, atau IP address..."
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

                <?php if (empty($likes)): ?>
                    <div class="text-center py-5">
                        <div class="avatar avatar-lg bg-light-secondary mb-3">
                            <i class="bi bi-heart fs-1"></i>
                        </div>
                        <h5>Belum ada like</h5>
                        <p class="text-muted">Post ini belum memiliki like yang sesuai dengan filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Pengguna</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($likes as $i => $like): ?>
                                <tr>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <?php if ($like['user_id']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-light-success me-2">
                                                    <i class="bi bi-person-check-fill"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($like['user_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($like['user_email']) ?></small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-light-secondary me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <span class="text-muted fst-italic">Tamu (Guest)</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light-secondary"><?= htmlspecialchars($like['ip_address']) ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted" title="<?= htmlspecialchars($like['user_agent']) ?>">
                                            <?= htmlspecialchars(truncateText($like['user_agent'], 80)) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?= formatTanggalRelatif($like['created_at']) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> | Menampilkan <?= min($perPage, $totalItems - $offset) ?> dari <?= $totalItems ?> like</small>
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