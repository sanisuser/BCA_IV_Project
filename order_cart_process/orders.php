<?php
$page_title = 'My Orders';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to view your orders'));
}

$user_id = (int)get_user_id();

$active_orders = [];

// Active orders: pending / shipped
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? AND status NOT IN ('delivered','cancelled') ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $active_orders[] = $row;
}
$stmt->close();

// Helper function to get status color
function getStatusClass($status) {
    return match($status) {
        'pending' => 'pending',
        'processing' => 'processing',
        'shipped' => 'dispatched',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        default => 'pending'
    };
}

// Helper function to show status label
function getStatusLabel($status) {
    return match($status) {
        'shipped' => 'Dispatched',
        'delivered' => 'Received',
        default => ucfirst((string)$status),
    };
}

// Helper function to get order status progress
function getOrderStatusProgress($status) {
    $steps = [
        'pending' => ['step' => 1, 'label' => 'Pending', 'completed' => true, 'active' => $status === 'pending'],
        'processing' => ['step' => 2, 'label' => 'Processing', 'completed' => in_array($status, ['processing', 'shipped', 'delivered']), 'active' => $status === 'processing'],
        'shipped' => ['step' => 3, 'label' => 'Dispatched', 'completed' => in_array($status, ['shipped', 'delivered']), 'active' => $status === 'shipped'],
        'delivered' => ['step' => 4, 'label' => 'Received', 'completed' => $status === 'delivered', 'active' => $status === 'delivered']
    ];
    
    if ($status === 'cancelled') {
        return [
            'pending' => ['step' => 1, 'label' => 'Pending', 'completed' => true, 'active' => false],
            'cancelled' => ['step' => 2, 'label' => 'Cancelled', 'completed' => false, 'active' => true]
        ];
    }
    
    return $steps;
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

<div class="orders-container">
    <div class="orders-header">
        <h1><i class="fas fa-receipt"></i> My Orders</h1>
        <p class="orders-count"><?php echo count($active_orders) > 0 ? ('You have ' . count($active_orders) . ' active order' . (count($active_orders) !== 1 ? 's' : '')) : 'No active orders'; ?></p>
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

    <?php if (count($active_orders) > 0): ?>
        <?php foreach ($active_orders as $order): ?>
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

            $itemCount = count($items);
            $itemNames = array_slice(array_column($items, 'title'), 0, 2);
            $itemText = implode(', ', $itemNames) . ($itemCount > 2 ? ' + ' . ($itemCount - 2) . ' more' : '');
            ?>

            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id"><?php echo htmlspecialchars($itemText !== '' ? $itemText : 'Order'); ?></div>
                        <div class="order-date"><i class="far fa-calendar-alt"></i> <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <span class="order-status <?php echo getStatusClass($order['status']); ?>">
                        <?php echo htmlspecialchars(getStatusLabel($order['status'])); ?>
                    </span>
                </div>

                <!-- Order Status Progress Bar -->
                <div class="order-progress-section">
                    <div class="progress-title">Order Status</div>
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <?php 
                            $progress = getOrderStatusProgress($order['status']);
                            $total_steps = count($progress);
                            $current_step = 0;
                            
                            foreach ($progress as $step_key => $step):
                                $current_step++;
                                $is_completed = $step['completed'];
                                $is_active = $step['active'];
                                $step_width = (100 / $total_steps);
                            ?>
                                <div class="progress-step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>"
                                     style="width: <?php echo $step_width; ?>%">
                                    <div class="step-icon">
                                        <?php if ($is_completed): ?>
                                            <i class="fas fa-check"></i>
                                        <?php else: ?>
                                            <i class="fas fa-circle"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="step-label"><?php echo htmlspecialchars($step['label']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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

                    <?php
                    $shipping_to_show = '';
                    if (!empty($order['ship_address'])) {
                        $shipping_to_show = (string)$order['ship_address'];
                    }
                    ?>

                    <?php if ($shipping_to_show !== ''): ?>
                        <div class="shipping-info">
                            <div class="shipping-label"><i class="fas fa-map-marker-alt"></i> Shipping Address</div>
                            <div class="shipping-address"><?php echo nl2br(htmlspecialchars($shipping_to_show)); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($order['admin_remark'])): ?>
                        <div class="admin-remark">
                            <div class="admin-remark-label">
                                <i class="fas fa-exclamation-circle"></i> Admin Note
                            </div>
                            <div class="admin-remark-text"><?php echo nl2br(htmlspecialchars($order['admin_remark'])); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['user_note']) && in_array($order['status'], ['delivered', 'cancelled'])): ?>
                        <div class="user-note">
                            <div class="user-note-label">
                                <i class="fas fa-comment"></i> Your Note
                            </div>
                            <div class="user-note-text"><?php echo nl2br(htmlspecialchars($order['user_note'])); ?></div>
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
                            <form method="POST" action="<?php echo SITE_URL; ?>/order_cart_process/process/order_receive.php" class="user-note-form">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                                <textarea name="user_note" rows="2" placeholder="Add a note about this order (optional)..." 
                                          class="user-note-input"></textarea>
                                <button type="submit" class="btn btn-primary btn-small"
                                        onclick="return confirm('Confirm you have received this order?');">
                                    <i class="fas fa-check"></i> Mark as Received
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="orders-empty">
            <i class="fas fa-shopping-bag"></i>
            <h2>No active orders</h2>
            <p>Your delivered/cancelled orders are available in your profile order history.</p>
            <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
