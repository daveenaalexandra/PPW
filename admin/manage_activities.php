<?php
require_once 'admin_check.php'; // Keamanan

// --- LOGIKA UNTUK PROSES AKSI ADMIN ---

// 1. Logika untuk TAMBAH KEGIATAN BARU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_activity'])) {
    // ... (logika tambah kegiatan yang sudah ada tetap sama) ...
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $activity_type = $_POST['activity_type'];
    $activity_date_start = $_POST['activity_date_start'];
    $activity_date_end = !empty($_POST['activity_date_end']) ? $_POST['activity_date_end'] : null;
    $location = trim($_POST['location']);
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $organizer_id = filter_input(INPUT_POST, 'organizer_id', FILTER_VALIDATE_INT);
    $image_path_for_db = null;
    if (isset($_FILES['activity_image']) && $_FILES['activity_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/activities/';
        $file_info = getimagesize($_FILES['activity_image']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if ($file_info && in_array($file_info['mime'], $allowed_types)) {
            if ($_FILES['activity_image']['size'] < 2000000) {
                $file_extension = pathinfo($_FILES['activity_image']['name'], PATHINFO_EXTENSION);
                $unique_filename = 'activity_' . uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $unique_filename;
                if (move_uploaded_file($_FILES['activity_image']['tmp_name'], $target_path)) {
                    $image_path_for_db = 'uploads/activities/' . $unique_filename;
                } else { $message = "Gagal memindahkan file gambar kegiatan."; $message_type = "danger"; }
            } else { $message = "Ukuran file gambar terlalu besar (maks 2MB)."; $message_type = "warning"; }
        } else { $message = "Tipe file gambar tidak valid."; $message_type = "warning"; }
    }
    if (!isset($message) && !empty($title)) {
        $stmt_insert = $conn->prepare("INSERT INTO activities (title, description, activity_type, activity_date_start, activity_date_end, location, max_participants, price, organizer_id, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("ssssssidis", $title, $description, $activity_type, $activity_date_start, $activity_date_end, $location, $max_participants, $price, $organizer_id, $image_path_for_db);
        if ($stmt_insert->execute()) {
            $message = "Kegiatan baru berhasil ditambahkan."; $message_type = "success";
        } else { $message = "Gagal menambahkan kegiatan: " . $stmt_insert->error; $message_type = "danger"; }
        $stmt_insert->close();
    }
}

// 2. --- LOGIKA UNTUK HAPUS KEGIATAN (BARU) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_activity'])) {
    $activity_id_to_delete = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);
    if ($activity_id_to_delete) {
        // Karena ada relasi ON DELETE CASCADE, menghapus kegiatan akan menghapus semua pendaftaran terkait.
        $stmt_delete = $conn->prepare("DELETE FROM activities WHERE activity_id = ?");
        $stmt_delete->bind_param("i", $activity_id_to_delete);
        if ($stmt_delete->execute()) {
            $message = "Kegiatan dan semua data pendaftaran terkait berhasil dihapus.";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus kegiatan.";
            $message_type = "danger";
        }
        $stmt_delete->close();
    }
}

// --- Mengambil data kegiatan dan pengguna ---
$activities_result = $conn->query("SELECT * FROM activities ORDER BY activity_date_start DESC");
$users_result = $conn->query("SELECT user_id, full_name, username FROM users ORDER BY full_name ASC");

$page_title = "Kelola Kegiatan";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container page-container">
    <a href="admin_dashboard.php" class="btn-back-to-dashboard"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    <h1 class="page-title">Kelola Kegiatan</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card my-4">
        <div class="card-header"><h4>Tambah Kegiatan Baru</h4></div>
        <div class="card-body">
            <form action="manage_activities.php" method="POST" enctype="multipart/form-data">
                <div class="form-group mb-2"><label for="title">Judul Kegiatan</label><input type="text" id="title" name="title" class="form-control" required></div>
                <div class="form-group mb-2"><label for="description">Deskripsi</label><textarea id="description" name="description" class="form-control" rows="3"></textarea></div>
                <div class="row">
                    <div class="col-md-6 form-group mb-2"><label for="activity_type">Tipe Kegiatan</label><select id="activity_type" name="activity_type" class="form-control" required><option value="Workshop">Workshop</option><option value="Competition">Competition</option><option value="Classes">Classes</option></select></div>
                    <div class="col-md-6 form-group mb-2"><label for="organizer_id">Penyelenggara (Organizer)</label><select id="organizer_id" name="organizer_id" class="form-control" required><option value="">-- Pilih Pengguna --</option><?php while($user = $users_result->fetch_assoc()): ?><option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?></option><?php endwhile; ?></select></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-2"><label for="activity_date_start">Tanggal & Waktu Mulai</label><input type="datetime-local" id="activity_date_start" name="activity_date_start" class="form-control" required></div>
                    <div class="col-md-6 form-group mb-2"><label for="activity_date_end">Tanggal & Waktu Selesai (Opsional)</label><input type="datetime-local" id="activity_date_end" name="activity_date_end" class="form-control"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group mb-2"><label for="location">Lokasi</label><input type="text" id="location" name="location" class="form-control"></div>
                    <div class="col-md-4 form-group mb-2"><label for="max_participants">Kuota Peserta (0=unlimited)</label><input type="number" id="max_participants" name="max_participants" class="form-control" value="0" required></div>
                    <div class="col-md-4 form-group mb-2"><label for="price">Biaya (Rp)</label><input type="number" step="0.01" id="price" name="price" class="form-control" value="0.00" required></div>
                </div>
                <div class="form-group mb-3"><label for="activity_image">Gambar Kegiatan (Maks 2MB)</label><input type="file" id="activity_image" name="activity_image" class="form-control"></div>
                <button type="submit" name="add_activity" class="btn btn-success">Tambah Kegiatan</button>
            </form>
        </div>
    </div>

    <h3 class="mt-5">Daftar Kegiatan yang Ada</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr><th>ID</th><th>Judul</th><th>Tipe</th><th>Tanggal Mulai</th><th>Kuota</th><th>Harga</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php $activities_result->data_seek(0); ?>
                <?php while($activity = $activities_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $activity['activity_id']; ?></td>
                    <td><?php echo htmlspecialchars($activity['title']); ?></td>
                    <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                    <td><?php echo date("d M Y H:i", strtotime($activity['activity_date_start'])); ?></td>
                    <td><?php echo $activity['max_participants'] == 0 ? 'Tak Terbatas' : $activity['max_participants']; ?></td>
                    <td>Rp <?php echo number_format($activity['price'], 0, ',', '.'); ?></td>
                    <td>
                        <a href="edit_activity.php?id=<?php echo $activity['activity_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        
                        <form action="manage_activities.php" method="POST" class="d-inline">
                            <input type="hidden" name="activity_id" value="<?php echo $activity['activity_id']; ?>">
                            <button type="submit" name="delete_activity" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('PERINGATAN! Menghapus kegiatan ini juga akan menghapus SEMUA data pendaftaran pengguna yang terkait. Tindakan ini tidak dapat diurungkan. Anda yakin?');">
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

<?php require_once __DIR__ . '/../php/partials/footer.php'; ?>