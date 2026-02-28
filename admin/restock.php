<?php
/**
 * admin/restock.php
 * 
 * Re-stock books - add quantity to existing books.
 */

require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';

$success = '';
$error = '';

// Get all books for dropdown
$books = [];
$stmt = $conn->query("SELECT book_id, title, author, stock FROM books ORDER BY title ASC");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $books[] = $row;
    }
}

$page_title = 'Re-stock Books';
$active_page = 'books';

// Get messages from process redirect
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/css/restock.css">

<div class="restock-container">
    <div class="restock-header">
        <div class="restock-title-area">
            <h1>Re-stock Books</h1>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="restock-alert success">
            <i class="fa-solid fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="restock-alert error">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

<div class="restock-wrapper">
    <!-- Left Column - Re-stock Form -->
    <div class="restock-column form-column">
        <div class="restock-card">
            <h2 class="column-title">Add Stock
            </h2>
            <form method="POST" action="restock_process.php" class="restock-form">
                
                <!-- Book Selection -->
                <div class="form-group">
                    <label for="book_search">
                        <i class="fa-solid fa-book"></i> Search & Select Book
                    </label>
                    <div class="search-input-wrapper">
                        <input type="text" 
                               id="book_search" 
                               list="book_list" 
                               class="search-input"
                               placeholder="Type to search books..." 
                               autocomplete="off"
                               oninput="updateBookId(this)">
                        <datalist id="book_list">
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo htmlspecialchars('#' . $book['book_id'] . ' - ' . $book['title'] . ' by ' . $book['author'] . ' (Stock: ' . $book['stock'] . ')'); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" name="book_id" id="book_id" required>
                    </div>
                    <small class="form-help">Start typing to search for a book by title or author</small>
                    
                    <!-- Stock indicator -->
                    <div id="stock-indicator" class="stock-indicator" style="display: none;">
                        <span class="stock-label">Current Stock:</span>
                        <span id="current-stock" class="stock-value"></span>
                    </div>
                </div>

                <!-- Quantity Input -->
                <div class="form-group">
                    <label for="quantity">
                        <i class="fa-solid fa-cubes"></i> Quantity to Add
                    </label>
                    <div class="quantity-wrapper">
                        <button type="button" class="qty-btn minus" onclick="adjustQty(-1)">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <input type="number" 
                               name="quantity" 
                               id="quantity" 
                               class="quantity-input"
                               min="1" 
                               max="1000"
                               value="1"
                               required>
                        <button type="button" class="qty-btn plus" onclick="adjustQty(1)">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <small class="form-help">Enter the number of copies to add (1-1000)</small>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn-update">
                        <i class="fa-solid fa-check"></i>
                        <span>Update Stock</span>
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn-cancel">
                        <i class="fa-solid fa-times"></i>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column - Low Stock Table -->
    <div class="restock-column table-column">
        <div class="restock-card low-stock-card">
            <h2 class="column-title">
                <i class="fa-solid fa-triangle-exclamation" style="color: #ffc107;"></i> 
                Low Stock Books <small>(≤ 10)</small>
            </h2>
            
            <?php 
            $low_stock_books = [];
            foreach ($books as $book) {
                if ($book['stock'] <= 10) {
                    $low_stock_books[] = $book;
                }
            }
            ?>
            
            <?php if (!empty($low_stock_books)): ?>
                <div class="low-stock-table-wrapper">
                    <table class="low-stock-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Author</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_books as $book): ?>
                                <tr onclick="selectBook('<?php echo htmlspecialchars('#' . $book['book_id'] . ' - ' . $book['title'] . ' by ' . $book['author'] . ' (Stock: ' . $book['stock'] . ')', ENT_QUOTES); ?>')" style="cursor: pointer;">
                                    <td class="book-title"><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td class="book-author"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td class="book-stock">
                                        <span class="stock-badge <?php echo $book['stock'] == 0 ? 'out' : ($book['stock'] <= 5 ? 'low' : 'medium'); ?>">
                                            <?php echo $book['stock']; ?>
                                        </span>
                                    </td>
                                    <td class="book-status">
                                        <?php if ($book['stock'] == 0): ?>
                                            <span class="status-text out">Out of Stock</span>
                                        <?php elseif ($book['stock'] <= 5): ?>
                                            <span class="status-text low">Critical</span>
                                        <?php else: ?>
                                            <span class="status-text medium">Low</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="table-hint"><i class="fa-solid fa-hand-pointer"></i> Click any book to re-stock it</p>
            <?php else: ?>
                <div class="no-low-stock">
                    <i class="fa-solid fa-check-circle" style="color: #28a745; font-size: 3rem;"></i>
                    <p>All books have sufficient stock!</p>
                    <small>No books with stock ≤ 10</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('book_search');
    if (!input) return;

    const listId = input.getAttribute('list');
    if (!listId) return;

    function toggleDatalist() {
        const hasText = (input.value || '').trim().length > 0;
        if (hasText) {
            input.setAttribute('list', listId);
        } else {
            input.removeAttribute('list');
        }
    }

    // Prevent showing all options on focus when empty
    toggleDatalist();
    input.addEventListener('focus', toggleDatalist);
    input.addEventListener('click', toggleDatalist);
    input.addEventListener('input', function() {
        toggleDatalist();
        updateBookId(input);
    });
    input.addEventListener('blur', toggleDatalist);
});

function updateBookId(input) {
    const bookIdInput = document.getElementById('book_id');
    const stockIndicator = document.getElementById('stock-indicator');
    const currentStockSpan = document.getElementById('current-stock');
    const value = (input.value || '').trim();

    const idMatch = value.match(/^#(\d+)\s*-/);
    const stockMatch = value.match(/\(Stock:\s*(\d+)\)/i);

    if (!idMatch) {
        bookIdInput.value = '';
        stockIndicator.style.display = 'none';
        return;
    }

    const bookId = parseInt(idMatch[1], 10);
    const stock = stockMatch ? parseInt(stockMatch[1], 10) : null;

    if (!Number.isFinite(bookId)) {
        bookIdInput.value = '';
        stockIndicator.style.display = 'none';
        return;
    }

    bookIdInput.value = String(bookId);

    if (stock === null || !Number.isFinite(stock)) {
        stockIndicator.style.display = 'none';
        return;
    }

    currentStockSpan.textContent = String(stock);
    stockIndicator.style.display = 'flex';

    // Color based on stock level
    if (stock === 0) {
        currentStockSpan.className = 'stock-value out-of-stock';
    } else if (stock <= 5) {
        currentStockSpan.className = 'stock-value low-stock';
    } else {
        currentStockSpan.className = 'stock-value good-stock';
    }
}

function adjustQty(delta) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) || 0;
    value += delta;
    if (value < 1) value = 1;
    if (value > 1000) value = 1000;
    input.value = value;
}

function selectBook(displayValue) {
    const input = document.getElementById('book_search');
    input.value = displayValue;
    updateBookId(input);

    // Scroll to form and focus
    document.querySelector('.form-column').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('quantity').focus();
}

// Form validation
document.querySelector('.restock-form').addEventListener('submit', function(e) {
    const bookId = document.getElementById('book_id').value;
    if (!bookId) {
        e.preventDefault();
        alert('Please select a book from the list.');
        document.getElementById('book_search').focus();
    }
});
</script>

</body>
</html>
