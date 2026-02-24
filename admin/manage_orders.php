
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
$allowed_filters = ['pending', 'shipped', 'delivered', 'cancelled', ''];
if (!in_array($status_filter, $allowed_filters, true)) {
    $status_filter = '';
}

$search_column = isset($_GET['column']) ? clean_input($_GET['column']) : 'all';
$q = trim($_GET['q'] ?? '');

// Validate search column
$allowed_columns = ['all', 'order_id', 'username', 'full_name', 'total_amount'];
if (!in_array($search_column, $allowed_columns, true)) {
    $search_column = 'all';
}

$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';

// Validate sort column
$allowed_sort = ['order_id', 'created_at', 'total_amount', 'status'];
if (!in_array($sort, $allowed_sort, true)) {
    $sort = 'created_at';
}

$allowed_order = ['ASC', 'DESC'];
if (!in_array($order, $allowed_order, true)) {
    $order = 'DESC';
}

// Helper function to generate sort URL
function sort_url(string $col, string $current_sort, string $current_order, string $status_filter, string $q, string $search_column): string {
    $new_order = ($current_sort === $col && $current_order === 'DESC') ? 'ASC' : 'DESC';
    $params = [
        'sort' => $col,
        'order' => $new_order
    ];
    if ($status_filter !== '') {
        $params['status'] = $status_filter;
    }
    if ($q !== '') {
        $params['q'] = $q;
        $params['column'] = $search_column;
    }
    return SITE_URL . '/admin/manage_orders.php?' . http_build_query($params);
}

// Helper function for sort icon
function sort_icon(string $col, string $current_sort, string $current_order): string {
    if ($current_sort !== $col) {
        return '<i class="fas fa-sort" style="color: #adb5bd; margin-left: 0.25rem;"></i>';
    }
    return $current_order === 'ASC' 
        ? '<i class="fas fa-sort-up" style="color: #3498db; margin-left: 0.25rem;"></i>'
        : '<i class="fas fa-sort-down" style="color: #3498db; margin-left: 0.25rem;"></i>';
}

function admin_status_label(string $status): string {
    return match ($status) {
        'shipped' => 'Dispatched',
        'cancelled' => 'Cancelled',
        default => ucfirst($status),
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['status']) ? clean_input($_POST['status']) : '';
    $admin_remark = isset($_POST['admin_remark']) ? clean_input($_POST['admin_remark']) : '';

    $allowed_statuses = ['shipped', 'cancelled'];
    if ($order_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
        $error = 'Invalid status update request.';
    } elseif (empty($admin_remark)) {
        $error = 'Remark is required when updating order status.';
    } else {
        $stmt = $conn->prepare('SELECT status FROM orders WHERE order_id = ? LIMIT 1');
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $current = $res ? ($res->fetch_assoc()['status'] ?? '') : '';
        $stmt->close();

        if ($current === '') {
            $error = 'Order not found.';
        } elseif ($current === 'delivered' || $current === 'cancelled') {
            $error = 'Delivered or cancelled orders cannot be modified.';
        } elseif ($current !== 'pending' && $new_status === 'shipped') {
            $error = 'Only pending orders can be dispatched.';
        } elseif ($current === 'shipped' && $new_status === 'cancelled') {
            $error = 'Shipped orders cannot be cancelled.';
        } else {
            $conn->begin_transaction();
            try {
                // If cancelling, restore stock first
                if ($new_status === 'cancelled') {
                    $items_stmt = $conn->prepare('SELECT book_id, quantity FROM order_items WHERE order_id = ?');
                    $items_stmt->bind_param('i', $order_id);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();
                    $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
                    $items_stmt->close();

                    $restore_stmt = $conn->prepare('UPDATE books SET stock = stock + ? WHERE book_id = ?');
                    foreach ($order_items as $item) {
                        $restore_stmt->bind_param('ii', $item['quantity'], $item['book_id']);
                        $restore_stmt->execute();
                    }
                    $restore_stmt->close();
                }

                $stmt = $conn->prepare('UPDATE orders SET status = ?, admin_remark = ?, updated_at = NOW() WHERE order_id = ?');
                $stmt->bind_param('ssi', $new_status, $admin_remark, $order_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $msg = $new_status === 'cancelled' ? 'cancelled' : 'dispatched';
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=' . $msg);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Failed to update order status: ' . $e->getMessage();
            }
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'] === 'cancelled' ? 'Order cancelled successfully.' : 'Order dispatched.';
}

$orders = [];
$query_base = "SELECT o.*, u.username, u.full_name, u.email, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.user_id";
$where_clause = '';
$params = [];
$types = '';

// Build WHERE clause
if ($status_filter !== '') {
    $where_clause = " WHERE o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($q !== '') {
    if ($search_column === 'all') {
        $where_clause = $where_clause ? $where_clause . " AND (o.order_id LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)" : " WHERE (o.order_id LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    } elseif ($search_column === 'order_id') {
        $where_clause = $where_clause ? $where_clause . " AND o.order_id = ?" : " WHERE o.order_id = ?";
        $params[] = (int)$q;
        $types .= 'i';
    } elseif ($search_column === 'total_amount') {
        $where_clause = $where_clause ? $where_clause . " AND o.total_amount = ?" : " WHERE o.total_amount = ?";
        $params[] = (float)$q;
        $types .= 'd';
    } else {
        $where_clause = $where_clause ? $where_clause . " AND u.$search_column LIKE ?" : " WHERE u.$search_column LIKE ?";
        $like = '%' . $q . '%';
        $params[] = $like;
        $types .= 's';
    }
}

$query = $query_base . $where_clause . " ORDER BY $sort $order";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
        <form method="GET" action="" style="display:flex; gap: 0.5rem; align-items:center; flex-wrap: wrap;">
            <select name="column" class="admin-input" style="min-width: 120px;">
                <option value="all" <?php echo $search_column === 'all' ? 'selected' : ''; ?>>All Columns</option>
                <option value="order_id" <?php echo $search_column === 'order_id' ? 'selected' : ''; ?>>Order ID</option>
                <option value="username" <?php echo $search_column === 'username' ? 'selected' : ''; ?>>Username</option>
                <option value="full_name" <?php echo $search_column === 'full_name' ? 'selected' : ''; ?>>Full Name</option>
                <option value="total_amount" <?php echo $search_column === 'total_amount' ? 'selected' : ''; ?>>Total Amount</option>
            </select>
            <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search..." class="admin-input" style="min-width: 200px;" />
            <select name="status" class="admin-input" style="min-width: 180px;">
                <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Dispatched</option>
                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="<?php echo SITE_URL; ?>/admin/manage_orders.php" class="btn btn-secondary">Clear</a>
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
        <table class="admin-table" style="min-width: 980px; border-collapse: collapse; width: 100%;">
            <thead style="background-color: #f5f5f5; border-bottom: 2px solid #ccc;">
                <tr>
                    <th style="padding: 10px; text-align: center; cursor: pointer;" class="order-id-divider">
                        <a href="<?php echo sort_url('order_id', $sort, $order, $status_filter, $q, $search_column); ?>" 
                        style="text-decoration: none; color: #333; display: inline-flex; align-items: center; gap: 0.25rem;">
                            Order <?php echo sort_icon('order_id', $sort, $order); ?>
                        </a>
                    </th>
                    <th style="padding: 10px; text-align: left;">User</th>
                    <th style="padding: 10px; text-align: center; cursor: pointer;">
                        <a href="<?php echo sort_url('created_at', $sort, $order, $status_filter, $q, $search_column); ?>" 
                        style="text-decoration: none; color: #333; display: inline-flex; align-items: center; gap: 0.25rem;">
                            Date <?php echo sort_icon('created_at', $sort, $order); ?>
                        </a>
                    </th>
                    <th style="padding: 10px; text-align: center; cursor: pointer;">
                        <a href="<?php echo sort_url('status', $sort, $order, $status_filter, $q, $search_column); ?>" 
                        style="text-decoration: none; color: #333; display: inline-flex; align-items: center; gap: 0.25rem;">
                            Status <?php echo sort_icon('status', $sort, $order); ?>
                        </a>
                    </th>
                    <th style="padding: 10px; text-align: center; cursor: pointer;">
                        <a href="<?php echo sort_url('total_amount', $sort, $order, $status_filter, $q, $search_column); ?>" 
                        style="text-decoration: none; color: #333; display: inline-flex; align-items: center; gap: 0.25rem;">
                            Total <?php echo sort_icon('total_amount', $sort, $order); ?>
                        </a>
                    </th>
                    <th style="width: 260px; padding: 10px; text-align: center;">Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) === 0): ?>
                    <tr><td colspan="7" style="padding: 1rem; color:#6c757d;">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr class="order-row">
                            <td class="order-id-divider">#<?php echo (int)$o['order_id']; ?></td>
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
                                <?php if (($o['status'] ?? '') === 'pending'): ?>
                                    <form method="POST" action="" class="order-update-form" onsubmit="return validateRemark(this)">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
                                        <div style="margin-bottom: 0.5rem;">
                                            <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;" required>
                                                <option value="">Select action...</option>
                                                <option value="shipped">Dispatch</option>
                                                <option value="cancelled">Cancel</option>
                                            </select>
                                        </div>
                                        <div style="margin-bottom: 0.5rem;">
                                            <input type="text" name="admin_remark" placeholder="Enter remark (required)" 
                                                   style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem;" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-check"></i> Update Status</button>
                                    </form>
                                <?php elseif (($o['status'] ?? '') === 'shipped'): ?>
                                    <div class="order-update-pill order-update-dispatched">Dispatched</div>
                                <?php elseif (($o['status'] ?? '') === 'delivered'): ?>
                                    <div class="order-update-pill order-update-delivered">Delivered</div>
                                <?php elseif (($o['status'] ?? '') === 'cancelled'): ?>
                                    <div class="order-update-pill order-update-cancelled">Cancelled</div>
                                <?php else: ?>
                                    <div class="order-update-pill order-update-default">
                                        <?php echo htmlspecialchars(admin_status_label((string)($o['status'] ?? ''))); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($o['admin_remark'])): ?>
                                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #e53e3e; background: #fff5f5; padding: 0.5rem; border-radius: 4px; border-left: 3px solid #e53e3e;">
                                        <i class="fa-solid fa-sticky-note"></i> <strong>Admin Remark:</strong> <?php echo htmlspecialchars($o['admin_remark']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($o['user_note']) && in_array($o['status'], ['delivered', 'cancelled'])): ?>
                                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #155724; background: #d4edda; padding: 0.5rem; border-radius: 4px; border-left: 3px solid #28a745;">
                                        <i class="fa-solid fa-comment"></i> <strong>User Note:</strong> <?php echo htmlspecialchars($o['user_note']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Order Details Row -->
                        <tr class="order-details-row" id="details-<?php echo (int)$o['order_id']; ?>" style="display: none;">
                            <td colspan="7" style="padding: 0; background: #f8f9fa;">
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
                                                if (!empty($o['ship_address'])) {
                                                    echo htmlspecialchars($o['ship_address']);
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

function validateRemark(form) {
    const remarkInput = form.querySelector('input[name="admin_remark"]');
    if (remarkInput && !remarkInput.value.trim()) {
        alert('Please enter a remark before updating the order status.');
        remarkInput.focus();
        return false;
    }
    return true;
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

