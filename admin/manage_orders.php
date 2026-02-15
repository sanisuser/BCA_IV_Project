
<?php
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';

$page_title = 'Manage Orders';
$active_page = 'orders';

$success = '';
$error = '';

$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$allowed_filters = ['pending', 'shipped', 'delivered', ''];
if (!in_array($status_filter, $allowed_filters, true)) {
    $status_filter = '';
}

function admin_status_label(string $status): string {
    return match ($status) {
        'shipped' => 'Dispatched',
        default => ucfirst($status),
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['status']) ? clean_input($_POST['status']) : '';

    $allowed_statuses = ['shipped'];
    if ($order_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
        $error = 'Invalid status update request.';
    } else {
        $stmt = $conn->prepare('SELECT status FROM orders WHERE order_id = ? LIMIT 1');
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $current = $res ? ($res->fetch_assoc()['status'] ?? '') : '';
        $stmt->close();

        if ($current === '') {
            $error = 'Order not found.';
        } elseif ($current !== 'pending') {
            $error = 'Only pending orders can be dispatched.';
        } else {
            $stmt = $conn->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?');
            $stmt->bind_param('si', $new_status, $order_id);
            if ($stmt->execute()) {
                $success = 'Order dispatched.';
            } else {
                $error = 'Failed to update order status.';
            }
            $stmt->close();
        }
    }
}

$orders = [];
if ($status_filter !== '') {
    $stmt = $conn->prepare('SELECT o.*, u.username, u.full_name, u.email, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.status = ? ORDER BY o.created_at DESC');
    $stmt->bind_param('s', $status_filter);
} else {
    $stmt = $conn->prepare('SELECT o.*, u.username, u.full_name, u.email, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.created_at DESC');
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>

<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="admin-header">
    <div>
        <h1 style="margin: 0;">Orders</h1>
        <div style="color: #6c757d; font-size: 0.9rem;">Home / Orders</div>
    </div>
    <div class="dash-top-actions">
        <form method="GET" action="" style="display:flex; gap: 0.5rem; align-items:center;">
            <select name="status" class="admin-input" style="min-width: 180px;">
                <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Dispatched</option>
                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
            </select>
            <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="alert-mini"><i class="fa-regular fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert-mini"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<section class="admin-card" style="padding: 0; overflow: hidden;">
    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #e9ecef; display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight: 600;">All Orders</div>
        <div style="color:#6c757d; font-size:0.9rem;">Total: <?php echo (int)count($orders); ?></div>
    </div>

    <div style="overflow:auto;">
        <table class="admin-table" style="min-width: 980px;">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th style="width: 260px;">Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) === 0): ?>
                    <tr><td colspan="6" style="padding: 1rem; color:#6c757d;">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr class="order-row">
                            <td>#<?php echo (int)$o['order_id']; ?></td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($o['full_name'] ?? $o['username']); ?></div>
                                <div style="color:#6c757d; font-size:0.85rem;"><?php echo htmlspecialchars($o['email'] ?? ''); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime((string)$o['created_at']))); ?></td>
                            <td>
                                <span class="badge" style="text-transform:capitalize;">
                                    <?php echo htmlspecialchars(admin_status_label((string)($o['status'] ?? 'pending'))); ?>
                                </span>
                            </td>
                            <td><?php echo format_price((float)($o['total_amount'] ?? 0)); ?></td>
                            <td>
                                <form method="POST" action="" style="display:flex; gap:0.5rem; align-items:center;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                    <?php if (($o['status'] ?? '') === 'pending'): ?>
                                        <select name="status" class="admin-input" style="min-width: 160px;">
                                            <option value="pending" selected disabled>Pending</option>
                                            <option value="shipped">Dispatched</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-truck"></i> Dispatch</button>
                                    <?php elseif (($o['status'] ?? '') === 'shipped'): ?>
                                        <select class="admin-input" style="min-width: 160px;" disabled>
                                            <option selected>Dispatched (locked)</option>
                                        </select>
                                        <button type="button" class="btn btn-secondary" disabled><i class="fa-solid fa-lock"></i> Locked</button>
                                    <?php elseif (($o['status'] ?? '') === 'delivered'): ?>
                                        <select class="admin-input" style="min-width: 160px;" disabled>
                                            <option selected>Delivered</option>
                                        </select>
                                        <button type="button" class="btn btn-secondary" disabled><i class="fa-solid fa-lock"></i> Locked</button>
                                    <?php else: ?>
                                        <select class="admin-input" style="min-width: 160px;" disabled>
                                            <option selected><?php echo htmlspecialchars(admin_status_label((string)($o['status'] ?? ''))); ?></option>
                                        </select>
                                        <button type="button" class="btn btn-secondary" disabled><i class="fa-solid fa-lock"></i> Locked</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <!-- Order Details Row -->
                        <tr class="order-details-row" id="details-<?php echo (int)$o['order_id']; ?>" style="display: none;">
                            <td colspan="6" style="padding: 0; background: #f8f9fa;">
                                <div style="padding: 1rem;">
                                    <?php
                                    // Fetch order items
                                    $items_stmt = $conn->prepare('
                                        SELECT oi.*, b.title, b.author, b.cover_image, b.isbn
                                        FROM order_items oi
                                        JOIN books b ON oi.book_id = b.book_id
                                        WHERE oi.order_id = ?
                                        ORDER BY oi.item_id
                                    ');
                                    $items_stmt->bind_param('i', $o['order_id']);
                                    $items_stmt->execute();
                                    $order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    $items_stmt->close();
                                    ?>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1rem;">
                                        <!-- Customer Info -->
                                        <div>
                                            <h5 style="margin: 0 0 0.75rem 0; color: #2c3e50; border-bottom: 2px solid #007bff; padding-bottom: 0.25rem;">Customer Information</h5>
                                            <div style="font-size: 0.9rem;">
                                                <div><strong>Name:</strong> <?php echo htmlspecialchars($o['full_name'] ?? $o['username']); ?></div>
                                                <div><strong>Email:</strong> <?php echo htmlspecialchars($o['email'] ?? ''); ?></div>
                                                <div><strong>Phone:</strong> <?php echo htmlspecialchars($o['user_phone'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        
                                        <!-- Shipping Address -->
                                        <div>
                                            <h5 style="margin: 0 0 0.75rem 0; color: #2c3e50; border-bottom: 2px solid #007bff; padding-bottom: 0.25rem;">Shipping Address</h5>
                                            <div style="font-size: 0.9rem; white-space: pre-line;">
                                                <?php
                                                // Try to get address from user_addresses if address_id exists
                                                if (!empty($o['address_id'])) {
                                                    $addr_stmt = $conn->prepare('SELECT * FROM user_addresses WHERE address_id = ?');
                                                    $addr_stmt->bind_param('i', $o['address_id']);
                                                    $addr_stmt->execute();
                                                    $address = $addr_stmt->get_result()->fetch_assoc();
                                                    $addr_stmt->close();
                                                    
                                                    if ($address) {
                                                        echo htmlspecialchars($address['full_name']) . "\n";
                                                        echo htmlspecialchars($address['address_line1']) . "\n";
                                                        if (!empty($address['address_line2'])) {
                                                            echo htmlspecialchars($address['address_line2']) . "\n";
                                                        }
                                                        echo htmlspecialchars($address['city']) . ", " . htmlspecialchars($address['state'] ?? '') . " " . htmlspecialchars($address['postal_code']) . "\n";
                                                        echo htmlspecialchars($address['country']);
                                                    } else {
                                                        echo 'Address not found';
                                                    }
                                                } elseif (!empty($o['shipping_address'])) {
                                                    echo htmlspecialchars($o['shipping_address']);
                                                } else {
                                                    echo 'No address information';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Items -->
                                    <h5 style="margin: 0 0 0.75rem 0; color: #2c3e50; border-bottom: 2px solid #007bff; padding-bottom: 0.25rem;">Order Items</h5>
                                    <div style="background: white; border-radius: 4px; overflow: hidden;">
                                        <?php if (empty($order_items)): ?>
                                            <div style="padding: 1rem; text-align: center; color: #6c757d;">No items found</div>
                                        <?php else: ?>
                                            <?php foreach ($order_items as $item): ?>
                                                <div style="display: flex; align-items: center; padding: 0.75rem; border-bottom: 1px solid #e9ecef;">
                                                    <img src="<?php echo SITE_URL . '/' . ($item['cover_image'] ?? 'assets/images/default-book.png'); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                         style="width: 40px; height: 56px; object-fit: cover; border-radius: 4px; margin-right: 1rem;"
                                                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-book.png'">
                                                    <div style="flex: 1;">
                                                        <div style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($item['title']); ?></div>
                                                        <div style="font-size: 0.85rem; color: #6c757d;">by <?php echo htmlspecialchars($item['author'] ?? 'Unknown'); ?></div>
                                                        <?php if (!empty($item['isbn'])): ?>
                                                            <div style="font-size: 0.85rem; color: #6c757d;">ISBN: <?php echo htmlspecialchars($item['isbn']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div style="text-align: center; margin-right: 1rem;">
                                                        <div style="font-size: 0.85rem; color: #6c757d;">Qty</div>
                                                        <div style="font-weight: 600;"><?php echo (int)$item['quantity']; ?></div>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <div style="font-size: 0.85rem; color: #6c757d;">Total</div>
                                                        <div style="font-weight: 600; color: #2c3e50;"><?php echo format_price((float)$item['price_at_time'] * (int)$item['quantity']); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <div style="padding: 0.75rem; background: #f8f9fa; text-align: right; font-weight: 600; color: #2c3e50;">
                                                Grand Total: <?php echo format_price((float)($o['total_amount'] ?? 0)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Delivered Option for Admin -->
                                    <?php if (($o['status'] ?? '') === 'shipped'): ?>
                                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef;">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                                <input type="hidden" name="status" value="delivered">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Mark this order as Delivered?')">
                                                    <i class="fa-solid fa-check"></i> Mark as Delivered
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
function toggleOrderDetails(orderId) {
    const detailsRow = document.getElementById('details-' + orderId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}

// Make order rows clickable
document.addEventListener('DOMContentLoaded', function() {
    const orderRows = document.querySelectorAll('.order-row');
    orderRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Don't toggle if clicking on form elements
            if (e.target.closest('form') || e.target.closest('button') || e.target.closest('select')) {
                return;
            }
            const orderId = this.querySelector('td').textContent.replace('#', '');
            toggleOrderDetails(orderId);
        });
    });
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

