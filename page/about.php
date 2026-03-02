<?php
/**
 * page/about.php
 * 
 * About Us page - Information about the website and mission.
 */

$page_title = 'About Us';

require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/page/css/about.css">

<main class="about-container">
    <div class="about-hero">
        <h1>About <?php echo SITE_NAME; ?></h1>
        <p class="tagline">Connecting readers with great books since 2026</p>
    </div>

    <div class="about-content">
        <section class="about-section">
            <h2>Our Mission</h2>
            <p>We believe that everyone deserves access to quality books at affordable prices. Our mission is to create a seamless marketplace where book lovers can discover, buy, and enjoy reading without barriers.</p>
        </section>

        <section class="about-section">
            <h2>What We Offer</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-book"></i>
                    <h3>Wide Selection</h3>
                    <p>Thousands of books across all genres, from classics to contemporary bestsellers.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Fast Delivery</h3>
                    <p>Quick and reliable shipping to get your books to you as soon as possible.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure Shopping</h3>
                    <p>Your data and transactions are protected with industry-standard security.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>Customer Support</h3>
                    <p>Friendly support team ready to help with any questions or concerns.</p>
                </div>
            </div>
        </section>

        <section class="about-section">
            <h2>Contact Us</h2>
            <p>Have questions? We'd love to hear from you. Reach out to us at:</p>
            <ul class="contact-list">
                <li><i class="fas fa-envelope"></i> Email: support@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com</li>
                <li><i class="fas fa-phone"></i> Phone: +1 (555) 123-4567</li>
                <li><i class="fas fa-map-marker-alt"></i> Address: 123 Book Street, Reading City, RC 12345</li>
            </ul>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
