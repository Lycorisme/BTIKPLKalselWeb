<?php
/**
 * Logout Handler
 */

// 1. Include Config (Ini akan mendaftarkan Database Handler)
require_once '../config/config.php';
require_once '../core/Database.php';
require_once '../core/Helper.php';

// 2. Start Session (Gunakan cek status agar aman)
// Session start wajib dipanggil SETELAH config agar menggunakan Database Handler
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Log aktivitas logout (Lakukan sebelum session di-destroy)
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance()->getConnection();
        // Ambil data dari session
        $userId = $_SESSION['user_id'];
        $userName = $_SESSION['user_name'] ?? 'Unknown';

        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, user_name, action_type, description, ip_address) 
            VALUES (?, ?, 'LOGOUT', 'User melakukan logout', ?)
        ");
        $stmt->execute([
            $userId, 
            $userName, 
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        // Silent fail agar logout tetap jalan meski log gagal
        error_log($e->getMessage());
    }
}

// 4. Destroy session (Otomatis menghapus dari database via handler)
session_destroy();

// 5. Clear session data variable
$_SESSION = [];

// 6. Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// 7. Hapus remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// 8. Redirect ke login
redirect(ADMIN_URL . 'login.php');