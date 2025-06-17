<?php
// File: logout.php
// Pastikan db_config.php di-include untuk BASE_URL
// Jika session_start() belum dipanggil oleh db_config.php, panggil di sini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/php/db_config.php';
}

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// Hancurkan sesi
session_destroy();

// Redirect ke halaman login dengan pesan sukses logout
header("Location: " . BASE_URL . "login.php?message=" . urlencode("Anda telah berhasil logout."));
exit();
?>