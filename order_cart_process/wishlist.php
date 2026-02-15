<?php
$page_title = 'My Wishlist';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to view your wishlist'));
}

$user_id = (int)get_user_id();

$items = [];
$stmt = $conn->prepare('
    SELECT w.book_id, w.created_at, b.title, b.author, b.price, b.cover_image, b.stock
    FROM wishlist w
    JOIN books b ON w.book_id = b.book_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/wishlist.css">

<div class="wishlist-container">
    <div class="wishlist-header">
        <h1><i class="fas fa-heart"></i> My Wishlist</h1>
        <p class="wishlist-count"><?php echo count($items); ?> book<?php echo count($items) !== 1 ? 's' : ''; ?> saved</p>
    </div>

    <?php if (count($items) > 0): ?>
        <div class="wishlist-grid">
            <?php foreach ($items as $item): ?>
                <div class="wishlist-card">
                    <div class="wishlist-cover">
                        <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        
                        <?php if (($item['stock'] ?? 0) <= 0): ?>
                            <span class="stock-badge out-of-stock">Out of Stock</span>
                        <?php elseif (($item['stock'] ?? 0) <= 5): ?>
                            <span class="stock-badge in-stock">Only <?php echo $item['stock']; ?> left</span>
                        <?php endif; ?>
                        
                        <a href="<?php echo SITE_URL; ?>/order_cart_process/process/wishlist_process.php?action=remove&id=<?php echo (int)$item['book_id']; ?>" 
                           class="remove-btn" 
                           onclick="return confirm('Remove from wishlist?')"
                           title="Remove from wishlist">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                    
                    <div class="wishlist-content">
                        <h3 class="wishlist-title">
                            <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo (int)$item['book_id']; ?>">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </h3>
                        <p class="wishlist-author">by <?php echo htmlspecialchars($item['author'] ?? 'Unknown Author'); ?></p>
                        <div class="wishlist-price"><?php echo format_price($item['price'] ?? 0); ?></div>
                        
                        <div class="wishlist-actions">
                            <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo (int)$item['book_id']; ?>" class="btn btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if (($item['stock'] ?? 0) > 0): ?>
                                <a class="btn btn-cart" href="<?php echo SITE_URL; ?>/order_cart_process/process/cart_process.php?action=add&id=<?php echo (int)$item['book_id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="wishlist-empty">
            <i class="fas fa-heart-broken"></i>
            <h2>Your wishlist is empty</h2>
            <p>Browse our collection and save your favorite books!</p>
            <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Books
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
