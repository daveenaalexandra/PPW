<?php
// File: checkout.php (Versi Final dengan perbaikan)

$page_title = "Checkout - Baking Lovers";
require_once 'php/partials/header.php';

// Validasi login dan keranjang
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = "Silakan login untuk melanjutkan ke checkout.";
    header("Location: " . BASE_URL . "login.php?redirect=" . urlencode(BASE_URL . 'checkout.php'));
    exit();
}
if (!isset($_SESSION['cart']['products']) || empty($_SESSION['cart']['products'])) {
    header("Location: " . BASE_URL . "cart.php?message=" . urlencode("Keranjang belanja Anda kosong."));
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = $_SESSION['cart']['products'];
$user_addresses = [];
$default_address_id = null;
$total_cart_price = 0;

// Ambil semua alamat tersimpan milik pengguna
$stmt_addresses = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, label ASC");
$stmt_addresses->bind_param("i", $user_id);
$stmt_addresses->execute();
$result_addresses = $stmt_addresses->get_result();
while ($row = $result_addresses->fetch_assoc()) {
    $user_addresses[] = $row;
    if ($row['is_default']) {
        $default_address_id = $row['address_id'];
    }
}
$stmt_addresses->close();
?>

<div class="container page-container checkout-page">
    <h1 class="page-title">Checkout Pesanan Anda</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="message error"><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></div>
    <?php endif; ?>
    
    <div class="checkout-layout">
        <div class="checkout-summary card-style">
            <h2>Ringkasan Pesanan</h2>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <?php
                        $subtotal = $item['price'] * $item['quantity'];
                        $total_cart_price += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td class="text-right">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Total Pembayaran</strong></td>
                        <td class="text-right"><strong>Rp <?php echo number_format($total_cart_price, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="checkout-form-block card-style">
            <h2>Informasi Pengiriman & Pembayaran</h2>
            <form id="checkoutForm" action="<?php echo BASE_URL; ?>process_order.php" method="POST" class="user-form">
                <input type="hidden" name="total_amount" value="<?php echo $total_cart_price; ?>">
                <input type="hidden" name="shipping_address" id="final_shipping_address">

                <div class="form-group">
                    <label for="address_selection">Pilih Alamat Tersimpan:</label>
                    <select id="address_selection" class="form-control">
                        <option value="new_address">-- Gunakan Alamat Baru --</option>
                        <?php foreach ($user_addresses as $addr): ?>
                            <option value='<?php echo json_encode($addr); ?>' <?php echo ($addr['address_id'] == $default_address_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($addr['label'] ?: $addr['recipient_name']) . ($addr['is_default'] ? ' (Default)' : ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="new_address_form_fields">
                    <h4>Detail Alamat Pengiriman:</h4>
                    <div class="form-group"><label for="recipient_name">Nama Penerima:</label><input type="text" id="recipient_name" class="address-input"></div>
                    <div class="form-group"><label for="phone_number">Nomor Telepon:</label><input type="text" id="phone_number" class="address-input"></div>
                    <div class="form-group"><label for="street_address">Nama Jalan:</label><input type="text" id="street_address" class="address-input"></div>
                    <div class="form-group"><label for="house_number">Nomor Rumah:</label><input type="text" id="house_number" class="address-input"></div>
                    <div class="form-group"><label for="neighborhood">Kelurahan/Desa (RT/RW):</label><input type="text" id="neighborhood" class="address-input"></div>
                    <div class="form-group"><label for="sub_district">Kecamatan:</label><input type="text" id="sub_district" class="address-input"></div>
                    <div class="form-group"><label for="city_regency">Kabupaten/Kota:</label><input type="text" id="city_regency" class="address-input"></div>
                    <div class="form-group"><label for="province">Provinsi:</label><input type="text" id="province" class="address-input"></div>
                    <div class="form-group"><label for="postal_code">Kode Pos:</label><input type="text" id="postal_code" class="address-input"></div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label for="payment_method">Metode Pembayaran:</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">-- Pilih Metode Pembayaran --</option>
                        <option value="Bank Transfer">Transfer Bank</option>
                        <option value="Cash on Delivery">Cash on Delivery (COD)</option>
                    </select>
                </div>

                <button type="submit" name="place_order" class="btn-submit-user-form">Tempatkan Pesanan</button>
            </form>
        </div>
    </div>
</div>

<?php 
require_once 'php/partials/footer.php'; 
if (isset($conn)) { $conn->close(); }
?>