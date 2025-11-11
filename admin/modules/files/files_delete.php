<?php
/**
 * Files Delete Handler - Soft Delete Implementation
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

// Get file ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID file tidak valid.');
    redirect(ADMIN_URL . 'modules/files/files_list.php');
}

try {
    // Cek apakah data file ada dan belum di-delete
    $stmtCheck = $db->prepare("SELECT * FROM downloadable_files WHERE id = ? AND deleted_at IS NULL");
    $stmtCheck->execute([$id]);
    $file = $stmtCheck->fetch();
    
    if (!$file) {
        setAlert('danger', 'File tidak ditemukan atau sudah dihapus.');
        redirect(ADMIN_URL . 'modules/files/files_list.php');
    }

    // Lakukan soft delete dengan update kolom deleted_at menjadi waktu sekarang
    $stmt = $db->prepare("UPDATE downloadable_files SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    logActivity('DELETE', "Soft delete file: {$file['title']}", 'downloadable_files', $id);
    setAlert('success', "File '{$file['title']}' berhasil dipindahkan ke Trash.");

} catch (PDOException $e) {
    error_log($e->getMessage());
    setAlert('danger', 'Gagal menghapus file. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/files/files_list.php');
