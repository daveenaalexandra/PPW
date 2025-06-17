<?php
session_start();
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/php/db_config.php';
}

// Langkah Debugging: Hentikan eksekusi dan lihat apa yang dikirim oleh form.
// Hapus atau beri komentar pada 2 baris ini setelah selesai debugging.
// var_dump($_POST);
// die();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item_id'], $_POST['item_type'])) {
    $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $itemType = $_POST['item_type'];

    if ($itemId > 0) {
        // Logika untuk menghapus PRODUK
        if ($itemType == 'product' && isset($_SESSION['cart']['products'][$itemId])) {
            unset($_SESSION['cart']['products'][$itemId]);
            $_SESSION['cart_message'] = "Produk berhasil dihapus dari keranjang.";
        
        // Logika untuk menghapus KEGIATAN
        } elseif ($itemType == 'activity' && isset($_SESSION['cart']['activities'][$itemId])) {
            unset($_SESSION['cart']['activities'][$itemId]);
            $_SESSION['cart_message'] = "Kegiatan berhasil dibatalkan dari keranjang.";
            
        } else {
            $_SESSION['cart_message'] = "Gagal menghapus: Item tidak ditemukan di keranjang.";
        }
    } else {
         $_SESSION['cart_message'] = "Gagal menghapus: Data tidak valid.";
    }
} else {
    $_SESSION['cart_message'] = "Permintaan tidak valid.";
}

header("Location: " . BASE_URL . "cart.php");
exit();
?>