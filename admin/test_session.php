<?php
// PENTING: Panggil config SEBELUM session_start
require_once '../config/config.php';

// Baru start session (handler sudah terdaftar dari config)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Session Test - ID: " . session_id() . "<br>";
$_SESSION['test_counter'] = ($_SESSION['test_counter'] ?? 0) + 1;
echo "Counter: " . $_SESSION['test_counter'] . "<br><br>";

echo "<pre>";
echo "Handler: ";
var_dump(ini_get('session.save_handler')); // HARUS "user" bukan "files"
echo "</pre>";

// Cek database
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([session_id()]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($session);
echo "</pre>";

if (!$session) {
    echo "<strong style='color:red'>ERROR: Session tidak tersimpan ke database!</strong>";
} else {
    echo "<strong style='color:green'>SUCCESS: Session tersimpan ke database!</strong>";
}
