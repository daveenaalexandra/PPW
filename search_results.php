<?php
// Ambil kata kunci pencarian dari URL, pastikan aman dari XSS
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$safe_search_query = htmlspecialchars($search_query);

$page_title = "Hasil Pencarian untuk \"$safe_search_query\"";
require_once 'php/partials/header.php';

$products_found = [];
$activities_found = [];

// Lakukan pencarian hanya jika query tidak kosong
if (!empty($search_query)) {
    // Persiapkan term pencarian untuk query LIKE agar bisa mencari kata di mana saja
    $search_term = "%" . $search_query . "%";

    // 1. Cari di tabel 'products' berdasarkan nama atau deskripsi
    $stmt_products = $conn->prepare("SELECT * FROM products WHERE product_name LIKE ? OR description LIKE ?");
    $stmt_products->bind_param("ss", $search_term, $search_term);
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();
    while ($row = $result_products->fetch_assoc()) {
        $products_found[] = $row;
    }
    $stmt_products->close();

    // 2. Cari di tabel 'activities' berdasarkan judul atau deskripsi
    $stmt_activities = $conn->prepare("SELECT * FROM activities WHERE title LIKE ? OR description LIKE ?");
    $stmt_activities->bind_param("ss", $search_term, $search_term);
    $stmt_activities->execute();
    $result_activities = $stmt_activities->get_result();
    while ($row = $result_activities->fetch_assoc()) {
        $activities_found[] = $row;
    }
    $stmt_activities->close();
}
?>

<div class="container page-container">
    <h1 class="page-title">Hasil Pencarian untuk: <span>"<?php echo $safe_search_query; ?>"</span></h1>

    <?php if (empty($products_found) && empty($activities_found)): ?>
        <div class="message info">
            <p>Maaf, tidak ada hasil yang ditemukan untuk pencarian Anda. Silakan coba dengan kata kunci lain.</p>
        </div>
    <?php else: ?>

        <?php if (!empty($products_found)): ?>
            <h3 class="results-section-header">Produk yang Ditemukan <span>(<?php echo count($products_found); ?>)</span></h3>
            <div class="search-results-grid">
                <?php foreach ($products_found as $product): ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="product-card-link">
                            <img src="<?php echo BASE_URL . ($product['image_url'] ?? 'images/default-product.jpg'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-card-image">
                            <div class="product-card-content">
                                <h5 class="product-card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                <p class="product-item-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                <span class="btn-view-detail-small">Lihat Detail</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($activities_found)): ?>
            <h3 class="results-section-header">Kegiatan yang Ditemukan <span>(<?php echo count($activities_found); ?>)</span></h3>
            <div class="search-results-grid">
                <?php foreach ($activities_found as $activity): ?>
                    <div class="activity-card">
                        <a href="activity_detail.php?id=<?php echo $activity['activity_id']; ?>" class="activity-card-link">
                            <img src="<?php echo BASE_URL . ($activity['image_url'] ?? 'images/default-activity.jpg'); ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>" class="activity-card-image">
                            <div class="activity-card-content">
                                <h5 class="activity-card-title"><?php echo htmlspecialchars($activity['title']); ?></h5>
                                <p class="activity-card-info">
                                    <strong>Tanggal:</strong> <?php echo date("d M Y", strtotime($activity['activity_date_start'])); ?>
                                </p>
                                <span class="btn-view-detail-small">Lihat Detail</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php
require_once 'php/partials/footer.php';
?>