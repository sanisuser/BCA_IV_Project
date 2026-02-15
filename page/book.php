<?php
/**
 * pages/book.php
 * 
 * Single book details page.
 */

$page_title = 'Book Details';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

// Get book ID from URL
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    redirect(SITE_URL . '/pages/books.php');
}

// Fetch book details
$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param('i', $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    redirect(SITE_URL . '/pages/books.php');
}

// Set page title to book name
$page_title = $book['title'];

// Check if book is in user's wishlist
$in_wishlist = false;
if (is_logged_in()) {
    $user_id = get_user_id();
    $stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->bind_param('ii', $user_id, $book_id);
    $stmt->execute();
    $stmt->store_result();
    $in_wishlist = $stmt->num_rows > 0;
    $stmt->close();
}

// Get related books (same genre, excluding current book)
$related = [];
$genre = $book['genre'] ?? '';
if (!empty($genre)) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE genre = ? AND book_id != ? LIMIT 4");
    $stmt->bind_param('si', $genre, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related[] = $row;
    }
    $stmt->close();
}
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/page/css/book.css">

<div class="book-details-container">
    <!-- Breadcrumb -->
    <nav class="book-breadcrumb">
        <a href="<?php echo SITE_URL; ?>/index.php">Home</a> 
        <span class="separator">/</span>
        <a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a>
        <?php if (!empty($book['genre'])): ?>
            <span class="separator">/</span>
            <a href="<?php echo SITE_URL; ?>/page/booklist.php?genre=<?php echo urlencode($book['genre']); ?>"><?php echo htmlspecialchars(ucfirst($book['genre'])); ?></a>
        <?php endif; ?>
        <span class="separator">/</span>
        <span class="current"><?php echo htmlspecialchars($book['title']); ?></span>
    </nav>

    <div class="book-grid">
        <!-- Book Cover -->
        <div class="book-cover-wrapper">
            <img src="<?php echo SITE_URL; ?>/<?php echo get_book_cover($book['cover_image']); ?>" 
                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                 class="book-cover">
        </div>

        <!-- Book Info -->
        <div class="book-info">
            <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
            <p class="book-author">by <span><?php echo htmlspecialchars($book['author']); ?></span></p>

            <!-- Price -->
            <div class="book-price">
                <?php echo format_price($book['price']); ?>
            </div>

            <!-- Stock Status -->
            <?php if (($book['stock'] ?? 0) === 1): ?>
            <div class="book-stock in-stock">
                <i class="fas fa-check-circle"></i> Only 1 left in stock!
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="book-actions">
                <?php if (($book['stock'] ?? 0) > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/order_cart_process/process/cart_process.php?action=add&id=<?php echo $book_id; ?>" 
                       class="btn-add-cart">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </a>
                <?php else: ?>
                    <button class="btn-disabled" disabled>
                        Out of Stock
                    </button>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                    <?php if ($in_wishlist): ?>
                        <a href="<?php echo SITE_URL; ?>/order_cart_process/process/wishlist_process.php?action=remove&id=<?php echo $book_id; ?>" 
                           class="btn-wishlist remove">
                            <i class="fas fa-heart-broken"></i> Remove from Wishlist
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/order_cart_process/process/wishlist_process.php?action=add&id=<?php echo $book_id; ?>" 
                           class="btn-wishlist add">
                            <i class="fas fa-heart"></i> Add to Wishlist
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Book Details -->
            <div class="book-details-card">
                <h3 class="book-details-title">Book Details</h3>
                <table class="book-details-table">
                    <tr>
                        <td class="label">Genre</td>
                        <td class="value"><?php echo htmlspecialchars($book['genre'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Published</td>
                        <td class="value"><?php echo $book['published_year'] ?? 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Condition</td>
                        <td class="value"><?php echo ucfirst($book['condition_status'] ?? 'new'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="book-description">
        <h2>Description</h2>
        <p><?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?></p>
    </div>

    <!-- Related Books -->
    <?php if (count($related) > 0): ?>
    <div class="related-books">
        <h2>Related Books</h2>
        <div class="horizontal-scroll related-horizontal" style="display:flex; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; gap:12px; -webkit-overflow-scrolling:touch;">
            <?php foreach ($related as $related_book): ?>
                <?php
                $cover = SITE_URL . '/' . get_book_cover($related_book['cover_image'] ?? '');
                $title = htmlspecialchars($related_book['title']);
                $author = htmlspecialchars($related_book['author'] ?? 'Unknown');
                $price = format_price($related_book['price'] ?? 0);
                $id = $related_book['book_id'];
                ?>
                <div class="book-card">
                    <div class="book-cover">
                        <img src="<?php echo $cover; ?>" alt="<?php echo $title; ?>" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-book.png'">
                        <div class="book-overlay">
                            <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $id; ?>" class="btn-quick">View</a>
                        </div>
                    </div>
                    <div class="book-info">
                        <h3 class="related-book-title"><?php echo $title; ?></h3>
                        <p class="related-book-author"><?php echo $author; ?></p>
                        <div class="related-book-price"><?php echo $price; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
