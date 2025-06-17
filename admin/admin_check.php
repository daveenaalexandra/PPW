<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Selalu muat file konfigurasi di awal.
// Ini akan mendefinisikan BASE_URL dan membuat koneksi $conn untuk semua skenario.
require_once __DIR__ . '/../php/db_config.php';

// Cek apakah pengguna sudah login DAN perannya adalah 'admin'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Jika tidak, tendang kembali ke halaman login.
    // BASE_URL sudah pasti tersedia dari require_once di atas.
    header("Location: " . BASE_URL . "login.php?error=access_denied");
    exit();
}

// Jika lolos, skrip akan lanjut.
// Variabel $conn dari db_config.php sudah pasti tersedia untuk file yang memanggil (misal: manage_orders.php).
?>