<?php
/**
 * Contact Messages - List (Inbox) - Full Mazer Design
 * Layout di-refaktor agar konsisten dengan files_list.php
 * DITAMBAH: Filter Soft Delete (Tampilkan Data)
 *
 * PERBAIKAN: Form filter di-refactor, perbaikan dark mode (table-light dihapus)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Pesan Kontak';
$currentPage = 'contact';

$db = Database::getInstance()->getConnection();

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? getSetting('items_per_page', 15));

$showDeleted = $_GET['show_deleted'] ?? '0';
$offset = ($page - 1) * $perPage;

// Opsi dropdown
$perPageOptions = [10, 15, 20, 50, 100];
if (!in_array($perPage, $perPageOptions) && $perPage > 0) {
    $perPageOptions[] = $perPage;
    sort($perPageOptions);
}
$showDeletedOptions = [
    '0' => 'Tampilkan Data Aktif',
    '1' => 'Tampilkan Data Terhapus'
];

// Build WHERE clause
$whereConditions = [];
$params = [];

// Handle soft delete filter first
if ($showDeleted !== '1') {
    $whereConditions[] = "deleted_at IS NULL";
}

if ($status && in_array($status, ['unread', 'read'])) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM contact_messages {$whereClause}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetch()['total'];
$totalPages = max(1, ceil($totalItems / $perPage));

// Ensure page is valid
if ($page > $totalPages && $totalItems > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Get messages
$sql = "SELECT * FROM contact_messages {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pastikan Tab Counts juga menghormati filter soft-delete
$tabBaseWhere = "status IN ('unread', 'read')";
if ($showDeleted !== '1') {
    $tabBaseWhere .= " AND deleted_at IS NULL";
}

// Get status counts for filter tabs
$countsStmt = $db->query("
    SELECT status, COUNT(*) as count
    FROM contact_messages
    WHERE {$tabBaseWhere}
    GROUP BY status
");
$statusCounts = [];
foreach ($countsStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = $row['count'];
}

// Total all messages
$totalCountStmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE {$tabBaseWhere}");
$totalCount = (int)$totalCountStmt->fetch()['total'];


include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola semua pesan masuk dari kontak</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pesan Kontak</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow">
            <div class="card-header">
                <ul class="nav nav-tabs nav-tabs-sm" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $status === '' ? 'active' : '' ?>" 
                           href="?<?= http_build_query(array_merge($_GET, ['status' => '', 'page' => 1])) ?>">
                            <i class="bi bi-inbox me-1"></i>
                            <span class="d-none d-md-inline">Semua Pesan</span>
                            <span class="badge bg-secondary ms-1"><?= $totalCount ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'unread' ? 'active' : '' ?>" 
                           href="?<?= http_build_query(array_merge($_GET, ['status' => 'unread', 'page' => 1])) ?>">
                            <i class="bi bi-envelope me-1"></i>
                            <span class="d-none d-md-inline">Belum Dibaca</span>
                            <span class="badge bg-danger ms-1"><?= $statusCounts['unread'] ?? 0 ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'read' ? 'active' : '' ?>" 
                           href="?<?= http_build_query(array_merge($_GET, ['status' => 'read', 'page' => 1])) ?>">
                            <i class="bi bi-envelope-open me-1"></i>
                            <span class="d-none d-md-inline">Sudah Dibaca</span>
                            <span class="badge bg-primary ms-1"><?= $statusCounts['read'] ?? 0 ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <?php if ($status): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <?php endif; ?>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Cari Pesan</label>
                        <input type="text" 
                               class="form-control form-control-sm" 
                               name="search" 
                               placeholder="Cari nama, email, atau subject..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="col-6 col-md-3">
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

                    <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                        <button class="btn btn-primary btn-sm flex-grow-1" type="submit">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        
                        <?php 
                        $isFiltered = $search || $showDeleted === '1' || (isset($_GET['per_page']) && $_GET['per_page'] != getSetting('items_per_page', 15));
                        if ($isFiltered): 
                        ?>
                            <a href="?<?= $status ? 'status=' . urlencode($status) : '' ?>" class="btn btn-secondary btn-sm" title="Reset">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($messages)): ?>
                    <div class="py-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-muted mb-2">
                            <?php if ($search): ?>
                                Tidak ada pesan yang cocok
                            <?php elseif ($status): ?>
                                Tidak ada pesan dengan status ini
                            <?php else: ?>
                                Belum ada pesan masuk
                            <?php endif; ?>
                        </h5>
                        <p class="text-muted small">
                            <?php if ($search): ?>
                                Coba ubah pencarian Anda atau hapus filter
                            <?php else: ?>
                                Pesan kontak dari website akan muncul di sini
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    
                    <div class="table-responsive d-none d-lg-block">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:120px;">Status</th>
                                    <th>Pengirim</th>
                                    <th>Subject & Pesan</th>
                                    <th style="width:140px;">Tanggal</th>
                                    <th style="width:120px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): 
                                    $isTrashed = !is_null($message['deleted_at'] ?? null);
                                ?>
                                    <tr class="<?= $isTrashed ? ' table-danger text-muted' : '' ?>">
                                        <td>
                                            <?php if ($isTrashed): ?>
                                                <span class="badge bg-dark">
                                                    <i class="bi bi-trash me-1"></i> Terhapus
                                                </span>
                                            <?php elseif ($message['status'] === 'unread'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-envelope-fill me-1"></i> Belum Dibaca
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="bi bi-envelope-open me-1"></i> Sudah Dibaca
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold <?= $message['status'] === 'unread' && !$isTrashed ? '' : 'text-muted' ?> text-break">
                                                <?= htmlspecialchars($message['name']) ?>
                                            </div>
                                            <small class="text-muted d-block text-break">
                                                <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                               class="text-decoration-none <?= $message['status'] === 'unread' && !$isTrashed ? 'fw-bold' : 'text-muted' ?> text-break">
                                                <?= htmlspecialchars(truncateText($message['subject'], 50)) ?>
                                            </a>
                                            <small class="text-muted d-block mt-1 text-break">
                                                <?= htmlspecialchars(truncateText($message['message'], 80)) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-nowrap">
                                                <div><?= formatTanggal($message['created_at'], 'd M Y') ?></div>
                                                <div class="text-muted"><?= formatTanggal($message['created_at'], 'H:i') ?></div>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isTrashed): ?>
                                                <span class="text-danger fw-semibold">
                                                    Deleted
                                                </span>
                                            <?php else: ?>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Lihat Lengkap">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                                       class="btn btn-outline-danger"
                                                       data-confirm-delete
                                                       data-title="Hapus Pesan"
                                                       data-message="Yakin ingin memindahkan pesan ini ke Trash?"
                                                       data-loading-text="Memindahkan..."
                                                       title="Hapus (Trash)">
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

                    <div class="d-lg-none">
                        <?php foreach ($messages as $message): 
                            $isTrashed = !is_null($message['deleted_at'] ?? null);
                        ?>
                            <div class="card mb-3 shadow-sm <?= $isTrashed ? 'border-danger' : ($message['status'] === 'unread' ? 'border-primary border-2' : '') ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1" style="min-width: 0;"> 
                                            <h6 class="mb-1 <?= $message['status'] === 'unread' && !$isTrashed ? 'fw-bold' : '' ?> text-break">
                                                <?= htmlspecialchars($message['name']) ?>
                                            </h6>
                                            <small class="text-muted d-block mb-2 text-break">
                                                <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($message['email']) ?>
                                            </small>
                                        </div>
                                        <?php if ($isTrashed): ?>
                                            <span class="badge bg-dark flex-shrink-0 ms-2">
                                                <i class="bi bi-trash"></i>
                                            </span>
                                        <?php elseif ($message['status'] === 'unread'): ?>
                                            <span class="badge bg-danger flex-shrink-0 ms-2">
                                                <i class="bi bi-envelope-fill"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-primary flex-shrink-0 ms-2">
                                                <i class="bi bi-envelope-open"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="small fw-bold mb-1 text-break">
                                            <?= htmlspecialchars($message['subject']) ?>
                                        </h6>
                                        <p class="text-muted small mb-0 text-break">
                                            <?= htmlspecialchars(truncateText($message['message'], 100)) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i> 
                                            <?= formatTanggal($message['created_at'], 'd M Y H:i') ?>
                                        </small>
                                        <?php if (!$isTrashed): ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="messages_view.php?id=<?= $message['id'] ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="messages_delete.php?id=<?= $message['id'] ?>" 
                                                   class="btn btn-outline-danger"
                                                   data-confirm-delete
                                                   data-title="Hapus Pesan"
                                                   data-message="Yakin ingin memindahkan pesan ini ke Trash?"
                                                   data-loading-text="Memindahkan...">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalItems > 0): ?>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                            <div>
                                <small class="text-muted">
                                    Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($messages) ?> dari <?= $totalItems ?> pesan
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
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>