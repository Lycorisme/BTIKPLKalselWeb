<?php
/**
 * Gallery Photos - Delete (Soft Delete)
 * Improved: Update album cover, check album status, better error handling
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

// Get photo ID and album ID
$photoId = $_GET['id'] ?? null;
$albumId = $_GET['album_id'] ?? null;

if (!$photoId) {
    setAlert('danger', 'Foto tidak ditemukan.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Get photo data
$stmt = $db->prepare("SELECT * FROM gallery_photos WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$photoId]);
$photo = $stmt->fetch();

if (!$photo) {
    setAlert('danger', 'Foto tidak ditemukan atau sudah dihapus.');
    redirect(ADMIN_URL . 'modules/gallery/albums_list.php');
}

// Use album_id from photo if not provided
if (!$albumId) {
    $albumId = $photo['album_id'];
}

try {
    $db->beginTransaction();
    
    // Check if this photo is used as album cover
    $stmt = $db->prepare("SELECT id, name, cover_photo FROM gallery_albums WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$albumId]);
    $album = $stmt->fetch();
    
    $isCoverPhoto = false;
    if ($album && $album['cover_photo'] === $photo['filename']) {
        $isCoverPhoto = true;
        
        // Find another active photo in the album to use as new cover
        $stmt = $db->prepare("
            SELECT filename 
            FROM gallery_photos 
            WHERE album_id = ? AND id != ? AND deleted_at IS NULL 
            ORDER BY display_order ASC, created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$albumId, $photoId]);
        $newCover = $stmt->fetch();
        
        // Update album cover
        if ($newCover) {
            $stmt = $db->prepare("UPDATE gallery_albums SET cover_photo = ? WHERE id = ?");
            $stmt->execute([$newCover['filename'], $albumId]);
        } else {
            // No more photos, set cover to NULL
            $stmt = $db->prepare("UPDATE gallery_albums SET cover_photo = NULL WHERE id = ?");
            $stmt->execute([$albumId]);
        }
    }
    
    // Soft delete photo
    $stmt = $db->prepare("UPDATE gallery_photos SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$photoId]);
    
    // Log activity
    $photoTitle = $photo['title'] ?: "Photo #{$photoId}";
    $logMessage = "Menghapus foto: {$photoTitle}";
    if ($isCoverPhoto) {
        $logMessage .= " (cover photo album)";
    }
    logActivity('DELETE', $logMessage, 'gallery_photos', $photoId);
    
    $db->commit();
    
    // Success message
    if ($isCoverPhoto) {
        setAlert('success', 'Foto cover berhasil dihapus dan cover album telah diperbarui!');
    } else {
        setAlert('success', 'Foto berhasil dipindahkan ke trash!');
    }
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Photo Delete Error: " . $e->getMessage());
    error_log("Photo ID: {$photoId}, Album ID: {$albumId}");
    setAlert('danger', 'Gagal menghapus foto. Silakan coba lagi.');
}

redirect(ADMIN_URL . 'modules/gallery/photos_list.php?album_id=' . $albumId);
