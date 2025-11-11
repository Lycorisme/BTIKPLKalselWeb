<?php
/**
 * Empty Trash - Complete System
 * Menghapus permanen semua item di trash (dengan filter optional)
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Only super_admin and admin can empty trash
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Akses ditolak. Hanya Super Admin dan Admin yang dapat mengosongkan trash.');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();
$type = $_GET['type'] ?? '';

/**
 * Function to safely delete files
 */
function deleteFileIfExists($filePath) {
    if (empty($filePath)) {
        return;
    }
    
    $fullPath = __DIR__ . '/../../../public/' . $filePath;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

try {
    $db->beginTransaction();
    
    switch ($type) {
        case 'posts':
            // Delete all post images
            $stmt = $db->query("SELECT featured_image FROM posts WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            
            // Get post IDs for cleanup
            $stmt = $db->query("SELECT id FROM posts WHERE deleted_at IS NOT NULL");
            $postIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($postIds)) {
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                
                // Delete related data
                $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM post_likes WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'post' AND commentable_id IN ($placeholders)")->execute($postIds);
            }
            
            // Delete posts
            $stmt = $db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM posts WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash posts: {$count} item dihapus permanen", 'posts', null);
            setAlert('success', "Semua posts di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'services':
            // Delete all service images
            $stmt = $db->query("SELECT image_path FROM services WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->query("SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM services WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash layanan: {$count} item dihapus permanen", 'services', null);
            setAlert('success', "Semua layanan di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'users':
            // Delete all user photos
            $stmt = $db->query("SELECT photo FROM users WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM users WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash users: {$count} item dihapus permanen", 'users', null);
            setAlert('success', "Semua user di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'pages':
            // Delete all page images
            $stmt = $db->query("SELECT featured_image FROM pages WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            
            // Get page IDs for cleanup
            $stmt = $db->query("SELECT id FROM pages WHERE deleted_at IS NOT NULL");
            $pageIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($pageIds)) {
                $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'page' AND commentable_id IN ($placeholders)")->execute($pageIds);
            }
            
            $stmt = $db->query("SELECT COUNT(*) FROM pages WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM pages WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash halaman: {$count} item dihapus permanen", 'pages', null);
            setAlert('success', "Semua halaman di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'categories':
            $stmt = $db->query("SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM post_categories WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash kategori: {$count} item dihapus permanen", 'post_categories', null);
            setAlert('success', "Semua kategori di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'files':
            // Delete all files and thumbnails
            $stmt = $db->query("SELECT file_path, thumbnail_path FROM downloadable_files WHERE deleted_at IS NOT NULL");
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($files as $file) {
                deleteFileIfExists($file['file_path']);
                if ($file['thumbnail_path']) {
                    deleteFileIfExists($file['thumbnail_path']);
                }
            }
            
            $count = count($files);
            $db->exec("DELETE FROM downloadable_files WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash files: {$count} item dihapus permanen", 'downloadable_files', null);
            setAlert('success', "Semua file di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'albums':
            // Delete all album covers and photos
            $stmt = $db->query("SELECT id, cover_photo FROM gallery_albums WHERE deleted_at IS NOT NULL");
            $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPhotos = 0;
            foreach ($albums as $album) {
                // Delete album cover
                deleteFileIfExists($album['cover_photo']);
                
                // Delete all photos in album
                $stmt = $db->prepare("SELECT filename, thumbnail FROM gallery_photos WHERE album_id = ?");
                $stmt->execute([$album['id']]);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($photos as $photo) {
                    deleteFileIfExists($photo['filename']);
                    deleteFileIfExists($photo['thumbnail']);
                    $totalPhotos++;
                }
                
                // Delete photos from database
                $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$album['id']]);
            }
            
            $count = count($albums);
            $db->exec("DELETE FROM gallery_albums WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash albums: {$count} album dan {$totalPhotos} foto dihapus permanen", 'gallery_albums', null);
            setAlert('success', "Semua album di trash ({$count} album, {$totalPhotos} foto) sudah dihapus permanen!");
            break;
            
        case 'photos':
            // Delete all photo files
            $stmt = $db->query("SELECT filename, thumbnail FROM gallery_photos WHERE deleted_at IS NOT NULL");
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($photos as $photo) {
                deleteFileIfExists($photo['filename']);
                deleteFileIfExists($photo['thumbnail']);
            }
            
            $count = count($photos);
            $db->exec("DELETE FROM gallery_photos WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash photos: {$count} item dihapus permanen", 'gallery_photos', null);
            setAlert('success', "Semua foto di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'banners':
            // Delete all banner images
            $stmt = $db->query("SELECT image_path FROM banners WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            
            $stmt = $db->query("SELECT COUNT(*) FROM banners WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM banners WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash banners: {$count} item dihapus permanen", 'banners', null);
            setAlert('success', "Semua banner di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case 'contacts':
            $stmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM contact_messages WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan trash pesan kontak: {$count} item dihapus permanen", 'contact_messages', null);
            setAlert('success', "Semua pesan kontak di trash ({$count} item) sudah dihapus permanen!");
            break;
            
        case '':
            // Empty ALL trash from all tables
            $totalDeleted = 0;
            
            // 1. Posts
            $stmt = $db->query("SELECT featured_image FROM posts WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            $stmt = $db->query("SELECT id FROM posts WHERE deleted_at IS NOT NULL");
            $postIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($postIds)) {
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM post_likes WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'post' AND commentable_id IN ($placeholders)")->execute($postIds);
            }
            $stmt = $db->query("SELECT COUNT(*) FROM posts WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM posts WHERE deleted_at IS NOT NULL");
            
            // 2. Services
            $stmt = $db->query("SELECT image_path FROM services WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            $stmt = $db->query("SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM services WHERE deleted_at IS NOT NULL");
            
            // 3. Users
            $stmt = $db->query("SELECT photo FROM users WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM users WHERE deleted_at IS NOT NULL");
            
            // 4. Pages
            $stmt = $db->query("SELECT featured_image FROM pages WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            $stmt = $db->query("SELECT id FROM pages WHERE deleted_at IS NOT NULL");
            $pageIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($pageIds)) {
                $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'page' AND commentable_id IN ($placeholders)")->execute($pageIds);
            }
            $stmt = $db->query("SELECT COUNT(*) FROM pages WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM pages WHERE deleted_at IS NOT NULL");
            
            // 5. Categories
            $stmt = $db->query("SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM post_categories WHERE deleted_at IS NOT NULL");
            
            // 6. Files
            $stmt = $db->query("SELECT file_path, thumbnail_path FROM downloadable_files WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
                deleteFileIfExists($file['file_path']);
                if ($file['thumbnail_path']) {
                    deleteFileIfExists($file['thumbnail_path']);
                }
            }
            $stmt = $db->query("SELECT COUNT(*) FROM downloadable_files WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM downloadable_files WHERE deleted_at IS NOT NULL");
            
            // 7. Albums (and their photos)
            $stmt = $db->query("SELECT id, cover_photo FROM gallery_albums WHERE deleted_at IS NOT NULL");
            $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($albums as $album) {
                deleteFileIfExists($album['cover_photo']);
                $stmt = $db->prepare("SELECT filename, thumbnail FROM gallery_photos WHERE album_id = ?");
                $stmt->execute([$album['id']]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $photo) {
                    deleteFileIfExists($photo['filename']);
                    deleteFileIfExists($photo['thumbnail']);
                }
                $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$album['id']]);
            }
            $totalDeleted += count($albums);
            $db->exec("DELETE FROM gallery_albums WHERE deleted_at IS NOT NULL");
            
            // 8. Photos
            $stmt = $db->query("SELECT filename, thumbnail FROM gallery_photos WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $photo) {
                deleteFileIfExists($photo['filename']);
                deleteFileIfExists($photo['thumbnail']);
            }
            $stmt = $db->query("SELECT COUNT(*) FROM gallery_photos WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM gallery_photos WHERE deleted_at IS NOT NULL");
            
            // 9. Banners
            $stmt = $db->query("SELECT image_path FROM banners WHERE deleted_at IS NOT NULL");
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
                deleteFileIfExists($file);
            }
            $stmt = $db->query("SELECT COUNT(*) FROM banners WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM banners WHERE deleted_at IS NOT NULL");
            
            // 10. Contact Messages
            $stmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NOT NULL");
            $totalDeleted += $stmt->fetchColumn();
            $db->exec("DELETE FROM contact_messages WHERE deleted_at IS NOT NULL");
            
            logActivity('DELETE', "Mengosongkan SEMUA trash: {$totalDeleted} item dihapus permanen dari semua modul", null, null);
            setAlert('success', "Semua trash di semua modul ({$totalDeleted} total item) sudah dihapus permanen!");
            break;
            
        default:
            $db->rollBack();
            setAlert('danger', 'Tipe trash tidak dikenal.');
            header("Location: trash_list.php");
            exit;
    }
    
    $db->commit();
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Empty Trash Error: " . $e->getMessage());
    setAlert('danger', 'Terjadi kesalahan saat mengosongkan trash: ' . $e->getMessage());
}

header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
exit;