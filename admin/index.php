<?php
/**
 * admin/index.php
 * 
 * Admin dashboard - main control panel with sections.
 */

require_once __DIR__ . '/../includes/functions.php';

// Check admin access
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';

// Get stats
$total_books = 0;
$available_books = 0;
$total_users = 0;
$total_orders = 0;
$total_sales = 0;
$total_profit = 0;
$delivered_orders = 0;
$low_stock_books = [];

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM books");
    if ($result) {
        $total_books = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COALESCE(SUM(stock), 0) as count FROM books");
    if ($result) {
        $available_books = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $total_users = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($result) {
        $total_orders = $result->fetch_assoc()['count'];
    }
    
    // Sales and profit from delivered orders only
    $result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE status = 'delivered'");
    if ($result) {
        $row = $result->fetch_assoc();
        $delivered_orders = $row['count'];
        $total_sales = $row['total_sales'];
        // Calculate profit as 30% of sales (adjust percentage as needed)
        $total_profit = $total_sales * 0.30;
    }
    
    // Get low stock books (stock <= 5)
    $low_stock_result = $conn->query("SELECT book_id, title, stock FROM books WHERE stock <= 5 ORDER BY stock ASC");
    if ($low_stock_result) {
        while ($row = $low_stock_result->fetch_assoc()) {
            $low_stock_books[] = $row;
        }
    }
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Format currency helper
function format_currency($amount) {
    return 'Rs ' . number_format($amount, 2);
}

$page_title = 'Admin Dashboard';
$active_page = 'dashboard';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-mini">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($low_stock_books)): ?>
                <div class="alert-mini" style="background: #fff3cd; border-color: #ffc107; color: #856404; margin-bottom: 1rem;">
                    <i class="fa-solid fa-triangle-exclamation" style="color: #ffc107;"></i>
                    <strong>Low Stock Alert!</strong> 
                    <?php echo count($low_stock_books); ?> book(s) need re-stocking:
                    <a href="<?php echo SITE_URL; ?>/admin/restock.php" style="color: #856404; text-decoration: underline; margin-left: 10px;">
                        <i class="fa-solid fa-boxes-stacked"></i> Re-stock Now
                    </a>
                </div>
            <?php endif; ?>

            <div class="admin-header">
                <div>
                    <h1 style="margin: 0;">Dashboard</h1>
                    <div style="color: #6c757d; font-size: 0.9rem;">Home / Dashboard</div>
                </div>
                <div class="dash-top-actions">
                    <div class="dash-pill"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y'); ?></div>
                </div>
            </div>

            <section class="dash-metrics">
                <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="dash-card-link">
                    <article class="dash-card">
                        <div class="dash-card-row">
                            <div class="dash-card-icon"><i class="fa-solid fa-book"></i></div>
                            <div class="dash-card-main">
                                <div class="dash-card-value"><?php echo (int)$total_books; ?></div>
                                <div class="dash-card-title">Books</div>
                            </div>
                        </div>
                    </article>
                </a>

                <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="dash-card-link">
                    <article class="dash-card">
                        <div class="dash-card-row">
                            <div class="dash-card-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                            <div class="dash-card-main">
                                <div class="dash-card-value"><?php echo (int)$available_books; ?></div>
                                <div class="dash-card-title">Total Book Quantity</div>
                            </div>
                        </div>
                    </article>
                </a>

                <a href="<?php echo SITE_URL; ?>/admin/manage_users.php" class="dash-card-link">
                    <article class="dash-card">
                        <div class="dash-card-row">
                            <div class="dash-card-icon"><i class="fa-solid fa-users"></i></div>
                            <div class="dash-card-main">
                                <div class="dash-card-value"><?php echo (int)$total_users; ?></div>
                                <div class="dash-card-title">Total Users</div>
                            </div>
                        </div>
                    </article>
                </a>

                <a href="<?php echo SITE_URL; ?>/admin/manage_orders.php" class="dash-card-link">
                    <article class="dash-card">
                        <div class="dash-card-row">
                            <div class="dash-card-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                            <div class="dash-card-main">
                                <div class="dash-card-value"><?php echo (int)$total_orders; ?></div>
                                <div class="dash-card-title">Total Orders</div>
                            </div>
                        </div>
                    </article>
                </a>

                <article class="dash-card">
                    <div class="dash-card-row">
                        <div class="dash-card-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <div class="dash-card-main">
                            <div class="dash-card-value"><?php echo format_currency($total_sales); ?></div>
                            <div class="dash-card-title">Total Sales</div>
                            <div class="dash-card-sub"><?php echo (int)$delivered_orders; ?> delivered orders</div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dash-recent">
                <div class="dash-recent-head">
                    <h3 style="margin: 0;"><i class="fa-solid fa-clock-rotate-left"></i> Recently Added Books</h3>
                    <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/admin/manage_books.php">View all</a>
                </div>
                <?php
                $recent = $conn->query("SELECT title, author, created_at FROM books ORDER BY created_at DESC LIMIT 6");
                if ($recent && $recent->num_rows > 0):
                ?>
                    <ul class="dash-recent-list">
                        <?php while ($book = $recent->fetch_assoc()): ?>
                            <li class="dash-recent-item">
                                <div class="dash-recent-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="dash-recent-meta">
                                    <span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($book['author']); ?></span>
                                    <span><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($book['created_at'])); ?></span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="dash-empty">
                        <i class="fa-solid fa-inbox"></i>
                        <div>No books found</div>
                    </div>
                <?php endif; ?>
            </section>

</body>
</html>
