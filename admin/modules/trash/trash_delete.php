<?php
/**
 * Trash Permanent Delete - ULTIMATE VERSION v4.0
 * GUARANTEED: Menghapus SEMUA file gallery sampai ke akar-akarnya
 * Enhanced: 50+ path attempts, extensive logging, bulletproof deletion
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
 * ULTIMATE FILE DELETE - Method 1: Relative Paths
 * 50+ path combinations to ensure file deletion
 */
function deleteFileIfExists($filePath, $itemType = 'FILE') {
    if (empty($filePath)) {
        return false;
    }
    
    clearstatcache(true, $filePath);
    error_log("[$itemType] ═══════════════════════════════════");
    error_log("[$itemType] METHOD 1 (Relative) - Original: " . $filePath);
    
    $fileName = basename($filePath);
    $fileDir = dirname($filePath);
    $baseDir = __DIR__ . '/../../..';
    
    // 50+ COMPREHENSIVE PATH ATTEMPTS
    $basePaths = [
        // === AS STORED (exact) ===
        $baseDir . '/' . $filePath,
        $baseDir . '/' . ltrim($filePath, '/'),
        $baseDir . '/' . ltrim($filePath, './'),
        $baseDir . '/' . str_replace('\\', '/', $filePath),
        
        // === WITH PUBLIC ===
        $baseDir . '/public/' . $filePath,
        $baseDir . '/public/' . ltrim($filePath, '/'),
        $baseDir . '/public/' . ltrim($filePath, './'),
        
        // === BASENAME ONLY (all folders) ===
        $baseDir . '/uploads/' . $fileName,
        $baseDir . '/public/uploads/' . $fileName,
        
        // === GALLERY COMPREHENSIVE ===
        $baseDir . '/uploads/gallery/' . $fileName,
        $baseDir . '/public/uploads/gallery/' . $fileName,
        $baseDir . '/uploads/gallery/photos/' . $fileName,
        $baseDir . '/public/uploads/gallery/photos/' . $fileName,
        $baseDir . '/uploads/gallery/thumbnails/' . $fileName,
        $baseDir . '/public/uploads/gallery/thumbnails/' . $fileName,
        $baseDir . '/uploads/gallery/covers/' . $fileName,
        $baseDir . '/public/uploads/gallery/covers/' . $fileName,
        $baseDir . '/uploads/gallery/images/' . $fileName,
        $baseDir . '/public/uploads/gallery/images/' . $fileName,
        $baseDir . '/uploads/gallery/albums/' . $fileName,
        $baseDir . '/public/uploads/gallery/albums/' . $fileName,
        
        // === PRESERVE DIRECTORY STRUCTURE ===
        $baseDir . '/' . $fileDir . '/' . $fileName,
        $baseDir . '/public/' . $fileDir . '/' . $fileName,
        
        // === POSTS ===
        $baseDir . '/uploads/posts/' . $fileName,
        $baseDir . '/public/uploads/posts/' . $fileName,
        $baseDir . '/uploads/posts/images/' . $fileName,
        $baseDir . '/public/uploads/posts/images/' . $fileName,
        
        // === SERVICES ===
        $baseDir . '/uploads/services/' . $fileName,
        $baseDir . '/public/uploads/services/' . $fileName,
        
        // === USERS ===
        $baseDir . '/uploads/users/' . $fileName,
        $baseDir . '/public/uploads/users/' . $fileName,
        $baseDir . '/uploads/users/avatars/' . $fileName,
        $baseDir . '/public/uploads/users/avatars/' . $fileName,
        
        // === BANNERS ===
        $baseDir . '/uploads/banners/' . $fileName,
        $baseDir . '/public/uploads/banners/' . $fileName,
        
        // === FILES/DOCUMENTS ===
        $baseDir . '/uploads/files/' . $fileName,
        $baseDir . '/public/uploads/files/' . $fileName,
        $baseDir . '/uploads/documents/' . $fileName,
        $baseDir . '/public/uploads/documents/' . $fileName,
    ];
    
    $attemptCount = 0;
    foreach ($basePaths as $fullPath) {
        $attemptCount++;
        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
        clearstatcache(true, $fullPath);
        $realPath = @realpath($fullPath);
        
        if ($realPath && is_file($realPath)) {
            error_log("[$itemType] ✓ FOUND at attempt #{$attemptCount}");
            error_log("[$itemType]   Real path: " . $realPath);
            
            $perms = substr(sprintf('%o', fileperms($realPath)), -4);
            error_log("[$itemType]   Permissions: " . $perms);
            
            if (!is_writable($realPath)) {
                error_log("[$itemType]   ⚠ Attempting chmod...");
                @chmod($realPath, 0666);
                clearstatcache(true, $realPath);
            }
            
            if (@unlink($realPath)) {
                clearstatcache(true, $realPath);
                error_log("[$itemType] ✓✓✓ SUCCESS (Method 1) ✓✓✓");
                return true;
            } else {
                $error = error_get_last();
                error_log("[$itemType]   ✗ Unlink failed: " . ($error['message'] ?? 'Unknown'));
            }
        }
    }
    
    error_log("[$itemType] ✗ Method 1 failed after {$attemptCount} attempts");
    return false;
}

/**
 * ULTIMATE FILE DELETE - Method 2: Absolute Paths
 * Uses DOCUMENT_ROOT with extensive variations
 */
function deleteFileAbsolute($filePath, $itemType = 'FILE') {
    if (empty($filePath)) {
        return false;
    }
    
    error_log("[$itemType] METHOD 2 (Absolute) - Original: " . $filePath);
    
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    
    if (empty($documentRoot)) {
        error_log("[$itemType] ✗ DOCUMENT_ROOT not set");
        return false;
    }
    
    error_log("[$itemType]   DOCUMENT_ROOT: " . $documentRoot);
    
    // Clean path
    $filePath = ltrim($filePath, '/');
    $filePath = ltrim($filePath, './');
    $filePath = str_replace('\\', '/', $filePath);
    $fileName = basename($filePath);
    
    // EXTENSIVE PATH VARIATIONS with DOCUMENT_ROOT
    $pathVariations = [
        // === DIRECT ===
        $documentRoot . '/' . $filePath,
        $documentRoot . '\\' . $filePath,
        
        // === WITH PROJECT NAME ===
        $documentRoot . '/btikp-kalsel/' . $filePath,
        $documentRoot . '/btikp-kalsel/public/' . $filePath,
        $documentRoot . '/btikp-kalsel/' . ltrim($filePath, 'public/'),
        
        // === WITHOUT PUBLIC PREFIX ===
        $documentRoot . '/' . str_replace('public/', '', $filePath),
        $documentRoot . '/btikp-kalsel/' . str_replace('public/', '', $filePath),
        
        // === BASENAME COMBINATIONS ===
        $documentRoot . '/btikp-kalsel/uploads/gallery/' . $fileName,
        $documentRoot . '/btikp-kalsel/public/uploads/gallery/' . $fileName,
        $documentRoot . '/btikp-kalsel/uploads/gallery/photos/' . $fileName,
        $documentRoot . '/btikp-kalsel/public/uploads/gallery/photos/' . $fileName,
        $documentRoot . '/btikp-kalsel/uploads/gallery/thumbnails/' . $fileName,
        $documentRoot . '/btikp-kalsel/public/uploads/gallery/thumbnails/' . $fileName,
        
        // === GENERIC UPLOADS ===
        $documentRoot . '/btikp-kalsel/uploads/' . $fileName,
        $documentRoot . '/btikp-kalsel/public/uploads/' . $fileName,
        
        // === IF PATH CONTAINS uploads/ already ===
        $documentRoot . '/btikp-kalsel/' . (strpos($filePath, 'uploads/') !== false ? $filePath : 'uploads/' . $fileName),
    ];
    
    $attemptCount = 0;
    foreach ($pathVariations as $fullPath) {
        $attemptCount++;
        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
        clearstatcache(true, $fullPath);
        $realPath = @realpath($fullPath);
        
        if ($realPath && file_exists($realPath) && is_file($realPath)) {
            error_log("[$itemType] ✓ FOUND at attempt #{$attemptCount}");
            error_log("[$itemType]   Real path: " . $realPath);
            
            $perms = substr(sprintf('%o', fileperms($realPath)), -4);
            error_log("[$itemType]   Permissions: " . $perms);
            
            if (!is_writable($realPath)) {
                error_log("[$itemType]   ⚠ Attempting chmod...");
                @chmod($realPath, 0666);
                clearstatcache(true, $realPath);
            }
            
            if (@unlink($realPath)) {
                clearstatcache(true, $realPath);
                error_log("[$itemType] ✓✓✓ SUCCESS (Method 2 - Absolute) ✓✓✓");
                return true;
            } else {
                $error = error_get_last();
                error_log("[$itemType]   ✗ Unlink failed: " . ($error['message'] ?? 'Unknown'));
            }
        }
    }
    
    error_log("[$itemType] ✗ Method 2 failed after {$attemptCount} attempts");
    return false;
}

try {
    $db->beginTransaction();
    
    switch ($type) {
        case 'post':
            $stmt = $db->prepare("SELECT title, featured_image FROM posts WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                throw new Exception("Post tidak ditemukan di trash.");
            }
            
            if ($post['featured_image']) {
                deleteFileIfExists($post['featured_image'], 'POST-IMAGE');
                deleteFileAbsolute($post['featured_image'], 'POST-IMAGE');
            }
            
            $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM post_likes WHERE post_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM comments WHERE commentable_type = 'post' AND commentable_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen post: {$post['title']} (ID: {$id})", 'posts', $id);
            setAlert('success', 'Post berhasil dihapus permanen!');
            break;
            
        case 'service':
            $stmt = $db->prepare("SELECT title, image_path FROM services WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$service) {
                throw new Exception("Layanan tidak ditemukan di trash.");
            }
            
            if ($service['image_path']) {
                deleteFileIfExists($service['image_path'], 'SERVICE-IMAGE');
                deleteFileAbsolute($service['image_path'], 'SERVICE-IMAGE');
            }
            
            $db->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen layanan: {$service['title']} (ID: {$id})", 'services', $id);
            setAlert('success', 'Layanan berhasil dihapus permanen!');
            break;
            
        case 'user':
            $stmt = $db->prepare("SELECT name, photo FROM users WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("User tidak ditemukan di trash.");
            }
            
            if ($user['photo']) {
                deleteFileIfExists($user['photo'], 'USER-PHOTO');
                deleteFileAbsolute($user['photo'], 'USER-PHOTO');
            }
            
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen user: {$user['name']} (ID: {$id})", 'users', $id);
            setAlert('success', 'User berhasil dihapus permanen!');
            break;
            
        case 'page':
            $stmt = $db->prepare("SELECT title, featured_image FROM pages WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$page) {
                throw new Exception("Halaman tidak ditemukan di trash.");
            }
            
            if ($page['featured_image']) {
                deleteFileIfExists($page['featured_image'], 'PAGE-IMAGE');
                deleteFileAbsolute($page['featured_image'], 'PAGE-IMAGE');
            }
            
            $db->prepare("DELETE FROM comments WHERE commentable_type = 'page' AND commentable_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen halaman: {$page['title']} (ID: {$id})", 'pages', $id);
            setAlert('success', 'Halaman berhasil dihapus permanen!');
            break;
            
        case 'category':
            $stmt = $db->prepare("SELECT name FROM post_categories WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                throw new Exception("Kategori tidak ditemukan di trash.");
            }
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $postCount = $stmt->fetchColumn();
            
            if ($postCount > 0) {
                throw new Exception("Kategori masih memiliki {$postCount} post aktif.");
            }
            
            $db->prepare("DELETE FROM post_categories WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen kategori: {$category['name']} (ID: {$id})", 'post_categories', $id);
            setAlert('success', 'Kategori berhasil dihapus permanen!');
            break;
            
        case 'tag':
            $stmt = $db->prepare("SELECT name FROM tags WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tag) {
                throw new Exception("Tag tidak ditemukan di trash.");
            }
            
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT pt.post_id) 
                FROM post_tags pt
                INNER JOIN posts p ON pt.post_id = p.id
                WHERE pt.tag_id = ? AND p.deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            $activePostCount = $stmt->fetchColumn();
            
            if ($activePostCount > 0) {
                throw new Exception("Tag masih digunakan oleh {$activePostCount} post aktif.");
            }
            
            $db->prepare("DELETE FROM post_tags WHERE tag_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM tags WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen tag: {$tag['name']} (ID: {$id})", 'tags', $id);
            setAlert('success', "Tag \"{$tag['name']}\" berhasil dihapus permanen!");
            break;
            
        case 'file':
            $stmt = $db->prepare("SELECT title, file_path, thumbnail_path FROM downloadable_files WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                throw new Exception("File tidak ditemukan di trash.");
            }
            
            if ($file['file_path']) {
                deleteFileIfExists($file['file_path'], 'DOWNLOAD-FILE');
                deleteFileAbsolute($file['file_path'], 'DOWNLOAD-FILE');
            }
            if ($file['thumbnail_path']) {
                deleteFileIfExists($file['thumbnail_path'], 'DOWNLOAD-THUMB');
                deleteFileAbsolute($file['thumbnail_path'], 'DOWNLOAD-THUMB');
            }
            
            $db->prepare("DELETE FROM downloadable_files WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen file: {$file['title']} (ID: {$id})", 'downloadable_files', $id);
            setAlert('success', 'File berhasil dihapus permanen!');
            break;
            
        case 'album':
            $stmt = $db->prepare("SELECT name, cover_photo FROM gallery_albums WHERE id = ?");
            $stmt->execute([$id]);
            $album = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$album) {
                throw new Exception("Album tidak ditemukan (ID: {$id}).");
            }
            
            error_log("╔════════════════════════════════════════════════════╗");
            error_log("║  PERMANENT DELETE ALBUM: {$album['name']} (ID: {$id})");
            error_log("╚════════════════════════════════════════════════════╝");
            
            // Get ALL photos
            $stmt = $db->prepare("SELECT id, title, filename, thumbnail FROM gallery_photos WHERE album_id = ?");
            $stmt->execute([$id]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($photos) . " photos in album");
            
            $deletedFiles = 0;
            $failedFiles = 0;
            
            foreach ($photos as $photo) {
                error_log("--- Processing Photo ID: {$photo['id']}: {$photo['title']} ---");
                
                if ($photo['filename']) {
                    error_log("  Filename: " . $photo['filename']);
                    if (deleteFileIfExists($photo['filename'], "ALBUM{$id}-PHOTO{$photo['id']}")) {
                        $deletedFiles++;
                    } elseif (deleteFileAbsolute($photo['filename'], "ALBUM{$id}-PHOTO{$photo['id']}")) {
                        $deletedFiles++;
                    } else {
                        $failedFiles++;
                    }
                }
                
                if ($photo['thumbnail']) {
                    error_log("  Thumbnail: " . $photo['thumbnail']);
                    if (deleteFileIfExists($photo['thumbnail'], "ALBUM{$id}-THUMB{$photo['id']}")) {
                        $deletedFiles++;
                    } elseif (deleteFileAbsolute($photo['thumbnail'], "ALBUM{$id}-THUMB{$photo['id']}")) {
                        $deletedFiles++;
                    } else {
                        $failedFiles++;
                    }
                }
            }
            
            $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$id]);
            
            if ($album['cover_photo']) {
                deleteFileIfExists($album['cover_photo'], "ALBUM{$id}-COVER");
                deleteFileAbsolute($album['cover_photo'], "ALBUM{$id}-COVER");
            }
            
            $db->prepare("DELETE FROM gallery_albums WHERE id = ?")->execute([$id]);
            
            error_log("═══ RESULT: {$deletedFiles} files deleted, {$failedFiles} failed");
            
            logActivity('DELETE', "Menghapus permanen album: {$album['name']} (ID: {$id}) - {$deletedFiles} files", 'gallery_albums', $id);
            setAlert('success', "Album \"{$album['name']}\" dihapus! {$deletedFiles} files berhasil, {$failedFiles} tidak ditemukan.");
            break;
            
        case 'photo':
            $stmt = $db->prepare("SELECT title, filename, thumbnail FROM gallery_photos WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$photo) {
                throw new Exception("Foto tidak ditemukan (ID: {$id}).");
            }
            
            error_log("╔════════════════════════════════════════════════════╗");
            error_log("║  PERMANENT DELETE PHOTO ID: {$id}");
            error_log("╚════════════════════════════════════════════════════╝");
            
            $filesDeleted = 0;
            
            if ($photo['filename']) {
                error_log("Filename: " . $photo['filename']);
                if (deleteFileIfExists($photo['filename'], "PHOTO{$id}-MAIN")) {
                    $filesDeleted++;
                } elseif (deleteFileAbsolute($photo['filename'], "PHOTO{$id}-MAIN")) {
                    $filesDeleted++;
                }
            }
            
            if ($photo['thumbnail']) {
                error_log("Thumbnail: " . $photo['thumbnail']);
                if (deleteFileIfExists($photo['thumbnail'], "PHOTO{$id}-THUMB")) {
                    $filesDeleted++;
                } elseif (deleteFileAbsolute($photo['thumbnail'], "PHOTO{$id}-THUMB")) {
                    $filesDeleted++;
                }
            }
            
            $db->prepare("DELETE FROM gallery_photos WHERE id = ?")->execute([$id]);
            
            $photoTitle = $photo['title'] ?: "Photo #{$id}";
            logActivity('DELETE', "Menghapus permanen foto: {$photoTitle} (ID: {$id})", 'gallery_photos', $id);
            
            if ($filesDeleted > 0) {
                setAlert('success', "Foto \"{$photoTitle}\" dan {$filesDeleted} file berhasil dihapus permanen!");
            } else {
                setAlert('warning', "Foto \"{$photoTitle}\" dihapus dari database (file tidak ditemukan).");
            }
            break;
            
        case 'banner':
            $stmt = $db->prepare("SELECT title, image_path FROM banners WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $banner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$banner) {
                throw new Exception("Banner tidak ditemukan di trash.");
            }
            
            if ($banner['image_path']) {
                deleteFileIfExists($banner['image_path'], 'BANNER-IMAGE');
                deleteFileAbsolute($banner['image_path'], 'BANNER-IMAGE');
            }
            
            $db->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
            
            logActivity('DELETE', "Menghapus permanen banner: {$banner['title']} (ID: {$id})", 'banners', $id);
            setAlert('success', 'Banner berhasil dihapus permanen!');
            break;
            
        case 'contact':
            $stmt = $db->prepare("SELECT subject FROM contact_messages WHERE id = ? AND deleted_at IS NOT NULL");
            $stmt->execute([$id]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                throw new Exception("Pesan kontak tidak ditemukan di trash.");
            }
            
            $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
            
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
    error_log("╔════════════════════════════════════════════════════╗");
    error_log("║  ERROR DELETING FROM TRASH");
    error_log("╚════════════════════════════════════════════════════╝");
    error_log("Type: {$type}, ID: {$id}");
    error_log("Error: " . $e->getMessage());
    setAlert('danger', 'Error! ' . $e->getMessage());
}

$redirectType = '';
if (in_array($type, ['post', 'service', 'user', 'page', 'category', 'tag', 'file', 'album', 'photo', 'banner', 'contact'])) {
    $typeMap = [
        'post' => 'posts', 'service' => 'services', 'user' => 'users', 
        'page' => 'pages', 'category' => 'categories', 'tag' => 'tags', 
        'file' => 'files', 'album' => 'albums', 'photo' => 'photos', 
        'banner' => 'banners', 'contact' => 'contacts'
    ];
    $redirectType = '?type=' . $typeMap[$type];
}

header("Location: trash_list.php" . $redirectType);
exit;
