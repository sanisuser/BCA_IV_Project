<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login'));
}

$user_id = (int)get_user_id();
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    redirect(SITE_URL . '/order_cart_process/orders.php?error=' . urlencode('Invalid order'));
}

// Only allow marking received if the order belongs to the user and is currently dispatched (shipped)
$stmt = $conn->prepare('SELECT status FROM orders WHERE order_id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    redirect(SITE_URL . '/order_cart_process/orders.php?error=' . urlencode('Order not found'));
}

$current = (string)($row['status'] ?? '');
if ($current !== 'shipped') {
    redirect(SITE_URL . '/order_cart_process/orders.php?error=' . urlencode('Order is not dispatched yet'));
}

$stmt = $conn->prepare('UPDATE orders SET status = \'delivered\', updated_at = NOW() WHERE order_id = ? AND user_id = ? AND status = \'shipped\'');
$stmt->bind_param('ii', $order_id, $user_id);
if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();
    redirect(SITE_URL . '/order_cart_process/orders.php?success=' . urlencode('Marked as received'));
}
$stmt->close();

redirect(SITE_URL . '/order_cart_process/orders.php?error=' . urlencode('Unable to mark as received'));
