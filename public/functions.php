<?php
/**
 * Public-Specific Helper Functions
 */

/**
 * Get site logo from settings
 */
function get_site_logo() {
    $logo = getSetting('site_logo');
    if (empty($logo)) {
        return uploadUrl('settings/logo-default.png');
    }
    return uploadUrl($logo);
}

/**
 * Get site favicon from settings
 */
function get_site_favicon() {
    $favicon = getSetting('site_favicon');
    if (empty($favicon)) {
        return BASE_URL . 'assets/favicon.ico';
    }
    return uploadUrl($favicon);
}

/**
 * Get featured image with fallback
 */
function get_featured_image($image_path) {
    if (empty($image_path)) {
        return BASE_URL . 'assets/images/blog-default.jpg';
    }
    return uploadUrl($image_path);
}

/**
 * Format relative date
 */
// function formatTanggalRelatif($date) {
//     if (empty($date)) return '-';
    
//     $timestamp = strtotime($date);
//     $diff = time() - $timestamp;
    
//     if ($diff < 60) {
//         return 'Baru saja';
//     } elseif ($diff < 3600) {
//         $minutes = floor($diff / 60);
//         return $minutes . ' menit yang lalu';
//     } elseif ($diff < 86400) {
//         $hours = floor($diff / 3600);
//         return $hours . ' jam yang lalu';
//     } elseif ($diff < 172800) {
//         return 'Kemarin';
//     } else {
//         return formatTanggal($date, 'd M Y');
//     }
// }

/**
 * Get banner image with fallback
 */
function get_banner_image($image_path) {
    if (empty($image_path)) {
        $placeholder = getSetting('banner_placeholder', 'settings/banner-placeholder.jpg');
        return uploadUrl($placeholder);
    }
    return uploadUrl($image_path);
}

/**
 * Get service image with fallback
 */
function get_service_image($image_path) {
    if (empty($image_path)) {
        $placeholder = getSetting('service_placeholder', 'settings/service-placeholder.jpg');
        return uploadUrl($placeholder);
    }
    return uploadUrl($image_path);
}

/**
 * Get album cover with fallback
 */
function get_album_cover($image_path) {
    if (empty($image_path)) {
        $placeholder = getSetting('album_placeholder', 'settings/album-placeholder.jpg');
        return uploadUrl($placeholder);
    }
    return uploadUrl($image_path);
}

/**
 * Increment post view count
 */
function increment_post_views($post_id) {
    global $db;
    try {
        // Update post view_count
        $stmt = $db->prepare("UPDATE posts SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
        
        // Log to page_views table (if table exists)
        $stmt = $db->prepare("
            INSERT INTO page_views (model_type, model_id, ip_address, user_agent, referrer, created_at)
            VALUES ('post', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $post_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

/**
 * Increment service view count
 */
function increment_service_views($service_id) {
    global $db;
    try {
        $stmt = $db->prepare("UPDATE services SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$service_id]);
        
        $stmt = $db->prepare("
            INSERT INTO page_views (model_type, model_id, ip_address, user_agent, referrer)
            VALUES ('service', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $service_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

/**
 * Get popular posts
 */
function get_popular_posts($limit = 5) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT id, title, slug, featured_image, created_at, view_count
            FROM posts
            WHERE status = 'published' AND deleted_at IS NULL
            ORDER BY view_count DESC, created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Get recent posts
 */
function get_recent_posts($limit = 5) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT id, title, slug, featured_image, created_at, view_count
            FROM posts
            WHERE status = 'published' AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Get related posts by category
 */
function get_related_posts($post_id, $category_id, $limit = 3) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT id, title, slug, featured_image, created_at
            FROM posts
            WHERE status = 'published' 
            AND deleted_at IS NULL 
            AND category_id = ? 
            AND id != ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$category_id, $post_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Get post like count
 */
function get_post_likes($post_id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * Check if user has liked post (by IP)
 */
function has_user_liked_post($post_id) {
    global $db;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM post_likes 
            WHERE post_id = ? AND ip_address = ?
        ");
        $stmt->execute([$post_id, $ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * Get post comments count
 */
function get_post_comments_count($post_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM comments 
            WHERE model_type = 'post' 
            AND model_id = ? 
            AND is_approved = 1
        ");
        $stmt->execute([$post_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * Get site stats
 */
function get_site_stats() {
    global $db;
    try {
        $stats = [];
        
        // Total posts
        $stmt = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 'published' AND deleted_at IS NULL");
        $stats['posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total gallery photos
        $stmt = $db->query("SELECT COUNT(*) as count FROM gallery_photos WHERE deleted_at IS NULL");
        $stats['photos'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total downloads
        $stmt = $db->query("SELECT COUNT(*) as count FROM downloadable_files WHERE is_active = 1 AND deleted_at IS NULL");
        $stats['files'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total services
        $stmt = $db->query("SELECT COUNT(*) as count FROM services WHERE is_active = 1 AND deleted_at IS NULL");
        $stats['services'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['posts' => 0, 'photos' => 0, 'files' => 0, 'services' => 0];
    }
}

/**
 * Get file icon and color based on file type
 */
function getFileIcon($file_type) {
    $file_type = strtolower(trim($file_type));
    
    $icons = [
        // Documents
        'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'text-red-600'],
        'doc' => ['icon' => 'fa-file-word', 'color' => 'text-blue-600'],
        'docx' => ['icon' => 'fa-file-word', 'color' => 'text-blue-600'],
        'xls' => ['icon' => 'fa-file-excel', 'color' => 'text-green-600'],
        'xlsx' => ['icon' => 'fa-file-excel', 'color' => 'text-green-600'],
        'ppt' => ['icon' => 'fa-file-powerpoint', 'color' => 'text-orange-600'],
        'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => 'text-orange-600'],
        'txt' => ['icon' => 'fa-file-alt', 'color' => 'text-gray-600'],
        
        // Archives
        'zip' => ['icon' => 'fa-file-archive', 'color' => 'text-yellow-600'],
        'rar' => ['icon' => 'fa-file-archive', 'color' => 'text-yellow-600'],
        '7z' => ['icon' => 'fa-file-archive', 'color' => 'text-yellow-600'],
        
        // Images
        'jpg' => ['icon' => 'fa-file-image', 'color' => 'text-purple-600'],
        'jpeg' => ['icon' => 'fa-file-image', 'color' => 'text-purple-600'],
        'png' => ['icon' => 'fa-file-image', 'color' => 'text-purple-600'],
        'gif' => ['icon' => 'fa-file-image', 'color' => 'text-purple-600'],
        'svg' => ['icon' => 'fa-file-image', 'color' => 'text-purple-600'],
        
        // Videos
        'mp4' => ['icon' => 'fa-file-video', 'color' => 'text-pink-600'],
        'avi' => ['icon' => 'fa-file-video', 'color' => 'text-pink-600'],
        'mkv' => ['icon' => 'fa-file-video', 'color' => 'text-pink-600'],
        'mov' => ['icon' => 'fa-file-video', 'color' => 'text-pink-600'],
        
        // Audio
        'mp3' => ['icon' => 'fa-file-audio', 'color' => 'text-indigo-600'],
        'wav' => ['icon' => 'fa-file-audio', 'color' => 'text-indigo-600'],
        'ogg' => ['icon' => 'fa-file-audio', 'color' => 'text-indigo-600'],
        
        // Code
        'php' => ['icon' => 'fa-file-code', 'color' => 'text-indigo-700'],
        'js' => ['icon' => 'fa-file-code', 'color' => 'text-yellow-500'],
        'html' => ['icon' => 'fa-file-code', 'color' => 'text-orange-500'],
        'css' => ['icon' => 'fa-file-code', 'color' => 'text-blue-500'],
        'json' => ['icon' => 'fa-file-code', 'color' => 'text-green-500'],
        'xml' => ['icon' => 'fa-file-code', 'color' => 'text-red-500'],
    ];
    
    // Return icon for specific file type or default
    return $icons[$file_type] ?? ['icon' => 'fa-file', 'color' => 'text-gray-500'];
}
