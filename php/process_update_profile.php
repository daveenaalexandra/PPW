<?php
// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi database dan BASE_URL dimuat
require_once __DIR__ . '/db_config.php';

// Keamanan: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Hanya proses jika form disubmit dengan benar
if (isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name']);
    $phone_number = !empty($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $date_of_birth = !empty($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : null;
    $gender = !empty($_POST['gender']) ? trim($_POST['gender']) : null;
    
    // Validasi dasar
    if (empty($full_name)) {
        header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Nama Lengkap tidak boleh kosong."));
        exit();
    }

    if (empty($phone_number)) {
        header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Nomor Telepon wajib diisi."));
        exit();
    }

    // --- LOGIKA UPLOAD GAMBAR ---
    $profile_picture_path = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/profile_pics/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }
        
        $file_info = getimagesize($_FILES['profile_picture']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        if ($file_info && in_array($file_info['mime'], $allowed_types)) {
            if ($_FILES['profile_picture']['size'] < 2097152) { // Maks 2MB
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $unique_filename = 'user' . $user_id . '_' . time() . '.' . $file_extension;
                $target_path = $upload_dir . $unique_filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                    // Jika berhasil, siapkan path untuk disimpan ke database
                    $profile_picture_path = 'uploads/profile_pics/' . $unique_filename;
                } else {
                    header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Gagal memindahkan file."));
                    exit();
                }
            } else {
                header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Ukuran file terlalu besar (maks 2MB)."));
                exit();
            }
        } else {
            header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Tipe file tidak valid."));
            exit();
        }
    }
    
    // --- UPDATE DATABASE ---
    $stmt_update = null;
    // Jika ADA gambar baru yang diunggah
    if ($profile_picture_path !== null) {
        // Update semua data TERMASUK kolom gambar
        $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, date_of_birth = ?, gender = ?, profile_picture_url = ? WHERE user_id = ?");
        $stmt_update->bind_param("sssssi", $full_name, $phone_number, $date_of_birth, $gender, $profile_picture_path, $user_id);
    } else {
        // Jika TIDAK ADA gambar baru, update data lainnya saja, jangan sentuh kolom gambar
        $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, date_of_birth = ?, gender = ? WHERE user_id = ?");
        $stmt_update->bind_param("ssssi", $full_name, $phone_number, $date_of_birth, $gender, $user_id);
    }

    if ($stmt_update) {
        if ($stmt_update->execute()) {
            $_SESSION['full_name'] = $full_name; // Update sesi jika perlu
            header("Location: " . BASE_URL . "profile.php?status=success");
        } else {
            header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Gagal memperbarui data ke database."));
        }
        $stmt_update->close();
    } else {
        header("Location: " . BASE_URL . "profile.php?status=error&message=" . urlencode("Gagal menyiapkan statement database."));
    }

} else {
    // Redirect jika diakses langsung tanpa POST
    header("Location: " . BASE_URL . "profile.php");
}

$conn->close();
exit();
?>