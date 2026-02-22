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

$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$is_book_view_page = ($current_page === 'book.php');
$is_home_page = ($current_page === 'index.php');
$hide_search_bar_pages = ['profile.php', 'cart.php', 'checkout.php', 'orders.php'];
$hide_search_bar = in_array($current_page, $hide_search_bar_pages, true);
$navbar_page_class = ($is_book_view_page ? ' is-book-view' : '') . ($is_home_page ? ' is-home' : '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/logo.png">
    
    <!-- Main stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/includes/css/header.css">
    
    <!-- Global Dark Theme -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/theme.css">
    
    <!-- Font Awesome for icons (LOCAL - Offline) -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/fontawesome/css/all.min.css">
    <!-- Search Suggestions Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/includes/css/search-suggestions.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar<?php echo $navbar_page_class; ?>">
    <div class="container nav-container">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
            <img src="<?php echo SITE_URL; ?>/assets/logo.png" alt="<?php echo SITE_NAME; ?> Logo" class="logo-img">
            <?php echo SITE_NAME; ?>
        </a>
        
        <?php if (!$hide_search_bar): ?>
        <!-- Search Bar -->
        <form class="search-form" action="<?php echo SITE_URL; ?>/page/booklist.php" method="GET" style="position: relative;">
            <input type="text" id="search-input" name="search" class="search-input" placeholder="Search books..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" autocomplete="off">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            <!-- Search Suggestions Dropdown -->
            <div id="search-suggestions" class="search-suggestions"></div>
        </form>
        <?php endif; ?>
        
        <!-- Navigation Links -->
        <ul class="nav-links">
            <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a></li>

            <?php if (is_logged_in()): ?>
                <?php if (is_admin_panel_access()): ?>
                <li><a href="<?php echo SITE_URL; ?>/admin/index.php" style="color: var(--accent, #eab308);">
                    <i class="fas fa-shield-alt"></i> Admin Panel
                </a></li>
                <?php endif; ?>
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
                        <li><a href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <ul class="dropdown-menu">
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
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="mobile-nav-link" aria-label="Books">
                    Books
                </a>
                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="mobile-profile-link" aria-label="Login">
                    <div class="mobile-profile-placeholder"><i class="fas fa-sign-in-alt"></i></div>
                </a>
            <?php endif; ?>

            <?php if (is_logged_in()): ?>
            <!-- Mobile Menu Toggle -->
            <button class="mobile-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Navigation (hidden by default) -->
<div id="mobile-menu" class="mobile-menu<?php echo $navbar_page_class; ?>">
    <?php if (is_logged_in()): ?>
        <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
        <a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php">Cart</a>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/wishlist.php">Wishlist</a>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/orders.php">Orders</a>
        <a href="<?php echo SITE_URL; ?>/auth/logout.php">Logout</a>
    <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a>
        <a href="<?php echo SITE_URL; ?>/auth/login.php">Login</a>
    <?php endif; ?>
</div>

<!-- Live Search Functionality -->
<script>
(function() {
    const searchInput = document.getElementById('search-input');
    const suggestionsContainer = document.getElementById('search-suggestions');
    
    if (!searchInput || !suggestionsContainer) return;

    function setupLiveSearch(inputEl, containerEl) {
        if (!inputEl || !containerEl) return;

        let debounceTimer;
        let currentFocus = -1;

        function addActive(items) {
            items.forEach(item => item.classList.remove('active'));
            if (currentFocus >= 0 && currentFocus < items.length) {
                items[currentFocus].classList.add('active');
                items[currentFocus].scrollIntoView({ block: 'nearest' });
            }
        }

        function debouncedSearch(query) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (query.length < 2) {
                    containerEl.classList.remove('active');
                    containerEl.innerHTML = '';
                    return;
                }

                containerEl.innerHTML = '<div class="search-suggestion-loading"><i class="fas fa-spinner"></i> Searching...</div>';
                containerEl.classList.add('active');

                fetch('<?php echo SITE_URL; ?>/includes/search_suggestions.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            containerEl.innerHTML = '<div class="search-suggestion-no-results">No books found</div>';
                        } else {
                            containerEl.innerHTML = data.map((book, index) => `
                                <a href="<?php echo SITE_URL; ?>/page/book.php?id=${book.id}" class="search-suggestion-item" data-index="${index}">
                                    <img src="${book.cover}" alt="${book.title}" class="search-suggestion-cover" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-book.png'">
                                    <div class="search-suggestion-info">
                                        <div class="search-suggestion-title">${book.title}</div>
                                        <div class="search-suggestion-author">by ${book.author || 'Unknown'}</div>
                                    </div>
                                </a>
                            `).join('');
                            currentFocus = -1;
                        }
                    })
                    .catch(() => {
                        containerEl.innerHTML = '<div class="search-suggestion-no-results">Error loading suggestions</div>';
                    });
            }, 300);
        }

        inputEl.addEventListener('input', function() {
            debouncedSearch(this.value.trim());
        });

        inputEl.addEventListener('keydown', function(e) {
            const items = containerEl.querySelectorAll('.search-suggestion-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentFocus++;
                if (currentFocus >= items.length) currentFocus = 0;
                addActive(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentFocus--;
                if (currentFocus < 0) currentFocus = items.length - 1;
                addActive(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentFocus > -1 && items[currentFocus]) {
                    items[currentFocus].click();
                } else {
                    this.closest('form').submit();
                }
            } else if (e.key === 'Escape') {
                containerEl.classList.remove('active');
                currentFocus = -1;
            }
        });

        inputEl.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                debouncedSearch(this.value.trim());
            }
        });

        document.addEventListener('click', function(e) {
            if (!inputEl.contains(e.target) && !containerEl.contains(e.target)) {
                containerEl.classList.remove('active');
            }
        });
    }

    setupLiveSearch(searchInput, suggestionsContainer);
})();

// Dropdown Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = this.closest('.dropdown');
            const menu = dropdown.querySelector('.dropdown-menu');
            const isActive = menu.classList.contains('active');
            
            // Close all other dropdowns first
            document.querySelectorAll('.dropdown-menu.active').forEach(otherMenu => {
                otherMenu.classList.remove('active');
            });
            
            // Only open if it wasn't already active (clicking profile keeps it open)
            if (!isActive) {
                menu.classList.add('active');
            }
        });
    });
    
    // Close dropdown only when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
});
</script>

<!-- Navbar JS for mobile menu toggle -->
<script src="<?php echo SITE_URL; ?>/includes/js/navbar.js"></script>
