<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to manage your wishlist'));
}

$user_id = (int)get_user_id();
$action = $_GET['action'] ?? '';
$book_id = (int)($_GET['id'] ?? 0);

if ($book_id <= 0) {
    redirect(SITE_URL . '/page/booklist.php?error=' . urlencode('Invalid book'));
}

$return_url = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . '/order_cart_process/wishlist.php');

if ($action === 'add') {
    $stmt = $conn->prepare('INSERT IGNORE INTO wishlist (user_id, book_id, created_at) VALUES (?, ?, NOW())');
    $stmt->bind_param('ii', $user_id, $book_id);
    $stmt->execute();
    $stmt->close();

    redirect($return_url);
}

if ($action === 'remove') {
    $stmt = $conn->prepare('DELETE FROM wishlist WHERE user_id = ? AND book_id = ?');
    $stmt->bind_param('ii', $user_id, $book_id);
    $stmt->execute();
    $stmt->close();

    redirect($return_url);
}

redirect($return_url);
