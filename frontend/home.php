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

// Fetch top favorite books (latest added, top 10)
$favorite_books = [];
$result = $conn->query("SELECT book_id, title, author, cover_image, price, created_at FROM books ORDER BY created_at DESC LIMIT 10");
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
        <a href="<?php echo SITE_URL; ?>/page/books.php?sort=newest" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
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

<!-- Slider JavaScript -->
<script>
let currentSlide = 0;
let slideInterval;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');
const totalSlides = slides.length;

function showSlide(index) {
    if (totalSlides === 0) return;
    
    // Wrap around
    if (index >= totalSlides) currentSlide = 0;
    else if (index < 0) currentSlide = totalSlides - 1;
    else currentSlide = index;
    
    // Update slides
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === currentSlide) {
            slide.classList.add('active');
        }
    });
    
    // Update dots
    dots.forEach((dot, i) => {
        dot.classList.remove('active');
        if (i === currentSlide) {
            dot.classList.add('active');
        }
    });
}

function changeSlide(direction) {
    showSlide(currentSlide + direction);
    resetAutoSlide();
}

function goToSlide(index, reset = false) {
    showSlide(index);
    if (reset) resetAutoSlide();
}

function startAutoSlide() {
    stopAutoSlide();
    slideInterval = setInterval(() => {
        showSlide(currentSlide + 1);
    }, 3000); // 3 seconds
}

function stopAutoSlide() {
    if (slideInterval) {
        clearInterval(slideInterval);
    }
}

function resetAutoSlide() {
    stopAutoSlide();
    startAutoSlide();
}

// Pause on hover
const sliderContainer = document.querySelector('.slider-container');
if (sliderContainer) {
    sliderContainer.addEventListener('mouseenter', stopAutoSlide);
    sliderContainer.addEventListener('mouseleave', startAutoSlide);
}

// Touch support for mobile
let touchStartX = 0;
let touchEndX = 0;

if (sliderContainer) {
    sliderContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoSlide();
    }, {passive: true});
    
    sliderContainer.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
        startAutoSlide();
    }, {passive: true});
}

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            changeSlide(1); // Swipe left - next
        } else {
            changeSlide(-1); // Swipe right - previous
        }
    }
}

// Start auto-slide on load
if (totalSlides > 0) {
    startAutoSlide();
}

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
