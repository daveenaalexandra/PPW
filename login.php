<?php
// File: login.php

// Aktifkan tampilan error untuk development (HAPUS atau set ke 0 di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "Login - Baking Lovers";
require_once 'php/partials/header.php'; // Header akan meng-include db_config.php & memulai sesi

$login_message = '';
$form_identifier = ''; // Untuk mempertahankan input di form jika login gagal

// Jika user sudah login (sesi sudah ada), redirect ke halaman profil
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "profile.php");
    exit();
}

// Tangani pesan dari redirect (misalnya, dari register.php, logout.php, atau halaman terproteksi)
// if (isset($_GET['message'])) {
//     $login_message = htmlspecialchars(urldecode($_GET['message']));
// }
// Khusus untuk pesan sukses registrasi
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    if (isset($_SESSION['registration_success_message'])) { // Ambil pesan dari sesi jika ada
        $login_message = $_SESSION['registration_success_message'];
        unset($_SESSION['registration_success_message']); // Hapus pesan setelah ditampilkan agar tidak muncul lagi
    } else {
        $login_message = "Registrasi berhasil! Silakan login."; // Fallback message
    }
}


if (isset($_POST['login'])) {
    $identifier = $_POST['identifier'];
    $password = $_POST['password'];

    $query = "SELECT *  FROM users WHERE (username = '$identifier' OR email  = '$identifier')  AND password_text = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // 1. Simpan informasi penting ke dalam session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role']; // <-- BARIS PENTING: Simpan peran pengguna

        // 2. Periksa peran pengguna dan arahkan ke halaman yang sesuai
        if ($user['role'] == 'admin') {
            // Jika pengguna adalah admin, arahkan ke dasbor admin
            header("Location: " . BASE_URL . "admin/admin_dashboard.php");
        } else {
            // Jika pengguna adalah member, arahkan ke halaman profil
            header("Location: " . BASE_URL . "profile.php");
        }
        exit(); // Selalu gunakan exit() setelah header redirect

    } else {
        echo "<script>alert('Login gagal! Username atau password salah.');</script>";
        echo "<script>window.location.href = 'login.php';</script>";
    }
}
?>

<div class="container page-container">
    <h1 class="page-title">Login Anggota</h1>

    <?php if (!empty($login_message)): ?>
        <div class="message <?php
                            // Logika untuk menentukan class pesan (success atau error)
                            $is_success_message = (isset($_GET['registration']) && $_GET['registration'] === 'success') ||
                                                  (stripos(strtolower($login_message), 'logout berhasil') !== false) ||
                                                  (stripos(strtolower($login_message), 'registrasi berhasil') !== false);
                            echo $is_success_message ? 'success' : 'error';
                           ?>">
            <?php echo $login_message; // Pesan sudah di-htmlspecialchars jika dari GET atau dari $errors[] ?>
        </div>
    <?php endif; ?>

    <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : ''; ?>" method="POST" class="user-form">
        <div class="form-group">
            <label for="identifier">Username atau Email:</label>
            <input type="text" id="identifier" name="identifier" required value="<?php echo htmlspecialchars($form_identifier); ?>">
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-submit-user-form" name="login">Login</button>
    </form>
    <p class="form-switch-link">Belum punya akun? <a href="<?php echo BASE_URL; ?>register.php">Register di sini</a>.</p>
</div>

<?php require_once 'php/partials/footer.php'; ?>