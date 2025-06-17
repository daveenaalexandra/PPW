<?php
require_once 'admin_check.php';

// --- Ambil data untuk statistik widget ---

// 1. Hitung total pengguna (member)
$total_users_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'member'");
$total_users = $total_users_result->fetch_assoc()['total'];

// 2. Hitung pesanan yang masih pending
$pending_orders_result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $pending_orders_result->fetch_assoc()['total'];

// 3. Hitung jumlah kegiatan yang memiliki peserta
$active_activities_result = $conn->query("SELECT COUNT(DISTINCT activity_id) as total FROM event_registrations");
$active_activities_count = $active_activities_result->fetch_assoc()['total'];

// 4. Hitung total produk yang tersedia
$total_products_result = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $total_products_result->fetch_assoc()['total'];


$page_title = "Admin Dashboard";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container page-container">
    <h1 class="page-title">Dashboard</h1>
    <p class="text-center" style="color: rgba(255, 255, 255, 0.7);">Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></strong>!</p>

    <div class="row mt-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="manage_users.php" class="widget-link">
                <div class="admin-widget card-users">
                    <div class="widget-inner">
                        <div class="widget-icon"><i class="fas fa-users fa-3x"></i></div>
                        <div class="widget-text">
                            <div class="widget-number"><?php echo $total_users; ?></div>
                            <div class="widget-label">Kelola Pengguna</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="manage_orders.php" class="widget-link">
                <div class="admin-widget card-orders">
                    <div class="widget-inner">
                        <div class="widget-icon"><i class="fas fa-box fa-3x"></i></div>
                        <div class="widget-text">
                            <div class="widget-number"><?php echo $pending_orders; ?></div>
                            <div class="widget-label">Kelola Pesanan</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="manage_activities.php" class="widget-link">
                <div class="admin-widget card-activities">
                    <div class="widget-inner">
                        <div class="widget-icon"><i class="fas fa-calendar-check fa-3x"></i></div>
                        <div class="widget-text">
                            <div class="widget-number"><?php echo $active_activities_count; ?></div>
                            <div class="widget-label">Kelola Kegiatan</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="manage_products.php" class="widget-link">
                <div class="admin-widget card-products">
                     <div class="widget-inner">
                        <div class="widget-icon"><i class="fas fa-cookie-bite fa-3x"></i></div>
                        <div class="widget-text">
                            <div class="widget-number"><?php echo $total_products; ?></div>
                            <div class="widget-label">Kelola Produk</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../php/partials/footer.php';
?>