<?php
session_start();
require_once __DIR__ . '/php/db_config.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Permintaan tidak valid.'];

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Anda harus login terlebih dahulu untuk mendaftar kegiatan.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['activity_id'])) {
    $activity_id = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);

    if (!$activity_id || $activity_id <= 0) {
        $response['message'] = 'Data kegiatan tidak valid.';
        echo json_encode($response);
        exit();
    }

    // Ambil detail kegiatan dari database
    $stmt = $conn->prepare("SELECT title, price FROM activities WHERE activity_id = ?");
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $activity = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$activity) {
        $response['message'] = 'Kegiatan tidak ditemukan.';
        echo json_encode($response);
        exit();
    }

    // Inisialisasi keranjang dengan struktur baru jika belum ada
    if (!isset($_SESSION['cart']['activities'])) {
        $_SESSION['cart']['activities'] = [];
    }
    if (!isset($_SESSION['cart']['products'])) {
        $_SESSION['cart']['products'] = [];
    }
    
    // Periksa apakah kegiatan sudah ada di keranjang
    if (isset($_SESSION['cart']['activities'][$activity_id])) {
        $response['status'] = 'info';
        $response['message'] = 'Anda sudah menambahkan kegiatan ini ke keranjang.';
    } else {
        // Tambahkan kegiatan ke keranjang sesi
        $_SESSION['cart']['activities'][$activity_id] = [
            'activity_id' => $activity_id,
            'title' => $activity['title'],
            'price' => $activity['price']
        ];
        $response['status'] = 'success';
        $response['message'] = '"' . htmlspecialchars($activity['title']) . '" berhasil ditambahkan ke keranjang.';
    }
}

echo json_encode($response);
exit();
?>