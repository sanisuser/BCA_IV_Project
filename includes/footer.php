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
            <!-- About -->
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p>Your one-stop destination for books. Discover, buy, and enjoy reading!</p>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/books.php">Browse Books</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/cart.php">Shopping Cart</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div class="footer-section">
                <h4>Contact</h4>
                <ul>
                    <li><i class="fas fa-envelope"></i> crm.bookhub@gmail.com</li>
                    <li><i class="fas fa-phone"></i> +977 98XXXXXXXX</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
