<?php
// Fetch random books for slider (up to 7)
$slider_books = [];
$result = $conn->query("SELECT book_id, title, author, cover_image, price, description FROM books ORDER BY RAND() LIMIT 7");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $slider_books[] = $row;
    }
    $result->free();
}

// Fetch bestseller books (at least 10 copies sold)
$bestseller_books = [];
$result = $conn->query("SELECT book_id, title, author, cover_image, price, total_sold FROM books WHERE COALESCE(total_sold, 0) >= 10 ORDER BY total_sold DESC, created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bestseller_books[] = $row;
    }
    $result->free();
}

// Fetch recently added books (latest 10)
$recent_books = [];
$result = $conn->query("SELECT book_id, title, author, cover_image, price, created_at FROM books ORDER BY created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_books[] = $row;
    }
    $result->free();
}

// Fetch top favorite books (highest rated, top 10)
$favorite_books = [];
$result = $conn->query("
    SELECT b.*, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.review_id) as review_count
    FROM books b
    LEFT JOIN reviews r ON b.book_id = r.book_id
    GROUP BY b.book_id
    HAVING review_count > 0
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 10
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $favorite_books[] = $row;
    }
    $result->free();
}
$available_genres = [];
$result = $conn->query("SELECT DISTINCT genre FROM books WHERE stock > 0 AND genre IS NOT NULL AND genre <> '' ORDER BY RAND() LIMIT 7");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $available_genres[] = (string)($row['genre'] ?? '');
    }
    $result->free();
}
$available_authors = [];

// $result = $conn->query("SELECT DISTINCT author FROM books WHERE stock > 0 AND author IS NOT NULL AND author <> '' ORDER BY author ASC");
$result = $conn->query("
    SELECT DISTINCT author
    FROM books
    WHERE stock > 0
      AND author IS NOT NULL
      AND author <> ''
    ORDER BY RAND()
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $available_authors[] = (string)($row['author'] ?? '');
    }
    $result->free();
}

// Fetch cart items for logged in user
$cart_books = [];
$cart_total = 0;
if (is_logged_in()) {
    $user_id = get_user_id();
    $stmt = $conn->prepare("
        SELECT c.cart_id, c.quantity, c.book_id, b.title, b.author, b.cover_image, b.price, b.stock 
        FROM cart c 
        JOIN books b ON c.book_id = b.book_id 
        WHERE c.user_id = ?
        ORDER BY c.cart_id DESC
        LIMIT 10
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_books[] = $row;
        $cart_total += ($row['price'] * $row['quantity']);
    }
    $stmt->close();
}
?>

<!-- Homepage CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/frontend/home.css">

<!-- Layer 1: Slider -->
<section class="slider-section">
    <div class="slider-container">
        <?php foreach ($slider_books as $index => $book): ?>
        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
            <div class="slide-bg" style="background-image: url('<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>')"></div>
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                <p class="slide-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                <p class="slide-desc"><?php echo !empty($book['description']) ? htmlspecialchars(substr($book['description'], 0, 150)) . '...' : 'Discover this amazing book!'; ?></p>
                <div class="slide-price"><?php echo format_price($book['price']); ?></div>
                <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $book['book_id']; ?>" class="btn-slide">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Slider Dots -->
        <div class="slider-dots">
            <?php foreach ($slider_books as $index => $book): ?>
            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>, true)"></span>
            <?php endforeach; ?>
        </div>

        <!-- Slider Arrows -->
        <button class="slider-arrow prev" onclick="changeSlide(-1)"><i class="fas fa-chevron-left"></i></button>
        <button class="slider-arrow next" onclick="changeSlide(1)"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>

<!-- Layer 2: Recently Added -->
<section class="section recently-added">
    <div class="section-header">
        <h2><i class="fas fa-clock"></i> Recently Added</h2>
        <a href="<?php echo SITE_URL; ?>/page/booklist.php?sort=newest" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="horizontal-scroll">
        <?php foreach ($recent_books as $book): ?>
        <div class="book-card">
            <div class="book-cover">
                <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                <div class="book-overlay">
                    <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $book['book_id']; ?>" class="btn-quick">View</a>
                </div>
            </div>
            <div class="book-info">
                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                <div class="book-price"><?php echo format_price($book['price']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Layer 2.5: Your Cart (Only shown if logged in and has items) -->
<?php if (is_logged_in() && !empty($cart_books)): ?>
<section class="section cart-section">
    <div class="section-header">
        <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
        <a href="<?php echo SITE_URL; ?>/order_cart_process/cart.php" class="view-all">View Cart <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="horizontal-scroll">
        <?php foreach ($cart_books as $item): ?>
        <div class="book-card cart-book-card">
            <div class="book-cover">
                <img src="<?php echo !empty($item['cover_image']) ? htmlspecialchars($item['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                <div class="book-overlay">
                    <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $item['book_id']; ?>" class="btn-quick">View</a>
                </div>
                <!-- Cart quantity badge -->
                <div class="cart-qty-badge"><?php echo $item['quantity']; ?>x</div>
            </div>
            <div class="book-info">
                <h3 class="book-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                <p class="book-author"><?php echo htmlspecialchars($item['author']); ?></p>
                <div class="book-price"><?php echo format_price($item['price']); ?></div>
                <!-- Stock status -->
                <div class="cart-stock-status">
                    <?php if ($item['stock'] > 10): ?>
                        <span class="stock-in"><i class="fas fa-check-circle"></i> Available</span>
                    <?php elseif ($item['stock'] > 0): ?>
                        <span class="stock-low"><i class="fas fa-exclamation-circle"></i> Only <?php echo $item['stock']; ?> left</span>
                    <?php else: ?>
                        <span class="stock-out"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Cart total summary -->
    <div class="cart-summary" style="padding: 0 20px; margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
        <span style="color: #6c757d; font-size: 0.9rem;"><?php echo count($cart_books); ?> item(s) in cart</span>
        <div style="display: flex; align-items: center; gap: 16px;">
            <span style="font-weight: 600; font-size: 1.1rem;">Total: <?php echo format_price($cart_total); ?></span>
            <a href="<?php echo SITE_URL; ?>/order_cart_process/checkout.php" class="btn-checkout" style="background: #007bff; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-credit-card"></i> Checkout
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Layer 3: Bestsellers -->
<section class="section top-favorites">
    <div class="section-header">
        <h2><i class="fas fa-fire"></i> Bestsellers</h2>
        <a href="<?php echo SITE_URL; ?>/page/booklist.php?sort=bestseller" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="horizontal-scroll">
        <?php foreach ($bestseller_books as $book): ?>
        <div class="book-card">
            <div class="book-cover">
                <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                <div class="book-overlay">
                    <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $book['book_id']; ?>" class="btn-quick">View</a>
                </div>
            </div>
            <div class="book-info">
                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                <div class="book-price"><?php echo format_price($book['price']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($bestseller_books)): ?>
            <div class="book-card" style="padding: 16px;">
                <div style="color: #6c757d;">No bestsellers yet (needs 10+ sales).</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Layer 3: Top Favorites -->
<section class="section top-favorites">
    <div class="section-header">
        <h2><i class="fas fa-heart"></i> Top Favorites</h2>
        <a href="<?php echo SITE_URL; ?>/page/booklist.php?sort=rating" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="horizontal-scroll">
        <?php foreach ($favorite_books as $book): ?>
        <div class="book-card">
            <div class="book-cover">
                <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy">
                <div class="book-overlay">
                    <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $book['book_id']; ?>" class="btn-quick">View</a>
                </div>
            </div>
            <div class="book-info">
                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                <div class="book-price"><?php echo format_price($book['price']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section meta-lists">
    <div class="section-header">
        <h2><i class="fas fa-layer-group"></i>Genre</h2>
    </div>
    <div class="chip-list" style="padding: 0 20px;">
        <?php foreach ($available_genres as $g): ?>
            <a class="chip" href="<?php echo SITE_URL; ?>/page/booklist.php?genre=<?php echo urlencode($g); ?>"><?php echo htmlspecialchars($g); ?></a>
        <?php endforeach; ?>
        <?php if (empty($available_genres)): ?>
            <div class="chip chip-muted">No genres available</div>
        <?php endif; ?>
    </div>
</section>

<section class="section meta-lists" style="padding-top: 0;">
    <div class="section-header">
        <h2><i class="fas fa-user-pen"></i>Author</h2>
    </div>
    <div class="chip-list" style="padding: 0 20px;">
        <?php foreach ($available_authors as $a): ?>
            <a class="chip" href="<?php echo SITE_URL; ?>/page/booklist.php?author=<?php echo urlencode($a); ?>"><?php echo htmlspecialchars($a); ?></a>
        <?php endforeach; ?>
        <?php if (empty($available_authors)): ?>
            <div class="chip chip-muted">No authors available</div>
        <?php endif; ?>
    </div>
</section>

<!-- Horizontal Scroll JavaScript -->
<script>
// Horizontal scroll with touch/drag support
const scrollContainers = document.querySelectorAll('.horizontal-scroll');
scrollContainers.forEach(container => {
    let isDown = false;
    let startX;
    let scrollLeft;
    container.addEventListener('mousedown', (e) => {
        isDown = true;
        container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
    });
    container.addEventListener('mouseleave', () => {
        isDown = false;
        container.style.cursor = 'grab';
    });
    container.addEventListener('mouseup', () => {
        isDown = false;
        container.style.cursor = 'grab';
    });

    container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2;
        container.scrollLeft = scrollLeft - walk;
    });

});
</script>