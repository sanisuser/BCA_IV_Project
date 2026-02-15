<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to place an order'));
}

$user_id = (int)get_user_id();
$action = $_POST['action'] ?? '';

if ($action !== 'place') {
    redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Invalid action'));
}

$shipping_address = trim($_POST['shipping_address'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

if ($shipping_address === '' || $payment_method === '') {
    redirect(SITE_URL . '/order_cart_process/checkout.php?error=' . urlencode('Please complete checkout details'));
}

$conn->begin_transaction();

try {
    $items = [];
    $total = 0;

    $stmt = $conn->prepare('
        SELECT c.book_id, c.quantity, b.price, b.stock
        FROM cart c
        JOIN books b ON c.book_id = b.book_id
        WHERE c.user_id = ?
        FOR UPDATE
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    if (count($items) === 0) {
        throw new Exception('Cart is empty');
    }

    foreach ($items as $it) {
        $stock = (int)($it['stock'] ?? 0);
        $qty = (int)($it['quantity'] ?? 0);
        if ($qty < 1 || $stock < $qty) {
            throw new Exception('Insufficient stock for one or more items');
        }
        $total += ((float)($it['price'] ?? 0) * $qty);
    }

    $stmt = $conn->prepare('INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, created_at) VALUES (?, ?, \'pending\', ?, ?, NOW())');
    $stmt->bind_param('idss', $user_id, $total, $shipping_address, $payment_method);
    $stmt->execute();
    $order_id = (int)$stmt->insert_id;
    $stmt->close();

    $stmt_item = $conn->prepare('INSERT INTO order_items (order_id, book_id, quantity, price_at_time) VALUES (?, ?, ?, ?)');
    $stmt_stock = $conn->prepare('UPDATE books SET stock = stock - ? WHERE book_id = ?');

    foreach ($items as $it) {
        $book_id = (int)$it['book_id'];
        $qty = (int)$it['quantity'];
        $price = (float)$it['price'];

        $stmt_item->bind_param('iiid', $order_id, $book_id, $qty, $price);
        $stmt_item->execute();

        $stmt_stock->bind_param('ii', $qty, $book_id);
        $stmt_stock->execute();
    }

    $stmt_item->close();
    $stmt_stock->close();

    $stmt = $conn->prepare('DELETE FROM cart WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    redirect(SITE_URL . '/order_cart_process/orders.php?success=' . urlencode('Order placed successfully'));
} catch (Throwable $e) {
    $conn->rollback();
    redirect(SITE_URL . '/order_cart_process/checkout.php?error=' . urlencode($e->getMessage()));
}
