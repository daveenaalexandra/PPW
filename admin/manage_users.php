<?php
require_once 'admin_check.php';

// --- LOGIKA UNTUK PROSES AKSI ADMIN ---

// 1. Logika Hapus Pengguna
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id_to_delete = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($user_id_to_delete) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            $message = "Pengguna berhasil dihapus secara permanen.";
            $message_type = "success";
        } else {
            $message = "Gagal menghapus pengguna.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// 2. Logika Bekukan/Aktifkan Akun
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status_user'])) {
    $user_id_to_toggle = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $current_status = filter_input(INPUT_POST, 'current_status', FILTER_VALIDATE_INT);
    
    if ($user_id_to_toggle) {
        $new_status = ($current_status == 1) ? 0 : 1; // Toggle status
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_status, $user_id_to_toggle);
        if ($stmt->execute()) {
            $status_text = ($new_status == 1) ? 'diaktifkan' : 'dibekukan';
            $message = "Akun pengguna berhasil $status_text.";
            $message_type = "success";
        } else {
            $message = "Gagal mengubah status akun.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// --- Mengambil semua data pengguna dan alamat mereka ---
$users_list = [];
$users_result = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY registration_date DESC");
while ($user = $users_result->fetch_assoc()) {
    $addresses = [];
    $addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
    $addr_stmt->bind_param("i", $user['user_id']);
    $addr_stmt->execute();
    $addr_result = $addr_stmt->get_result();
    while ($addr = $addr_result->fetch_assoc()) {
        $addresses[] = $addr;
    }
    $user['addresses'] = $addresses;
    $users_list[] = $user;
    $addr_stmt->close();
}

$page_title = "Kelola Pengguna";
require_once __DIR__ . '/../php/partials/header.php';
?>

<div class="container-fluid page-container"> 
    <h1 class="page-title">Kelola Pengguna Terdaftar</h1>
    
    <?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="table-responsive mt-4">
        <table class="table table-bordered table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Status Akun</th>
                    <th>Tanggal Daftar</th>
                    <th style="width: 320px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users_list)): ?>
                    <?php foreach($users_list as $user): ?>
                    <tr class="<?php echo $user['is_active'] == 0 ? 'table-secondary' : ''; ?>">
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['is_active'] == 1): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Dibekukan</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date("d M Y", strtotime($user['registration_date'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addressesModal" 
                                    data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                    data-addresses='<?php echo json_encode($user['addresses']); ?>'>
                                Lihat Alamat
                            </button>

                            <form action="manage_users.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                                <?php if ($user['is_active'] == 1): ?>
                                    <button type="submit" name="toggle_status_user" class="btn btn-warning btn-sm">Bekukan</button>
                                <?php else: ?>
                                    <button type="submit" name="toggle_status_user" class="btn btn-success btn-sm">Aktifkan</button>
                                <?php endif; ?>
                            </form>
                            
                            <form action="manage_users.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" 
                                        onclick="return confirm('PERINGATAN! Menghapus pengguna akan menghapus SEMUA data terkait (pesanan, alamat, pendaftaran kegiatan). Apakah Anda benar-benar yakin?');">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">Belum ada pengguna (member) yang terdaftar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addressesModal" tabindex="-1" aria-labelledby="addressesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addressesModalLabel">Alamat Terdaftar untuk Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalBodyContent">
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>


<?php
// Letakkan JavaScript sebelum footer
$footer_scripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    var addressesModal = document.getElementById("addressesModal");
    addressesModal.addEventListener("show.bs.modal", function (event) {
        var button = event.relatedTarget;
        var userName = button.getAttribute("data-user-name");
        var addressesJson = button.getAttribute("data-addresses");
        var addresses = JSON.parse(addressesJson);
        
        var modalTitle = addressesModal.querySelector(".modal-title");
        var modalBody = addressesModal.querySelector(".modal-body");
        
        modalTitle.textContent = "Alamat Terdaftar untuk: " + userName;
        
        if (addresses.length > 0) {
            var html = "<ul class=\'list-group\'>";
            addresses.forEach(function(addr) {
                html += "<li class=\'list-group-item\'>";
                html += "<strong>" + (addr.label || "Alamat") + "</strong> (" + (addr.is_default == 1 ? "Default" : "Opsional") + ")<br>";
                html += addr.recipient_name + " (" + addr.phone_number + ")<br>";
                html += [addr.street_address, addr.house_number, addr.neighborhood, addr.sub_district, addr.city_regency, addr.province, addr.postal_code].filter(Boolean).join(", ");
                html += "</li>";
            });
            html += "</ul>";
            modalBody.innerHTML = html;
        } else {
            modalBody.innerHTML = "<p>Pengguna ini belum memiliki alamat terdaftar.</p>";
        }
    });
});
</script>
';

require_once __DIR__ . '/../php/partials/footer.php';

// Jika Anda ingin memuat skrip dari footer.php, pastikan footer.php bisa menanganinya
// Jika tidak, cara di atas (echo langsung) sudah cukup.
if (isset($footer_scripts)) {
    echo $footer_scripts;
}
?>