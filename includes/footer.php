<?php
/**
 * footer.php
 * 
 * Footer section and closing tags.
 * Include this at the end of every page.
 * 
 * Usage:
 *   require_once __DIR__ . '/includes/footer.php';
 */
?>

<!-- Footer CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/includes/css/footer.css">

</main>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Your one-stop destination for books. Discover, buy, and enjoy reading!</p>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/page/booklist.php">Browse Books</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php">Cart</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/order_cart_process/wishlist.php">Wishlist</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/legal/privacy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/legal/terms.php">Terms &amp; Conditions</a></li>
                    <!-- <li><a href="<?php echo SITE_URL; ?>/legal/refund.php">Refund Policy</a></li> -->
                </ul>
            </div>

            <div class="footer-section">
                <h4>About Us</h4>
                <p>Learn more about our mission to connect readers with great books.</p>
                <a href="<?php echo SITE_URL; ?>/page/about.php" class="footer-btn">
                    <span>Read More</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
