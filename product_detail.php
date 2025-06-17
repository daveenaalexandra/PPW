<?php
// File: product_detail.php (Versi Rapi)

// ========================================================================
// --- 1. INISIALISASI & PENGAMBILAN DATA ---
// ========================================================================

$page_title_default = "Detail Produk - Baking Lovers";
require_once 'php/partials/header.php'; // Memuat header, koneksi DB, dan memulai sesi

// Validasi ID produk dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || $_GET['id'] <= 0) {
    echo "<div class='container page-container'><p class='message error'>ID Produk tidak valid atau tidak ditemukan.</p></div>";
    require_once 'php/partials/footer.php';
    exit();
}
$product_id = (int)$_GET['id'];
$product = null;

if (isset($conn) && $conn instanceof mysqli) {
    // Ambil detail produk dari tabel products, gabungkan dengan judul kegiatan jika ada
    $stmt_product = $conn->prepare(
        "SELECT p.product_id, p.product_name, p.description, p.price, p.image_url, p.stock_quantity, p.activity_id, a.title AS related_activity_title
         FROM products p
         LEFT JOIN activities a ON p.activity_id = a.activity_id
         WHERE p.product_id = ?"
    );
    if ($stmt_product) {
        $stmt_product->bind_param("i", $product_id);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();
        $product = $result_product->fetch_assoc();
        $stmt_product->close();
    }
}

// Jika produk tidak ditemukan, tampilkan pesan dan hentikan eksekusi
if (!$product) {
    echo "<div class='container page-container'><p class='message error'>Produk dengan ID tersebut tidak ditemukan.</p></div>";
    require_once 'php/partials/footer.php';
    if (isset($conn)) { $conn->close(); }
    exit();
}

// Set judul halaman dinamis setelah mendapatkan data produk
$page_title = htmlspecialchars($product['product_name']) . " | Baking Lovers";
?>

<div class="container page-container product-detail-page">
    <div class="product-detail-layout">

        <div class="product-image-container">
            <?php if (!empty($product['image_url'])): ?>
                <img src="<?php echo BASE_URL . htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-detail-main-image">
            <?php else: ?>
                 <img src="<?php echo BASE_URL; ?>images/default-product.jpg" alt="Gambar tidak tersedia" class="product-detail-main-image">
            <?php endif; ?>
        </div>

        <div class="product-info-container">
            <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>

            <?php if ($product['activity_id'] && $product['related_activity_title']): ?>
                <p class="related-activity-link">
                    Terkait kegiatan: <a href="<?php echo BASE_URL . 'activity_detail.php?id=' . $product['activity_id']; ?>"><?php echo htmlspecialchars($product['related_activity_title']); ?></a>
                </p>
            <?php endif; ?>

            <p class="product-price-detail">
                <?php echo ($product['price'] > 0) ? "Rp " . number_format($product['price'], 0, ',', '.') : "Hubungi kami untuk harga"; ?>
            </p>
            
            <?php if ($product['stock_quantity'] > 0): ?>
                <p class="product-stock-detail in-stock">Stok: <?php echo $product['stock_quantity']; ?> tersedia</p>
            <?php else: ?>
                <p class="product-stock-detail out-of-stock">Stok Habis</p>
            <?php endif; ?>

            <div class="product-description">
                <p><strong>Deskripsi Produk:</strong></p>
                <?php echo nl2br(htmlspecialchars($product['description'] ?: 'Tidak ada deskripsi untuk produk ini.')); ?>
            </div>

            <div class="product-action-box">
                <?php if ($product['stock_quantity'] > 0): ?>
                     <button type="button" class="btn-submit-user-form btn-add-to-cart"
                             data-product-id="<?php echo $product['product_id']; ?>"
                             data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                             data-product-price="<?php echo $product['price']; ?>">
                         <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                     </button>
                    <div id="add-to-cart-message" class="message" style="display:none; margin-top: 15px;"></div>
                <?php else: ?>
                    <p class="message info">Produk ini sedang tidak tersedia.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <p style="margin-top: 40px; text-align:left;">
        <a href="<?php echo BASE_URL; ?>all_products.php" class="btn-link">&larr; Kembali ke Semua Produk</a>
    </p>
</div>

<?php 
require_once 'php/partials/footer.php'; 

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>