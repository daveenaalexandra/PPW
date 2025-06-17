<?php
// File: index.php
$page_title = "Selamat Datang di Baking Lovers";
require 'php/partials/header.php'; // Sudah termasuk koneksi db dan session

// ========================================================================
// --- PENGAMBILAN DATA (LOGIC) ---
// Semua query database dikumpulkan di sini untuk memisahkan logika dari tampilan.
// ========================================================================

// 1. Mengambil 4 Produk Unggulan (Featured Products)
$featured_products = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql_products = "SELECT product_id, product_name, image_url, price FROM products WHERE is_featured = TRUE ORDER BY created_at DESC LIMIT 4";
    $result_products = $conn->query($sql_products);
    if ($result_products && $result_products->num_rows > 0) {
        while($row = $result_products->fetch_assoc()) {
            $featured_products[] = $row;
        }
    }
}

// 3. Data untuk kategori kegiatan (statis, lebih efisien karena tidak query)
$main_activity_categories = [
    'Workshop' => [
        'image' => 'images/category-workshop.jpg',
        'description' => 'Asah keahlian baking Anda dengan bimbingan langsung dari para ahli.',
        'list_page' => 'list_workshops.php'
    ],
    'Competition' => [
        'image' => 'images/category-competition.jpg',
        'description' => 'Tunjukkan bakat dan kreativitas Anda dalam berbagai kompetisi baking seru.',
        'list_page' => 'list_competition.php'
    ],
    'Classes' => [
        'image' => 'images/category-classes.jpg',
        'description' => 'Perdalam pengetahuan dan teknik baking Anda melalui kelas-kelas komprehensif kami.',
        'list_page' => 'list_classes.php'
    ]
];

?>

<div class="banner-container">
    <video autoplay loop muted playsinline id="bannerVideo">
        <source src="<?php echo BASE_URL; ?>images/baking-footage.mp4" type="video/mp4">
        Browser Anda tidak mendukung tag video.
    </video>
    <div class="video-label">Welcome</div>
</div>

<section id="about" class="content-section about-us-section">
    <div class="container about-us-container">
        <div class="about-us-image-column">
            <div class="about-us-image-wrapper">
                <img src="<?php echo BASE_URL; ?>images/about-us-cheesecake.webp" alt="New York Cheesecake">
                <div class="image-label"></div>
            </div>
        </div>
        <div class="about-us-content-column">
            <div class="title-box about-us-title-box">
                ABOUT US
            </div>
            <p>Komunitas ini adalah wadah bagi para pecinta baking untuk berbagi, belajar, dan berkembang bersama. Kami percaya bahwa baking adalah seni yang menyenangkan dan bisa dinikmati semua orang. Mari temukan inspirasi dan resep terbaru di sini!</p>
            <p>Bergabunglah dengan kami untuk mengikuti berbagai kegiatan menarik, mulai dari workshop, demo masak, hingga tantangan baking bulanan.</p>
        </div>
    </div>
</section>

<section id="activities" class="content-section activities-overview-section">
    <div class="container">
        <h2 class="section-title activities-main-title">PILIH JENIS KEGIATAN KAMI</h2>
        <div class="activity-category-grid">
            <?php foreach ($main_activity_categories as $category_name => $category_data): ?>
                <div class="activity-category-card">
                    <a href="<?php echo BASE_URL . htmlspecialchars($category_data['list_page']); ?>" class="activity-category-link">
                        <img src="<?php echo BASE_URL . htmlspecialchars($category_data['image']); ?>" alt="<?php echo htmlspecialchars($category_name); ?>" class="activity-category-image">
                        <div class="activity-category-content">
                            <h3 class="activity-category-name"><?php echo htmlspecialchars($category_name); ?></h3>
                            <p class="activity-category-description"><?php echo htmlspecialchars($category_data['description']); ?></p>
                            <span class="btn-view-category">Lihat Pilihan <?php echo htmlspecialchars($category_name); ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="product" class="content-section product-section">
     <div class="container">
        <div class="product-section-header">
            <h3 class="product-title-text">Featured Products</h3>
            <a href="<?php echo BASE_URL; ?>all_products.php" class="product-more-button-link">
                More <span class="arrow-icon">→</span>
            </a>
        </div>
        <div id="initialProductGrid" class="product-grid initial-product-view">
            <?php if (!empty($featured_products)): ?>
                <?php foreach ($featured_products as $product): ?>
                    <?php
                        $product_image = (!empty($product['image_url'])) ? BASE_URL . htmlspecialchars($product['image_url']) : BASE_URL . 'images/default-product.jpg';
                        $product_name = htmlspecialchars($product['product_name']);
                        $product_price_info = ($product['price'] > 0) ? " (Rp " . number_format($product['price'], 0, ',', '.') . ")" : "";
                        $product_detail_url = BASE_URL . 'product_detail.php?id=' . $product['product_id'];
                    ?>
                    <div class="product-card">
                        <a href="<?php echo $product_detail_url; ?>" class="product-card-link">
                            <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>" class="product-card-image">
                            <p class="product-card-name"><?php echo $product_name . $product_price_info; ?></p>
                            <span class="btn-view-detail-small" style="display:block; margin-top:5px;">Detail Produk</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Produk unggulan belum tersedia.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section id="partners" class="content-section partners-section">
     <div class="container">
        <h2 class="section-title partners-title">Our Partners</h2>
        <div class="partners-empty-space">
             <?php
                $sql_partners = "SELECT name, logo_url, website_url FROM partners ORDER BY name ASC LIMIT 5";
                $result_partners = $conn->query($sql_partners);
                if ($result_partners && $result_partners->num_rows > 0) {
                    echo '<div class="partners-grid-actual">';
                    while($row = $result_partners->fetch_assoc()) {
                        $partner_logo = (!empty($row['logo_url'])) ? BASE_URL . htmlspecialchars($row['logo_url']) : BASE_URL . 'images/default-partner-logo.png';
                        $partner_website = (!empty($row['website_url'])) ? htmlspecialchars($row['website_url']) : '#';
                        echo '<div class="partner-item">';
                        echo '    <a href="' . $partner_website . '" target="_blank" title="' . htmlspecialchars($row['name']) . '">';
                        echo '        <img src="' . $partner_logo . '" alt="' . htmlspecialchars($row['name']) . '">';
                        echo '    </a>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else { echo '<p class="coming-soon-text">(Partner kami akan segera hadir di sini!)</p>'; }
            ?>
        </div>
    </div>
</section>

<section id="contact" class="contact-section-footer">
     <div class="container contact-container">
        <h2 class="contact-section-title">Keep in Touch</h2>
        <div class="contact-content-area">
            <div class="contact-form-wrapper">
                <form id="contactForm" action="<?php echo BASE_URL; ?>php/contact_process.php" method="POST">
                    <div class="form-group">
                        <input type="email" id="email" name="sender_email" placeholder="Email Anda" required value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>">
                    </div>
                    <div class="form-group-row">
                        <div class="form-group-half">
                            <input type="text" id="sender_name" name="sender_name" placeholder="Nama Anda" required value="<?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : ''; ?>">
                        </div>
                        <div class="form-group-half">
                            <input type="text" id="subject" name="subject" placeholder="Subjek Pesan">
                        </div>
                    </div>
                    <div class="form-group">
                        <textarea id="message" name="message_text" rows="4" placeholder="Pesan Anda" required></textarea>
                    </div>
                    <button type="submit" class="btn-submit-contact">Send</button>
                </form>
                <div id="formMessage" class="form-message-container"></div>
            </div>
            <div class="contact-info-wrapper">
                <div class="social-icons">
                    <a href="linkedin.in/bakinglovers"><img src="<?php echo BASE_URL; ?>images/linkedin.png" alt="LinkedIn"></a>
                    <a href="tiktok.com/bakinglovers"><img src="<?php echo BASE_URL; ?>images/tik-tok.png" alt="TikTok"></a>
                    <a href="x.com/bakinglovers"><img src="<?php echo BASE_URL; ?>images/twitter.png" alt="X/Twitter"></a>
                    <a href="youtube.com/bakinglovers"><img src="<?php echo BASE_URL; ?>images/youtube.png" alt="YouTube"></a>
                    <a href="instagram.com/bakinglovers"><img src="<?php echo BASE_URL; ?>images/instagram.png" alt="Instagram"></a>
                </div>
                <p class="contact-detail"><img src="<?php echo BASE_URL; ?>images/phone-call.png" alt="Phone"> +62 878 9827 2631</p>
                <p class="contact-detail"><img src="<?php echo BASE_URL; ?>images/email.png" alt="Email"> trial123@gmail.com</p>
                <p class="contact-detail address-detail"><img src="<?php echo BASE_URL; ?>images/maps-and-flags.png" alt="Address"> Jl. Apaya gatau No.11, Daerah Gaada, Planet Namek, Posnya Gaboleh Tau, 12345</p>
            </div>
        </div>
    </div>
</section>

<?php 
require_once 'php/partials/footer.php'; 

// Menutup koneksi database di akhir file yang menggunakannya
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>