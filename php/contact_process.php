<?php
// File: php/contact_process.php
// Pastikan db_config.php di-include untuk BASE_URL dan koneksi $conn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/db_config.php';
}

$response = ['success' => false, 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi data input dari form
    $sender_name = trim(filter_input(INPUT_POST, 'sender_name', FILTER_SANITIZE_STRING));
    $sender_email = trim(filter_input(INPUT_POST, 'sender_email', FILTER_SANITIZE_EMAIL));
    $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING));
    $message_text = trim(filter_input(INPUT_POST, 'message_text', FILTER_SANITIZE_STRING));

    // Jika subjek kosong, berikan nilai default (opsional)
    if (empty($subject)) {
        $subject = "Pesan dari Form Kontak (Tanpa Subjek)";
    }

    // Validasi dasar
    $errors = [];
    if (empty($sender_name)) {
        $errors[] = "Nama tidak boleh kosong.";
    }
    if (empty($sender_email)) {
        $errors[] = "Email tidak boleh kosong.";
    } elseif (!filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if (empty($message_text)) {
        $errors[] = "Pesan tidak boleh kosong.";
    }

    if (!empty($errors)) {
        $response['message'] = implode("<br>", $errors);
    } else {
        // Pastikan $conn ada
        if (!isset($conn) || !$conn instanceof mysqli) {
            require_once __DIR__ . '/db_config.php'; // Coba include lagi jika belum
            if (!isset($conn) || !$conn instanceof mysqli) {
                $response['message'] = 'Koneksi database tidak tersedia untuk memproses kontak.';
                error_log("Contact Process: DB Connection not available.");
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }
        }

        // Nama kolom di tabel: sender_name, sender_email, subject, message_text
        $stmt = $conn->prepare("INSERT INTO contact_messages (sender_name, sender_email, subject, message_text) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $sender_name, $sender_email, $subject, $message_text);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Pesan Anda berhasil dikirim! Terima kasih.';
            } else {
                $response['message'] = 'Gagal mengirim pesan ke database. Silakan coba lagi nanti.';
                error_log("Error executing contact_messages insert: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = 'Gagal menyiapkan statement database. Silakan coba lagi nanti.';
            error_log("Error preparing contact_messages insert: " . $conn->error);
        }
    }
} else {
    $response['message'] = 'Metode pengiriman tidak valid.';
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>