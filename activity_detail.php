<?php
// File: activity_detail.php (Versi Final Rapi)

// ========================================================================
// --- 1. INISIALISASI & VALIDASI ---
// ========================================================================

$page_title = "Detail Kegiatan - Baking Lovers";
require_once 'php/partials/header.php';

// Validasi ID kegiatan dari URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT) || $_GET['id'] <= 0) {
    echo "<div class='container page-container'><p class='message error'>ID Kegiatan tidak valid atau tidak ditemukan.</p></div>";
    require_once 'php/partials/footer.php';
    exit();
}
$activity_id = (int)$_GET['id'];

// ========================================================================
// --- 2. PENGAMBILAN DATA DARI DATABASE ---
// ========================================================================

$activity = null;
$related_products = [];

if (isset($conn) && $conn instanceof mysqli) {
    // Ambil detail kegiatan utama
    $stmt_activity = $conn->prepare(
        "SELECT a.activity_id, a.title, a.description, a.activity_type, a.activity_date_start, a.activity_date_end,
                a.location, a.image_url, a.max_participants, a.price, u.full_name AS organizer_name
         FROM activities a
         LEFT JOIN users u ON a.organizer_id = u.user_id
         WHERE a.activity_id = ?"
    );
    $stmt_activity->bind_param("i", $activity_id);
    $stmt_activity->execute();
    $activity = $stmt_activity->get_result()->fetch_assoc();
    $stmt_activity->close();

    if ($activity) {
        // Ambil produk yang terkait dengan kegiatan ini
        $stmt_related_products = $conn->prepare(
            "SELECT product_id, product_name, price, image_url FROM products WHERE activity_id = ? ORDER BY product_name ASC LIMIT 3"
        );
        $stmt_related_products->bind_param("i", $activity_id);
        $stmt_related_products->execute();
        $result_related_products = $stmt_related_products->get_result();
        if ($result_related_products) {
            while ($prod_row = $result_related_products->fetch_assoc()) {
                $related_products[] = $prod_row;
            }
        }
        $stmt_related_products->close();
    }
}

// Jika kegiatan tidak ditemukan, hentikan eksekusi.
if (!$activity) {
    echo "<div class='container page-container'><p class='message error'>Kegiatan dengan ID tersebut tidak ditemukan.</p></div>";
    require_once 'php/partials/footer.php';
    if (isset($conn)) { $conn->close(); }
    exit();
}

// Ambil data lain yang dibutuhkan
$available_slots_raw = $conn->query("SELECT GetAvailableSlots(" . $activity_id . ") AS slots")->fetch_assoc()['slots'] ?? 0;
$available_slots = ($available_slots_raw == 999999) ? PHP_INT_MAX : $available_slots_raw;

$user_is_registered = false;
$user_registration_status = '';
if (isset($_SESSION['user_id'])) {
    $stmt_check_reg = $conn->prepare("SELECT status FROM event_registrations WHERE activity_id = ? AND user_id = ?");
    $stmt_check_reg->bind_param("ii", $activity_id, $_SESSION['user_id']);
    $stmt_check_reg->execute();
    if ($reg_data = $stmt_check_reg->get_result()->fetch_assoc()) {
        $user_is_registered = true;
        $user_registration_status = $reg_data['status'];
    }
    $stmt_check_reg->close();
}

// ========================================================================
// --- 3. PERSIAPAN LOGIKA & VARIABEL UNTUK TAMPILAN ---
// ========================================================================

$page_title = htmlspecialchars($activity['title']) . " | Baking Lovers";
$is_logged_in = isset($_SESSION['user_id']);
$event_has_passed = strtotime($activity['activity_date_start']) <= time();
$slots_are_available = ($activity['max_participants'] == 0 || $available_slots > 0);
$can_register = $is_logged_in && !$user_is_registered && $slots_are_available && !$event_has_passed;

?>

<div class="container page-container activity-detail-page">

    <div class="activity-detail-header-block">
        <?php if (!empty($activity['image_url'])): ?>
            <img src="<?php echo BASE_URL . htmlspecialchars($activity['image_url']); ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>" class="activity-header-image">
        <?php endif; ?>
        <h1 class="activity-header-title"><?php echo htmlspecialchars($activity['title']); ?></h1>
        <p class="activity-header-organizer">Tipe: <?php echo htmlspecialchars($activity['activity_type']); ?> | Diselenggarakan oleh: <?php echo htmlspecialchars($activity['organizer_name'] ?: 'Komunitas Baking Lovers'); ?></p>
    </div>

    <div class="activity-detail-content-block">
        <p><strong>Tanggal & Waktu:</strong>
            <?php
                echo date("l, d F Y, H:i", strtotime($activity['activity_date_start']));
                if ($activity['activity_date_end'] && date("Y-m-d", strtotime($activity['activity_date_start'])) == date("Y-m-d", strtotime($activity['activity_date_end']))) {
                    echo " - " . date("H:i", strtotime($activity['activity_date_end']));
                } elseif ($activity['activity_date_end']) {
                    echo " s/d " . date("l, d F Y, H:i", strtotime($activity['activity_date_end']));
                }
            ?>
        </p>
        <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($activity['location']); ?></p>
        <p><strong>Biaya Pendaftaran:</strong>
            <span class="activity-price">
                <?php 
                if ($activity['price'] > 0) {
                    echo "Rp " . number_format($activity['price'], 0, ',', '.');
                } else {
                    echo "Gratis";
                }
                ?>
            </span>
        </p>
        <p><strong>Deskripsi Lengkap:</strong></p>
        <div class="activity-description-text">
            <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
        </div>
        <hr class="detail-divider">
        <p><strong>Ketersediaan Slot/Peserta:</strong>
            <?php
            if ($available_slots === PHP_INT_MAX) {
                echo "Tidak terbatas";
            } else {
                echo $slots_are_available ? $available_slots . " slot tersedia" : "Penuh";
            }
            ?>
            (dari total <?php echo $activity['max_participants'] > 0 ? $activity['max_participants'] : 'N/A'; ?>)
        </p>

        <div class="registration-action-box">
             <?php if ($can_register): ?>
                <?php if ($activity['price'] > 0): ?>
                    <button type="button" class="btn-submit-user-form btn-register-activity" id="btn-add-activity-to-cart"
                            data-activity-id="<?php echo $activity['activity_id']; ?>">
                        Daftar & Bayar (Rp <?php echo number_format($activity['price'], 0, ',', '.'); ?>)
                    </button>
                    <div id="add-to-cart-message" class="message" style="display:none; margin-top: 15px;"></div>
                <?php else: ?>
                    <form action="<?php echo BASE_URL; ?>process_registration.php" method="POST" class="registration-form">
                        <input type="hidden" name="activity_id" value="<?php echo $activity['activity_id']; ?>">
                        <button type="submit" name="register_activity" class="btn-submit-user-form btn-register-activity">Daftar (Gratis)</button>
                    </form>
                <?php endif; ?>
            <?php elseif ($user_is_registered): ?>
                <p class="message info">Anda sudah terdaftar pada kegiatan ini dengan status: <strong><?php echo htmlspecialchars(ucfirst($user_registration_status)); ?></strong>.</p>
            <?php elseif ($event_has_passed): ?>
                 <p class="message info">Pendaftaran untuk kegiatan ini sudah ditutup (kegiatan sudah berlalu).</p>
            <?php elseif (!$slots_are_available): ?>
                <p class="message error">Maaf, tidak ada slot tersedia untuk kegiatan ini.</p>
            <?php elseif (!$is_logged_in): ?>
                <p class="message info">Silakan <a href="<?php echo BASE_URL; ?>login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Login</a> atau <a href="<?php echo BASE_URL; ?>register.php">Register</a> untuk mendaftar.</p>
            <?php endif; ?>
        </div>
    </div> <<?php if (!empty($related_products)): ?>                
                <h2 class="section-title" style="margin-top: 50px;">Produk Terkait</h2>
                <div class="related-products-section card-style">
                    <div class="product-grid related-items-grid">
                        <?php foreach ($related_products as $prod): ?>
                            <div class="product-card category-item">
                                <img src="<?php echo (!empty($prod['image_url'])) ? BASE_URL . htmlspecialchars($prod['image_url']) : BASE_URL . 'images/default-product.jpg'; ?>" alt="<?php echo htmlspecialchars($prod['product_name']); ?>" class="product-card-image">
                                <div class="category-item-content">
                                    <h6 class="category-item-title"><?php echo htmlspecialchars($prod['product_name']); ?></h6>
                                    <p class="product-price"><strong>Harga:</strong> Rp <?php echo number_format($prod['price'], 0, ',', '.'); ?></p>
                                    <a href="<?php echo BASE_URL . 'product_detail.php?id=' . $prod['product_id']; ?>" class="btn-view-detail-small">Lihat Produk</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
    <?php endif; ?>

    <p style="margin-top: 30px; text-align:left;">
        <a href="<?php echo BASE_URL; ?>index.php#activities" class="btn-link">&larr; Kembali ke Halaman Utama</a>
    </p>
</div>

<?php 
require_once 'php/partials/footer.php';
if (isset($conn)) { $conn->close(); }
?>