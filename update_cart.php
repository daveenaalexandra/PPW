<?php
// File: update_cart.php
session_start();
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/php/db_config.php';
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'], $_POST['quantity'])) {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($productId > 0 && $quantity >= 0 && isset($_SESSION['cart']['products'][$productId])) {
        if ($quantity == 0) {
            // Jika kuantitas 0, hapus item
            unset($_SESSION['cart']['products'][$productId]);
            $_SESSION['cart_message'] = "Produk berhasil dihapus dari keranjang.";
        } else {
            // Perbarui kuantitas
            $_SESSION['cart']['products'][$productId]['quantity'] = $quantity;
            $_SESSION['cart_message'] = "Kuantitas produk berhasil diperbarui.";
        }
    } else {
        $_SESSION['cart_message'] = "Gagal memperbarui keranjang: Data tidak valid.";
    }
} else {
    $_SESSION['cart_message'] = "Permintaan tidak valid.";
}

header("Location: " . BASE_URL . "cart.php");
exit();
?>