<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/db.php';

    if (!is_logged_in()) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    if (!is_admin()) {
        echo json_encode(['error' => 'Not admin']);
        exit;
    }

    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    if ($order_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order ID']);
        exit;
    }

    // Fetch order with user info
    $stmt = $conn->prepare('
        SELECT o.*, u.username, u.full_name, u.email, u.phone as user_phone
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = ?
    ');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Fetch order items with book details
    $stmt = $conn->prepare('
        SELECT oi.*, b.title, b.author, b.cover_image, b.isbn
        FROM order_items oi
        JOIN books b ON oi.book_id = b.book_id
        WHERE oi.order_id = ?
        ORDER BY oi.item_id
    ');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = [];
    while ($row = $stmt->get_result()->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    // Fetch address if address_id exists
    $address = null;
    if (!empty($order['address_id'])) {
        $stmt = $conn->prepare('SELECT * FROM user_addresses WHERE address_id = ?');
        $stmt->bind_param('i', $order['address_id']);
        $stmt->execute();
        $address = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    function admin_status_label($status) {
        if ($status === 'shipped') {
            return 'Dispatched';
        }
        return ucfirst($status);
    }

    echo json_encode([
        'order' => $order,
        'items' => $items,
        'address' => $address,
        'status_label' => admin_status_label($order['status'] ?? 'pending')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
