<?php
if (!isset($page_title)) {
    $page_title = 'Admin';
}

// Ensure session and functions are available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/functions.php';

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
    require_once __DIR__ . '/../../includes/db.php';
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/logo.png">
    <!-- Fonts loaded via local CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/includes/css/header.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/css/admin.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/fontawesome/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container nav-container">
            <!-- Logo -->
            <a href="<?php echo SITE_URL; ?>/admin/index.php" class="logo">
                <img src="<?php echo SITE_URL; ?>/assets/logo.png" alt="<?php echo SITE_NAME; ?> Logo" class="logo-img">
                <?php echo SITE_NAME; ?> Admin
            </a>
            
            <!-- Search Bar -->
            <form class="search-form" action="<?php echo SITE_URL; ?>/admin/manage_books.php" method="GET">
                <input type="text" name="search" class="search-input" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>
            
            <!-- Navigation Links -->
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Site</a></li>
                
                <?php if (is_logged_in()): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle user-dropdown">
                            <?php if (isset($current_user['profile_image']) && $current_user['profile_image'] !== ''): ?>
                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($current_user['profile_image']); ?>" alt="Profile" class="nav-profile-img">
                            <?php else: ?>
                                <div class="nav-profile-placeholder"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <span class="nav-username"><?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username'] ?? 'User'); ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo SITE_URL; ?>/auth/profile.php"><i class="fas fa-id-card"></i> My Profile</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Back to Site</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <?php endif; ?>
            </ul>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Navigation (hidden by default) -->
    <div id="mobile-menu" class="mobile-menu">
        <a href="<?php echo SITE_URL; ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?php echo SITE_URL; ?>/admin/manage_books.php"><i class="fas fa-book"></i> Manage Books</a>
        <a href="<?php echo SITE_URL; ?>/admin/manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
        <a href="<?php echo SITE_URL; ?>/admin/manage_orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
        <a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Back to Site</a>
        <?php if (is_logged_in()): ?>
            <a href="<?php echo SITE_URL; ?>/auth/profile.php"><i class="fas fa-id-card"></i> My Profile</a>
            <a href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <?php endif; ?>
    </div>

    <!-- Navbar JS for mobile menu toggle -->
    <script src="<?php echo SITE_URL; ?>/includes/js/navbar.js"></script>
    
    <!-- Admin Sidebar Toggle Script -->
    <script>
    function toggleAdminSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        sidebar.classList.toggle('active');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggleBtn = document.querySelector('.mobile-sidebar-toggle');
        if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && (!toggleBtn || !toggleBtn.contains(e.target))) {
                sidebar.classList.remove('active');
            }
        }
    });
    </script>

    <div class="admin-container">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <main class="admin-main">
            <!-- Mobile Sidebar Toggle -->
            <button type="button" class="mobile-sidebar-toggle" onclick="toggleAdminSidebar()">
                <i class="fas fa-bars"></i> Menu
            </button>
