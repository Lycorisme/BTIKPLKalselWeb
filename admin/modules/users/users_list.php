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

// Menambahkan opsi filter untuk 'is_active = 3'
$activeOptions = [
    '' => 'Semua Status',
    '1' => 'Aktif',
    '0' => 'Tidak Aktif',
    '3' => 'Menunggu Persetujuan'
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

    <section class="row mb-3">
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stats-icon purple mb-2 mx-auto">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h6 class="text-muted mb-1">Super Admin</h6>
                    <h6 class="mb-0 fw-bold"><?= $roleCounts['super_admin'] ?></h6>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stats-icon blue mb-2 mx-auto">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h6 class="text-muted mb-1">Admin</h6>
                    <h6 class="mb-0 fw-bold"><?= $roleCounts['admin'] ?></h6>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stats-icon green mb-2 mx-auto">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h6 class="text-muted mb-1">Editor</h6>
                    <h6 class="mb-0 fw-bold"><?= $roleCounts['editor'] ?></h6>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stats-icon red mb-2 mx-auto">
                        <i class="bi bi-person"></i>
                    </div>
                    <h6 class="text-muted mb-1">Author</h6>
                    <h6 class="mb-0 fw-bold"><?= $roleCounts['author'] ?></h6>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="card shadow">
            <div class="card-header d-flex flex-wrap flex-row justify-content-between align-items-center gap-2">
                <div class="card-title m-0 fw-bold">Daftar Pengguna</div>
                <div>
                    <a href="users_add.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i>
                        <span class="d-none d-sm-inline">Tambah Pengguna</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form method="get" class="row g-2 align-items-center mb-3 filter-form">
                    <div class="col-12 col-sm-3">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/email..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="role" class="form-select form-select-sm custom-dropdown">
                            <?php foreach($roleOptions as $val=>$txt): ?>
                                <option value="<?= $val ?>"<?= $role===$val?' selected':'' ?>><?= $txt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="is_active" class="form-select form-select-sm custom-dropdown">
                            <?php foreach($activeOptions as $val=>$txt): ?>
                                <option value="<?= $val ?>"<?= $isActive===$val?' selected':'' ?>><?= $txt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="perpage" class="form-select form-select-sm custom-dropdown">
                            <?php foreach($perPageOptions as $n): ?>
                                <option value="<?= $n ?>"<?= $perPage==$n?' selected':'' ?>><?= $n ?> / hal</option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-2">
                        <select name="show_deleted" class="form-select form-select-sm custom-dropdown">
                            <?php foreach($deletedOptions as $val=>$txt): ?>
                                <option value="<?= $val ?>"<?= $showDeleted===$val?' selected':'' ?>><?= $txt ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-1">
                        <button class="btn btn-outline-primary btn-sm w-100" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <?php if ($role || $isActive!=='' || $search|| $showDeleted=='1'): ?>
                    <div class="mb-3">
                        <a href="users_list.php" class="btn btn-sm btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                <?php endif ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:45px">No</th>
                                <th>Nama</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th>Role</th>
                                <th class="d-none d-lg-table-cell">Status</th>
                                <th class="d-none d-lg-table-cell">Last Login</th>
                                <th class="text-center" style="width:140px">Aksi</th>
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
                                                <img src="<?= $user['photo'] ? uploadUrl($user['photo']) : ADMIN_URL . 'assets/static/images/faces/1.jpg' ?>" alt="Avatar">
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
                                                <small class="text-muted d-md-none"><?= htmlspecialchars($user['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= getRoleBadge($user['role']) ?></td>
                                    <td class="d-none d-lg-table-cell">
                                        
                                        <?php if ($user['is_active'] == 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php elseif ($user['is_active'] == 0): ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php elseif ($user['is_active'] == 3): ?>
                                            <span class="badge bg-warning">Menunggu ACC</span>
                                        <?php endif ?>

                                        <?php if ($isTrashed): ?>
                                            <span class="badge bg-danger ms-1">Deleted</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <small><?= $user['last_login_at'] ? formatTanggal($user['last_login_at'], 'd M Y H:i') : 'Belum login' ?></small>
                                    </td>
                                    <td class="text-center">
                                        
                                        <?php if ($isTrashed): ?>
                                            <span class="badge bg-danger">Deleted</span>
                                        <?php elseif ($user['is_active'] == 3): ?>
                                            <div class="btn-group btn-group-sm">
                                                
                                                <a href="users_acc.php?id=<?= $user['id'] ?>" class="btn btn-success"
                                                   data-confirm-delete
                                                   data-title="Setujui Pengguna"
                                                   data-message="Anda yakin ingin menyetujui (ACC) pengguna &quot;<?= htmlspecialchars($user['name']) ?>&quot;? Akun ini akan diaktifkan."
                                                   data-loading-text="Menyetujui..." title="Setujui (ACC)">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                
                                                <a href="users_reject.php?id=<?= $user['id'] ?>" class="btn btn-danger"
                                                   data-confirm-delete
                                                   data-title="Tolak Pengguna"
                                                   data-message="Tolak dan hapus permanen user &quot;<?= htmlspecialchars($user['name']) ?>&quot;? Tindakan ini tidak dapat dibatalkan."
                                                   data-loading-text="Menolak..." title="Tolak">
                                                   <i class="bi bi-x-circle"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="users_view.php?id=<?= $user['id'] ?>" class="btn btn-info" title="Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="users_edit.php?id=<?= $user['id'] ?>" class="btn btn-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if($user['id']!=getCurrentUser()['id']): ?>
                                                
                                                <a href="users_delete.php?id=<?= $user['id'] ?>" class="btn btn-danger"
                                                   data-confirm-delete
                                                   data-title="<?= htmlspecialchars($user['name']) ?>"
                                                   data-message="User &quot;<?= htmlspecialchars($user['name']) ?>&quot; akan dipindahkan ke Trash. Lanjutkan?"
                                                   data-loading-text="Menghapus user..." title="Hapus">
                                                   <i class="bi bi-trash"></i>
                                                </a>
                                                <?php endif ?>
                                            </div>
                                        <?php endif ?>

                                    </td>
                                 </tr>
                            <?php endforeach; endif ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
                    <div>
                        <small class="text-muted">
                            Halaman <?= $page ?> dari <?= $totalPages ?> · Menampilkan <?= count($users) ?> dari <?= $totalItems ?> Pengguna
                        </small>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item<?= $page<=1?' disabled':'' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php 
                            $from=max(1,$page-1); 
                            $to=min($totalPages,$page+1);
                            for($i=$from;$i<=$to;$i++): 
                            ?>
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

<?php include '../../includes/footer.php'; ?>