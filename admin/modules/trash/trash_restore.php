<?php
/**
 * Trash Restore - Complete System
 * Mengembalikan data dari soft delete ke state aktif
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

$db = Database::getInstance()->getConnection();

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID tidak valid.');
    header("Location: trash_list.php");
    exit;
}

try {
    $db->beginTransaction();
    
    switch ($type) {
        case 'post':
            $stmt = $db->prepare("UPDATE posts SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore post ID: {$id}", 'posts', $id);
            setAlert('success', 'Post berhasil dipulihkan!');
            break;
            
        case 'service':
            $stmt = $db->prepare("UPDATE services SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore layanan ID: {$id}", 'services', $id);
            setAlert('success', 'Layanan berhasil dipulihkan!');
            break;
            
        case 'user':
            $stmt = $db->prepare("UPDATE users SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore user ID: {$id}", 'users', $id);
            setAlert('success', 'User berhasil dipulihkan!');
            break;
            
        case 'page':
            $stmt = $db->prepare("UPDATE pages SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore halaman ID: {$id}", 'pages', $id);
            setAlert('success', 'Halaman berhasil dipulihkan!');
            break;
            
        case 'category':
            $stmt = $db->prepare("UPDATE post_categories SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore kategori ID: {$id}", 'post_categories', $id);
            setAlert('success', 'Kategori berhasil dipulihkan!');
            break;
            
        case 'file':
            $stmt = $db->prepare("UPDATE downloadable_files SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore file ID: {$id}", 'downloadable_files', $id);
            setAlert('success', 'File berhasil dipulihkan!');
            break;
            
        case 'album':
            $stmt = $db->prepare("UPDATE gallery_albums SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore album ID: {$id}", 'gallery_albums', $id);
            setAlert('success', 'Album berhasil dipulihkan!');
            break;
            
        case 'photo':
            $stmt = $db->prepare("UPDATE gallery_photos SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore foto ID: {$id}", 'gallery_photos', $id);
            setAlert('success', 'Foto berhasil dipulihkan!');
            break;
            
        case 'banner':
            $stmt = $db->prepare("UPDATE banners SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore banner ID: {$id}", 'banners', $id);
            setAlert('success', 'Banner berhasil dipulihkan!');
            break;
            
        case 'contact':
            $stmt = $db->prepare("UPDATE contact_messages SET deleted_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('UPDATE', "Me-restore pesan kontak ID: {$id}", 'contact_messages', $id);
            setAlert('success', 'Pesan kontak berhasil dipulihkan!');
            break;
            
        default:
            $db->rollBack();
            setAlert('danger', 'Tipe data tidak dikenal.');
            header("Location: trash_list.php");
            exit;
    }
    
    $db->commit();
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Trash Restore Error: " . $e->getMessage());
    setAlert('danger', 'Terjadi kesalahan saat memulihkan data: ' . $e->getMessage());
}

// Redirect back to trash list with appropriate filter
$redirectType = '';
if (in_array($type, ['post', 'service', 'user', 'page', 'category', 'file', 'album', 'photo', 'banner', 'contact'])) {
    $typeMap = [
        'post' => 'posts',
        'service' => 'services',
        'user' => 'users',
        'page' => 'pages',
        'category' => 'categories',
        'file' => 'files',
        'album' => 'albums',
        'photo' => 'photos',
        'banner' => 'banners',
        'contact' => 'contacts'
    ];
    $redirectType = '?type=' . $typeMap[$type];
}

header("Location: trash_list.php" . $redirectType);
exit;