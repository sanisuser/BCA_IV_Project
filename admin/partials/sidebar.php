<?php
if (!isset($active_page)) {
    $active_page = '';
}
?>
<aside class="admin-sidebar">
    <nav>
        <a href="<?php echo SITE_URL; ?>/admin/index.php" class="<?php echo $active_page === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="<?php echo $active_page === 'books' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Manage Books</a>
        <a href="<?php echo SITE_URL; ?>/admin/manage_users.php" class="<?php echo $active_page === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Users</a>
        <a href="<?php echo SITE_URL; ?>/admin/manage_orders.php" class="<?php echo $active_page === 'orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag"></i> Orders</a>
        <a href="<?php echo SITE_URL; ?>/index.php" style="margin-top: auto; border-top: 1px solid #34495e;"><i class="fas fa-arrow-left"></i> Back to Site</a>
        <a href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
