<?php
// File: order_confirmation.php (Versi Final Rapi)

// ========================================================================
// --- 1. INISIALISASI & PENGAMBILAN DATA ---
// ========================================================================

$page_title = "Konfirmasi Pesanan - Baking Lovers";
require_once 'php/partials/header.php'; // Memuat header, koneksi DB, dan memulai sesi

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Silakan login untuk melihat konfirmasi pesanan Anda.";
    header("Location: " . BASE_URL . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$order_details = null;
$order_items = [];
$error_message = '';

// Ambil order_id dari URL dan validasi
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id) {
    $error_message = "ID Pesanan tidak valid atau tidak ditemukan.";
} else {
    if (isset($conn) && $conn instanceof mysqli) {
        // Ambil detail pesanan utama
        $stmt_order = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt_order->bind_param("ii", $order_id, $_SESSION['user_id']);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();
        $order_details = $result_order->fetch_assoc();
        $stmt_order->close();

        // Jika detail pesanan ditemukan, ambil item-itemnya
        if ($order_details) {
            $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            while ($row = $result_items->fetch_assoc()) {
                $order_items[] = $row;
            }
            $stmt_items->close();
        } else {
            $error_message = "Detail pesanan tidak ditemukan atau Anda tidak memiliki akses ke pesanan ini.";
        }
    } else {
        $error_message = "Koneksi database tidak tersedia.";
    }
}

?>

<div class="container page-container order-confirmation-page">
    <h1 class="page-title">Konfirmasi Pesanan</h1>

    <?php if ($order_details): ?>
        <div class="message success">
            <p>Terima kasih! Pesanan Anda dengan ID #<strong><?php echo htmlspecialchars($order_details['order_id']); ?></strong> telah berhasil ditempatkan.</p>
            <p>Kami akan segera memproses pesanan Anda.</p>
        </div>

        <div class="order-details-block card-style">
            <h2>Detail Pesanan Anda</h2>
            
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">ID Pesanan:</span>
                    <span class="detail-value">#<?php echo htmlspecialchars($order_details['order_id']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Tanggal Pesanan:</span>
                    <span class="detail-value"><?php echo date("d F Y H:i", strtotime($order_details['order_date'])); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Total Pembayaran:</span>
                    <span class="detail-value">Rp <?php echo number_format($order_details['total_amount'], 0, ',', '.'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Metode Pembayaran:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order_details['payment_method']); ?></span>
                </div>
                <div class="detail-item full-width">
                    <span class="detail-label">Alamat Pengiriman:</span>
                    <span class="detail-value address-value"><?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status Pesanan:</span>
                    <span class="detail-value status-value status-<?php echo strtolower(htmlspecialchars($order_details['status'])); ?>"><?php echo htmlspecialchars(ucfirst($order_details['status'])); ?></span>
                </div>
            </div>

            <h3 class="items-title">Item Pesanan:</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kuantitas</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td class="text-right">Rp <?php echo number_format($item['price_per_item'], 0, ',', '.'); ?></td>
                            <td class="text-right">Rp <?php echo number_format($item['quantity'] * $item['price_per_item'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="message error">
            <p><?php echo $error_message; ?></p>
            <p>Silakan periksa <a href="<?php echo BASE_URL; ?>profile.php">Profil Anda</a> untuk melihat riwayat pesanan.</p>
        </div>
    <?php endif; ?>

    <p style="margin-top: 30px; text-align:center;">
        <a href="<?php echo BASE_URL; ?>index.php" class="btn-link">&larr; Kembali ke Halaman Utama</a>
    </p>
</div>

<?php 
require_once 'php/partials/footer.php'; 

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>