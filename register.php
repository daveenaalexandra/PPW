<?php
// File: register.php
$page_title = "Register - Baking Lovers";
require_once 'php/partials/header.php';

$registration_message = '';
$registration_success = false;
$form_data = ['username' => '', 'full_name' => '', 'email' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_data['username'] = $username = trim($_POST['username']);
    $form_data['full_name'] = $full_name = trim($_POST['full_name']);
    $form_data['email'] = $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password']; // Password teks biasa
    $confirm_password = $_POST['confirm_password'];

    $errors = [];
    if (empty($username)) $errors[] = "Username wajib diisi.";
    if (empty($full_name)) $errors[] = "Nama Lengkap wajib diisi.";
    if (empty($email)) {
        $errors[] = "Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if (empty($password)) $errors[] = "Password wajib diisi.";
    // Validasi panjang password bisa tetap ada jika diinginkan
    // if (strlen($password) < 6 && !empty($password)) $errors[] = "Password minimal 6 karakter.";
    if ($password !== $confirm_password) $errors[] = "Password dan konfirmasi password tidak cocok.";

    if (empty($errors)) {
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $registration_message = "Username atau email sudah terdaftar.";
            } else {
                // TIDAK ADA HASHING LAGI
                // $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert user baru dengan password teks biasa
                // Pastikan nama kolom di DB adalah password_text
                $stmt_insert = $conn->prepare("INSERT INTO users (username, full_name, email, password_text, role) VALUES (?, ?, ?, ?, 'member')");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssss", $username, $full_name, $email, $password); // Simpan $password langsung
                    if ($stmt_insert->execute()) {
                        $_SESSION['registration_success_message'] = "Registrasi berhasil! Silakan login.";
                        header("Location: " . BASE_URL . "login.php?registration=success");
                        exit();
                    } else {
                        $registration_message = "Registrasi gagal. Silakan coba lagi.";
                        error_log("Error inserting user: " . $stmt_insert->error);
                    }
                    $stmt_insert->close();
                } else {
                    $registration_message = "Gagal menyiapkan statement registrasi.";
                     error_log("Error preparing user insert: " . $conn->error);
                }
            }
            $stmt_check->close();
        } else {
             $registration_message = "Gagal menyiapkan statement pengecekan user.";
             error_log("Error preparing user check: " . $conn->error);
        }
    } else {
        $registration_message = implode("<br>", $errors);
    }
}
?>

<div class="container page-container">
    <h1 class="page-title">Registrasi Anggota Baru</h1>

    <?php if (!empty($registration_message)): ?>
        <div class="message <?php echo $registration_success ? 'success' : 'error'; ?>">
            <?php echo $registration_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!$registration_success): ?>
    <form action="register.php" method="POST" class="user-form">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($form_data['username']); ?>">
        </div>
        <div class="form-group">
            <label for="full_name">Nama Lengkap:</label>
            <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($form_data['full_name']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($form_data['email']); ?>">
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn-submit-user-form">Register</button>
    </form>
    <p class="form-switch-link">Sudah punya akun? <a href="<?php echo BASE_URL; ?>login.php">Login di sini</a>.</p>
    <?php endif; ?>
</div>

<?php require_once 'php/partials/footer.php'; ?>