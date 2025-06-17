<?php
// File: cart.php (Versi Final Bersih - Gabungan Produk & Kegiatan)

// ========================================================================
// --- 1. INISIALISASI & PERSIAPAN DATA ---
// ========================================================================

$page_title = "Keranjang Belanja & Pendaftaran - Baking Lovers";
require_once 'php/partials/header.php'; // Memuat header, koneksi DB, dan memulai sesi

// Ambil pesan dari sesi jika ada (misal: setelah update/hapus item)
$cart_message = $_SESSION['cart_message'] ?? null;
unset($_SESSION['cart_message']); // Hapus pesan setelah diambil

// Ambil data keranjang dari sesi dengan struktur baru
$product_items = $_SESSION['cart']['products'] ?? [];
$activity_items = $_SESSION['cart']['activities'] ?? [];
$total_cart_price = 0;

?>

<div class="container page-container cart-page">
    <h1 class="page-title">Keranjang Anda</h1>

    <?php if ($cart_message): ?>
        <div class="message success"><?php echo htmlspecialchars($cart_message); ?></div>
    <?php endif; ?>

    <?php if (empty($product_items) && empty($activity_items)): ?>
        <div class="cart-empty message info" style="text-align: center;">
            <p>Keranjang Anda masih kosong.</p>
            <a href="<?php echo BASE_URL; ?>index.php#product" class="btn-link">Mulai Belanja & Cari Kegiatan!</a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            
            <?php if (!empty($product_items)): ?>
                <h3>Produk Belanjaan</h3>
                <div class="cart-items-list">
                    <?php foreach ($product_items as $product_id => $item): ?>
                        <?php
                            $subtotal = $item['price'] * $item['quantity'];
                            $total_cart_price += $subtotal;
                        ?>
                        <div class="cart-item card-style">
                            <div class="cart-item__details">
                                <h4 class="cart-item__name"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <p class="cart-item__price">Harga Satuan: Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                                <p class="cart-item__subtotal">Subtotal: Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></p>
                            </div>
                            <div class="cart-item__actions">
                                <form action="<?php echo BASE_URL; ?>update_cart.php" method="POST" class="update-cart-form">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <label for="qty_<?php echo $product_id; ?>">Qty:</label>
                                    <input type="number" id="qty_<?php echo $product_id; ?>" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" class="qty-input">
                                    <button type="submit" name="update_qty" class="btn-sm">Update</button>
                                    <form action="<?php echo BASE_URL; ?>remove_from_cart.php" method="POST">
                                        <input type="hidden" name="item_id" value="<?php echo $product_id; ?>">
                                        <input type="hidden" name="item_type" value="product">
                                        <button type="submit" name="remove_item" class="btn-sm btn-danger">Hapus</button>
                                    </form>                                
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($activity_items)): ?>
                <h3 style="margin-top: 40px;">Kegiatan untuk Diikuti</h3>
                <div class="cart-items-list">
                    <?php foreach ($activity_items as $activity_id => $item): ?>
                        <?php $total_cart_price += $item['price']; ?>
                        <div class="cart-item card-style">
                            <div class="cart-item__details">
                                <h4 class="cart-item__name"><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="cart-item__price">Biaya Pendaftaran: Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                            </div>
                            <div class="cart-item__actions">
                                <form action="<?php echo BASE_URL; ?>remove_from_cart.php" method="POST">
                                    <input type="hidden" name="item_id" value="<?php echo $activity_id; ?>">
                                    <input type="hidden" name="item_type" value="activity">
                                    <button type="submit" name="remove_item" class="btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="cart-summary card-style">
                <h3>Total Pembayaran</h3>
                <p class="cart-summary__total">Rp <?php echo number_format($total_cart_price, 0, ',', '.'); ?></p>
                <a href="<?php echo BASE_URL; ?>checkout.php" class="btn-submit-user-form">Lanjutkan ke Checkout</a>
                <a href="<?php echo BASE_URL; ?>index.php#product" class="btn-link cart-summary__continue">&larr; Lanjut Belanja</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php 
require_once 'php/partials/footer.php'; 
?>