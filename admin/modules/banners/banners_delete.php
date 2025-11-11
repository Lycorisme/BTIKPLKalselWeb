<?php
/**
 * Banners Delete Handler - Soft Delete Implementation
 */
require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Only super_admin and admin can delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Anda tidak memiliki akses ke halaman ini');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();

// Get banner ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID banner tidak valid.');
    redirect(ADMIN_URL . 'modules/banners/banners_list.php');
}

try {
    // Cek apakah data banner ada dan belum di-delete
    $stmtCheck = $db->prepare("SELECT * FROM banners WHERE id = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$id]);
    $banner = $stmtCheck->fetch();
    
    if (!$banner) {
        setAlert('danger', 'Banner tidak ditemukan atau sudah dihapus.');
        redirect(ADMIN_URL . 'modules/banners/banners_list.php');
    }

    // Lakukan soft delete dengan update kolom deleted_at menjadi waktu sekarang
    $stmt = $db->prepare("UPDATE banners SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    logActivity('DELETE', "Soft delete banner: {$banner['title']}", 'banners', $id);
    setAlert('success', "Banner '{$banner['title']}' berhasil dipindahkan ke Trash.");

} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus banner. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/banners/banners_list.php');
