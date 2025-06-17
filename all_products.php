<?php
// File: all_products.php (Versi dengan Pagination Bootstrap)

// ========================================================================
// --- 1. INISIALISASI & PENGAMBILAN DATA ---
// ========================================================================

$page_title = "Semua Produk Kami - Baking Lovers";
require_once 'php/partials/header.php'; // Memuat header, koneksi DB, dan memulai sesi

$all_products = [];
$error_message = '';
$total_pages = 0; // Inisialisasi variabel total halaman
$current_page = 1; // Inisialisasi variabel halaman saat ini

if (isset($conn) && $conn instanceof mysqli) {
    // --- PENAMBAHAN BAGIAN A: TENTUKAN VARIABEL PAGINATION ---
    $items_per_page = 4; // Tampilkan 8 produk per halaman. Anda bisa ubah angka ini.
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;

    // --- PENAMBAHAN BAGIAN B: HITUNG TOTAL ITEM & TOTAL HALAMAN ---
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM products");
    if($count_stmt) {
        $count_stmt->execute();
        $total_items = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $count_stmt->close();
        $total_pages = ceil($total_items / $items_per_page);
    } else {
        error_log("Error counting products: " . $conn->error);
    }

    // --- PENAMBAHAN BAGIAN C: HITUNG OFFSET UNTUK QUERY SQL ---
    $offset = ($current_page - 1) * $items_per_page;


    // --- PERUBAHAN BAGIAN D: MODIFIKASI QUERY UTAMA DENGAN LIMIT DAN OFFSET ---
    $stmt_products = $conn->prepare(
        "SELECT product_id, product_name, image_url, price, description, activity_id
         FROM products
         ORDER BY is_featured DESC, created_at DESC
         LIMIT ? OFFSET ?" // Query diubah untuk mengambil data per halaman
    );

    if ($stmt_products) {
        $stmt_products->bind_param("ii", $items_per_page, $offset); // Bind parameter baru
        $stmt_products->execute();
        $result_products = $stmt_products->get_result();

        if ($result_products) {
            while ($row = $result_products->fetch_assoc()) {
                $all_products[] = $row;
            }
        }
        $stmt_products->close();
    } else {
        error_log("Error preparing statement for all_products: " . $conn->error);
        $error_message = "Terjadi kesalahan saat mengambil data produk.";
    }
} else {
    $error_message = "Koneksi database tidak tersedia.";
}

?>

<div class="container page-container">
    <h1 class="page-title">Semua Produk Kami</h1>
    
    <?php if($total_pages > 0): ?>
    <p class="page-subtitle">Menampilkan halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?> halaman.</p>
    <?php endif; ?>


    <div class="all-products-list-container">
        <?php if (!empty($error_message)): ?>
            <p class="message error"><?php echo $error_message; ?></p>
        <?php elseif (!empty($all_products)): ?>
            <div class="product-grid all-items-view">
                <?php foreach ($all_products as $product): ?>
                    <?php
                        // Logika untuk menyiapkan variabel (tidak ada perubahan di sini)
                        $product_image = (!empty($product['image_url'])) ? BASE_URL . htmlspecialchars($product['image_url']) : BASE_URL . 'images/default-product.jpg';
                        $product_name = htmlspecialchars($product['product_name']);
                        $product_price_info = ($product['price'] > 0) ? "Rp " . number_format($product['price'], 0, ',', '.') : "Harga Spesial";
                        $product_short_desc = !empty($product['description']) ? htmlspecialchars(substr($product['description'], 0, 80)) . '...' : 'Lihat detail untuk info lebih lanjut.';
                        $product_detail_url = BASE_URL . 'product_detail.php?id=' . $product['product_id'];
                    ?>
                    <div class="product-card"> <a href="<?php echo $product_detail_url; ?>" class="product-card-link">
                            <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>" class="product-card-image">
                            <div class="product-card-content">
                                <h5 class="product-card-title"><?php echo $product_name; ?></h5>
                                <p class="product-item-price"><?php echo $product_price_info; ?></p>
                                <p class="product-card-desc"><?php echo $product_short_desc; ?></p>
                                <span class="btn-view-detail-small">Lihat Detail</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="message info">Belum ada produk yang tersedia saat ini. Silakan cek kembali nanti.</p>
        <?php endif; ?>
    </div>
    
    <div class="pagination-container mt-5">
        <?php
        // Hanya tampilkan navigasi jika ada lebih dari 1 halaman
        if ($total_pages > 1) {
            echo '<nav aria-label="Page navigation">';
            echo '  <ul class="pagination justify-content-center">';

            // Tombol "Previous"
            $prev_class = ($current_page <= 1) ? "disabled" : ""; // Nonaktifkan jika di halaman pertama
            echo '<li class="page-item ' . $prev_class . '">';
            echo '  <a class="page-link" href="?page=' . ($current_page - 1) . '" aria-label="Previous">';
            echo '    <span aria-hidden="true">&laquo;</span>';
            echo '  </a>';
            echo '</li>';

            // Link Halaman Angka
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i == $current_page) ? "active" : ""; // Beri class 'active' untuk halaman saat ini
                echo '<li class="page-item ' . $active_class . '">';
                echo '  <a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                echo '</li>';
            }

            // Tombol "Next"
            $next_class = ($current_page >= $total_pages) ? "disabled" : ""; // Nonaktifkan jika di halaman terakhir
            echo '<li class="page-item ' . $next_class . '">';
            echo '  <a class="page-link" href="?page=' . ($current_page + 1) . '" aria-label="Next">';
            echo '    <span aria-hidden="true">&raquo;</span>';
            echo '  </a>';
            echo '</li>';

            echo '  </ul>';
            echo '</nav>';
        }
        ?>
    </div>

    <p style="margin-top: 30px; text-align:center;">
        <a href="<?php echo BASE_URL; ?>index.php#product" class="btn-link">&larr; Kembali ke Halaman Utama</a>
    </p>
</div>

<?php 
require_once 'php/partials/footer.php'; 
// Tidak perlu menutup koneksi di sini karena sudah ditangani oleh footer.php
?>