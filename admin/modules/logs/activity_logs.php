<?php
/**
 * Activity Logs Page - Full Mazer Design
 * PERBAIKAN:
 * 1. Mengganti mb-3 pada kolom statistik dengan gy-2 pada row (mengurangi jeda mobile)
 * 2. Menambah text-nowrap pada sel tabel (email, ip, timestamp) untuk fix overflow di mobile
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$pageTitle = 'Activity Logs';

// Only admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();

// Get items per page
$itemsPerPage = (int)($_GET['per_page'] ?? getSetting('items_per_page', 25));
$page = max(1, (int)($_GET['page'] ?? 1));

// Get filters
$userId = $_GET['user_id'] ?? '';
$actionType = $_GET['action_type'] ?? '';
$modelType = $_GET['model_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Build query for export (without pagination)
    $sql = "SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    if ($userId) {
        $sql .= " AND al.user_id = ?";
        $params[] = $userId;
    }
    
    if ($actionType) {
        $sql .= " AND al.action_type = ?";
        $params[] = $actionType;
    }
    
    if ($modelType) {
        $sql .= " AND al.model_type = ?";
        $params[] = $modelType;
    }
    
    if ($dateFrom) {
        $sql .= " AND DATE(al.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND DATE(al.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    if ($search) {
        $sql .= " AND (al.description LIKE ? OR u.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 5000";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=activity_logs_' . date('Y-m-d_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['ID', 'User', 'Email', 'Action', 'Description', 'Module', 'Model ID', 'IP Address', 'Timestamp']);
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['user_name'],
            $log['user_email'],
            $log['action_type'],
            $log['description'],
            $log['model_type'] ?? '-',
            $log['model_id'] ?? '-',
            $log['ip_address'] ?? '-',
            $log['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}

// Logika cleanup dipindah ke 'activity_logs_delete.php'

// Build query with filters
$sql = "SELECT 
            al.*,
            COALESCE(u.name, 'System') as user_display_name,
            u.email as user_email,
            u.photo as user_photo
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE 1=1";

$params = [];

if ($userId) {
    $sql .= " AND al.user_id = ?";
    $params[] = $userId;
}

if ($actionType) {
    $sql .= " AND al.action_type = ?";
    $params[] = $actionType;
}

if ($modelType) {
    $sql .= " AND al.model_type = ?";
    $params[] = $modelType;
}

if ($dateFrom) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $dateTo;
}

if ($search) {
    $sql .= " AND (al.description LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total
$countSql = "SELECT COUNT(*) FROM (" . $sql . ") as filtered";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $itemsPerPage));

// Add pagination
$offset = ($page - 1) * $itemsPerPage;
$sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Get logs
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get all users for filter
$usersStmt = $db->query("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name");
$users = $usersStmt->fetchAll();

// Action types
$actionTypes = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'VIEW'];

// Get distinct model types
$modelStmt = $db->query("
    SELECT DISTINCT model_type 
    FROM activity_logs 
    WHERE model_type IS NOT NULL 
    ORDER BY model_type
");
$modelTypes = $modelStmt->fetchAll(PDO::FETCH_COLUMN);

// Buat opsi dropdown untuk Per Page
$perPageOptions = [10, 25, 50, 100];
if (!in_array($itemsPerPage, $perPageOptions) && $itemsPerPage > 0) {
    $perPageOptions[] = $itemsPerPage;
    sort($perPageOptions);
}

// Get statistics
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT user_id) as unique_users,
        MAX(created_at) as last_activity
    FROM activity_logs
");
$stats = $statsStmt->fetch();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Monitor dan kelola semua aktivitas sistem</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Activity Logs</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="row gy-2 mb-4">
        <div class="col-12 col-md-4">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon blue mb-2 mx-auto"><i class="bi bi-file-text"></i></div>
                <h6 class="text-muted mb-1">Total Logs</h6>
                <h6 class="mb-0"><?= formatNumber($stats['total']) ?></h6>
            </div></div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon green mb-2 mx-auto"><i class="bi bi-people"></i></div>
                <h6 class="text-muted mb-1">Unique Users</h6>
                <h6 class="mb-0"><?= formatNumber($stats['unique_users']) ?></h6>
            </div></div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon purple mb-2 mx-auto"><i class="bi bi-clock-history"></i></div>
                <h6 class="text-muted mb-1">Last Activity</h6>
                <h6 class="mb-0"><?= formatTanggalRelatif($stats['last_activity']) ?></h6>
            </div></div>
        </div>
    </section>
    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Activity Logs</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-download"></i> <span class="d-none d-md-inline">Export CSV</span>
                    </button>
                    <?php if (hasRole(['super_admin'])): ?>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                            <i class="bi bi-trash"></i> <span class="d-none d-md-inline">Cleanup</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-3">
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Cari deskripsi/user...">
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="user_id" class="form-select">
                            <option value="">Semua User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"<?= $userId === (string)$user['id'] ? ' selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="action_type" class="form-select">
                            <option value="">Semua Action</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?= $type ?>"<?= $actionType === $type ? ' selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="model_type" class="form-select">
                            <option value="">Semua Module</option>
                            <?php foreach ($modelTypes as $type): ?>
                                <option value="<?= $type ?>"<?= $modelType === $type ? ' selected' : '' ?>><?= ucfirst($type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="Dari Tanggal">
                    </div>
                    <div class="col-12 col-md-3">
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>" placeholder="Sampai Tanggal">
                    </div>
                    <div class="col-6 col-md-2">
                        <select name="per_page" class="form-select">
                            <?php foreach ($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $itemsPerPage == $n ? ' selected' : '' ?>><?= $n ?>/hlm</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>

                <?php 
                $isFiltered = $userId || $actionType || $modelType || $dateFrom || $dateTo || $search || (isset($_GET['per_page']) && $_GET['per_page'] != getSetting('items_per_page', 25));
                if ($isFiltered): 
                ?>
                    <div class="mb-3">
                        <a href="activity_logs.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:45px;">No</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Module</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        Belum ada activity log
                                    </td>
                                </tr>
                            <?php else: foreach ($logs as $i => $log): ?>
                                <tr>
                                    <td><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-md me-2">
                                                <?php if ($log['user_photo']): ?>
                                                    <img src="<?= uploadUrl($log['user_photo']) ?>" alt="">
                                                <?php else: ?>
                                                    <img src="<?= ADMIN_URL ?>assets/static/images/faces/1.jpg" alt="">
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-break"><?= htmlspecialchars($log['user_display_name']) ?></div>
                                                <small class="text-muted text-nowrap"><?= htmlspecialchars($log['user_email'] ?? '-') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getActionColor($log['action_type']) ?>">
                                            <?= $log['action_type'] ?>
                                        </span>
                                    </td>
                                    <td class="text-break"><?= htmlspecialchars($log['description']) ?></td>
                                    <td>
                                        <?php if ($log['model_type']): ?>
                                            <span class="badge bg-secondary"><?= ucfirst($log['model_type']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="font-monospace text-nowrap"><?= $log['ip_address'] ?? '-' ?></small>
                                    </td>
                                    <td class="text-nowrap">
                                        <div><?= formatTanggalRelatif($log['created_at']) ?></div>
                                        <small class="text-muted"><?= formatTanggal($log['created_at'], 'd M Y H:i') ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total > 0): ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                        <div>
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($logs) ?> dari <?= formatNumber($total) ?> log
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

<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export to CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Export activity logs dengan filter yang sedang aktif.</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Perhatian:</strong> Maksimal 5000 records akan di-export.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="?export=csv<?= $userId ? '&user_id='.$userId : '' ?><?= $actionType ? '&action_type='.$actionType : '' ?><?= $modelType ? '&model_type='.$modelType : '' ?><?= $dateFrom ? '&date_from='.$dateFrom : '' ?><?= $dateTo ? '&date_to='.$dateTo : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
                   class="btn btn-success"
                   data-confirm-export> <i class="bi bi-download"></i> Download CSV
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (hasRole(['super_admin'])): ?>
<div class="modal fade" id="cleanupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="activity_logs_delete.php" id="cleanupForm">
                <div class="modal-header">
                    <h5 class="modal-title">Cleanup Old Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> Aksi ini tidak bisa di-undo!
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Hapus log lebih dari:</label>
                        <select name="cleanup_days" class="form-select" required>
                            <option value="7" selected>7 hari</option>
                            <option value="30">30 hari</option>
                            <option value="60">60 hari</option>
                            <option value="90">90 hari</option>
                            <option value="180">180 hari (6 bulan)</option>
                            <option value="365">365 hari (1 tahun)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" name="cleanup_logs" class="btn btn-danger" id="confirmCleanupBtn">
                        <i class="bi bi-trash"></i> Hapus Logs Lama
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Handler untuk tombol EXPORT
    document.querySelector('[data-confirm-export]').addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');

        notify.confirm({
            type: 'info',
            title: 'Konfirmasi Export',
            message: 'Anda akan men-download file CSV berisi log. Lanjutkan?',
            confirmText: 'Ya, Download',
            cancelText: 'Batal',
            onConfirm: function() {
                notify.info('Mempersiapkan file export...', 2000);
                window.location.href = href;
            }
        });
    });

    // Handler untuk tombol CLEANUP (hanya jika ada)
    const cleanupBtn = document.getElementById('confirmCleanupBtn');
    if (cleanupBtn) {
        cleanupBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = document.getElementById('cleanupForm');
            const select = form.querySelector('select[name="cleanup_days"]');
            const days = select.options[select.selectedIndex].text;

            notify.confirm({
                type: 'danger', // Tipe danger untuk aksi menghapus
                title: 'Hapus Logs Lama?',
                message: `PERINGATAN: Anda akan menghapus permanen semua log yang lebih tua dari <strong>${days}</strong>. Aksi ini tidak bisa dibatalkan. Lanjutkan?`,
                confirmText: 'Ya, Hapus Permanen',
                cancelText: 'Batal',
                onConfirm: function() {
                    notify.loading('Menghapus logs...');
                    form.submit(); // Ini akan submit ke 'activity_logs_delete.php'
                }
            });
        });
    }

});
</script>

<?php include '../../includes/footer.php'; ?>