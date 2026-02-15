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

// Fetch user addresses
$addresses = [];
$addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addr_stmt->bind_param('i', $user_id);
$addr_stmt->execute();
$addr_result = $addr_stmt->get_result();
while ($addr_row = $addr_result->fetch_assoc()) {
    $addresses[] = $addr_row;
}
$addr_stmt->close();

$stmt = $conn->prepare('
    SELECT c.cart_id, c.quantity, c.book_id, b.title, b.price, b.stock, b.cover_image
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
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/address.css">

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
                    
                    <?php if (count($addresses) > 0): ?>
                        <div class="address-selection">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Select Shipping Address</label>
                            <?php foreach ($addresses as $index => $addr): ?>
                                <div class="address-option">
                                    <label class="address-radio-label">
                                        <input type="radio" name="selected_address" value="<?php echo $addr['address_id']; ?>" 
                                               <?php echo ($addr['is_default'] || $index === 0) ? 'checked' : ''; ?>
                                               class="address-radio"
                                               onchange="toggleCustomAddress(this)">
                                        <div class="address-card">
                                            <div class="address-header">
                                                <span class="address-type"><?php echo ucfirst($addr['address_type']); ?></span>
                                                <?php if ($addr['is_default']): ?>
                                                    <span class="default-badge">Default</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="address-details">
                                                <p><strong><?php echo htmlspecialchars($addr['full_name']); ?></strong></p>
                                                <p><?php echo nl2br(htmlspecialchars($addr['address_line1'])); ?></p>
                                                <?php if (!empty($addr['address_line2'])): ?>
                                                    <p><?php echo nl2br(htmlspecialchars($addr['address_line2'])); ?></p>
                                                <?php endif; ?>
                                                <p><?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['state'] ?? ''); ?> <?php echo htmlspecialchars($addr['postal_code']); ?></p>
                                                <p><?php echo htmlspecialchars($addr['country']); ?></p>
                                                <?php if (!empty($addr['phone'])): ?>
                                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($addr['phone']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="address-option">
                                <label class="address-radio-label">
                                    <input type="radio" name="selected_address" value="custom" class="address-radio" onchange="toggleCustomAddress(this)">
                                    <div class="address-card custom-address-card">
                                        <div class="address-header">
                                            <span class="address-type">Custom Address</span>
                                        </div>
                                        <div class="address-details">
                                            <p><i class="fas fa-plus"></i> Enter a new shipping address</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Custom Address Form (Hidden by default) -->
                        <div id="custom-address-form" style="display: none;" class="custom-address-section">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-map-marker-alt"></i> Custom Shipping Address</label>
                                <textarea name="shipping_address" rows="4" class="form-textarea"
                                          placeholder="Enter your full address including street, city, and postal code..."></textarea>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- No saved addresses, show custom form -->
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Shipping Address</label>
                            <textarea name="shipping_address" rows="4" required class="form-textarea"
                                      placeholder="Enter your full address including street, city, and postal code..."></textarea>
                        </div>
                    <?php endif; ?>
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
                        
                        <label class="payment-option" style="opacity: 0.6; cursor: not-allowed;">
                            <input type="radio" name="payment_method" value="card" disabled>
                            <div class="payment-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="payment-details">
                                <div class="payment-name">Credit/Debit Card</div>
                                <div class="payment-desc" style="color: #dc3545;">
                                    <i class="fas fa-info-circle"></i> Coming Soon - Not available yet
                                </div>
                                <div class="payment-notice" style="font-size: 0.8rem; color: #6c757d; margin-top: 4px;">
                                    This payment system is under development. Tune in for future updates!
                                </div>
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
                <span>VAT (13%)</span>
                <span><?php echo format_price($total * 0.13); ?></span>
            </div>

            <div class="summary-total">
                <span>Total (incl. VAT)</span>
                <span><?php echo format_price($total * 1.13); ?></span>
            </div>

            <a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
        </div>
    </div>
</div>

<script>
function toggleCustomAddress(radio) {
    const customForm = document.getElementById('custom-address-form');
    if (radio.value === 'custom') {
        customForm.style.display = 'block';
        // Make the textarea required when custom is selected
        const textarea = customForm.querySelector('textarea');
        if (textarea) textarea.required = true;
    } else {
        customForm.style.display = 'none';
        // Remove required when custom is not selected
        const textarea = customForm.querySelector('textarea');
        if (textarea) textarea.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkedRadio = document.querySelector('input[name="selected_address"]:checked');
    if (checkedRadio) {
        toggleCustomAddress(checkedRadio);
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
