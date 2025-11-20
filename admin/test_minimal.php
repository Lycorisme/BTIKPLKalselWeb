<?php
// TEST TANPA REQUIRE FILE LAIN — hanya config & session handler
require_once '../config/config.php';

// Start session SETELAH handler didaftarkan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tulis/muat session
$_SESSION['minimal_test'] = ($_SESSION['minimal_test'] ?? 0) + 1;

// Output handler
echo "<h3>Session Handler Test</h3>";
echo "Handler: <strong>";
var_dump(ini_get('session.save_handler'));
echo "</strong><br>";

// Output session status
echo "Session status: ";
switch(session_status()){
    case PHP_SESSION_DISABLED: echo "DISABLED"; break;
    case PHP_SESSION_NONE:     echo "NONE";     break;
    case PHP_SESSION_ACTIVE:   echo "ACTIVE";   break;
}
echo "<br>";

// Output session id and value
echo "Session ID: <strong>" . session_id() . "</strong><br>";
echo "Session minimal_test counter: " . $_SESSION['minimal_test'] . "<br>";

// Database check
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([session_id()]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h4>Database session record:</h4>";
echo "<pre>";
print_r($session);
echo "</pre>";

if (!$session) {
    echo '<span style="color:red"><b>ERROR: Session tidak masuk ke database!</b></span>';
    echo '<br>Jika "Handler: files", berarti masih ada session_start() sebelum config, atau ada include/autoload lain sebelum config.';
} else {
    echo '<span style="color:green"><b>SUCCESS: Session sudah tersimpan di database!</b></span>';
}
?>
