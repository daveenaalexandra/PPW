<?php
// File: process_order.php (Versi Modifikasi - Hanya Proses Produk)
session_start();

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/php/db_config.php';
}

$redirect_url_success = BASE_URL . "order_confirmation.php";
$redirect_url_failure = BASE_URL . "checkout.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php?redirect=" . urlencode(BASE_URL . 'checkout.php'));
        exit();
    }

    // ===== PERUBAHAN 1: Cek keranjang dengan struktur baru =====
    if (empty($_SESSION['cart']['products']) && empty($_SESSION['cart']['activities'])) {
        header("Location: " . $redirect_url_failure . "?status=error&message=" . urlencode("Keranjang belanja Anda kosong."));
        exit();
    }

    $userId = $_SESSION['user_id'];
    $totalAmount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $shippingAddress = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_STRING);
    $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    
    // ===== PERUBAHAN 2: Ambil HANYA produk dari keranjang =====
    $cart_products = $_SESSION['cart']['products'] ?? [];
    $cart_activities = $_SESSION['cart']['activities'] ?? [];

    if (!$totalAmount || !$shippingAddress || !$paymentMethod) {
        header("Location: " . $redirect_url_failure . "?status=error&message=" . urlencode("Data pesanan tidak lengkap."));
        exit();
    }

    $conn->begin_transaction();

    try {
        // 1. Masukkan data ke tabel 'orders'
        // (Tidak ada perubahan di blok ini)
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, 'pending')");
        if (!$stmt_order) {
            throw new Exception("Error preparing order statement: " . $conn->error);
        }
        $stmt_order->bind_param("idss", $userId, $totalAmount, $shippingAddress, $paymentMethod);
        if (!$stmt_order->execute()) {
            throw new Exception("Error executing order statement: " . $stmt_order->error);
        }
        $orderId = $conn->insert_id;
        $stmt_order->close();

        // 2. Masukkan item produk ke 'order_items' dan kurangi stok
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price_per_item) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");

        if (!$stmt_item || !$stmt_stock) {
            throw new Exception("Error preparing item/stock statements: " . $conn->error);
        }
        
        // ===== PERUBAHAN 3: Loop hanya pada item produk =====
        if (!empty($cart_products)) {
            foreach ($cart_products as $productId => $item) {
                // (Tidak ada perubahan di dalam logika loop ini, karena ini memang untuk produk)
                $check_stock_stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
                $check_stock_stmt->bind_param("i", $productId);
                $check_stock_stmt->execute();
                $result_stock = $check_stock_stmt->get_result()->fetch_assoc();
                $current_stock = $result_stock['stock_quantity'];
                $check_stock_stmt->close();

                if ($current_stock < $item['quantity']) {
                    throw new Exception("Stok tidak mencukupi untuk produk " . htmlspecialchars($item['product_name']) . ". Hanya tersedia " . $current_stock . ".");
                }

                $stmt_item->bind_param("iisid", $orderId, $item['product_id'], $item['product_name'], $item['quantity'], $item['price']);
                if (!$stmt_item->execute()) {
                    throw new Exception("Error inserting order item: " . $stmt_item->error);
                }

                $stmt_stock->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                if (!$stmt_stock->execute() || $stmt_stock->affected_rows === 0) {
                     throw new Exception("Gagal mengurangi stok untuk " . htmlspecialchars($item['product_name']) . ".");
                }
            }
        }
        $stmt_item->close();
        $stmt_stock->close();
        if (!empty($cart_activities)) {
            $stmt_reg = $conn->prepare("INSERT INTO event_registrations (activity_id, user_id, status) VALUES (?, ?, 'confirmed')");
            foreach ($cart_activities as $item) {
                $stmt_reg->bind_param("ii", $item['activity_id'], $userId);
                $stmt_reg->execute();
            }
            $stmt_reg->close();
        }
        // (Di sini nanti kita akan tambahkan logika untuk memproses kegiatan)

        $conn->commit();

        // ===== PERUBAHAN 4: Hapus HANYA produk dari keranjang =====
        unset($_SESSION['cart']);
        
        // Simpan order_id untuk halaman konfirmasi
        $_SESSION['last_order_id'] = $orderId; 

        header("Location: " . $redirect_url_success . "?order_id=" . $orderId);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Order processing error: " . $e->getMessage());
        header("Location: " . $redirect_url_failure . "?status=error&message=" . urlencode("Terjadi kesalahan saat memproses pesanan: " . $e->getMessage()));
        exit();
    }

} else {
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>