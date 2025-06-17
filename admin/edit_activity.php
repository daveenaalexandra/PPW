<?php
require_once 'admin_check.php';

// --- VALIDASI ID DARI URL ---
$activity_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$activity_id) {
    echo "ID Kegiatan tidak valid.";
    exit;
}

// --- LOGIKA UNTUK UPDATE DATA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_activity'])) {
    // Ambil data dari form
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $activity_type = $_POST['activity_type'];
    $activity_date_start = $_POST['activity_date_start'];
    $location = trim($_POST['location']);
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    // Siapkan query update
    $stmt_update = $conn->prepare("UPDATE activities SET title = ?, description = ?, activity_type = ?, activity_date_start = ?, location = ?, max_participants = ?, price = ? WHERE activity_id = ?");
    $stmt_update->bind_param("sssssidi", $title, $description, $activity_type, $activity_date_start, $location, $max_participants, $price, $activity_id);
    
    if ($stmt_update->execute()) {
        $message = "Kegiatan berhasil diperbarui.";
        $message_type = "success";
    } else {
        $message = "Gagal memperbarui kegiatan: " . $stmt_update->error;
        $message_type = "danger";
    }
    $stmt_update->close();
}

// --- AMBIL DATA KEGIATAN SAAT INI UNTUK DITAMPILKAN DI FORM ---
$stmt_select = $conn->prepare("SELECT * FROM activities WHERE activity_id = ?");
$stmt_select->bind_param("i", $activity_id);
$stmt_select->execute();
$activity_result = $stmt_select->get_result();
if ($activity_result->num_rows === 0) {
    echo "Kegiatan tidak ditemukan.";
    exit;
}
$activity = $activity_result->fetch_assoc();
$stmt_select->close();

$page_title = "Edit Kegiatan";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container page-container">
    <a href="manage_activities.php">&larr; Kembali ke Kelola Kegiatan</a>
    <h1 class="page-title">Edit Kegiatan: <?php echo htmlspecialchars($activity['title']); ?></h1>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card my-4">
        <div class="card-body">
            <form action="edit_activity.php?id=<?php echo $activity_id; ?>" method="POST">
                <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>">
                <div class="form-group mb-2">
                    <label for="title">Judul Kegiatan</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($activity['title']); ?>" required>
                </div>
                <div class="form-group mb-2">
                    <label for="description">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($activity['description']); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-2">
                        <label for="activity_type">Tipe Kegiatan</label>
                        <select name="activity_type" class="form-control" required>
                            <option value="Workshop" <?php echo ($activity['activity_type'] == 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Competition" <?php echo ($activity['activity_type'] == 'Competition') ? 'selected' : ''; ?>>Competition</option>
                            <option value="Classes" <?php echo ($activity['activity_type'] == 'Classes') ? 'selected' : ''; ?>>Classes</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group mb-2">
                        <label for="activity_date_start">Tanggal & Waktu Mulai</label>
                        <input type="datetime-local" name="activity_date_start" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($activity['activity_date_start'])); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group mb-2">
                        <label for="location">Lokasi</label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($activity['location']); ?>">
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label for="max_participants">Kuota Peserta (0 untuk tak terbatas)</label>
                        <input type="number" name="max_participants" class="form-control" value="<?php echo $activity['max_participants']; ?>" required>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label for="price">Biaya Pendaftaran (Rp)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $activity['price']; ?>" required>
                    </div>
                </div>
                <button type="submit" name="update_activity" class="btn btn-primary mt-3">Perbarui Kegiatan</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../php/partials/footer.php';
?>