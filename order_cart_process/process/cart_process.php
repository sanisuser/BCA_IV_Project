<?php
/**
 * process/cart_process.php
 * 
 * Handles cart actions: add, update, remove items.
 * 
 * Actions:
 *   ?action=add&id=123     - Add book to cart
 *   ?action=remove&id=1    - Remove cart item
 *   POST action=update     - Update quantity
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to manage your cart'));
}

$user_id = get_user_id();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    // ADD ITEM TO CART
    case 'add':
        $book_id = (int)($_GET['id'] ?? 0);
        
        if ($book_id <= 0) {
            redirect(SITE_URL . '/page/booklist.php?error=' . urlencode('Invalid book'));
        }
        
        // Check if book exists and has stock
        $stmt = $conn->prepare("SELECT stock FROM books WHERE book_id = ?");
        $stmt->bind_param('i', $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $stmt->close();
        
        if (!$book || $book['stock'] <= 0) {
            redirect(SITE_URL . '/page/book.php?id=' . $book_id . '&error=' . urlencode('Book is out of stock'));
        }
        
        // Check if already in cart
        $stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param('ii', $user_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update quantity if already in cart
            $new_qty = min($existing['quantity'] + 1, $book['stock']);
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $stmt->bind_param('ii', $new_qty, $existing['cart_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Add new item
            $stmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity, created_at) VALUES (?, ?, 1, NOW())");
            $stmt->bind_param('ii', $user_id, $book_id);
            $stmt->execute();
            $stmt->close();
        }
        
        redirect(SITE_URL . '/order_cart_process/cart.php?success=' . urlencode('Book added to cart'));
        break;
    
    // UPDATE QUANTITY
    case 'update':
        $cart_id = (int)($_POST['cart_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($cart_id <= 0 || $quantity < 1) {
            redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Invalid request'));
        }
        
        // Verify cart item belongs to this user
        $stmt = $conn->prepare("
            SELECT c.cart_id, b.stock 
            FROM cart c 
            JOIN books b ON c.book_id = b.book_id 
            WHERE c.cart_id = ? AND c.user_id = ?
        ");
        $stmt->bind_param('ii', $cart_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if (!$item) {
            redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Item not found'));
        }

        // If stock is 0, remove the item from cart
        if ((int)$item['stock'] <= 0) {
            $del = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $del->bind_param('ii', $cart_id, $user_id);
            $del->execute();
            $del->close();
            redirect(SITE_URL . '/order_cart_process/cart.php?success=' . urlencode('Out of stock item removed from cart'));
        }
        
        // Limit to available stock
        $quantity = min($quantity, $item['stock']);
        $quantity = max(1, (int)$quantity);
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $stmt->bind_param('ii', $quantity, $cart_id);
        $stmt->execute();
        $stmt->close();
        
        redirect(SITE_URL . '/order_cart_process/cart.php?success=' . urlencode('Quantity updated'));
        break;
    
    // REMOVE ITEM
    case 'remove':
        $cart_id = (int)($_GET['id'] ?? 0);
        
        if ($cart_id <= 0) {
            redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Invalid request'));
        }
        
        // Verify and delete
        $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        redirect(SITE_URL . '/order_cart_process/cart.php?success=' . urlencode('Item removed from cart'));
        break;
    
    // CLEAR ENTIRE CART
    case 'clear':
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        
        redirect(SITE_URL . '/order_cart_process/cart.php?success=' . urlencode('Cart cleared'));
        break;
    
    default:
        redirect(SITE_URL . '/order_cart_process/cart.php?error=' . urlencode('Invalid action'));
}
