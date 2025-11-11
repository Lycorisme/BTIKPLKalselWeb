<?php
/**
 * Users List Page - Full Mazer, Soft Delete, Custom Notif/Confirm, Responsive Table
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';
require_once '../../../models/User.php';

// Only super_admin and admin can access
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$pageTitle = 'Kelola Pengguna';
$userModel = new User();

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)($_GET['perpage'] ?? 10);
$search   = trim($_GET['search'] ?? '');
$role     = $_GET['role'] ?? '';
$isActive = $_GET['is_active'] ?? '';
$showDeleted = $_GET['show_deleted'] ?? '0';

$filters = [];
if ($role) $filters['role'] = $role;
if ($isActive !== '') $filters['is_active'] = $isActive;
if ($search) $filters['search'] = $search;
if ($showDeleted == '1') $filters['show_deleted'] = true;

$result = $userModel->getPaginated($page, $perPage, $filters);
$users  = $result['data'];
$totalItems = $result['total'];
$totalPages = $result['last_page'];
$offset = ($page - 1) * $perPage;

// -- Extra for dashboard card
$roleCounts = $userModel->countByRole();
$roleOptions = [
    '' => 'Semua Role',
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'editor' => 'Editor',
    'author' => 'Author'
];
$activeOptions = [
    '' => 'Semua Status',
    '1' => 'Aktif',
    '0' => 'Tidak Aktif',
    '3' => 'Baru (Pending)'
];
$perPageOptions = [10,25,50,100];
$deletedOptions = [
    '0' => 'Data Aktif Saja',
    '1' => 'Tampilkan Data Terhapus'
];

include '../../includes/header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6">
                <h3><?= $pageTitle ?></h3>
                <p class="text-subtitle text-muted">Kelola pengguna sistem dan hak akses</p>
            </div>
            <div class="col-12 col-md-6">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= ADMIN_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pengguna</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="row mb-4">
        <div class="col-md-3 col-6 mb-2">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon purple mb-2 mx-auto"><i class="bi bi-shield-check"></i></div>
                <h6 class="text-muted mb-1">Super Admin</h6>
                <h6 class="mb-0"><?= $roleCounts['super_admin'] ?></h6>
            </div></div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon blue mb-2 mx-auto"><i class="bi bi-person-badge"></i></div>
                <h6 class="text-muted mb-1">Admin</h6>
                <h6 class="mb-0"><?= $roleCounts['admin'] ?></h6>
            </div></div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon green mb-2 mx-auto"><i class="bi bi-pencil-square"></i></div>
                <h6 class="text-muted mb-1">Editor</h6>
                <h6 class="mb-0"><?= $roleCounts['editor'] ?></h6>
            </div></div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card"><div class="card-body text-center">
                <div class="stats-icon red mb-2 mx-auto"><i class="bi bi-person"></i></div>
                <h6 class="text-muted mb-1">Author</h6>
                <h6 class="mb-0"><?= $roleCounts['author'] ?></h6>
            </div></div>
        </div>
    </section>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap flex-row justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Pengguna</div>
                <div>
                    <a href="users_add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-md-inline">Tambah Pengguna</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Panel -->
                <form method="get" class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-sm-3">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama/email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="role" class="form-select custom-dropdown">
                            <?php foreach($roleOptions as $val=>$txt): ?>
                                <option value="<?= $val ?>"<?= $role===$val?' selected':'' ?>><?= $txt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="is_active" class="form-select custom-dropdown">
                            <?php foreach($activeOptions as $val=>$txt): ?>
                                <option value="<?= $val ?>"<?= $isActive===$val?' selected':'' ?>><?= $txt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="perpage" class="form-select custom-dropdown">
                            <?php foreach($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage==$n?' selected':'' ?>><?= $n ?> / halaman</option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="show_deleted" class="form-select custom-dropdown">
                            <?php foreach($deletedOptions as $val=>$txt): ?>
                                <option value="<?= $val ?>"<?= $showDeleted===$val?' selected':'' ?>><?= $txt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-1">
                        <button class="btn btn-outline-primary w-100" type="submit">
                            <i class="bi bi-search"></i> <span class="d-none d-md-inline"></span>
                        </button>
                    </div>
                </form>

                <?php if ($role || $isActive!=='' || $search|| $showDeleted=='1'): ?>
                    <div class="mb-3">
                        <a href="users_list.php" class="btn btn-sm btn-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                    </div>
                <?php endif ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:45px">No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th class="text-center" style="width:230px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                            <?php else: foreach($users as $i=>$user):
                            $isTrashed = !is_null($user['deleted_at'] ?? null); ?>
                                <tr<?= $isTrashed ? ' class="table-danger text-muted"' : '' ?>>
                                    <td><?= $offset+$i+1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-md me-2">
                                                <img src="<?= $user['photo'] ? uploadUrl($user['photo']) : ADMIN_URL . 'assets/static/images/faces/1.jpg' ?>">
                                            </div>
                                            <span class="fw-semibold"><?= htmlspecialchars($user['name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= getRoleBadge($user['role']) ?></td>
                                    <td>
                                        <?php if ($user['is_active'] == 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php elseif ($user['is_active'] == 3): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif ?>
                                        <?php if ($isTrashed): ?>
                                            <span class="badge bg-secondary ms-1">Deleted</span>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <small><?= $user['last_login_at'] ? formatTanggal($user['last_login_at'], 'd M Y H:i') : 'Belum login' ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isTrashed): ?>
                                            <span class="text-danger">
                                                <strong>Deleted at <?= formatTanggal($user['deleted_at'], 'd M Y H:i') ?></strong>
                                            </span>
                                        <?php else: ?>
                                            <div class="btn-group btn-group-sm gap-1">
                                                <?php if($user['is_active'] == 3): ?>
                                                    <!-- Hanya tampilkan ACC dan Reject untuk status Pending dengan konfirmasi -->
                                                    <button type="button" 
                                                       class="btn btn-success btn-acc-user" 
                                                       title="ACC Akun Baru"
                                                       data-id="<?= $user['id'] ?>"
                                                       data-name="<?= htmlspecialchars($user['name']) ?>"
                                                       data-email="<?= htmlspecialchars($user['email']) ?>">
                                                        <i class="bi bi-check2-circle"></i>
                                                    </button>
                                                    <button type="button" 
                                                       class="btn btn-danger btn-reject-user" 
                                                       title="Tolak Akun Baru"
                                                       data-id="<?= $user['id'] ?>"
                                                       data-name="<?= htmlspecialchars($user['name']) ?>"
                                                       data-email="<?= htmlspecialchars($user['email']) ?>">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Tampilkan tombol normal untuk user aktif/tidak aktif -->
                                                    <a href="users_view.php?id=<?= $user['id'] ?>" class="btn btn-info" title="Detail"><i class="bi bi-eye"></i></a>
                                                    <a href="users_edit.php?id=<?= $user['id'] ?>" class="btn btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                                    <?php if($user['id']!=getCurrentUser()['id']): ?>
                                                        <a href="users_delete.php?id=<?= $user['id'] ?>" 
                                                           class="btn btn-danger"
                                                           data-confirm-delete
                                                           data-title="<?= htmlspecialchars($user['name']) ?>"
                                                           data-message="User &quot;<?= htmlspecialchars($user['name']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                           data-loading-text="Menghapus user..." 
                                                           title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif ?>
                                                <?php endif ?>
                                            </div>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination bawah selalu tampil -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                    <div>
                        <small class="text-muted">
                            Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($users) ?> dari <?= $totalItems ?> pengguna
                        </small>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination mb-0">
                            <li class="page-item<?= $page<=1?' disabled':'' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php $from=max(1,$page-2); $to=min($totalPages,$page+2);
                            for($i=$from;$i<=$to;$i++): ?>
                            <li class="page-item<?= $i==$page?' active':'' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor ?>
                            <li class="page-item<?= $page>=$totalPages?' disabled':'' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">
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

<script>
// Custom Notification Handler untuk ACC dan Reject User
document.addEventListener('DOMContentLoaded', function() {
    // Handler untuk tombol ACC
    document.querySelectorAll('.btn-acc-user').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.id;
            const userName = this.dataset.name;
            const userEmail = this.dataset.email;
            
            showConfirmAlert({
                type: 'warning',
                title: 'Konfirmasi Aktivasi Akun',
                message: `Apakah Anda yakin ingin mengaktifkan akun berikut?<br><br><strong>${userName}</strong><br><small class="text-muted">${userEmail}</small>`,
                confirmText: 'Ya, Aktifkan',
                cancelText: 'Batal',
                onConfirm: function() {
                    showLoading('Mengaktifkan akun...');
                    window.location.href = `users_acc.php?id=${userId}`;
                }
            });
        });
    });
    
    // Handler untuk tombol Reject
    document.querySelectorAll('.btn-reject-user').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.id;
            const userName = this.dataset.name;
            const userEmail = this.dataset.email;
            
            showConfirmAlert({
                type: 'danger',
                title: 'Konfirmasi Penolakan Akun',
                message: `Apakah Anda yakin ingin menolak akun berikut?<br><br><strong>${userName}</strong><br><small class="text-muted">${userEmail}</small><br><br><span class="text-danger">Akun akan dinonaktifkan dan user tidak dapat login.</span>`,
                confirmText: 'Ya, Tolak',
                cancelText: 'Batal',
                onConfirm: function() {
                    showLoading('Menolak akun...');
                    window.location.href = `users_reject.php?id=${userId}`;
                }
            });
        });
    });
});

// Function untuk menampilkan konfirmasi alert
function showConfirmAlert(options) {
    const overlay = document.createElement('div');
    overlay.className = 'btikp-alert-overlay';
    
    const alertBox = document.createElement('div');
    alertBox.className = `btikp-alert btikp-alert-${options.type || 'warning'}`;
    
    // Icon berdasarkan type
    let iconClass = 'bi-question-circle';
    if (options.type === 'success') iconClass = 'bi-check-circle';
    if (options.type === 'danger' || options.type === 'error') iconClass = 'bi-exclamation-circle';
    if (options.type === 'warning') iconClass = 'bi-exclamation-triangle';
    if (options.type === 'info') iconClass = 'bi-info-circle';
    
    alertBox.innerHTML = `
        <div class="btikp-alert-icon">
            <i class="bi ${iconClass}"></i>
        </div>
        <div class="btikp-alert-title">${options.title || 'Konfirmasi'}</div>
        <div class="btikp-alert-message">${options.message || 'Apakah Anda yakin?'}</div>
        <div class="btikp-alert-actions">
            <button class="btikp-btn btikp-btn-secondary btn-cancel">${options.cancelText || 'Batal'}</button>
            <button class="btikp-btn btikp-btn-${options.type || 'warning'} btn-confirm">${options.confirmText || 'Ya'}</button>
        </div>
    `;
    
    overlay.appendChild(alertBox);
    document.body.appendChild(overlay);
    
    setTimeout(() => overlay.classList.add('show'), 10);
    
    // Handler tombol Batal
    alertBox.querySelector('.btn-cancel').addEventListener('click', function() {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 300);
        if (options.onCancel) options.onCancel();
    });
    
    // Handler tombol Konfirmasi
    alertBox.querySelector('.btn-confirm').addEventListener('click', function() {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 300);
        if (options.onConfirm) options.onConfirm();
    });
    
    // Close on overlay click
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('show');
            setTimeout(() => overlay.remove(), 300);
            if (options.onCancel) options.onCancel();
        }
    });
}

// Function untuk menampilkan loading overlay
function showLoading(message) {
    const overlay = document.createElement('div');
    overlay.className = 'btikp-loading-overlay';
    overlay.id = 'btikp-loading';
    
    overlay.innerHTML = `
        <div class="btikp-loading">
            <div class="btikp-loading-spinner"></div>
            <div class="btikp-loading-message">${message || 'Memproses...'}</div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    setTimeout(() => overlay.classList.add('show'), 10);
}

// Function untuk hide loading
function hideLoading() {
    const overlay = document.getElementById('btikp-loading');
    if (overlay) {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 300);
    }
}
</script>

<?php include '../../includes/footer.php'; ?>