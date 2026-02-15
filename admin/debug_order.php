<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test basic functionality first
    echo json_encode([
        'test' => 'Basic test working',
        'php_version' => PHP_VERSION,
        'order_id' => isset($_GET['order_id']) ? $_GET['order_id'] : 'not set'
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
