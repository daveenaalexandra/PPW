<?php
// File: php/partials/footer.php (Versi Perbaikan)
?>
</main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo BASE_URL; ?>js/global.js"></script>

    <?php
    // Memuat skrip spesifik berdasarkan halaman saat ini
    $currentPage = basename($_SERVER['PHP_SELF']);

    if ($currentPage == 'index.php') {
        echo '<script src="' . BASE_URL . 'js/index-main.js"></script>';
    }
    else if ($currentPage == 'profile.php') {
        echo '<script src="' . BASE_URL . 'js/profile.js"></script>';
    }
    else if ($currentPage == 'checkout.php') {
        echo '<script src="' . BASE_URL . 'js/checkout.js"></script>';
    }
    else if ($currentPage == 'product_detail.php') {
        echo '<script src="' . BASE_URL . 'js/product_detail.js"></script>';
    }
    // Pastikan Anda juga sudah menambahkan skrip untuk activity_detail.php jika ada
    else if ($currentPage == 'activity_detail.php') {
        echo '<script src="' . BASE_URL . 'js/activity_detail.js"></script>';
    }


    // Kode JavaScript untuk Modal di manage_users.php
    if ($currentPage == 'manage_users.php' && isset($footer_scripts)) {
        echo $footer_scripts;
    }
    ?>
</body>
</html>