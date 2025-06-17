<?php
require_once 'admin_check.php'; // Keamanan: Pastikan hanya admin yang bisa akses

// --- LOGIKA UNTUK MENAMBAHKAN KEGIATAN BARU ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_activity'])) {
    // Ambil dan sanitasi data dari form
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $activity_type = $_POST['activity_type'];
    $activity_date_start = $_POST['activity_date_start'];
    $location = trim($_POST['location']);
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    // Validasi dasar
    if (!empty($title) && !empty($activity_type) && !empty($activity_date_start)) {
        $stmt_insert = $conn->prepare("INSERT INTO activities (title, description, activity_type, activity_date_start, location, max_participants, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssssid", $title, $description, $activity_type, $activity_date_start, $location, $max_participants, $price);
        
        if ($stmt_insert->execute()) {
            $message = "Kegiatan baru berhasil ditambahkan.";
            $message_type = "success";
        } else {
            $message = "Gagal menambahkan kegiatan: " . $stmt_insert->error;
            $message_type = "danger";
        }
        $stmt_insert->close();
    } else {
        $message = "Judul, Tipe, dan Tanggal Mulai tidak boleh kosong.";
        $message_type = "warning";
    }
}

// --- Mengambil semua data kegiatan untuk ditampilkan di tabel ---
$activities_result = $conn->query("SELECT * FROM activities ORDER BY activity_date_start DESC");

$page_title = "Kelola Kegiatan";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container page-container">
    <h1 class="page-title">Kelola Kegiatan</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card my-4">
        <div class="card-header">
            <h4>Tambah Kegiatan Baru</h4>
        </div>
        <div class="card-body">
            <form action="manage_activities.php" method="POST">
                <div class="form-group mb-2">
                    <label for="title">Judul Kegiatan</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group mb-2">
                    <label for="description">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-2">
                        <label for="activity_type">Tipe Kegiatan</label>
                        <select name="activity_type" class="form-control" required>
                            <option value="Workshop">Workshop</option>
                            <option value="Competition">Competition</option>
                            <option value="Classes">Classes</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group mb-2">
                        <label for="activity_date_start">Tanggal & Waktu Mulai</label>
                        <input type="datetime-local" name="activity_date_start" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group mb-2">
                        <label for="location">Lokasi</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label for="max_participants">Kuota Peserta (0 untuk tak terbatas)</label>
                        <input type="number" name="max_participants" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label for="price">Biaya Pendaftaran (Rp)</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="0.00" required>
                    </div>
                </div>
                <button type="submit" name="add_activity" class="btn btn-success mt-3">Tambah Kegiatan</button>
            </form>
        </div>
    </div>

    <h3 class="mt-5">Daftar Kegiatan yang Ada</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Judul</th>
                    <th>Tipe</th>
                    <th>Tanggal Mulai</th>
                    <th>Kuota</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
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