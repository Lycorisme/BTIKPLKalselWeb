<?php
/**
 * View User Detail Page
 * Display complete user information and activity
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../core/Model.php';
require_once '../../../models/User.php';

// Only super_admin and admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

 $pageTitle = 'Detail Pengguna';

 $userModel = new User();

// Get user ID
 $userId = $_GET['id'] ?? 0;
 $user = $userModel->find($userId);

if (!$user) {
    setAlert('danger', 'Pengguna tidak ditemukan');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

// Log activity for viewing user detail
logActivity('VIEW', "Melihat detail pengguna: {$user['name']}", 'users', $userId);

// Get user's recent activity logs
 $db = Database::getInstance()->getConnection();
 $stmt = $db->prepare("
    SELECT * FROM activity_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
 $stmt->execute([$userId]);
 $activities = $stmt->fetchAll();

// Get user's posts count
 $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND deleted_at IS NULL");
 $stmt->execute([$userId]);
 $postsCount = $stmt->fetchColumn();

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><i class=""></i><?= $pageTitle ?></h3>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="users_list.php">Pengguna</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <!-- User Profile Card -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="avatar avatar-xl mb-3">
                            <?php if ($user['photo']): ?>
                                <img src="<?= uploadUrl($user['photo']) ?>" alt="Photo">
                            <?php else: ?>
                                <img src="<?= ADMIN_URL ?>assets/static/images/faces/1.jpg" alt="Avatar">
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                        
                        <div class="mb-3">
                            <?= getRoleBadge($user['role']) ?>
                            <?php if ($user['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="users_edit.php?id=<?= $user['id'] ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Edit Pengguna
                            </a>
                            <?php if ($user['id'] != getCurrentUser()['id']): ?>
                                <a href="users_delete.php?id=<?= $user['id'] ?>" 
                                   class="btn btn-danger"
                                   data-confirm-delete
                                   data-title="Hapus Pengguna"
                                   data-message="Apakah Anda yakin ingin menghapus pengguna &quot;<?= htmlspecialchars($user['name']) ?>&quot;? Tindakan ini tidak dapat dibatalkan."
                                   data-loading-text="Menghapus...">
                                    <i class="bi bi-trash"></i> Hapus Pengguna
                                </a>
                            <?php endif; ?>
                            <a href="users_list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics Card -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statistik</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total Posts</span>
                                <strong><?= formatNumber($postsCount) ?></strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Bergabung</span>
                                <strong><?= formatTanggal($user['created_at'], 'd M Y') ?></strong>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Login Terakhir</span>
                                <strong>
                                    <?= $user['last_login_at'] ? formatTanggal($user['last_login_at'], 'd M Y H:i') : 'Belum pernah' ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Information -->
            <div class="col-md-8">
                <!-- Contact Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Kontak</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>Email</strong></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telepon</strong></td>
                                <td><?= htmlspecialchars($user['phone'] ?: '-') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Alamat</strong></td>
                                <td><?= nl2br(htmlspecialchars($user['address'] ?: '-')) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Sistem</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td width="200"><strong>User ID</strong></td>
                                <td><?= $user['id'] ?></td>
                            </tr>
                            <tr>
                                <td><strong>Role</strong></td>
                                <td><?= getRoleBadge($user['role']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Dibuat</strong></td>
                                <td><?= formatTanggal($user['created_at'], 'd M Y H:i') ?></td>
                            </tr>
                            <?php if ($user['updated_at']): ?>
                            <tr>
                                <td><strong>Diupdate</strong></td>
                                <td><?= formatTanggal($user['updated_at'], 'd M Y H:i') ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Login Terakhir</strong></td>
                                <td>
                                    <?= $user['last_login_at'] ? formatTanggal($user['last_login_at'], 'd M Y H:i') : 'Belum pernah login' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Aktivitas Terkini</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <p class="text-muted text-center py-3">Belum ada aktivitas</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Aksi</th>
                                            <th>Deskripsi</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?= getActionColor($activity['action']) ?>">
                                                        <?= $activity['action'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($activity['description']) ?></td>
                                                <td>
                                                    <small><?= formatTanggal($activity['created_at'], 'd M Y H:i') ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>