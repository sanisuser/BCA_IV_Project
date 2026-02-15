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
$total_users = 0;
$total_orders = 0;
$bookCoverCount = 0;
$bookPdfCount = 0;

// Check table columns
$bookCols = [];
if ($result = $conn->query('SHOW COLUMNS FROM books')) {
    while ($row = $result->fetch_assoc()) {
        if (isset($row['Field'])) $bookCols[(string)$row['Field']] = true;
    }
    $result->free();
}

// Find PDF column
$pdfCol = '';
foreach (['ebook', 'pdf', 'pdf_url', 'pdf_link', 'pdf_path'] as $c) {
    if (isset($bookCols[$c])) { $pdfCol = $c; break; }
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM books");
    if ($result) {
        $total_books = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $total_users = $result->fetch_assoc()['count'];
    }
    
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($result) {
        $total_orders = $result->fetch_assoc()['count'];
    }
    
    // Cover images count
    if (isset($bookCols['cover_image'])) {
        $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE cover_image IS NOT NULL AND cover_image <> ''");
        if ($result) {
            $bookCoverCount = $result->fetch_assoc()['count'];
        }
    }
    
    // PDF count
    if ($pdfCol !== '') {
        $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE `$pdfCol` IS NOT NULL AND `$pdfCol` <> ''");
        if ($result) {
            $bookPdfCount = $result->fetch_assoc()['count'];
        }
    }
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

function pct($part, $total) {
    if (!$part || !$total) return 0;
    if ($total <= 0) return 0;
    $v = (int)round(($part / $total) * 100);
    if ($v < 0) return 0;
    if ($v > 100) return 100;
    return $v;
}

$coverPct = pct($bookCoverCount, $total_books);
$pdfPct = pct($bookPdfCount, $total_books);

$page_title = 'Admin Dashboard';
$active_page = 'dashboard';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
            <?php if (!empty($error)): ?>
                <div class="alert-mini">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="admin-header">
                <div>
                    <h1 style="margin: 0;">Dashboard</h1>
                    <div style="color: #6c757d; font-size: 0.9rem;">Home / Dashboard</div>
                </div>
                <div class="dash-top-actions">
                    <div class="dash-pill"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y'); ?></div>
                    <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/admin/manage_books.php"><i class="fa-solid fa-list"></i> View Lists</a>
                </div>
            </div>

            <section class="dash-metrics">
                <article class="dash-card">
                    <div class="dash-card-row">
                        <div class="dash-card-icon"><i class="fa-solid fa-book"></i></div>
                        <div class="dash-card-main">
                            <div class="dash-card-value"><?php echo (int)$total_books; ?></div>
                            <div class="dash-card-title">Total Books</div>
                            <div class="dash-card-sub">All books in database</div>
                        </div>
                    </div>
                    <div class="dash-progress">
                        <div class="dash-progress-bar dash-progress-blue" style="width: 100%"></div>
                    </div>
                    <div class="dash-progress-meta"><span>Books</span><strong><?php echo (int)$total_books; ?></strong></div>
                </article>

                <article class="dash-card">
                    <div class="dash-card-row">
                        <div class="dash-card-icon"><i class="fa-regular fa-image"></i></div>
                        <div class="dash-card-main">
                            <div class="dash-card-value"><?php echo (int)$bookCoverCount; ?>/<?php echo (int)$total_books; ?></div>
                            <div class="dash-card-title">Books With Cover</div>
                            <div class="dash-card-sub">Coverage availability</div>
                        </div>
                    </div>
                    <div class="dash-progress">
                        <div class="dash-progress-bar dash-progress-green" style="width: <?php echo (int)$coverPct; ?>%"></div>
                    </div>
                    <div class="dash-progress-meta"><span>Coverage</span><strong><?php echo (int)$coverPct; ?>%</strong></div>
                </article>

                <article class="dash-card">
                    <div class="dash-card-row">
                        <div class="dash-card-icon"><i class="fa-regular fa-file-pdf"></i></div>
                        <div class="dash-card-main">
                            <div class="dash-card-value"><?php echo (int)$bookPdfCount; ?>/<?php echo (int)$total_books; ?></div>
                            <div class="dash-card-title">Books With PDF</div>
                            <div class="dash-card-sub"><?php echo $pdfCol !== '' ? 'Column: ' . htmlspecialchars($pdfCol) : 'No PDF column found'; ?></div>
                        </div>
                    </div>
                    <div class="dash-progress">
                        <div class="dash-progress-bar dash-progress-amber" style="width: <?php echo (int)$pdfPct; ?>%"></div>
                    </div>
                    <div class="dash-progress-meta"><span>Availability</span><strong><?php echo (int)$pdfPct; ?>%</strong></div>
                </article>

                <article class="dash-card">
                    <div class="dash-card-row">
                        <div class="dash-card-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="dash-card-main">
                            <div class="dash-card-value"><?php echo (int)$total_users; ?></div>
                            <div class="dash-card-title">Total Users</div>
                            <div class="dash-card-sub">Registered accounts</div>
                        </div>
                    </div>
                    <div class="dash-progress">
                        <div class="dash-progress-bar dash-progress-red" style="width: 100%"></div>
                    </div>
                    <div class="dash-progress-meta"><span>Users</span><strong><?php echo (int)$total_users; ?></strong></div>
                </article>

                <article class="dash-card">
                    <div class="dash-card-row">
                        <div class="dash-card-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                        <div class="dash-card-main">
                            <div class="dash-card-value"><?php echo (int)$total_orders; ?></div>
                            <div class="dash-card-title">Total Orders</div>
                            <div class="dash-card-sub">Orders placed by users</div>
                        </div>
                    </div>
                    <div class="dash-progress">
                        <div class="dash-progress-bar dash-progress-purple" style="width: 100%"></div>
                    </div>
                    <div class="dash-progress-meta"><span>Orders</span><strong><?php echo (int)$total_orders; ?></strong></div>
                </article>
            </section>

            <section class="dash-quick">
                <div class="dash-quick-head">
                    <h3 style="margin: 0;"><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
                    <div style="color: #6c757d; font-size: 0.9rem;">Jump to common admin tasks</div>
                </div>
                <div class="dash-quick-actions">
                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-primary"><i class="fa-solid fa-book"></i> Book List</a>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php?action=edit&id=0" class="btn btn-secondary"><i class="fa-solid fa-circle-plus"></i> Add Book</a>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_users.php" class="btn btn-secondary"><i class="fa-solid fa-users"></i> User List</a>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_orders.php" class="btn btn-secondary"><i class="fa-solid fa-shopping-bag"></i> Orders</a>
                </div>
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
<?php require_once __DIR__ . '/partials/footer.php'; ?>
