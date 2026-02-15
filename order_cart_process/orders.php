<?php
$page_title = 'My Orders';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to view your orders'));
}

$user_id = (int)get_user_id();

$orders = [];
$stmt = $conn->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Helper function to get status color
function getStatusClass($status) {
    return match($status) {
        'pending' => 'pending',
        'processing' => 'processing',
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        default => 'pending'
    };
}

// Helper function to show status label
function getStatusLabel($status) {
    return match($status) {
        'shipped' => 'Dispatched',
        default => ucfirst((string)$status),
    };
}

// Get payment method icon
function getPaymentIcon($method) {
    return match($method) {
        'cash_on_delivery' => 'fa-money-bill-wave',
        'card' => 'fa-credit-card',
        default => 'fa-wallet'
    };
}
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/order_cart_process/css/orders.css">

<div class="orders-container">
    <div class="orders-header">
        <h1><i class="fas fa-receipt"></i> My Orders</h1>
        <p class="orders-count"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?> placed</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
            <?php
            // Get order items with book details
            $items = [];
            $stmt = $conn->prepare('
                SELECT oi.quantity, oi.price_at_time, b.book_id, b.title, b.cover_image
                FROM order_items oi
                JOIN books b ON oi.book_id = b.book_id
                WHERE oi.order_id = ?
            ');
            $oid = (int)$order['order_id'];
            $stmt->bind_param('i', $oid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $items[] = $r;
            }
            $stmt->close();
            ?>

            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id">Order #<?php echo (int)$order['order_id']; ?></div>
                        <div class="order-date"><i class="far fa-calendar-alt"></i> <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <span class="order-status <?php echo getStatusClass($order['status']); ?>">
                        <?php echo htmlspecialchars(getStatusLabel($order['status'])); ?>
                    </span>
                </div>

                <div class="order-body">
                    <div class="order-items">
                        <?php foreach ($items as $it): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="<?php echo SITE_URL . '/' . get_book_cover($it['cover_image']); ?>" alt="<?php echo htmlspecialchars($it['title']); ?>">
                                </div>
                                <div class="item-details">
                                    <div class="item-title"><?php echo htmlspecialchars($it['title']); ?></div>
                                    <div class="item-meta">Qty: <?php echo (int)$it['quantity']; ?> × <?php echo format_price($it['price_at_time']); ?></div>
                                </div>
                                <div class="item-price"><?php echo format_price(($it['price_at_time'] ?? 0) * ($it['quantity'] ?? 0)); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($order['shipping_address'])): ?>
                        <div class="shipping-info">
                            <div class="shipping-label"><i class="fas fa-map-marker-alt"></i> Shipping Address</div>
                            <div class="shipping-address"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-footer">
                    <div class="payment-method">
                        <i class="fas <?php echo getPaymentIcon($order['payment_method']); ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'Unknown')); ?>
                    </div>
                    <div class="order-total">
                        <span class="order-total-label">Total:</span>
                        <span class="order-total-value"><?php echo format_price($order['total_amount']); ?></span>
                    </div>
                    <?php if (($order['status'] ?? '') === 'shipped'): ?>
                        <div class="order-actions">
                            <a href="<?php echo SITE_URL; ?>/order_cart_process/process/order_receive.php?order_id=<?php echo (int)$order['order_id']; ?>" 
                               class="btn btn-primary btn-small"
                               onclick="return confirm('Confirm you have received this order?');">
                                <i class="fas fa-check"></i> Received
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="orders-empty">
            <i class="fas fa-shopping-bag"></i>
            <h2>No orders yet</h2>
            <p>Once you place an order, it will appear here.</p>
            <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
