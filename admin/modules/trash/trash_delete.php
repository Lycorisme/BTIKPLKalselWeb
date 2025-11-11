<?php
/**
 * Trash Permanent Delete - Complete System
 * Menghapus permanen data dari database beserta file terkait
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// Only super_admin and admin can permanently delete
if (!hasRole(['super_admin', 'admin'])) {
    setAlert('danger', 'Akses ditolak. Hanya Super Admin dan Admin yang dapat menghapus permanen.');
    redirect(ADMIN_URL);
}

$db = Database::getInstance()->getConnection();
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setAlert('danger', 'ID tidak valid.');
    header("Location: trash_list.php");
    exit;
}

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
        case 'post':
            // Get post data
            $stmt = $db->prepare("SELECT title, featured_image FROM posts WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                throw new Exception("Post tidak ditemukan di trash.");
            }
            
            // Delete featured image
            if ($post['featured_image']) {
                deleteFileIfExists($post['featured_image']);
            }
            
            // Delete post tags first (foreign key)
            $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
            
            // Delete post likes
            $db->prepare("DELETE FROM post_likes WHERE post_id = ?")->execute([$id]);
            
            // Delete comments
            $db->prepare("DELETE FROM comments WHERE commentable_type = 'post' AND commentable_id = ?")->execute([$id]);
            
            // Delete post
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen post: {$post['title']} (ID: {$id})", 'posts', $id);
            setAlert('success', 'Post berhasil dihapus permanen!');
            break;
            
        case 'service':
            // Get service data
            $stmt = $db->prepare("SELECT title, image_path FROM services WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$service) {
                throw new Exception("Layanan tidak ditemukan di trash.");
            }
            
            // Delete service image
            if ($service['image_path']) {
                deleteFileIfExists($service['image_path']);
            }
            
            // Delete service
            $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen layanan: {$service['title']} (ID: {$id})", 'services', $id);
            setAlert('success', 'Layanan berhasil dihapus permanen!');
            break;
            
        case 'user':
            // Get user data
            $stmt = $db->prepare("SELECT name, photo FROM users WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User tidak ditemukan di trash.");
            }
            
            // Delete user avatar
            if ($user['photo']) {
                deleteFileIfExists($user['photo']);
            }
            
            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen user: {$user['name']} (ID: {$id})", 'users', $id);
            setAlert('success', 'User berhasil dihapus permanen!');
            break;
            
        case 'page':
            // Get page data
            $stmt = $db->prepare("SELECT title, featured_image FROM pages WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$page) {
                throw new Exception("Halaman tidak ditemukan di trash.");
            }
            
            // Delete featured image
            if ($page['featured_image']) {
                deleteFileIfExists($page['featured_image']);
            }
            
            // Delete comments
            $db->prepare("DELETE FROM comments WHERE commentable_type = 'page' AND commentable_id = ?")->execute([$id]);
            
            // Delete page
            $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen halaman: {$page['title']} (ID: {$id})", 'pages', $id);
            setAlert('success', 'Halaman berhasil dihapus permanen!');
            break;
            
        case 'category':
            // Get category data
            $stmt = $db->prepare("SELECT name FROM post_categories WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                throw new Exception("Kategori tidak ditemukan di trash.");
            }
            
            // Check if category has posts
            $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $postCount = $stmt->fetchColumn();
            
            if ($postCount > 0) {
                throw new Exception("Kategori masih memiliki {$postCount} post aktif. Pindahkan atau hapus post terlebih dahulu.");
            }
            
            // Delete category
            $stmt = $db->prepare("DELETE FROM post_categories WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen kategori: {$category['name']} (ID: {$id})", 'post_categories', $id);
            setAlert('success', 'Kategori berhasil dihapus permanen!');
            break;
            
        case 'file':
            // Get file data
            $stmt = $db->prepare("SELECT title, file_path, thumbnail_path FROM downloadable_files WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                throw new Exception("File tidak ditemukan di trash.");
            }
            
            // Delete file and thumbnail
            if ($file['file_path']) {
                deleteFileIfExists($file['file_path']);
            }
            if ($file['thumbnail_path']) {
                deleteFileIfExists($file['thumbnail_path']);
            }
            
            // Delete file record
            $stmt = $db->prepare("DELETE FROM downloadable_files WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen file: {$file['title']} (ID: {$id})", 'downloadable_files', $id);
            setAlert('success', 'File berhasil dihapus permanen!');
            break;
            
        case 'album':
            // Get album data
            $stmt = $db->prepare("SELECT name, cover_photo FROM gallery_albums WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $album = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$album) {
                throw new Exception("Album tidak ditemukan di trash.");
            }
            
            // Delete all photos in album
            $stmt = $db->prepare("SELECT filename, thumbnail FROM gallery_photos WHERE album_id = ?");
            $stmt->execute([$id]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($photos as $photo) {
                if ($photo['filename']) {
                    deleteFileIfExists($photo['filename']);
                }
                if ($photo['thumbnail']) {
                    deleteFileIfExists($photo['thumbnail']);
                }
            }
            
            // Delete photos from database
            $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$id]);
            
            // Delete album cover
            if ($album['cover_photo']) {
                deleteFileIfExists($album['cover_photo']);
            }
            
            // Delete album
            $stmt = $db->prepare("DELETE FROM gallery_albums WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen album: {$album['name']} (ID: {$id}) beserta " . count($photos) . " foto", 'gallery_albums', $id);
            setAlert('success', 'Album beserta semua foto berhasil dihapus permanen!');
            break;
            
        case 'photo':
            // Get photo data
            $stmt = $db->prepare("SELECT title, filename, thumbnail FROM gallery_photos WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$photo) {
                throw new Exception("Foto tidak ditemukan di trash.");
            }
            
            // Delete photo files
            if ($photo['filename']) {
                deleteFileIfExists($photo['filename']);
            }
            if ($photo['thumbnail']) {
                deleteFileIfExists($photo['thumbnail']);
            }
            
            // Delete photo record
            $stmt = $db->prepare("DELETE FROM gallery_photos WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen foto: " . ($photo['title'] ?: "Photo #{$id}") . " (ID: {$id})", 'gallery_photos', $id);
            setAlert('success', 'Foto berhasil dihapus permanen!');
            break;
            
        case 'banner':
            // Get banner data
            $stmt = $db->prepare("SELECT title, image_path FROM banners WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $banner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$banner) {
                throw new Exception("Banner tidak ditemukan di trash.");
            }
            
            // Delete banner image
            if ($banner['image_path']) {
                deleteFileIfExists($banner['image_path']);
            }
            
            // Delete banner
            $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen banner: {$banner['title']} (ID: {$id})", 'banners', $id);
            setAlert('success', 'Banner berhasil dihapus permanen!');
            break;
            
        case 'contact':
            // Get contact message data
            $stmt = $db->prepare("SELECT subject FROM contact_messages WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                throw new Exception("Pesan kontak tidak ditemukan di trash.");
            }
            
            // Delete contact message
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen pesan kontak: {$contact['subject']} (ID: {$id})", 'contact_messages', $id);
            setAlert('success', 'Pesan kontak berhasil dihapus permanen!');
            break;
            
        default:
            $db->rollBack();
            setAlert('danger', 'Tipe data tidak dikenal.');
            header("Location: trash_list.php");
            exit;
    }
    
    $db->commit();
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Trash Delete Error: " . $e->getMessage());
    setAlert('danger', 'Error! ' . $e->getMessage());
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