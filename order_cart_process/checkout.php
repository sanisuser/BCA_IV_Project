<?php
$page_title = 'Checkout';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to checkout'));
}

$user_id = (int)get_user_id();

// Fetch user's profile shipping address and coordinates
$profile_ship_address = '';
$profile_ship_lat = '';
$profile_ship_lng = '';
$profile_ship_place_name = '';
$ship_stmt = $conn->prepare("SELECT ship_address, ship_latitude, ship_longitude, ship_place_name FROM users WHERE user_id = ?");
$ship_stmt->bind_param('i', $user_id);
$ship_stmt->execute();
$ship_result = $ship_stmt->get_result();
if ($ship_row = $ship_result->fetch_assoc()) {
    $profile_ship_address = $ship_row['ship_address'] ?? '';
    $profile_ship_lat = $ship_row['ship_latitude'] ?? '';
    $profile_ship_lng = $ship_row['ship_longitude'] ?? '';
    $profile_ship_place_name = $ship_row['ship_place_name'] ?? '';
}
$ship_stmt->close();

// Check if user has a profile shipping address
$has_profile_address = !empty($profile_ship_address);

$cart_items = [];
$total = 0;

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

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

require_once __DIR__ . '/../includes/header_navbar.php';
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

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

                <!-- Shipping Section -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                    
                    <?php if ($has_profile_address): ?>
                    <!-- Display Profile Shipping Address -->
                    <div class="address-display" style="background: #f8f9fa; border: 2px solid #007bff; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <h4 style="margin: 0; color: #333;"><i class="fas fa-home"></i> Your Shipping Address</h4>
                            <a href="<?php echo SITE_URL; ?>/auth/profile.php" class="btn-edit-address" style="background: #007bff; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                        <div class="address-content" style="background: white; padding: 12px; border-radius: 6px; border: 1px solid #dee2e6;">
                            <p style="margin: 0; font-size: 1rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($profile_ship_address)); ?></p>
                            <?php if (!empty($profile_ship_place_name)): ?>
                            <small style="color: #6c757d; display: block; margin-top: 8px;">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($profile_ship_place_name); ?>
                            </small>
                            <?php endif; ?>
                            <?php if (!empty($profile_ship_lat) && !empty($profile_ship_lng)): ?>
                            <small style="color: #28a745; display: block; margin-top: 4px;">
                                <i class="fas fa-check-circle"></i> Location verified on map
                            </small>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="selected_address" value="profile">
                    </div>
                    <?php else: ?>
                    <!-- Error: No shipping address set -->
                    <div class="address-error" style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin-bottom: 16px; text-align: center;">
                        <div style="font-size: 2rem; color: #ffc107; margin-bottom: 12px;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4 style="margin: 0 0 12px 0; color: #856404;">Shipping Address Not Set</h4>
                        <p style="margin: 0 0 16px 0; color: #856404; font-size: 0.95rem;">
                            Please set your shipping address in your profile before placing an order.
                        </p>
                        <a href="<?php echo SITE_URL; ?>/auth/profile.php" class="btn-set-address" style="background: #007bff; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 500;">
                            <i class="fas fa-map-marker-alt"></i> Set Shipping Address
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Order Summary (Mobile Only) -->
                <div class="mobile-order-summary">
                    <h3 class="summary-header"><i class="fas fa-shopping-bag"></i> Order Summary</h3>

                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item"> 
                                <div class="summary-item-image">
                                    <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
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

                <?php if ($has_profile_address): ?>
                <button type="submit" class="btn-place-order">
                    <i class="fas fa-lock"></i> Place Order
                </button>
                <?php else: ?>
                <button type="button" class="btn-place-order" disabled style="opacity: 0.6; cursor: not-allowed; background: #6c757d;">
                    <i class="fas fa-lock"></i> Set Address to Place Order
                </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Order Summary (Desktop Only) -->
        <div class="order-summary desktop-summary">
            <h3 class="summary-header"><i class="fas fa-shopping-bag"></i> Order Summary</h3>

            <div class="summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item"> 
                        <div class="summary-item-image">
                            <img src="<?php echo SITE_URL . '/' . get_book_cover($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
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
// Simple checkout validation - profile address is used automatically
document.addEventListener('DOMContentLoaded', function() {
    // Profile shipping address is required and pre-filled
    // No additional JavaScript needed for address selection
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
