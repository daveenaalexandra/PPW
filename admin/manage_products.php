<?php
require_once 'admin_check.php';

// --- LOGIKA UNTUK UPDATE STOK ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $product_id_to_update = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $new_stock = filter_input(INPUT_POST, 'new_stock', FILTER_VALIDATE_INT);

    if ($product_id_to_update && $new_stock >= 0) {
        $stmt_update = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
        $stmt_update->bind_param("ii", $new_stock, $product_id_to_update);
        if ($stmt_update->execute()) {
            $update_message = "Stok untuk produk #$product_id_to_update berhasil diperbarui menjadi $new_stock.";
        } else {
            $update_message = "Gagal memperbarui stok.";
        }
        $stmt_update->close();
    }
}

// Mengambil semua data produk
$products_result = $conn->query("SELECT * FROM products ORDER BY product_name ASC");

$page_title = "Kelola Produk";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container page-container">
    <h1 class="page-title">Kelola Produk dan Stok</h1>

    <?php if (isset($update_message)): ?>
        <div class="alert alert-success"><?php echo $update_message; ?></div>
    <?php endif; ?>

    <div class="table-responsive mt-4">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Produk</th>
                    <th>Harga</th>
                    <th>Stok Saat Ini</th>
                    <th>Ubah Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php while($product = $products_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['product_id']; ?></td>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                    <td><strong><?php echo $product['stock_quantity']; ?></strong></td>
                    <td>
                        <form action="manage_products.php" method="POST" class="form-inline">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="number" name="new_stock" class="form-control form-control-sm mr-2" style="width: 80px;" value="<?php echo $product['stock_quantity']; ?>">
                            <button type="submit" name="update_stock" class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../php/partials/footer.php';
?>