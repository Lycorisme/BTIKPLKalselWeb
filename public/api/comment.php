<?php
/**
 * Comment API Endpoint
 * With Rate Limiting Protection
 */

session_start();

require_once '../config.php';
require_once '../../core/Database.php';
require_once '../../core/RateLimiter.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$postId = (int)($_POST['post_id'] ?? 0);
$authorName = trim($_POST['author_name'] ?? '');
$authorEmail = trim($_POST['author_email'] ?? '');
$content = trim($_POST['content'] ?? '');

// ===== RATE LIMITING - START =====
$rateLimiter = new RateLimiter();
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Use email as identifier if provided, otherwise IP
$identifier = !empty($authorEmail) ? $authorEmail : $ipAddress;

// Check rate limit: max 10 comments per 15 minutes
$rateCheck = $rateLimiter->check($identifier, 'comment', 10, 15);

if (!$rateCheck['allowed']) {
    echo json_encode([
        'success' => false,
        'message' => $rateCheck['message']
    ]);
    exit;
}
// ===== RATE LIMITING - END =====

// Validation
$errors = [];

if (empty($postId)) {
    $errors[] = 'Post ID tidak valid';
}

if (empty($authorName)) {
    $errors[] = 'Nama harus diisi';
} elseif (strlen($authorName) < 2) {
    $errors[] = 'Nama minimal 2 karakter';
} elseif (strlen($authorName) > 100) {
    $errors[] = 'Nama maksimal 100 karakter';
}

if (empty($authorEmail)) {
    $errors[] = 'Email harus diisi';
} elseif (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
}

if (empty($content)) {
    $errors[] = 'Komentar harus diisi';
} elseif (strlen($content) < 10) {
    $errors[] = 'Komentar minimal 10 karakter';
} elseif (strlen($content) > 1000) {
    $errors[] = 'Komentar maksimal 1000 karakter';
}

// Return validation errors
if (!empty($errors)) {
    // Don't record rate limit for validation errors
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit;
}

// Check if post exists
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id FROM posts WHERE id = ? AND status = 'published' AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        echo json_encode([
            'success' => false,
            'message' => 'Post tidak ditemukan'
        ]);
        exit;
    }
    
    // Insert comment
    $stmt = $db->prepare("
        INSERT INTO comments (
            commentable_type,
            commentable_id,
            name,
            email,
            content,
            status,
            ip_address,
            user_agent,
            created_at
        ) VALUES (
            'post',
            ?,
            ?,
            ?,
            ?,
            'pending',
            ?,
            ?,
            NOW()
        )
    ");
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $result = $stmt->execute([
        $postId,
        $authorName,
        $authorEmail,
        $content,
        $ipAddress,
        $userAgent
    ]);
    
    if ($result) {
        // Record successful comment submission
        $rateLimiter->record($identifier, 'comment', 15);
        
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil dikirim dan menunggu persetujuan admin.',
            'reload' => false
        ]);
    } else {
        // Also record failed attempts to prevent spam retry
        $rateLimiter->record($identifier, 'comment', 15);
        
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan komentar. Silakan coba lagi.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Comment API Error: " . $e->getMessage());
    
    // Record error attempts
    $rateLimiter->record($identifier, 'comment', 15);
    
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.'
    ]);
}
