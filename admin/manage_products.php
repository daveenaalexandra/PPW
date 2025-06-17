<?php
require_once 'admin_check.php';

// --- LOGIKA UNTUK PROSES AKSI ADMIN ---

// 1. Logika untuk TAMBAH PRODUK BARU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $activity_id = !empty($_POST['activity_id']) ? filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT) : null;
    $image_path_for_db = null;

    // --- Proses Upload Gambar ---
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/products/';
        $file_info = getimagesize($_FILES['product_image']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if ($file_info && in_array($file_info['mime'], $allowed_types)) {
            if ($_FILES['product_image']['size'] < 2000000) {
                $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                $unique_filename = 'prod_' . uniqid() . '.' . $file_extension;
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $unique_filename)) {
                    $image_path_for_db = 'uploads/products/' . $unique_filename;
                } else { $message = "Gagal memindahkan file."; $message_type = "danger"; }
            } else { $message = "Ukuran file terlalu besar (maks 2MB)."; $message_type = "warning"; }
        } else { $message = "Tipe file tidak valid."; $message_type = "warning"; }
    }

    if (!isset($message) && !empty($product_name)) {
        $stmt_insert = $conn->prepare("INSERT INTO products (product_name, description, price, stock_quantity, is_featured, image_url, activity_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("ssdiisi", $product_name, $description, $price, $stock_quantity, $is_featured, $image_path_for_db, $activity_id);
        if ($stmt_insert->execute()) {
            $message = "Produk baru berhasil ditambahkan."; $message_type = "success";
        } else { $message = "Gagal menambahkan produk: " . $stmt_insert->error; $message_type = "danger"; }
        $stmt_insert->close();
    }
}

// 2. Logika untuk HAPUS PRODUK
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    $product_id_to_delete = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    if ($product_id_to_delete) {
        // PERINGATAN: Pastikan relasi di database Anda (foreign key) sudah diatur dengan benar!
        // Jika produk terkait dengan order_items, penghapusan bisa gagal jika tidak ada ON DELETE SET NULL/CASCADE.
        $stmt_delete = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt_delete->bind_param("i", $product_id_to_delete);
        if ($stmt_delete->execute()) {
            $message = "Produk berhasil dihapus."; $message_type = "success";
        } else { $message = "Gagal menghapus produk. Kemungkinan produk ini terkait dengan data pesanan yang sudah ada."; $message_type = "danger"; }
        $stmt_delete->close();
    }
}


// 3. Logika untuk UPDATE STOK (tetap ada)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $product_id_to_update = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $new_stock = filter_input(INPUT_POST, 'new_stock', FILTER_VALIDATE_INT);

    if ($product_id_to_update && $new_stock >= 0) {
        $stmt_update = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
        $stmt_update->bind_param("ii", $new_stock, $product_id_to_update);
        if ($stmt_update->execute()) {
            $message = "Stok berhasil diperbarui."; $message_type = "success";
        } else { $message = "Gagal memperbarui stok."; $message_type = "danger"; }
        $stmt_update->close();
    }
}

// Mengambil data produk dan kegiatan untuk dropdown
$products_result = $conn->query("SELECT * FROM products ORDER BY product_name ASC");
$activities_for_dropdown = $conn->query("SELECT activity_id, title FROM activities ORDER BY title ASC");

$page_title = "Kelola Produk";
require_once __DIR__ . '/../php/partials/header.php';
?>
<div class="container page-container">
    <a href="admin_dashboard.php" class="btn-back-to-dashboard"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    <h1 class="page-title">Kelola Produk</h1>

    <?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card my-4">
        <div class="card-header"><h4>Tambah Produk Baru</h4></div>
        <div class="card-body">
            <form action="manage_products.php" method="POST" enctype="multipart/form-data">
                <div class="form-group mb-2"><label for="product_name">Nama Produk</label><input type="text" id="product_name" name="product_name" class="form-control" required></div>
                <div class="form-group mb-2"><label for="description">Deskripsi</label><textarea id="description" name="description" class="form-control" rows="3"></textarea></div>
                <div class="row">
                    <div class="col-md-6 form-group mb-2"><label for="price">Harga (Rp)</label><input type="number" step="0.01" id="price" name="price" class="form-control" value="0.00" required></div>
                    <div class="col-md-6 form-group mb-2"><label for="stock_quantity">Jumlah Stok</label><input type="number" id="stock_quantity" name="stock_quantity" class="form-control" value="0" required></div>
                </div>
                <div class="form-group mb-2">
                    <label for="activity_id">Kaitkan dengan Kegiatan (Opsional)</label>
                    <select id="activity_id" name="activity_id" class="form-control">
                        <option value="">-- Tidak Dikaitkan --</option>
                        <?php while($activity = $activities_for_dropdown->fetch_assoc()): ?>
                            <option value="<?php echo $activity['activity_id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                 <div class="form-group mb-2"><label for="product_image">Gambar Produk (Opsional, Maks 2MB)</label><input type="file" id="product_image" name="product_image" class="form-control"></div>
                <div class="form-check mb-3"><input type="checkbox" id="is_featured" name="is_featured" value="1" class="form-check-input"><label class="form-check-label" for="is_featured">Jadikan Produk Unggulan</label></div>
                <button type="submit" name="add_product" class="btn btn-success">Tambah Produk</button>
            </form>
        </div>
    </div>

    <div class="card my-4">
        <div class="card-header"><h4>Daftar Produk yang Ada</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                     <thead class="thead-dark">
                        <tr><th>ID</th><th>Nama Produk</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $product['product_id']; ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td>
                                <form action="manage_products.php" method="POST" class="form-inline d-inline-flex">
                                    <input type="number" name="new_stock" class="form-control form-control-sm" style="width: 80px;" value="<?php echo $product['stock_quantity']; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" name="update_stock" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </td>
                            <td>
                                <form action="manage_products.php" method="POST" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus produk ini secara permanen?');">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../php/partials/footer.php'; ?>