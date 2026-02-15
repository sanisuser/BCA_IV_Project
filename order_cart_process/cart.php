<?php
/**
 * pages/cart.php
 * 
 * Shopping cart page - shows items and checkout.
 */

$page_title = 'Shopping Cart';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

// Redirect if not logged in
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to view your cart'));
}

// Get cart items from database for this user
$user_id = get_user_id();
$cart_items = [];
$total = 0;

$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity, c.book_id, b.title, b.price, b.cover_image, b.stock 
    FROM cart c 
    JOIN books b ON c.book_id = b.book_id 
    WHERE c.user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}
$stmt->close();
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/cart.css">

<div class="cart-container">
    <div class="cart-header">
        <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
        <p class="cart-count"><?php echo count($cart_items); ?> item<?php echo count($cart_items) !== 1 ? 's' : ''; ?> in your cart</p>
    </div>
    
    <?php if (count($cart_items) > 0): ?>
        
        <div class="cart-layout">
            
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        
                        <!-- Book Cover -->
                        <div class="item-cover">
                            <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                        
                        <!-- Book Info -->
                        <div class="item-info">
                            <h3 class="item-title">
                                <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $item['book_id']; ?>">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            </h3>
                            <p class="item-price-unit">
                                <strong><?php echo format_price($item['price']); ?></strong> each
                            </p>
                            
                            <div class="item-quantity">
                                <?php if ($item['stock'] > 0): ?>
                                    <form action="<?php echo SITE_URL; ?>/order_cart_process/process/cart_process.php" method="POST" class="quantity-stepper-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <button type="button" class="qty-btn qty-minus" onclick="this.parentElement.querySelector('.quantity-input').value--; this.parentElement.submit();">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="quantity" 
                                               value="<?php echo min($item['quantity'], $item['stock']); ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>"
                                               class="quantity-input" readonly>
                                        <button type="button" class="qty-btn qty-plus" onclick="if(parseInt(this.parentElement.querySelector('.quantity-input').value) < <?php echo $item['stock']; ?>) { this.parentElement.querySelector('.quantity-input').value++; this.parentElement.submit(); }">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="stock-unavailable"><i class="fas fa-times-circle"></i> Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Item Total -->
                        <div class="item-total">
                            <?php echo format_price($item['price'] * $item['quantity']); ?>
                        </div>
                        
                        <!-- Remove Button -->
                        <a href="<?php echo SITE_URL; ?>/order_cart_process/process/cart_process.php?action=remove&id=<?php echo $item['cart_id']; ?>" 
                           class="btn-remove"
                           onclick="return confirm('Remove this item from cart?')"
                           title="Remove item">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary">
                <h3 class="summary-title"><i class="fas fa-receipt"></i> Order Summary</h3>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo format_price($total); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-row">
                    <span>Tax</span>
                    <span>Included</span>
                </div>
                
                <hr class="summary-divider">
                
                <div class="summary-total">
                    <span>Total</span>
                    <span><?php echo format_price($total); ?></span>
                </div>
                
                <a href="<?php echo SITE_URL; ?>/order_cart_process/checkout.php" class="btn btn-checkout">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                
                <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-continue">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
            
        </div>
        
    <?php else: ?>
        
        <div class="cart-empty">
            <i class="fas fa-shopping-basket"></i>
            <h2>Your cart is empty</h2>
            <p>Browse our collection and add some amazing books!</p>
            <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Books
            </a>
        </div>
        
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
