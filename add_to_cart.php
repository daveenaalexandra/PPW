<?php
// File: add_to_cart.php
// File ini menangani penambahan produk ke keranjang belanja melalui AJAX.

session_start(); // Mulai sesi jika belum dimulai

// Pastikan BASE_URL didefinisikan (dari db_config.php atau sejenisnya)
if (!defined('BASE_URL')) {
    // Sesuaikan path jika db_config.php berada di lokasi yang berbeda relatif terhadap file ini
    require_once __DIR__ . '/php/db_config.php';
}

header('Content-Type: application/json'); // Respons dengan JSON

$response = ['status' => 'error', 'message' => 'Permintaan tidak valid.'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {

    // --- TAMBAHKAN PEMERIKSAAN LOGIN DI SINI ---
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'Anda harus login terlebih dahulu untuk menambahkan produk ke keranjang.';
        echo json_encode($response);
        exit();
    }
    // --- AKHIR PEMERIKSAAN LOGIN ---

    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $productName = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
    $productPrice = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT);

    // Validasi dasar
    if (!$productId || $productId <= 0 || !$quantity || $quantity <= 0 || !$productName || $productPrice === false) {
        $response['message'] = 'Data produk tidak lengkap atau tidak valid.';
        echo json_encode($response);
        exit();
    }

    // Inisialisasi keranjang dalam sesi jika tidak ada
    if (!isset($_SESSION['cart']['products'])) {
        $_SESSION['cart']['products'] = [];
    }

    // Periksa apakah produk sudah ada di keranjang
    if (isset($_SESSION['cart']['products'][$productId])) {
        // Produk ada, perbarui kuantitas
        $_SESSION['cart']['products'][$productId]['quantity'] += $quantity;
        $response['status'] = 'success';
        $response['message'] = htmlspecialchars($productName) . ' berhasil ditambahkan ke keranjang (kuantitas diperbarui).';
    } else {
        // Produk tidak ada di keranjang, tambahkan item baru
        $_SESSION['cart']['products'][$productId] = [
            'product_id' => $productId,
            'product_name' => $productName,
            'price' => $productPrice,
            'quantity' => $quantity,
        ];
        $response['status'] = 'success';
        $response['message'] = htmlspecialchars($productName) . ' berhasil ditambahkan ke keranjang.';
    }

} else {
    $response['message'] = 'Metode permintaan tidak didukung.';
}

echo json_encode($response);
exit();
?>