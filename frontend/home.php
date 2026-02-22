<?php
/**
 * home.php
 * Homepage with slider, recently added, and top favorites
 */

// Fetch random books for slider (up to 7)
$slider_books = [];
$result = $conn->query("SELECT book_id, title, author, cover_image, price, description FROM books ORDER BY RAND() LIMIT 7");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $slider_books[] = $row;
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
                <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
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

<!-- Layer 3: Top Favorites -->
<section class="section top-favorites">
    <div class="section-header">
        <h2><i class="fas fa-heart"></i> Top Favorites</h2>
        <a href="<?php echo SITE_URL; ?>/page/books.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="horizontal-scroll">
        <?php foreach ($favorite_books as $book): ?>
        <div class="book-card">
            <div class="book-cover">
                <img src="<?php echo !empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : SITE_URL . '/assets/images/default-book.png'; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
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
