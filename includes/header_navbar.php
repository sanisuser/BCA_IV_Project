<?php
/**
 * header.php
 * 
 * HTML head section + navigation bar.
 * Include this at the start of every page.
 * 
 * Usage:
 *   require_once __DIR__ . '/../includes/header.php';
 */

// Ensure session and functions are available
// require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

// Define constants if not already defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'BookHub');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', '');
}

// Fetch user data if logged in
$current_user = null;
if (is_logged_in()) {
    require_once __DIR__ . '/db.php';
    $user_id = get_user_id();
    $stmt = $conn->prepare("SELECT username, full_name, profile_image FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Main stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/includes/css/header.css">
    
    <!-- Font Awesome for icons (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <div class="container nav-container">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
            <img src="<?php echo SITE_URL; ?>/assets/logo.png" alt="<?php echo SITE_NAME; ?> Logo" class="logo-img">
            <?php echo SITE_NAME; ?>
        </a>
        
        <!-- Search Bar -->
        <form class="search-form" action="<?php echo SITE_URL; ?>/page/booklist.php" method="GET">
            <input type="text" name="search" class="search-input" placeholder="Search books..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
        </form>
        
        <!-- Navigation Links -->
        <ul class="nav-links">
            <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a></li>

            <?php if (is_logged_in()): ?>
                 <li><a href="<?php echo SITE_URL; ?>/order_cart_process/wishlist.php">
                    <i class="fas fa-heart"></i> Wishlist
                </a></li>
                <li><a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a></li>
                <li><a href="<?php echo SITE_URL; ?>/order_cart_process/orders.php">
                    <i class="fas fa-receipt"></i> Orders
                </a></li>
               
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle user-dropdown">
                        <?php if (isset($current_user['profile_image']) && $current_user['profile_image'] !== ''): ?>
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($current_user['profile_image']); ?>" alt="Profile" class="nav-profile-img">
                        <?php else: ?>
                            <div class="nav-profile-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <span class="nav-username"><?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username'] ?? 'User'); ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo SITE_URL; ?>/auth/profile.php"><i class="fas fa-id-card"></i> My Profile</a></li>
                        <li><a href="#" onclick="toggleDarkMode(); return false;"><i class="fas fa-moon"></i> Dark Mode</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#" onclick="toggleDarkMode(); return false;"><i class="fas fa-moon"></i> Dark Mode</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>

        <div class="nav-right-mobile">
            <?php if (is_logged_in()): ?>
                <!-- Mobile Profile Link -->
                <a href="<?php echo SITE_URL; ?>/auth/profile.php" class="mobile-profile-link">
                    <?php if (isset($current_user['profile_image']) && $current_user['profile_image'] !== ''): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($current_user['profile_image']); ?>" alt="Profile" class="mobile-profile-img">
                    <?php else: ?>
                        <div class="mobile-profile-placeholder"><i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Navigation (hidden by default) -->
<div id="mobile-menu" class="mobile-menu">
    <form class="mobile-search-form" action="<?php echo SITE_URL; ?>/page/booklist.php" method="GET">
        <input type="text" name="search" class="mobile-search-input" placeholder="Search books...">
        <button type="submit" class="mobile-search-btn"><i class="fas fa-search"></i></button>
    </form>
    <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
    <a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a>
    <?php if (is_logged_in()): ?>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php">Cart</a>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/wishlist.php">Wishlist</a>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/orders.php">Orders</a>
        <a href="#" onclick="toggleDarkMode(); return false;">Dark Mode</a>
        <a href="<?php echo SITE_URL; ?>/auth/logout.php">Logout</a>
    <?php else: ?>
        <a href="#" onclick="toggleDarkMode(); return false;">Dark Mode</a>
        <a href="<?php echo SITE_URL; ?>/auth/login.php">Login</a>
    <?php endif; ?>
</div>

<!-- Dark Mode Toggle Script -->
<script>
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
    
    // Update icon
    const icon = document.querySelector('.fa-moon, .fa-sun');
    if (icon) {
        icon.classList.toggle('fa-moon', !isDark);
        icon.classList.toggle('fa-sun', isDark);
    }
}

// Check saved preference on load
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
        // Update icon if needed
        const icon = document.querySelector('.fa-moon');
        if (icon) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }
});
</script>

<!-- Navbar JS for mobile menu toggle -->
<script src="<?php echo SITE_URL; ?>/includes/js/navbar.js"></script>
