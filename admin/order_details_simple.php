<?php
header('Content-Type: application/json');

// Test step 1: Basic includes
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Test step 2: Check auth
if (!is_logged_in()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!is_admin()) {
    echo json_encode(['error' => 'Not admin']);
    exit;
}

// Test step 3: Get order_id
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Test step 4: Simple query
$stmt = $conn->prepare('SELECT * FROM orders WHERE order_id = ? LIMIT 1');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'order' => $order,
    'test' => 'Working'
]);
?>
