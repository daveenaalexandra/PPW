<?php
require_once 'admin_check.php';

// --- LOGIKA UNTUK PROSES AKSI ADMIN ---

// 1. Logika untuk memproses update status PESANAN PRODUK
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status'])) {
    $order_id_to_update = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['new_status'];
    
    $allowed_statuses = ['pending', 'shipped', 'completed', 'cancelled'];
    if ($order_id_to_update && in_array($new_status, $allowed_statuses)) {
        $stmt_update = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt_update->bind_param("si", $new_status, $order_id_to_update);
        if ($stmt_update->execute()) {
            $message = "Status pesanan #$order_id_to_update berhasil diperbarui.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui status pesanan.";
            $message_type = "danger";
        }
        $stmt_update->close();
    }
}

// 2. Logika untuk memproses update status PENDAFTARAN KEGIATAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_registration_status'])) {
    $reg_id_to_update = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
    $new_reg_status = $_POST['new_reg_status'];

    $allowed_reg_statuses = ['confirmed', 'waitlisted', 'cancelled'];
    if ($reg_id_to_update && in_array($new_reg_status, $allowed_reg_statuses)) {
        $stmt_update_reg = $conn->prepare("UPDATE event_registrations SET status = ? WHERE registration_id = ?");
        $stmt_update_reg->bind_param("si", $new_reg_status, $reg_id_to_update);
        if ($stmt_update_reg->execute()) {
            $message = "Status pendaftaran #$reg_id_to_update berhasil diperbarui.";
            $message_type = "success";
        } else {
            $message = "Gagal memperbarui status pendaftaran.";
            $message_type = "danger";
        }
        $stmt_update_reg->close();
    }
}


// --- PENGAMBILAN DATA UNTUK TAMPILAN ---

// 1. Mengambil semua data pesanan produk
$orders_result = $conn->query("SELECT * FROM orders ORDER BY order_date DESC");

// 2. Mengambil semua data pendaftaran kegiatan dan mengelompokkannya
$activity_registrations = [];
$sql_registrations = "
    SELECT 
        a.activity_id, a.title as activity_title, a.activity_date_start,
        u.user_id, u.full_name, u.email,
        er.registration_id, er.status as registration_status
    FROM event_registrations er
    JOIN activities a ON er.activity_id = a.activity_id
    JOIN users u ON er.user_id = u.user_id
    ORDER BY a.activity_date_start DESC, u.full_name ASC
";
$registrations_result = $conn->query($sql_registrations);

while ($reg = $registrations_result->fetch_assoc()) {
    $activity_id = $reg['activity_id'];
    if (!isset($activity_registrations[$activity_id])) {
        $activity_registrations[$activity_id] = [
            'title' => $reg['activity_title'],
            'date' => $reg['activity_date_start'],
            'registrants' => []
        ];
    }
    $activity_registrations[$activity_id]['registrants'][] = $reg;
}


$page_title = "Kelola Pesanan & Pendaftaran";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container-fluid page-container">
    <h1 class="page-title">Kelola Pesanan & Pendaftaran</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card my-4">
        <div class="card-header"><h4>Daftar Pesanan Produk</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID Pesanan</th><th>Tanggal</th><th>ID Pengguna</th><th>Total</th><th>Status Saat Ini</th><th>Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo date("d M Y H:i", strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['user_id']; ?></td>
                            <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                            <td><strong><?php echo htmlspecialchars(ucfirst($order['status'])); ?></strong></td>
                            <td>
                                <form action="manage_orders.php" method="POST" class="form-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="new_status" class="form-control form-control-sm mr-2">
                                        <?php foreach (['pending', 'shipped', 'completed', 'cancelled'] as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo ($order['status'] == $status) ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_order_status" class="btn btn-primary btn-sm mt-1">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card my-4">
        <div class="card-header"><h4>Daftar Peserta Kegiatan</h4></div>
        <div class="card-body">
            <?php if (empty($activity_registrations)): ?>
                <p>Belum ada pengguna yang terdaftar pada kegiatan manapun.</p>
            <?php else: ?>
                <?php foreach ($activity_registrations as $activity_id => $data): ?>
                    <h5 class="mt-3"><?php echo htmlspecialchars($data['title']); ?> <small>(<?php echo date("d M Y", strtotime($data['date'])); ?>)</small></h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID Pendaftaran</th><th>Nama Peserta</th><th>Email</th><th>Status</th><th>Ubah Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['registrants'] as $registrant): ?>
                                    <tr>
                                        <td><?php echo $registrant['registration_id']; ?></td>
                                        <td><?php echo htmlspecialchars($registrant['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($registrant['email']); ?></td>
                                        <td><strong><?php echo htmlspecialchars(ucfirst($registrant['registration_status'])); ?></strong></td>
                                        <td>
                                            <form action="manage_orders.php" method="POST" class="form-inline">
                                                <input type="hidden" name="registration_id" value="<?php echo $registrant['registration_id']; ?>">
                                                <select name="new_reg_status" class="form-control form-control-sm mr-2">
                                                    <?php foreach (['confirmed', 'waitlisted', 'cancelled'] as $reg_status): ?>
                                                        <option value="<?php echo $reg_status; ?>" <?php echo ($registrant['registration_status'] == $reg_status) ? 'selected' : ''; ?>><?php echo ucfirst($reg_status); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="update_registration_status" class="btn btn-primary btn-sm mt-1">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../php/partials/footer.php';
?>