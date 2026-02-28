<?php
/**
 * admin/restock_process.php
 * 
 * Process re-stock form - update book quantity.
 */

require_once __DIR__ . '/../includes/functions.php';

// Check admin access
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/restock.php');
}

// Get and validate input
$book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

// Validation
if ($book_id <= 0) {
    redirect(SITE_URL . '/admin/restock.php?error=' . urlencode('Please select a book.'));
}

if ($quantity <= 0 || $quantity > 1000) {
    redirect(SITE_URL . '/admin/restock.php?error=' . urlencode('Please enter a valid quantity between 1 and 1000.'));
}

// Check if book exists
$check_stmt = $conn->prepare("SELECT title, stock FROM books WHERE book_id = ?");
$check_stmt->bind_param('i', $book_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    redirect(SITE_URL . '/admin/restock.php?error=' . urlencode('Book not found.'));
}

$book = $result->fetch_assoc();
$check_stmt->close();

// Update stock
$update_stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE book_id = ?");
$update_stmt->bind_param('ii', $quantity, $book_id);

if ($update_stmt->execute()) {
    $new_stock = $book['stock'] + $quantity;
    $message = sprintf(
        'Successfully added %d copies of "%s". New stock: %d copies.',
        $quantity,
        $book['title'],
        $new_stock
    );
    $update_stmt->close();
    redirect(SITE_URL . '/admin/restock.php?success=' . urlencode($message));
} else {
    $update_stmt->close();
    redirect(SITE_URL . '/admin/restock.php?error=' . urlencode('Failed to update stock. Please try again.'));
}
