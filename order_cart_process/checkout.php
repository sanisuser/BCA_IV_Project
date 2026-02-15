<?php
$page_title = 'Checkout';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to checkout'));
}

$user_id = (int)get_user_id();

$cart_items = [];
$total = 0;

$stmt = $conn->prepare('
    SELECT c.cart_id, c.quantity, c.book_id, b.title, b.price, b.stock
    FROM cart c
    JOIN books b ON c.book_id = b.book_id
    WHERE c.user_id = ?
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}
$stmt->close();

if (count($cart_items) === 0) {
    redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Your cart is empty'));
}
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/checkout.css">

<div class="checkout-container">
    <div class="checkout-header">
        <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        <p class="checkout-subtitle">Complete your order by providing your details below</p>
    </div>

    <div class="checkout-layout">
        <!-- Checkout Form -->
        <div class="checkout-form-wrapper">
            <form method="POST" action="<?php echo SITE_URL; ?>/order_cart_process/process/order_process.php">
                <input type="hidden" name="action" value="place">

                <!-- Shipping Section -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-map-marker-alt"></i> Shipping Address</label>
                        <textarea name="shipping_address" rows="4" required class="form-textarea"
                                  placeholder="Enter your full address including street, city, and postal code..."></textarea>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-wallet"></i> Payment Method</h3>
                    
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash_on_delivery" checked>
                            <div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="payment-details">
                                <div class="payment-name">Cash on Delivery</div>
                                <div class="payment-desc">Pay when you receive your order</div>
                            </div>
                        </label>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card">
                            <div class="payment-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="payment-details">
                                <div class="payment-name">Credit/Debit Card</div>
                                <div class="payment-desc">Pay securely with your card</div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-place-order">
                    <i class="fas fa-lock"></i> Place Order
                </button>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <h3 class="summary-header"><i class="fas fa-shopping-bag"></i> Order Summary</h3>

            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <div class="summary-item-image">
                            <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                        <div class="summary-item-details">
                            <div class="summary-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="summary-item-meta">Qty: <?php echo (int)$item['quantity']; ?> × <?php echo format_price($item['price']); ?></div>
                        </div>
                        <div class="summary-item-price"><?php echo format_price(($item['price'] ?? 0) * ($item['quantity'] ?? 0)); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="summary-divider">

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

            <div class="summary-total">
                <span>Total</span>
                <span><?php echo format_price($total); ?></span>
            </div>

            <a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
