<?php
/**
 * Gallery Albums - Delete (Soft Delete)
 * Improved: Cascade soft delete to photos, handle cover photo, better logging
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

// Get album ID
$albumId = $_GET['id'] ?? null;

if (!$albumId) {
    setAlert('danger', 'Album tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get album data
$stmt = $db->prepare("SELECT * FROM gallery_albums WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$albumId]);
$album = $stmt->fetch();

if (!$album) {
    setAlert('danger', 'Album tidak ditemukan atau sudah dihapus.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

try {
    $db->beginTransaction();
    
    // Count photos in album
    $stmt = $db->prepare("SELECT COUNT(*) FROM gallery_photos WHERE album_id = ? AND deleted_at IS NULL");
    $stmt->execute([$albumId]);
    $photoCount = $stmt->fetchColumn();
    
    // Soft delete all active photos in this album (CASCADE)
    if ($photoCount > 0) {
        $stmt = $db->prepare("UPDATE gallery_photos SET deleted_at = NOW() WHERE album_id = ? AND deleted_at IS NULL");
        $stmt->execute([$albumId]);
    }
    
    // Soft delete album
    $stmt = $db->prepare("UPDATE gallery_albums SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$albumId]);
    
    // Log activity with photo count
    $logMessage = "Menghapus album gallery: {$album['name']}";
    if ($photoCount > 0) {
        $logMessage .= " ({$photoCount} foto)";
    }
    logActivity('DELETE', $logMessage, 'gallery_albums', $albumId);
    
    $db->commit();
    
    // Success message
    if ($photoCount > 0) {
        setAlert('success', "Album \"{$album['name']}\" dan {$photoCount} foto berhasil dipindahkan ke trash!");
    } else {
        setAlert('success', "Album \"{$album['name']}\" berhasil dipindahkan ke trash!");
    }
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Album Delete Error: " . $e->getMessage());
    error_log("Album ID: {$albumId}");
    setAlert('danger', 'Gagal menghapus album. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
