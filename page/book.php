<?php
/**
 * pages/book.php
 * 
 * Single book details page.
 */

// Start output buffering to prevent headers already sent errors
ob_start();

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

// Get book reviews
$reviews = [];
$avg_rating = 0;
$review_count = 0;
$user_has_reviewed = false;
$user_review = null;

// Check if reviews table exists
$table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($table_check && $table_check->num_rows > 0) {
    // Fetch all approved reviews for this book
    $stmt = $conn->prepare("
        SELECT r.*, u.username, u.full_name, u.profile_image 
        FROM reviews r 
        JOIN users u ON r.user_id = u.user_id 
        WHERE r.book_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();
    
    // Calculate average rating
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE book_id = ?");
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating_data = $result->fetch_assoc();
    $avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
    $review_count = $rating_data['count'] ?? 0;
    $stmt->close();
    
    // Check if current user has already reviewed
    if (is_logged_in()) {
        $user_id = get_user_id();
        $stmt = $conn->prepare("SELECT * FROM reviews WHERE book_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $book_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_has_reviewed = true;
            $user_review = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

// Handle review submission
$review_error = '';
$review_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && is_logged_in()) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if ($rating < 1 || $rating > 5) {
        $review_error = 'Please select a rating between 1 and 5 stars.';
    } elseif (empty($comment)) {
        $review_error = 'Please write a review comment.';
    } elseif (strlen($comment) < 10) {
        $review_error = 'Review comment must be at least 10 characters long.';
    } else {
        $user_id = get_user_id();
        
        // Check if user already reviewed (update or insert)
        if ($user_has_reviewed) {
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE book_id = ? AND user_id = ?");
            $stmt->bind_param('isii', $rating, $comment, $book_id, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (book_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiis', $book_id, $user_id, $rating, $comment);
        }
        
        if ($stmt->execute()) {
            $review_success = $user_has_reviewed ? 'Your review has been updated!' : 'Your review has been submitted!';
            // Refresh page to show new review
            redirect(SITE_URL . '/page/book.php?id=' . $book_id . '&review_success=1');
        } else {
            $review_error = 'Failed to submit review. Please try again.';
        }
        $stmt->close();
    }
}

// Check for success message from redirect
if (isset($_GET['review_success']) && $_GET['review_success'] == '1') {
    $review_success = 'Your review has been submitted successfully!';
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
            
            <!-- Rating below cover (desktop) -->
            <?php if ($review_count > 0): ?>
            <div class="book-cover-rating">
                <div class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="rating-text"><?php echo $avg_rating; ?> (<?php echo $review_count; ?>)</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Book Info -->
        <div class="book-info">
            <div class="book-title-rating">
                <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                
                <!-- Rating beside title (mobile) -->
                <?php if ($review_count > 0): ?>
                <div class="book-title-rating-stars">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text"><?php echo $avg_rating; ?> (<?php echo $review_count; ?>)</span>
                </div>
                <?php endif; ?>
            </div>
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

    <!-- Reviews Section -->
    <div class="reviews-section">
        <div class="reviews-header">
            <h2><i class="fas fa-star"></i> Reviews & Ratings</h2>
            <?php if ($review_count > 0): ?>
                <div class="reviews-summary">
                    <div class="average-rating">
                        <span class="rating-number"><?php echo $avg_rating; ?></span>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="review-count">(<?php echo $review_count; ?> review<?php echo $review_count > 1 ? 's' : ''; ?>)</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($review_error)): ?>
            <div class="review-message error"><?php echo htmlspecialchars($review_error); ?></div>
        <?php endif; ?>
        <?php if (!empty($review_success)): ?>
            <div class="review-message success"><?php echo htmlspecialchars($review_success); ?></div>
        <?php endif; ?>

        <!-- Review Form (for logged-in users) -->
        <?php if (is_logged_in()): ?>
            <div class="review-form-container">
                <div class="review-form-header">
                    <h3><?php echo $user_has_reviewed ? 'Edit Your Review' : 'Write a Review'; ?></h3>
                    <span class="reviewing-as">as <strong><?php echo htmlspecialchars($current_user['full_name'] ?? $current_user['username']); ?></strong></span>
                </div>
                <form method="POST" action="" class="review-form">
                    <div class="rating-input">
                        <label>Your Rating:</label>
                        <div class="star-rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" 
                                    <?php echo ($user_has_reviewed && $user_review['rating'] == $i) ? 'checked' : ''; ?> required>
                                <label for="star<?php echo $i; ?>" class="star-label"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="comment-input">
                        <label for="review-comment">Your Review:</label>
                        <textarea name="comment" id="review-comment" rows="4" placeholder="Share your thoughts about this book... (minimum 10 characters)" required minlength="10"><?php echo $user_has_reviewed ? htmlspecialchars($user_review['comment']) : ''; ?></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn-submit-review">
                        <i class="fas fa-paper-plane"></i> <?php echo $user_has_reviewed ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-login-prompt">
                <p><i class="fas fa-lock"></i> Please <a href="<?php echo SITE_URL; ?>/auth/login.php">login</a> to write a review.</p>
            </div>
        <?php endif; ?>

        <!-- Reviews List (only show if reviews exist) -->
        <?php if (count($reviews) > 0): ?>
            <div class="reviews-list">
                <h3>Customer Reviews</h3>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <?php if (!empty($review['profile_image'])): ?>
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($review['profile_image']); ?>" alt="" class="reviewer-avatar">
                                <?php else: ?>
                                    <div class="reviewer-avatar-placeholder"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                                <div class="reviewer-details">
                                    <span class="reviewer-name"><?php echo htmlspecialchars($review['full_name'] ?? $review['username']); ?></span>
                                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-reviews">
                <p><i class="fas fa-comment-slash"></i> No reviews yet. Be the first to review this book!</p>
            </div>
        <?php endif; ?>
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

// Flush output buffer
ob_end_flush();
?>
