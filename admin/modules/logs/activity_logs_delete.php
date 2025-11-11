<?php
/**
 * Handle Activity Log Cleanup
 * Dijalankan dari modal di activity_logs.php
 * DIPERBAIKI: Logika penghapusan sesuai dengan pilihan "Hapus log lebih dari X hari"
 */

require_once '../../includes/auth_check.php';
require_once '../../../core/Database.php';
require_once '../../../core/Helper.php';

// 1. Keamanan: Pastikan metode adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setAlert('danger', 'Aksi tidak diizinkan (Invalid request method).');
    redirect(ADMIN_URL . 'modules/logs/activity_logs.php');
}

// 2. Keamanan: Hanya Super Admin yang bisa menghapus
if (!hasRole(['super_admin'])) {
    setAlert('danger', 'Anda tidak memiliki izin untuk melakukan aksi ini.');
    redirect(ADMIN_URL . 'modules/logs/activity_logs.php');
}

// 3. Validasi Input
$cleanupDays = (int)($_POST['cleanup_days'] ?? 0);

// Validasi ketat berdasarkan opsi yang tersedia
$allowedDays = [7, 30, 60, 90, 180, 365];
if (!in_array($cleanupDays, $allowedDays)) {
    setAlert('danger', 'Periode cleanup tidak valid.');
    redirect(ADMIN_URL . 'modules/logs/activity_logs.php');
}

// 4. Eksekusi Database
try {
    $db = Database::getInstance()->getConnection();
    
    // Hitung tanggal batas (cutoff date)
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-$cleanupDays days"));
    
    // Query untuk menghapus log yang lebih LAMA dari tanggal batas
    // Contoh: Jika pilih "30 hari", maka hapus semua log sebelum 30 hari yang lalu
    $sql = "DELETE FROM activity_logs 
            WHERE created_at < :cutoff_date";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([':cutoff_date' => $cutoffDate]);
    
    $deletedCount = $stmt->rowCount();

    // 5. Log Aksi Ini
    // Penting: logActivity harus dipanggil *setelah* $db di-instance
    logActivity(
        'DELETE', 
        "Cleanup activity logs: Menghapus $deletedCount record(s) yang lebih tua dari $cleanupDays hari (sebelum $cutoffDate)", 
        'activity_logs'
    );
    
    // 6. Set Notifikasi Sukses
    if ($deletedCount > 0) {
        setAlert('success', "Berhasil menghapus $deletedCount log yang lebih tua dari $cleanupDays hari.");
    } else {
        setAlert('info', "Tidak ada log yang lebih tua dari $cleanupDays hari untuk dihapus.");
    }

} catch (PDOException $e) {
    // 7. Set Notifikasi Gagal
    // Catat error teknis di server log
    error_log("Log Cleanup Error: " . $e->getMessage());
    
    // Tampilkan pesan error yang aman ke user
    setAlert('danger', 'Gagal menghapus logs: Terjadi kesalahan pada database.');
}

// 8. Redirect kembali ke halaman list (notifikasi akan otomatis tampil)
redirect(ADMIN_URL . 'modules/logs/activity_logs.php');