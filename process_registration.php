<?php
// File: process_registration.php
// Pastikan db_config.php di-include untuk BASE_URL dan koneksi $conn
// Jika session_start() belum dipanggil oleh db_config.php, panggil di sini
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Cek apakah BASE_URL sudah terdefinisi, jika tidak, include db_config
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/php/db_config.php'; // Asumsi file ini di root, dan db_config di folder php
}


$redirect_url_base = "activity_detail.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_activity'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php?message=" . urlencode("Silakan login untuk mendaftar."));
        exit();
    }

    $activity_id = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);
    $user_id_session = $_SESSION['user_id'];

    if (!$activity_id || $activity_id <= 0) {
        header("Location: " . BASE_URL . "index.php?message=" . urlencode("ID Kegiatan tidak valid."));
        exit();
    }
    
    $redirect_url = BASE_URL . $redirect_url_base . "?id=" . $activity_id;

    // Pastikan $conn ada
    if (!isset($conn) || !$conn instanceof mysqli) {
        // Coba include db_config lagi jika $conn tidak ada (meski seharusnya sudah oleh header)
        require_once __DIR__ . '/php/db_config.php';
        if (!isset($conn) || !$conn instanceof mysqli) {
             $error_msg = "Koneksi database tidak tersedia.";
             error_log($error_msg);
             header("Location: " . $redirect_url . "&reg_status=error&reg_message=" . urlencode($error_msg));
             exit();
        }
    }


    $stmt_proc = $conn->prepare("CALL RegisterUserForActivity(?, ?, @registration_message_out)");
    if ($stmt_proc) {
        $stmt_proc->bind_param("ii", $user_id_session, $activity_id);
        
        if ($stmt_proc->execute()) {
            $stmt_proc->close();

            $select_message_out = $conn->query("SELECT @registration_message_out AS message_out");
            if ($select_message_out) {
                $result_message_out = $select_message_out->fetch_assoc();
                $registration_message = $result_message_out['message_out'];
                $select_message_out->free();

                if (stripos($registration_message, 'sukses') !== false || stripos($registration_message, 'success') !== false) {
                    header("Location: " . $redirect_url . "&reg_status=success&reg_message=" . urlencode($registration_message));
                } else {
                    header("Location: " . $redirect_url . "&reg_status=error&reg_message=" . urlencode($registration_message));
                }
                exit();
            } else {
                $error_msg = "Gagal mengambil pesan hasil prosedur: " . $conn->error;
                error_log($error_msg);
                header("Location: " . $redirect_url . "&reg_status=error&reg_message=" . urlencode("Terjadi kesalahan pada server (output)."));
                exit();
            }
        } else {
            $error_msg = "Gagal mengeksekusi prosedur pendaftaran: " . $stmt_proc->error;
            error_log($error_msg);
            $stmt_proc->close();
            header("Location: " . $redirect_url . "&reg_status=error&reg_message=" . urlencode("Gagal memproses pendaftaran (eksekusi)."));
            exit();
        }
    } else {
        $error_msg = "Gagal menyiapkan statement prosedur pendaftaran: " . $conn->error;
        error_log($error_msg);
        header("Location: " . $redirect_url . "&reg_status=error&reg_message=" . urlencode("Gagal memproses pendaftaran (statement)."));
        exit();
    }
}
else {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Tidak perlu menutup koneksi di sini karena skrip melakukan redirect dan exit
?>