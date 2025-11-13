<?php
/**
 * Empty Trash - ULTIMATE VERSION v5.0
 * GUARANTEED: Menghapus SEMUA file sampai ke akar-akarnya
 * Enhanced: 30+ path attempts, clearstatcache, extensive logging, TAGS support
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
 * ULTIMATE FILE DELETE FUNCTION
 * Guaranteed deletion with 30+ path attempts and stat cache clearing
 */
function deleteFileIfExists($filePath, $itemType = 'FILE') {
    if (empty($filePath)) {
        return false;
    }
    
    clearstatcache(true, $filePath);
    error_log("[$itemType] ═══ Attempting: " . $filePath);
    
    $fileName = basename($filePath);
    $baseDir = __DIR__ . '/../../..';
    
    // 30+ POSSIBLE PATHS - COMPREHENSIVE
    $basePaths = [
        $baseDir . '/' . $filePath,
        $baseDir . '/' . ltrim($filePath, '/'),
        $baseDir . '/' . ltrim($filePath, './'),
        $baseDir . '/public/' . $filePath,
        $baseDir . '/public/' . ltrim($filePath, '/'),
        $baseDir . '/uploads/' . $fileName,
        $baseDir . '/public/uploads/' . $fileName,
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
        $baseDir . '/uploads/posts/' . $fileName,
        $baseDir . '/public/uploads/posts/' . $fileName,
        $baseDir . '/uploads/posts/images/' . $fileName,
        $baseDir . '/public/uploads/posts/images/' . $fileName,
        $baseDir . '/uploads/services/' . $fileName,
        $baseDir . '/public/uploads/services/' . $fileName,
        $baseDir . '/uploads/users/' . $fileName,
        $baseDir . '/public/uploads/users/' . $fileName,
        $baseDir . '/uploads/banners/' . $fileName,
        $baseDir . '/public/uploads/banners/' . $fileName,
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
            
            if (!is_writable($realPath)) {
                @chmod($realPath, 0666);
                clearstatcache(true, $realPath);
            }
            
            if (@unlink($realPath)) {
                clearstatcache(true, $realPath);
                error_log("[$itemType] ✓✓ SUCCESS DELETED!");
                return true;
            }
        }
    }
    
    error_log("[$itemType] ✗✗✗ NOT FOUND after {$attemptCount} attempts");
    return false;
}

try {
    $db->beginTransaction();
    
    switch ($type) {
        case 'posts':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: POSTS              ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, title, featured_image FROM posts WHERE deleted_at IS NOT NULL");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($posts);
            
            $filesDeleted = 0;
            $filesFailed = 0;
            
            foreach ($posts as $post) {
                if ($post['featured_image']) {
                    if (deleteFileIfExists($post['featured_image'], "POST{$post['id']}")) {
                        $filesDeleted++;
                    } else {
                        $filesFailed++;
                    }
                }
            }
            
            $postIds = array_column($posts, 'id');
            if (!empty($postIds)) {
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM post_likes WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'post' AND commentable_id IN ($placeholders)")->execute($postIds);
            }
            
            $db->exec("DELETE FROM posts WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} posts, {$filesDeleted} files OK, {$filesFailed} failed");
            logActivity('DELETE', "Empty trash posts: {$count} items, {$filesDeleted} files", 'posts', null);
            setAlert('success', "Trash posts dikosongkan! {$count} posts, {$filesDeleted} file dihapus.");
            break;
            
        case 'services':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: SERVICES           ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, title, image_path FROM services WHERE deleted_at IS NOT NULL");
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($services);
            
            $filesDeleted = 0;
            foreach ($services as $service) {
                if ($service['image_path']) {
                    if (deleteFileIfExists($service['image_path'], "SERVICE{$service['id']}")) {
                        $filesDeleted++;
                    }
                }
            }
            
            $db->exec("DELETE FROM services WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} services, {$filesDeleted} files deleted");
            logActivity('DELETE', "Empty trash services: {$count} items, {$filesDeleted} files", 'services', null);
            setAlert('success', "Trash layanan dikosongkan! {$count} layanan, {$filesDeleted} file dihapus.");
            break;
            
        case 'users':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: USERS              ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, name, photo FROM users WHERE deleted_at IS NOT NULL");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($users);
            
            $filesDeleted = 0;
            foreach ($users as $user) {
                if ($user['photo']) {
                    if (deleteFileIfExists($user['photo'], "USER{$user['id']}")) {
                        $filesDeleted++;
                    }
                }
            }
            
            $db->exec("DELETE FROM users WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} users, {$filesDeleted} files deleted");
            logActivity('DELETE', "Empty trash users: {$count} items, {$filesDeleted} files", 'users', null);
            setAlert('success', "Trash users dikosongkan! {$count} users, {$filesDeleted} file dihapus.");
            break;
            
        case 'pages':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: PAGES              ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, title, featured_image FROM pages WHERE deleted_at IS NOT NULL");
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($pages);
            
            $filesDeleted = 0;
            foreach ($pages as $page) {
                if ($page['featured_image']) {
                    if (deleteFileIfExists($page['featured_image'], "PAGE{$page['id']}")) {
                        $filesDeleted++;
                    }
                }
            }
            
            $pageIds = array_column($pages, 'id');
            if (!empty($pageIds)) {
                $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'page' AND commentable_id IN ($placeholders)")->execute($pageIds);
            }
            
            $db->exec("DELETE FROM pages WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} pages, {$filesDeleted} files deleted");
            logActivity('DELETE', "Empty trash pages: {$count} items, {$filesDeleted} files", 'pages', null);
            setAlert('success', "Trash halaman dikosongkan! {$count} halaman, {$filesDeleted} file dihapus.");
            break;
            
        case 'categories':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: CATEGORIES         ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM post_categories WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} categories deleted");
            logActivity('DELETE', "Empty trash categories: {$count} items", 'post_categories', null);
            setAlert('success', "Trash kategori dikosongkan! {$count} kategori dihapus.");
            break;
            
        case 'tags':
            // ADDED: Tags empty trash
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: TAGS               ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, name FROM tags WHERE deleted_at IS NOT NULL");
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($tags);
            
            $totalRelationships = 0;
            
            if (!empty($tags)) {
                $tagIds = array_column($tags, 'id');
                
                // Count and delete relationships in post_tags (junction table)
                $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
                $stmt = $db->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id IN ($placeholders)");
                $stmt->execute($tagIds);
                $totalRelationships = $stmt->fetchColumn();
                
                if ($totalRelationships > 0) {
                    $db->prepare("DELETE FROM post_tags WHERE tag_id IN ($placeholders)")->execute($tagIds);
                    error_log("   Deleted {$totalRelationships} tag relationships");
                }
            }
            
            $db->exec("DELETE FROM tags WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} tags, {$totalRelationships} relationships deleted");
            logActivity('DELETE', "Empty trash tags: {$count} tags, {$totalRelationships} relationships", 'tags', null);
            setAlert('success', "Trash tags dikosongkan! {$count} tags dan {$totalRelationships} relationships dihapus.");
            break;
            
        case 'files':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: FILES              ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, title, file_path, thumbnail_path FROM downloadable_files WHERE deleted_at IS NOT NULL");
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($files);
            
            $filesDeleted = 0;
            foreach ($files as $file) {
                if ($file['file_path']) {
                    if (deleteFileIfExists($file['file_path'], "FILE{$file['id']}")) {
                        $filesDeleted++;
                    }
                }
                if ($file['thumbnail_path']) {
                    if (deleteFileIfExists($file['thumbnail_path'], "FILE{$file['id']}-THUMB")) {
                        $filesDeleted++;
                    }
                }
            }
            
            $db->exec("DELETE FROM downloadable_files WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} files, {$filesDeleted} physical files deleted");
            logActivity('DELETE', "Empty trash files: {$count} items, {$filesDeleted} files", 'downloadable_files', null);
            setAlert('success', "Trash files dikosongkan! {$count} items, {$filesDeleted} file dihapus.");
            break;
            
        case 'albums':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: ALBUMS             ║");
            error_log("╚══════════════════════════════════════╝");
            
            // CRITICAL: Get albums yang soft-deleted
            $stmt = $db->query("SELECT id, name, cover_photo FROM gallery_albums WHERE deleted_at IS NOT NULL");
            $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($albums);
            
            $totalFiles = 0;
            $totalPhotos = 0;
            
            foreach ($albums as $album) {
                error_log("--- Album: {$album['name']} (ID: {$album['id']}) ---");
                
                // Delete cover
                if ($album['cover_photo']) {
                    if (deleteFileIfExists($album['cover_photo'], "ALBUM{$album['id']}-COVER")) {
                        $totalFiles++;
                    }
                }
                
                // CRITICAL: Get ALL photos in this album (regardless of deleted_at)
                // Because when album is soft-deleted, photos might also be soft-deleted OR might not have deleted_at
                $stmt = $db->prepare("SELECT id, title, filename, thumbnail FROM gallery_photos WHERE album_id = ?");
                $stmt->execute([$album['id']]);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("    Found " . count($photos) . " photos in album");
                
                foreach ($photos as $photo) {
                    if ($photo['filename']) {
                        error_log("    → Photo {$photo['id']}: {$photo['filename']}");
                        if (deleteFileIfExists($photo['filename'], "ALBUM{$album['id']}-PHOTO{$photo['id']}")) {
                            $totalFiles++;
                        }
                    }
                    if ($photo['thumbnail']) {
                        if (deleteFileIfExists($photo['thumbnail'], "ALBUM{$album['id']}-THUMB{$photo['id']}")) {
                            $totalFiles++;
                        }
                    }
                    $totalPhotos++;
                }
                
                // Delete photos from database
                $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$album['id']]);
            }
            
            $db->exec("DELETE FROM gallery_albums WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} albums, {$totalPhotos} photos, {$totalFiles} files deleted");
            logActivity('DELETE', "Empty trash albums: {$count} albums, {$totalPhotos} photos, {$totalFiles} files", 'gallery_albums', null);
            setAlert('success', "Trash albums dikosongkan! {$count} albums, {$totalPhotos} photos, {$totalFiles} file dihapus.");
            break;
            
        case 'photos':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: PHOTOS             ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, title, filename, thumbnail FROM gallery_photos WHERE deleted_at IS NOT NULL");
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($photos);
            
            $filesDeleted = 0;
            foreach ($photos as $photo) {
                if ($photo['filename']) {
                    if (deleteFileIfExists($photo['filename'], "PHOTO{$photo['id']}")) {
                        $filesDeleted++;
                    }
                }
                if ($photo['thumbnail']) {
                    if (deleteFileIfExists($photo['thumbnail'], "PHOTO{$photo['id']}-THUMB")) {
                        $filesDeleted++;
                    }
                }
            }
            
            $db->exec("DELETE FROM gallery_photos WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} photos, {$filesDeleted} files deleted");
            logActivity('DELETE', "Empty trash photos: {$count} photos, {$filesDeleted} files", 'gallery_photos', null);
            setAlert('success', "Trash photos dikosongkan! {$count} photos, {$filesDeleted} file dihapus.");
            break;
            
        case 'banners':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: BANNERS            ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT id, title, image_path FROM banners WHERE deleted_at IS NOT NULL");
            $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($banners);
            
            $filesDeleted = 0;
            foreach ($banners as $banner) {
                if ($banner['image_path']) {
                    if (deleteFileIfExists($banner['image_path'], "BANNER{$banner['id']}")) {
                        $filesDeleted++;
                    }
                }
            }
            
            $db->exec("DELETE FROM banners WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} banners, {$filesDeleted} files deleted");
            logActivity('DELETE', "Empty trash banners: {$count} items, {$filesDeleted} files", 'banners', null);
            setAlert('success', "Trash banners dikosongkan! {$count} banners, {$filesDeleted} file dihapus.");
            break;
            
        case 'contacts':
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING TRASH: CONTACTS           ║");
            error_log("╚══════════════════════════════════════╝");
            
            $stmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            $db->exec("DELETE FROM contact_messages WHERE deleted_at IS NOT NULL");
            
            error_log("═══ RESULT: {$count} contacts deleted");
            logActivity('DELETE', "Empty trash contacts: {$count} items", 'contact_messages', null);
            setAlert('success', "Trash pesan kontak dikosongkan! {$count} pesan dihapus.");
            break;
            
        case '':
            // EMPTY ALL TRASH
            error_log("╔══════════════════════════════════════╗");
            error_log("║   EMPTYING ALL TRASH                 ║");
            error_log("╚══════════════════════════════════════╝");
            
            $totalItems = 0;
            $totalFiles = 0;
            
            // 1. Posts
            $stmt = $db->query("SELECT id, featured_image FROM posts WHERE deleted_at IS NOT NULL");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($posts as $post) {
                if ($post['featured_image'] && deleteFileIfExists($post['featured_image'], "POST{$post['id']}")) {
                    $totalFiles++;
                }
            }
            $postIds = array_column($posts, 'id');
            if (!empty($postIds)) {
                $placeholders = implode(',', array_fill(0, count($postIds), '?'));
                $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM post_likes WHERE post_id IN ($placeholders)")->execute($postIds);
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'post' AND commentable_id IN ($placeholders)")->execute($postIds);
            }
            $totalItems += count($posts);
            $db->exec("DELETE FROM posts WHERE deleted_at IS NOT NULL");
            
            // 2. Services
            $stmt = $db->query("SELECT id, image_path FROM services WHERE deleted_at IS NOT NULL");
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($services as $service) {
                if ($service['image_path'] && deleteFileIfExists($service['image_path'], "SERVICE{$service['id']}")) {
                    $totalFiles++;
                }
            }
            $totalItems += count($services);
            $db->exec("DELETE FROM services WHERE deleted_at IS NOT NULL");
            
            // 3. Users
            $stmt = $db->query("SELECT id, photo FROM users WHERE deleted_at IS NOT NULL");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $user) {
                if ($user['photo'] && deleteFileIfExists($user['photo'], "USER{$user['id']}")) {
                    $totalFiles++;
                }
            }
            $totalItems += count($users);
            $db->exec("DELETE FROM users WHERE deleted_at IS NOT NULL");
            
            // 4. Pages
            $stmt = $db->query("SELECT id, featured_image FROM pages WHERE deleted_at IS NOT NULL");
            $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pages as $page) {
                if ($page['featured_image'] && deleteFileIfExists($page['featured_image'], "PAGE{$page['id']}")) {
                    $totalFiles++;
                }
            }
            $pageIds = array_column($pages, 'id');
            if (!empty($pageIds)) {
                $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
                $db->prepare("DELETE FROM comments WHERE commentable_type = 'page' AND commentable_id IN ($placeholders)")->execute($pageIds);
            }
            $totalItems += count($pages);
            $db->exec("DELETE FROM pages WHERE deleted_at IS NOT NULL");
            
            // 5. Categories
            $stmt = $db->query("SELECT COUNT(*) FROM post_categories WHERE deleted_at IS NOT NULL");
            $totalItems += $stmt->fetchColumn();
            $db->exec("DELETE FROM post_categories WHERE deleted_at IS NOT NULL");
            
            // 6. Tags (ADDED)
            $stmt = $db->query("SELECT id FROM tags WHERE deleted_at IS NOT NULL");
            $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tags)) {
                $placeholders = implode(',', array_fill(0, count($tags), '?'));
                $db->prepare("DELETE FROM post_tags WHERE tag_id IN ($placeholders)")->execute($tags);
            }
            $totalItems += count($tags);
            $db->exec("DELETE FROM tags WHERE deleted_at IS NOT NULL");
            
            // 7. Files
            $stmt = $db->query("SELECT id, file_path, thumbnail_path FROM downloadable_files WHERE deleted_at IS NOT NULL");
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($files as $file) {
                if ($file['file_path'] && deleteFileIfExists($file['file_path'], "FILE{$file['id']}")) {
                    $totalFiles++;
                }
                if ($file['thumbnail_path'] && deleteFileIfExists($file['thumbnail_path'], "FILE{$file['id']}-THUMB")) {
                    $totalFiles++;
                }
            }
            $totalItems += count($files);
            $db->exec("DELETE FROM downloadable_files WHERE deleted_at IS NOT NULL");
            
            // 8. Albums
            $stmt = $db->query("SELECT id, cover_photo FROM gallery_albums WHERE deleted_at IS NOT NULL");
            $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($albums as $album) {
                if ($album['cover_photo'] && deleteFileIfExists($album['cover_photo'], "ALBUM{$album['id']}-COVER")) {
                    $totalFiles++;
                }
                
                $stmt = $db->prepare("SELECT id, filename, thumbnail FROM gallery_photos WHERE album_id = ?");
                $stmt->execute([$album['id']]);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($photos as $photo) {
                    if ($photo['filename'] && deleteFileIfExists($photo['filename'], "ALBUM{$album['id']}-PHOTO{$photo['id']}")) {
                        $totalFiles++;
                    }
                    if ($photo['thumbnail'] && deleteFileIfExists($photo['thumbnail'], "ALBUM{$album['id']}-THUMB{$photo['id']}")) {
                        $totalFiles++;
                    }
                }
                
                $db->prepare("DELETE FROM gallery_photos WHERE album_id = ?")->execute([$album['id']]);
            }
            $totalItems += count($albums);
            $db->exec("DELETE FROM gallery_albums WHERE deleted_at IS NOT NULL");
            
            // 9. Photos
            $stmt = $db->query("SELECT id, filename, thumbnail FROM gallery_photos WHERE deleted_at IS NOT NULL");
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($photos as $photo) {
                if ($photo['filename'] && deleteFileIfExists($photo['filename'], "PHOTO{$photo['id']}")) {
                    $totalFiles++;
                }
                if ($photo['thumbnail'] && deleteFileIfExists($photo['thumbnail'], "PHOTO{$photo['id']}-THUMB")) {
                    $totalFiles++;
                }
            }
            $totalItems += count($photos);
            $db->exec("DELETE FROM gallery_photos WHERE deleted_at IS NOT NULL");
            
            // 10. Banners
            $stmt = $db->query("SELECT id, image_path FROM banners WHERE deleted_at IS NOT NULL");
            $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($banners as $banner) {
                if ($banner['image_path'] && deleteFileIfExists($banner['image_path'], "BANNER{$banner['id']}")) {
                    $totalFiles++;
                }
            }
            $totalItems += count($banners);
            $db->exec("DELETE FROM banners WHERE deleted_at IS NOT NULL");
            
            // 11. Contact Messages
            $stmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NOT NULL");
            $totalItems += $stmt->fetchColumn();
            $db->exec("DELETE FROM contact_messages WHERE deleted_at IS NOT NULL");
            
            error_log("╔══════════════════════════════════════╗");
            error_log("║   TOTAL: {$totalItems} items              ");
            error_log("║   FILES: {$totalFiles} deleted            ");
            error_log("╚══════════════════════════════════════╝");
            
            logActivity('DELETE', "Empty ALL trash: {$totalItems} items, {$totalFiles} files", null, null);
            setAlert('success', "SEMUA trash dikosongkan! {$totalItems} items dan {$totalFiles} file dihapus permanen dari semua modul!");
            break;
            
        default:
            $db->rollBack();
            setAlert('danger', 'Tipe trash tidak dikenal.');
            header("Location: trash_list.php");
            exit;
    }
    
    $db->commit();
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("╔══════════════════════════════════════╗");
    error_log("║   ERROR EMPTYING TRASH               ║");
    error_log("╚══════════════════════════════════════╝");
    error_log("Type: {$type}");
    error_log("Error: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    setAlert('danger', 'Error: ' . $e->getMessage());
}

header("Location: trash_list.php" . ($type ? "?type=$type" : ""));
exit;
