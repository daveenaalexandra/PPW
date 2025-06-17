<?php
// File: profile.php
$page_title = "Profil Pengguna - Baking Lovers";
require_once 'php/partials/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php?redirect=" . urlencode(BASE_URL . 'profile.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_data = null;
$addresses = [];
$registered_activities = [];
$user_orders = []; // Untuk menyimpan riwayat pesanan
$message = '';
$message_type = '';

// Ambil data pengguna (dengan kolom tambahan)
$stmt_user = $conn->prepare("SELECT user_id, username, email, full_name, phone_number, date_of_birth, gender, profile_picture_url FROM users WHERE user_id = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();
    $stmt_user->close();
} else {
    $message = "Error: Gagal mengambil data pengguna.";
    $message_type = "error";
    error_log("Error preparing user data statement: " . $conn->error);
}

// Fungsi helper untuk memformat alamat
function format_address_display($address_data) {
    $parts = [];
    if (!empty($address_data['street_address'])) $parts[] = $address_data['street_address'];
    if (!empty($address_data['house_number'])) $parts[] = 'No. ' . $address_data['house_number'];
    if (!empty($address_data['neighborhood'])) $parts[] = $address_data['neighborhood'];
    if (!empty($address_data['sub_district'])) $parts[] = 'Kec. ' . $address_data['sub_district'];
    if (!empty($address_data['city_regency'])) $parts[] = $address_data['city_regency'];
    if (!empty($address_data['province'])) $parts[] = $address_data['province'];
    if (!empty($address_data['postal_code'])) $parts[] = 'Kode Pos: ' . $address_data['postal_code'];

    $full_address = implode(', ', $parts);
    
    $display_text = '';
    if (!empty($address_data['label'])) {
        $display_text .= "<strong>" . htmlspecialchars($address_data['label']) . "</strong><br>";
    }
    $display_text .= htmlspecialchars($address_data['recipient_name']);
    if (!empty($address_data['phone_number'])) $display_text .= " (" . htmlspecialchars($address_data['phone_number']) . ")";
    $display_text .= "<br>" . $full_address;
    
    return $display_text;
}


// Ambil semua alamat pengguna
$stmt_addresses = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
if ($stmt_addresses) {
    $stmt_addresses->bind_param("i", $user_id);
    $stmt_addresses->execute();
    $result_addresses = $stmt_addresses->get_result();
    while ($row = $result_addresses->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmt_addresses->close();
} else {
    $message = "Error: Gagal mengambil data alamat.";
    $message_type = "error";
    error_log("Error preparing address statement: " . $conn->error);
}

// Ambil kegiatan yang terdaftar (Upcoming Activities)
$stmt_registered_activities = $conn->prepare(
    "SELECT er.registration_id, er.status, er.registration_time, a.activity_id, a.title, a.activity_type, a.activity_date_start, a.location
     FROM event_registrations er
     JOIN activities a ON er.activity_id = a.activity_id
     WHERE er.user_id = ? AND a.activity_date_start > NOW()
     ORDER BY a.activity_date_start ASC"
);
if ($stmt_registered_activities) {
    $stmt_registered_activities->bind_param("i", $user_id);
    $stmt_registered_activities->execute();
    $result_registered_activities = $stmt_registered_activities->get_result();
    while ($row = $result_registered_activities->fetch_assoc()) {
        $registered_activities[] = $row;
    }
    $stmt_registered_activities->close();
} else {
    error_log("Error preparing registered activities statement: " . $conn->error);
}

// Ambil riwayat kegiatan (Past Activities)
$history_activities = [];
$stmt_history_activities = $conn->prepare(
    "SELECT er.registration_id, er.status, er.registration_time, a.activity_id, a.title, a.activity_type, a.activity_date_start, a.location
     FROM event_registrations er
     JOIN activities a ON er.activity_id = a.activity_id
     WHERE er.user_id = ? AND a.activity_date_start <= NOW()
     ORDER BY a.activity_date_start DESC"
);
if ($stmt_history_activities) {
    $stmt_history_activities->bind_param("i", $user_id);
    $stmt_history_activities->execute();
    $result_history_activities = $stmt_history_activities->get_result();
    while ($row = $result_history_activities->fetch_assoc()) {
        $history_activities[] = $row;
    }
    $stmt_history_activities->close();
} else {
    error_log("Error preparing history activities statement: " . $conn->error);
}

// Ambil riwayat pesanan
$stmt_user_orders = $conn->prepare(
    "SELECT order_id, order_date, total_amount, status, payment_method, shipping_address
     FROM orders
     WHERE user_id = ?
     ORDER BY order_date DESC"
);
if ($stmt_user_orders) {
    $stmt_user_orders->bind_param("i", $user_id);
    $stmt_user_orders->execute();
    $result_user_orders = $stmt_user_orders->get_result();
    while ($order_row = $result_user_orders->fetch_assoc()) {
        // Untuk setiap pesanan, ambil item-itemnya
        $order_items = [];
        $stmt_order_items = $conn->prepare("SELECT product_name, quantity, price_per_item FROM order_items WHERE order_id = ?");
        if ($stmt_order_items) {
            $stmt_order_items->bind_param("i", $order_row['order_id']);
            $stmt_order_items->execute();
            $result_order_items = $stmt_order_items->get_result();
            while ($item_row = $result_order_items->fetch_assoc()) {
                $order_items[] = $item_row;
            }
            $stmt_order_items->close();
        }
        $order_row['items'] = $order_items; // Tambahkan item ke dalam data pesanan
        $user_orders[] = $order_row;
    }
    $stmt_user_orders->close();
} else {
    error_log("Error preparing user orders statement: " . $conn->error);
}


// Tangani operasi POST (update profil, alamat)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile_info'])) {
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
        $stmt_update_user = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        if ($stmt_update_user) {
            $stmt_update_user->bind_param("si", $full_name, $user_id);
            if ($stmt_update_user->execute()) {
                $message = "Informasi profil berhasil diperbarui.";
                $message_type = "success";
                $user_data['full_name'] = $full_name; // Update in memory
            } else {
                $message = "Gagal memperbarui informasi profil: " . $stmt_update_user->error;
                $message_type = "error";
            }
            $stmt_update_user->close();
        }
    }
    elseif (isset($_POST['add_address']) || isset($_POST['edit_address'])) {
        $address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
        $label = filter_input(INPUT_POST, 'label', FILTER_SANITIZE_STRING);
        $recipient_name = filter_input(INPUT_POST, 'recipient_name', FILTER_SANITIZE_STRING);
        $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
        $street_address = filter_input(INPUT_POST, 'street_address', FILTER_SANITIZE_STRING);
        $house_number = filter_input(INPUT_POST, 'house_number', FILTER_SANITIZE_STRING);
        $neighborhood = filter_input(INPUT_POST, 'neighborhood', FILTER_SANITIZE_STRING);
        $sub_district = filter_input(INPUT_POST, 'sub_district', FILTER_SANITIZE_STRING);
        $city_regency = filter_input(INPUT_POST, 'city_regency', FILTER_SANITIZE_STRING);
        $province = filter_input(INPUT_POST, 'province', FILTER_SANITIZE_STRING);
        $postal_code = filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_STRING);
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        // Validasi dasar
        if (empty($recipient_name) || empty($street_address) || empty($city_regency) || empty($province) || empty($postal_code)) {
            $message = "Harap lengkapi semua kolom alamat wajib (Nama Penerima, Jalan, Kota/Kab, Provinsi, Kode Pos).";
            $message_type = "error";
        } else {
            // Mulai transaksi untuk memastikan hanya satu default address
            $conn->begin_transaction();
            try {
                if ($is_default) {
                    // Set semua alamat lain pengguna ini menjadi non-default
                    $stmt_reset_default = $conn->prepare("UPDATE user_addresses SET is_default = FALSE WHERE user_id = ?");
                    if (!$stmt_reset_default) throw new Exception("Failed to prepare reset default statement: " . $conn->error);
                    $stmt_reset_default->bind_param("i", $user_id);
                    if (!$stmt_reset_default->execute()) throw new Exception("Failed to execute reset default: " . $stmt_reset_default->error);
                    $stmt_reset_default->close();
                }

                if (isset($_POST['add_address'])) {
                    $stmt_insert = $conn->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone_number, street_address, house_number, neighborhood, sub_district, city_regency, province, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if (!$stmt_insert) throw new Exception("Failed to prepare insert address statement: " . $conn->error);
                    $stmt_insert->bind_param("issssssssssi", $user_id, $label, $recipient_name, $phone_number, $street_address, $house_number, $neighborhood, $sub_district, $city_regency, $province, $postal_code, $is_default);
                    if (!$stmt_insert->execute()) throw new Exception("Failed to execute insert address: " . $stmt_insert->error);
                    $stmt_insert->close();
                    $message = "Alamat baru berhasil ditambahkan.";
                } elseif (isset($_POST['edit_address']) && $address_id > 0) {
                    $stmt_update = $conn->prepare("UPDATE user_addresses SET label = ?, recipient_name = ?, phone_number = ?, street_address = ?, house_number = ?, neighborhood = ?, sub_district = ?, city_regency = ?, province = ?, postal_code = ?, is_default = ? WHERE address_id = ? AND user_id = ?");
                    if (!$stmt_update) throw new Exception("Failed to prepare update address statement: " . $conn->error);
                    $stmt_update->bind_param("ssssssssssiii", $label, $recipient_name, $phone_number, $street_address, $house_number, $neighborhood, $sub_district, $city_regency, $province, $postal_code, $is_default, $address_id, $user_id);
                    if (!$stmt_update->execute()) throw new Exception("Failed to execute update address: " . $stmt_update->error);
                    $stmt_update->close();
                    $message = "Alamat berhasil diperbarui.";
                }
                $message_type = "success";
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Terjadi kesalahan saat menyimpan alamat: " . $e->getMessage();
                $message_type = "error";
                error_log("Address save error: " . $e->getMessage());
            }
            // Refresh alamat setelah operasi
            header("Location: " . BASE_URL . "profile.php?msg=" . urlencode($message) . "&type=" . $message_type);
            exit();
        }
    }
    elseif (isset($_POST['delete_address'])) {
        $address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
        if ($address_id > 0) {
            $stmt_delete = $conn->prepare("DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?");
            if ($stmt_delete) {
                $stmt_delete->bind_param("ii", $address_id, $user_id);
                if ($stmt_delete->execute()) {
                    $message = "Alamat berhasil dihapus.";
                    $message_type = "success";
                } else {
                    $message = "Gagal menghapus alamat: " . $stmt_delete->error;
                    $message_type = "error";
                }
                $stmt_delete->close();
            }
        }
        header("Location: " . BASE_URL . "profile.php?msg=" . urlencode($message) . "&type=" . $message_type);
        exit();
    }
    elseif (isset($_POST['set_default_address'])) {
        $address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
        if ($address_id > 0) {
            $conn->begin_transaction();
            try {
                // Set semua alamat lain pengguna ini menjadi non-default
                $stmt_reset_default = $conn->prepare("UPDATE user_addresses SET is_default = FALSE WHERE user_id = ?");
                if (!$stmt_reset_default) throw new Exception("Failed to prepare reset default statement: " . $conn->error);
                $stmt_reset_default->bind_param("i", $user_id);
                if (!$stmt_reset_default->execute()) throw new Exception("Failed to execute reset default: " . $stmt_reset_default->error);
                $stmt_reset_default->close();

                // Set alamat yang dipilih menjadi default
                $stmt_set_default = $conn->prepare("UPDATE user_addresses SET is_default = TRUE WHERE address_id = ? AND user_id = ?");
                if (!$stmt_set_default) throw new Exception("Failed to prepare set default statement: " . $conn->error);
                $stmt_set_default->bind_param("ii", $address_id, $user_id);
                if (!$stmt_set_default->execute()) throw new Exception("Failed to execute set default: " . $stmt_set_default->error);
                $stmt_set_default->close();

                $message = "Alamat default berhasil diubah.";
                $message_type = "success";
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Gagal mengatur alamat default: " . $e->getMessage();
                $message_type = "error";
                error_log("Set default address error: " . $e->getMessage());
            }
        }
        header("Location: " . BASE_URL . "profile.php?msg=" . urlencode($message) . "&type=" . $message_type);
        exit();
    }
}

// Ambil pesan dari URL jika ada (setelah redirect POST)
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars(urldecode($_GET['msg']));
    $message_type = htmlspecialchars($_GET['type']);
}

?>

<div class="container page-container profile-page">
    <h1 class="page-title">Profil Anda</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo htmlspecialchars($message_type); ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($user_data): ?>
        <div class="profile-info-block card-style">
            <h2>Informasi Akun</h2>
            <div class="profile-info-layout">

                <div class="profile-pic-container">
                    <p><strong>Foto Profil</strong></p>
                    <img id="profile-pic-preview" 
                        src="<?php echo (!empty($user_data['profile_picture_url'])) ? BASE_URL . htmlspecialchars($user_data['profile_picture_url']) : BASE_URL . 'images/default-profile.png'; ?>" 
                        alt="Foto Profil">
                </div>

                <div class="profile-details-container">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    
                    <form action="<?php echo BASE_URL; ?>php/process_update_profile.php" method="POST" enctype="multipart/form-data" class="profile-details-form">
                        
                        <div class="form-group">
                            <label for="full_name">Nama Lengkap:</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone_number">Nomor Telepon:</label>
                            <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Tanggal Lahir: (Opsional)</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user_data['date_of_birth'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="gender">Jenis Kelamin: (Opsional)</label>
                            <select id="gender" name="gender">
                                <option value="" <?php echo empty($user_data['gender']) ? 'selected' : ''; ?>>-- Pilih --</option>
                                <option value="Pria" <?php echo ($user_data['gender'] ?? '') === 'Pria' ? 'selected' : ''; ?>>Pria</option>
                                <option value="Wanita" <?php echo ($user_data['gender'] ?? '') === 'Wanita' ? 'selected' : ''; ?>>Wanita</option>
                                <option value="Lainnya" <?php echo ($user_data['gender'] ?? '') === 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="profile_picture">Ubah Foto Profil: (Opsional)</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/png, image/jpeg, image/gif">
                            <small>Maks 2MB. Tipe: JPG, PNG, GIF.</small>
                        </div>
                        
                        <div class="form-group">
                            <label></label> 
                            <button type="submit" name="update_profile" class="btn-sm">Perbarui Profil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="profile-addresses-block card-style" style="margin-top: 30px;">
            <h2>Alamat Pengiriman Anda</h2>
            <?php if (empty($addresses)): ?>
                <p>Anda belum memiliki alamat tersimpan.</p>
            <?php else: ?>
                <div class="addresses-list">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-item card-style-mini" style="margin-bottom: 15px; padding: 15px; border: 1px solid #eee; <?php echo $addr['is_default'] ? 'background-color: #e6ffe6; border-color: #66bb6a;' : ''; ?>">
                            <?php echo format_address_display($addr); ?>
                            <?php if ($addr['is_default']): ?>
                                <p><small><em>(Alamat Default)</em></small></p>
                            <?php endif; ?>
                            <div class="address-actions" style="margin-top: 10px;">
                                <button class="btn-sm btn-edit-address" data-address='<?php echo json_encode($addr); ?>'>Edit</button>
                                <form action="" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                                    <button type="submit" name="delete_address" class="btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus alamat ini?');">Hapus</button>
                                </form>
                                <?php if (!$addr['is_default']): ?>
                                    <form action="" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                                        <button type="submit" name="set_default_address" class="btn-sm btn-secondary">Set Sebagai Default</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <button class="btn-submit-user-form" id="addAddressBtn" style="margin-top: 20px;">Tambah Alamat Baru</button>

            <div id="addressFormContainer" class="card-style" style="margin-top: 20px; padding: 20px; display: none;">
                <h3><span id="formTitle">Tambah</span> Alamat</h3>
                <form id="addressForm" action="" method="POST" class="user-form">
                    <input type="hidden" name="address_id" id="address_id" value="">
                    <div class="form-group">
                        <label for="label">Label Alamat (contoh: Rumah, Kantor):</label>
                        <input type="text" id="label" name="label" value="">
                    </div>
                    <div class="form-group">
                        <label for="recipient_name_form">Nama Penerima: <span style="color:red;">*</span></label>
                        <input type="text" id="recipient_name_form" name="recipient_name" required value="">
                    </div>
                    <div class="form-group">
                        <label for="phone_number_form">Nomor Telepon Penerima:</label>
                        <input type="text" id="phone_number_form" name="phone_number" value="">
                    </div>
                    <div class="form-group">
                        <label for="street_address_form">Nama Jalan: <span style="color:red;">*</span></label>
                        <input type="text" id="street_address_form" name="street_address" required value="">
                    </div>
                    <div class="form-group">
                        <label for="house_number_form">Nomor Rumah/Bangunan:</label>
                        <input type="text" id="house_number_form" name="house_number" value="">
                    </div>
                    <div class="form-group">
                        <label for="neighborhood_form">Kelurahan/Desa (RT/RW):</label>
                        <input type="text" id="neighborhood_form" name="neighborhood" value="">
                    </div>
                    <div class="form-group">
                        <label for="sub_district_form">Kecamatan:</label>
                        <input type="text" id="sub_district_form" name="sub_district" value="">
                    </div>
                    <div class="form-group">
                        <label for="city_regency_form">Kabupaten/Kota: <span style="color:red;">*</span></label>
                        <input type="text" id="city_regency_form" name="city_regency" required value="">
                    </div>
                    <div class="form-group">
                        <label for="province_form">Provinsi: <span style="color:red;">*</span></label>
                        <input type="text" id="province_form" name="province" required value="">
                    </div>
                    <div class="form-group">
                        <label for="postal_code_form">Kode Pos: <span style="color:red;">*</span></label>
                        <input type="text" id="postal_code_form" name="postal_code" required value="">
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="is_default" name="is_default">
                        <label for="is_default">Jadikan Alamat Default</label>
                    </div>
                    <button type="submit" id="submitAddressBtn" name="add_address" class="btn-submit-user-form">Simpan Alamat</button>
                    <button type="button" id="cancelAddressBtn" class="btn-danger">Batal</button>
                </form>
            </div>
        </div>

        <div class="profile-section card-style" style="margin-top: 30px;">
            <h2>Kegiatan Anda yang Akan Datang</h2>
            <?php if (empty($registered_activities)): ?>
                <p>Anda belum terdaftar pada kegiatan yang akan datang.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul Kegiatan</th>
                            <th>Tipe</th>
                            <th>Tanggal Mulai</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registered_activities as $activity): ?>
                            <tr>
                                <td><a href="<?php echo BASE_URL; ?>activity_detail.php?id=<?php echo $activity['activity_id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></a></td>
                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                <td><?php echo date("d M Y H:i", strtotime($activity['activity_date_start'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['location']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($activity['status'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="profile-section card-style" style="margin-top: 30px;">
            <h2>Riwayat Kegiatan Anda</h2>
            <?php if (empty($history_activities)): ?>
                <p>Anda belum memiliki riwayat kegiatan.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul Kegiatan</th>
                            <th>Tipe</th>
                            <th>Tanggal Mulai</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_activities as $activity): ?>
                            <tr>
                                <td><a href="<?php echo BASE_URL; ?>activity_detail.php?id=<?php echo $activity['activity_id']; ?>"><?php echo htmlspecialchars($activity['title']); ?></a></td>
                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                <td><?php echo date("d M Y H:i", strtotime($activity['activity_date_start'])); ?></td>
                                <td><?php echo htmlspecialchars($activity['location']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($activity['status'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>


        <div class="profile-section card-style" style="margin-top: 30px;">
            <h2>Riwayat Pesanan Anda</h2>
            <?php if (empty($user_orders)): ?>
                <p>Anda belum melakukan pemesanan produk.</p>
            <?php else: ?>
                <?php foreach ($user_orders as $order): ?>
                    <div class="order-history-item card-style-mini" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd;">
                        <h4>Pesanan #<?php echo htmlspecialchars($order['order_id']); ?> - <?php echo date("d M Y H:i", strtotime($order['order_date'])); ?></h4>
                        <p><strong>Status:</strong> <span style="font-weight: bold; color: <?php 
                            if ($order['status'] == 'completed') echo 'green';
                            else if ($order['status'] == 'pending') echo 'orange';
                            else if ($order['status'] == 'shipped') echo 'blue';
                            else echo 'red';
                        ?>;"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></p>
                        <p><strong>Total:</strong> Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                        <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p><strong>Alamat Pengiriman:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        
                        <h5>Item Pesanan:</h5>
                        <?php if (!empty($order['items'])): ?>
                            <table class="data-table-nested" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; border: 1px solid #eee; text-align: left;">Produk</th>
                                        <th style="padding: 8px; border: 1px solid #eee; text-align: left;">Qty</th>
                                        <th style="padding: 8px; border: 1px solid #eee; text-align: left;">Harga Satuan</th>
                                        <th style="padding: 8px; border: 1px solid #eee; text-align: left;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td style="padding: 8px; border: 1px solid #eee;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td style="padding: 8px; border: 1px solid #eee;"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td style="padding: 8px; border: 1px solid #eee;">Rp <?php echo number_format($item['price_per_item'], 0, ',', '.'); ?></td>
                                            <td style="padding: 8px; border: 1px solid #eee;">Rp <?php echo number_format($item['quantity'] * $item['price_per_item'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Tidak ada item ditemukan untuk pesanan ini.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <p class="message error">Tidak dapat memuat data profil.</p>
    <?php endif; ?>

</div>


<?php require_once 'php/partials/footer.php'; ?>