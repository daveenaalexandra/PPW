<?php
// File: list_workshops.php (Versi dengan Pagination Bootstrap)

// ========================================================================
// --- 1. INISIALISASI & PENGAMBILAN DATA ---
// ========================================================================

$page_title = "Daftar Workshop - Baking Lovers";
require_once 'php/partials/header.php';

$list_activities = [];
$error_message = '';
$activity_type_filter = 'Workshop'; // Filter spesifik untuk halaman ini
$total_pages = 0; // Inisialisasi variabel total halaman
$current_page = 1; // Inisialisasi variabel halaman saat ini

if (isset($conn) && $conn instanceof mysqli) {

    // --- PENAMBAHAN BAGIAN A: TENTUKAN VARIABEL PAGINATION ---
    $items_per_page = 3; // Tampilkan 6 workshop per halaman. Anda bisa ubah angka ini.
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;

    // --- PENAMBAHAN BAGIAN B: HITUNG TOTAL ITEM & TOTAL HALAMAN ---
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM activities WHERE activity_type = ?");
    if($count_stmt) {
        $count_stmt->bind_param("s", $activity_type_filter);
        $count_stmt->execute();
        $total_items = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $count_stmt->close();
        $total_pages = ceil($total_items / $items_per_page);
    } else {
        error_log("Error counting activities for type '$activity_type_filter': " . $conn->error);
    }

    // --- PENAMBAHAN BAGIAN C: HITUNG OFFSET UNTUK QUERY SQL ---
    $offset = ($current_page - 1) * $items_per_page;


    // ========================================================================
    // --- 2. QUERY UTAMA DIMODIFIKASI DENGAN LIMIT & OFFSET ---
    // ========================================================================

    $stmt_activities_list = $conn->prepare(
        "SELECT activity_id, title, image_url, SUBSTRING(description, 1, 120) as short_desc, activity_date_start, location
         FROM activities
         WHERE activity_type = ?
         ORDER BY activity_date_start DESC
         LIMIT ? OFFSET ?" // Query diubah untuk mengambil data per halaman
    );

    if ($stmt_activities_list) {
        $stmt_activities_list->bind_param("sii", $activity_type_filter, $items_per_page, $offset); // Bind parameter baru
        $stmt_activities_list->execute();
        $result_activities_list = $stmt_activities_list->get_result();

        if ($result_activities_list) {
            while ($row = $result_activities_list->fetch_assoc()) {
                $list_activities[] = $row;
            }
        }
        $stmt_activities_list->close();
    } else {
        error_log("Error preparing statement for list_workshops: " . $conn->error);
        $error_message = "Terjadi kesalahan saat mengambil data kegiatan.";
    }
} else {
    $error_message = "Koneksi database tidak tersedia.";
}

?>

<div class="container page-container">
    <h1 class="page-title">Pilihan Workshop Kami</h1>

    <?php if($total_pages > 0): ?>
    <p class="page-subtitle">Menampilkan halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?> halaman.</p>
    <?php endif; ?>

    <div class="activity-list-container">
        <?php if (!empty($error_message)): ?>
            <p class="message error"><?php echo $error_message; ?></p>
        <?php elseif (!empty($list_activities)): ?>
            <div class="activities-grid">
                <?php foreach ($list_activities as $activity): ?>
                    <?php
                        // Logika untuk menyiapkan variabel (tidak ada perubahan di sini)
                        $default_image = BASE_URL . 'images/default-' . strtolower($activity_type_filter) . '.jpg';
                        $activity_image = (!empty($activity['image_url'])) ? BASE_URL . htmlspecialchars($activity['image_url']) : $default_image;
                        $activity_title = htmlspecialchars($activity['title']);
                        $activity_short_desc = htmlspecialchars($activity['short_desc']) . '...';
                        $activity_detail_url = BASE_URL . 'activity_detail.php?id=' . $activity['activity_id'];
                    ?>
                    <div class="activity-card">
                        <a href="<?php echo $activity_detail_url; ?>" class="activity-card-link">
                            <img src="<?php echo $activity_image; ?>" alt="<?php echo $activity_title; ?>" class="activity-card-image">
                            <div class="activity-card-content">
                                <h5 class="activity-card-title"><?php echo $activity_title; ?></h5>
                                <p class="activity-card-desc"><?php echo $activity_short_desc; ?></p>
                                <div class="activity-card-footer">
                                    <p class="activity-card-info">
                                        <strong>Tanggal:</strong> <?php echo date("d M Y", strtotime($activity['activity_date_start'])); ?><br>
                                        <strong>Lokasi:</strong> <?php echo htmlspecialchars($activity['location']); ?>
                                    </p>
                                    <span class="btn-view-detail-small">Lihat Detail & Daftar</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="message info">Belum ada workshop yang tersedia di halaman ini. Silakan cek kembali nanti.</p>
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
        <a href="<?php echo BASE_URL; ?>index.php#activities" class="btn-link">&larr; Kembali ke Kategori Kegiatan</a>
    </p>
</div>

<?php 
require_once 'php/partials/footer.php'; 
// Tidak perlu menutup koneksi di sini lagi karena sudah ditangani oleh footer.php
?>