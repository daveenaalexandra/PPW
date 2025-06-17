<?php
// File: php/partials/header.php (Struktur Lengkap dan Benar)

if (!defined('DB_SERVER')) {
    require_once __DIR__ . '/../db_config.php';
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page_for_nav = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Baking Lovers'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/global.css?v=<?php echo filemtime(__DIR__ . '/../../css/global.css'); ?>">

    <?php
    // Logika untuk memuat CSS spesifik tetap sama
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage == 'index.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/index.css">'; }
    else if ($currentPage == 'activity_detail.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/activity_detail.css">'; }
    else if ($currentPage == 'all_products.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/all_products.css">'; }
    else if (in_array($currentPage, ['list_classes.php', 'list_workshops.php', 'list_competition.php'])) { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/activity-list.css">'; }
    else if (in_array($currentPage, ['login.php', 'register.php'])) { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/user-forms.css">'; }
    else if ($currentPage == 'order_confirmation.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/order_confirmation.css">'; }
    else if ($currentPage == 'profile.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/profile.css">'; }
    else if ($currentPage == 'cart.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/cart.css">'; }
    else if ($currentPage == 'checkout.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/checkout.css">'; }
    else if ($currentPage == 'product_detail.php') { echo '<link rel="stylesheet" href="' . BASE_URL . 'css/product_detail.css">'; }
    else if (in_array($currentPage, ['admin_dashboard.php','manage_orders.php', 'manage_activities.php', 'edit_activity.php', 'manage_products.php', 'manage_users.php'])) {echo '<link rel="stylesheet" href="' . BASE_URL . 'css/admin.css">';}
    ?>
</head>
<body>

<header class="navbar">
    <div class="navbar-left">
        <div class="logo">
            <a href="<?php echo BASE_URL; ?>index.php">Baking Lovers</a>
        </div>
        <form action="<?php echo BASE_URL; ?>search_results.php" method="GET" class="search-form">
            <input type="search" name="q" placeholder="Cari produk atau kegiatan..." required>
            <button type="submit">Cari</button>
        </form>
    </div>
    <nav>

        <ul>
            <li class="dropdown" id="ourDropdown">
                <button class="dropbtn">Our Profile<span class="arrow-down"></span></button>
                <div class="dropdown-content">
                    <a href="<?php echo BASE_URL; ?>index.php#about">About Us</a>
                    <a href="<?php echo BASE_URL; ?>index.php#partners">Partners</a>
                    <a href="<?php echo BASE_URL; ?>index.php#contact">Contact</a>
                </div>
            </li>
            <li><a href="<?php echo BASE_URL; ?>index.php#activities">Activities</a></li>
            <li><a href="<?php echo BASE_URL; ?>index.php#product">Products</a></li>
            
            <?php // INI ADALAH IF UTAMA YANG HILANG DI KODE ANDA ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                
                <li class="dropdown" id="userDropdown">
                    <button class="dropbtn">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        <span class="arrow-down"></span>
                    </button>
                    <div class="dropdown-content">
                        <?php // Pengecekan peran admin/member ada DI DALAM sini ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <a href="<?php echo BASE_URL; ?>admin/admin_dashboard.php">Dashboard Admin</a>
                            <a href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>profile.php">Profile</a>
                            <a href="<?php echo BASE_URL; ?>cart.php">
                                Keranjang
                                <?php
                                $product_count = isset($_SESSION['cart']['products']) ? count($_SESSION['cart']['products']) : 0;
                                $activity_count = isset($_SESSION['cart']['activities']) ? count($_SESSION['cart']['activities']) : 0;
                                $cart_item_count = $product_count + $activity_count;
                                echo " (" . $cart_item_count . ")";
                                ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                        <?php endif; ?>
                    </div>
                </li>

            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
            <?php endif; // Ini adalah penutup dari IF utama ?>
        </ul>

    </nav>
</header>

<main>