<?php
define('DB_SERVER', 'localhost'); // Biasanya 'localhost'
define('DB_USERNAME', 'root');    // Ganti dengan username database Anda
define('DB_PASSWORD', '');        // Ganti dengan password database Anda
define('DB_NAME', 'baking_lovers_db_advanced'); // Ganti dengan nama database Anda
define('BASE_URL', '/Project%20UAS/');
// Mencoba terhubung ke database MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// Set charset ke utf8mb4 untuk dukungan karakter yang lebih baik
$conn->set_charset("utf8mb4");
?>