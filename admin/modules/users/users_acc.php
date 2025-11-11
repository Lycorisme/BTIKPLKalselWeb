<?php
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

$userId = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Pastikan user valid dan statusnya NEW (is_active = 3)
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 3 AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setAlert('danger', 'Pengguna tidak valid atau sudah diproses.');
    redirect(ADMIN_URL . 'modules/users/users_list.php');
}

// Proses ACC: ubah status user ke aktif (is_active = 1)
$stmt = $db->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
$stmt->execute([$userId]);

// Log activity
logActivity('USER_ACC', 'Akun pengguna diaktifkan/diacc', 'users', $userId);

// Notifikasi custom ke user_list
setAlert('success', 'Akun berhasil di-ACC dan diaktifkan.');
redirect(ADMIN_URL . 'modules/users/users_list.php');
