<?php
/**
 * Download File Handler
 * Tracks download count with token-based anti-double-count protection
 */

require_once __DIR__ . '/../config.php';

// Get file ID and token
$file_id = $_GET['id'] ?? 0;
$token = $_GET['token'] ?? '';

if (empty($file_id) || !is_numeric($file_id)) {
    die('Invalid file ID');
}

try {
    // Get file info
    $stmt = $db->prepare("
        SELECT * FROM downloadable_files 
        WHERE id = ? AND is_active = 1 AND deleted_at IS NULL
    ");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        die('File not found');
    }
    
    // Build full file path
    $file_path = __DIR__ . '/../' . $file['file_path'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        die('File not found on server');
    }
    
    // ===== ANTI-DOUBLE-COUNT WITH TOKEN =====
    $should_count = false;
    
    if (!empty($token)) {
        $token_key = 'dl_token_' . $token;
        
        // Only count if this is the first time we see this token
        if (!isset($_SESSION[$token_key])) {
            $_SESSION[$token_key] = time();
            $should_count = true;
            
            // Clean old tokens (older than 1 hour)
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, 'dl_token_') === 0 && (time() - $value) > 3600) {
                    unset($_SESSION[$key]);
                }
            }
        }
    } else {
        // Fallback without token (IP-based)
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $download_key = 'download_ip_' . $file_id . '_' . md5($ip_address);
        
        if (!isset($_SESSION[$download_key]) || (time() - $_SESSION[$download_key]) > 5) {
            $_SESSION[$download_key] = time();
            $should_count = true;
        }
    }
    
    // Increment counter only once
    if ($should_count) {
        $stmt = $db->prepare("
            UPDATE downloadable_files 
            SET download_count = download_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$file_id]);
        
        // Log activity
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, action_type, description, model_type, model_id, ip_address, user_agent, created_at)
            VALUES (NULL, 'DOWNLOAD', ?, 'downloadable_files', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'Download file: ' . $file['title'],
            $file_id,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    // ===== END ANTI-DOUBLE-COUNT =====
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: attachment; filename="' . basename($file['file_path']) . '"');
    header('Content-Length: ' . $file['file_size']);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    error_log('Download Error: ' . $e->getMessage());
    die('Error downloading file');
}
