<?php
/**
 * Services List Page - Full Mazer, Soft Delete, Custom Notif/Confirm, Responsive Table
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/Service.php';

$pageTitle = 'Kelola Layanan';
$db = Database::getInstance()->getConnection();
$serviceModel = new Service();

// Pagination & Filter
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';

// Build WHERE clause - MUST INCLUDE deleted_at check
$where = [];
$params = [];

// DEFAULT: Hanya tampilkan data yang BELUM di-delete
if ($showDeleted !== '1') {
    $where[] = "deleted_at IS NULL";
}

// Filter status
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

// Filter search
if ($search) {
    $where[] = "(title LIKE ? OR slug LIKE ? OR description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total
$countSql = "SELECT COUNT(*) FROM services $whereClause";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));
$offset = ($page - 1) * $perPage;

// Get data - FIXED: removed ORDER BY `order` ASC
$sql = "SELECT * FROM services $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Status counts
$statusCounts = $serviceModel->countByStatus();

// Options for dropdown
$statusOptions = [
    '' => 'Semua Status',
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived'
];
$perPageOptions = [10, 25, 50, 100];
$showDeletedOptions = [
    '0' => 'Tampilkan Data Aktif',
    '1' => 'Tampilkan Data Terhapus'
];

include '../../includes/header.php';
?>

<!-- 
================================================================
 CSS PERBAIKAN UNTUK TEXT OVERFLOW
================================================================
-->
<style>
.truncate-text {
    display: inline-block;
    max-width: 300px; /* Lebar maksimum teks sebelum dipotong */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle; /* Menjaga teks tetap rapi di tengah sel */
}
</style>
<!-- ============================================================ -->


<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola layanan dan informasi publik</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Layanan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Layanan</div>
                <div>
                    <a href="services_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline">Tambah Layanan</span>
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Filter Panel -->
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-sm-3">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari layanan..." class="form-control">
                    </div>
                    <div class="col-6 col-sm-3">
                        <select name="status" class="form-select custom-dropdown">
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= $val ?>"<?= $status === $val ? ' selected' : '' ?>><?= $label ?></option>
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
                    <div class="col-6 col-sm-2">
                        <select name="per_page" class="form-select custom-dropdown">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage == $n ? ' selected' : '' ?>><?= $n ?>/Halaman</option>
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
                        <a href="services_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Services Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Judul</th>
                                <th>Slug</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Dibuat</th>
                                <th class="text-center" style="width:150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            <?php else: foreach ($services as $i => $service):
                                $isTrashed = !is_null($service['deleted_at'] ?? null);
                            ?>
                                <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <!-- ===== PERBAIKAN DI SINI ===== -->
                                        <strong class="truncate-text" title="<?= htmlspecialchars($service['title']) ?>">
                                            <?= htmlspecialchars($service['title']) ?>
                                        </strong>
                                        <!-- ========================== -->

                                        <?php if ($isTrashed): ?>
                                            <span class="badge bg-secondary ms-2">Terhapus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- ===== PERBAIKAN DI SINI ===== -->
                                        <code class="truncate-text" title="<?= htmlspecialchars($service['slug']) ?>">
                                            <?= htmlspecialchars($service['slug']) ?>
                                        </code>
                                        <!-- ========================== -->
                                    </td>
                                    <td>
                                        <small><?= truncateText(strip_tags($service['description'] ?? ''), 60) ?></small>
                                    </td>
                                    <td class="text-center"><?= getStatusBadge($service['status']) ?></td>
                                    <td class="text-center">
                                        <small><?= formatTanggal($service['created_at'], 'd M Y') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isTrashed): ?>
                                            <span class="text-danger fw-semibold">
                                                Deleted at <?= formatTanggal($service['deleted_at'], 'd M Y H:i') ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="services_view.php?id=<?= $service['id'] ?>" class="btn btn-info" title="Lihat">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="services_edit.php?id=<?= $service['id'] ?>" class="btn btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="services_delete.php?id=<?= $service['id'] ?>" class="btn btn-danger"
                                                   data-confirm-delete
                                                   data-title="<?= htmlspecialchars($service['title']) ?>"
                                                   data-message="Layanan &quot;<?= htmlspecialchars($service['title']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                   data-loading-text="Menghapus layanan..."
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

                <!-- Pagination bawah selalu tampil -->
                <?php if ($totalItems > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($services) ?> dari <?= $totalItems ?> layanan
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