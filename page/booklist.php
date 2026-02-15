<?php
/**
 * pages/books.php
 * 
 * Book listing page with pagination and filtering.
 */

$page_title = 'Browse Books';

require_once __DIR__ . '/../includes/header_navbar.php';
require_once __DIR__ . '/../includes/db.php';

// Get search/filter parameters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$genre = isset($_GET['genre']) ? clean_input($_GET['genre']) : '';
// If search is provided but author is not, use search as author too
$author = isset($_GET['author']) ? clean_input($_GET['author']) : ($search ?: '');
$year_from = isset($_GET['year_from']) ? (int)$_GET['year_from'] : 0;
$year_to = isset($_GET['year_to']) ? (int)$_GET['year_to'] : 0;
$condition = isset($_GET['condition']) ? clean_input($_GET['condition']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$per_page = 12; // Books per page
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(title LIKE ? OR author LIKE ? OR description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($author)) {
    $where[] = "author LIKE ?";
    $params[] = '%' . $author . '%';
    $types .= 's';
}

if (!empty($genre)) {
    $where[] = "genre = ?";
    $params[] = $genre;
    $types .= 's';
}

if ($year_from > 0 && $year_to > 0) {
    $where[] = "published_year BETWEEN ? AND ?";
    $params[] = $year_from;
    $params[] = $year_to;
    $types .= 'ii';
} elseif ($year_from > 0) {
    $where[] = "published_year >= ?";
    $params[] = $year_from;
    $types .= 'i';
} elseif ($year_to > 0) {
    $where[] = "published_year <= ?";
    $params[] = $year_to;
    $types .= 'i';
}

if (!empty($condition)) {
    $where[] = "condition_status = ?";
    $params[] = $condition;
    $types .= 's';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM books $where_sql";
$total_books = 0;

if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_books = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($count_sql);
    $total_books = $result->fetch_assoc()['total'];
}

$total_pages = ceil($total_books / $per_page);

// Get books for current page
$sql = "SELECT * FROM books $where_sql ORDER BY book_id DESC LIMIT ? OFFSET ?";
$books = [];

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $all_params = array_merge($params, [$per_page, $offset]);
    $all_types = $types . 'ii';
    $stmt->bind_param($all_types, ...$all_params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    $stmt->close();
}

// Get all distinct values for filter dropdowns
$genres = [];
$genre_result = $conn->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
while ($row = $genre_result->fetch_assoc()) {
    $genres[] = $row['genre'];
}

$authors = [];
$author_result = $conn->query("SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author LIMIT 50");
while ($row = $author_result->fetch_assoc()) {
    $authors[] = $row['author'];
}

$years = [];
$year_result = $conn->query("SELECT DISTINCT published_year FROM books WHERE published_year IS NOT NULL AND published_year > 0 ORDER BY published_year DESC");
while ($row = $year_result->fetch_assoc()) {
    $years[] = $row['published_year'];
}

$conditions = ['new', 'used'];
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/page/css/booklist.css">

<div class="booklist-container with-sidebar">
    
    <!-- Mobile Filter Toggle Button -->
    <button type="button" class="mobile-filter-toggle" onclick="toggleMobileFilters()">
        <i class="fas fa-filter"></i> Filters
    </button>
    
    <!-- Left Sidebar Filters -->
    <aside class="filter-sidebar" id="filter-sidebar">
        <div class="filter-header">
            <h3><i class="fas fa-filter"></i> Filters</h3>
            <button type="button" class="filter-close" onclick="toggleMobileFilters()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="GET" action="<?php echo SITE_URL; ?>/page/booklist.php" class="filter-form">
            
            <!-- Author Filter -->
            <div class="filter-group">
                <label>Author</label>
                <input type="text" name="author" list="author-list" value="<?php echo htmlspecialchars($author); ?>" placeholder="Search author..." class="filter-search">
                <datalist id="author-list">
                    <?php foreach ($authors as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <!-- Year Filter -->
            <div class="filter-group">
                <label>Published Year</label>
                <div class="year-range">
                    <input type="number" name="year_from" value="<?php echo $year_from > 0 ? $year_from : ''; ?>" placeholder="From" class="filter-search year-input" min="1900" max="2099">
                    <span class="year-separator">-</span>
                    <input type="number" name="year_to" value="<?php echo $year_to > 0 ? $year_to : ''; ?>" placeholder="To" class="filter-search year-input" min="1900" max="2099">
                </div>
            </div>
            
            <!-- Genre Filter -->
            <div class="filter-group">
                <label>Genre</label>
                <input type="text" name="genre" list="genre-list" value="<?php echo htmlspecialchars($genre); ?>" placeholder="Search genre..." class="filter-search">
                <datalist id="genre-list">
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <!-- Condition Filter -->
            <div class="filter-group">
                <label>Condition</label>
                <input type="text" name="condition" list="condition-list" value="<?php echo htmlspecialchars($condition); ?>" placeholder="Search condition..." class="filter-search">
                <datalist id="condition-list">
                    <?php foreach ($conditions as $c): ?>
                        <option value="<?php echo ucfirst($c); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-small"><i class="fas fa-filter"></i> Apply</button>
                <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-secondary btn-small"><i class="fas fa-times"></i> Clear</a>
            </div>
        </form>
    </aside>
    
    <!-- Main Content -->
    <main class="booklist-main">
        
        <!-- Page Header -->
        <div class="page-header">
            <?php if (!empty($genre)): ?>
                <h1><?php echo htmlspecialchars(ucfirst($genre)); ?> Books</h1>
                <nav class="breadcrumb-nav">
                    <a href="<?php echo SITE_URL; ?>/index.php">Home</a> 
                    <span class="separator">/</span>
                    <a href="<?php echo SITE_URL; ?>/page/booklist.php">Books</a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo htmlspecialchars(ucfirst($genre)); ?></span>
                </nav>
            <?php else: ?>
                <h1>Browse Books</h1>
                <nav class="breadcrumb-nav">
                    <a href="<?php echo SITE_URL; ?>/index.php">Home</a> 
                    <span class="separator">/</span>
                    <span class="current">Books</span>
                </nav>
            <?php endif; ?>
        </div>
    
    <!-- Books Grid -->
    <?php if (count($books) > 0): ?>
        
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-card-image-wrapper">
                    <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $book['book_id']; ?>">
                        <img src="<?php echo SITE_URL . '/' . get_book_cover($book['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                             class="book-card-image">
                    </a>
                    <?php if (is_logged_in()): ?>
                        <?php
                        // Check if book is in wishlist
                        $user_id = get_user_id();
                        $book_id = $book['book_id'];
                        $stmt = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND book_id = ?");
                        $stmt->bind_param('ii', $user_id, $book_id);
                        $stmt->execute();
                        $stmt->store_result();
                        $in_wishlist = $stmt->num_rows > 0;
                        $stmt->close();
                        ?>
                        <a href="<?php echo SITE_URL; ?>/order_cart_process/process/wishlist_process.php?action=<?php echo $in_wishlist ? 'remove' : 'add'; ?>&id=<?php echo $book['book_id']; ?>" 
                           class="wishlist-icon <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>">
                            <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        </a>
                    <?php endif; ?>
                    </div>
                    <div class="book-card-body">
                        <h3 class="book-card-title"><?php echo truncate(htmlspecialchars($book['title']), 40); ?></h3>
                        <p class="book-card-author"><?php echo htmlspecialchars($book['author'] ?? 'Unknown Author'); ?></p>
                        <?php if (!empty($book['genre'])): ?>
                            <span class="book-card-genre"><?php echo htmlspecialchars($book['genre']); ?></span>
                        <?php endif; ?>
                        <div class="book-card-price"><?php echo format_price($book['price'] ?? 0); ?></div>
                        <div class="book-card-actions">
                            <a href="<?php echo SITE_URL; ?>/page/book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-primary btn-small">View</a>
                            <?php if (($book['stock'] ?? 0) > 0): ?>
                                <a href="<?php echo SITE_URL; ?>/order_cart_process/process/cart_process.php?action=add&id=<?php echo $book['book_id']; ?>" 
                                   class="btn btn-small" style="background-color: #27ae60; color: white;">
                                    <i class="fas fa-cart-plus"></i> Add
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-small" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo SITE_URL; ?>/page/booklist.php?page=<?php echo $page - 1; ?><?php echo !empty($author) ? '&author=' . urlencode($author) : ''; ?><?php echo $year_from > 0 ? '&year_from=' . $year_from : ''; ?><?php echo $year_to > 0 ? '&year_to=' . $year_to : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?><?php echo !empty($condition) ? '&condition=' . urlencode($condition) : ''; ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/page/booklist.php?page=<?php echo $i; ?><?php echo !empty($author) ? '&author=' . urlencode($author) : ''; ?><?php echo $year_from > 0 ? '&year_from=' . $year_from : ''; ?><?php echo $year_to > 0 ? '&year_to=' . $year_to : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?><?php echo !empty($condition) ? '&condition=' . urlencode($condition) : ''; ?>" class="pagination-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo SITE_URL; ?>/page/booklist.php?page=<?php echo $page + 1; ?><?php echo !empty($author) ? '&author=' . urlencode($author) : ''; ?><?php echo $year_from > 0 ? '&year_from=' . $year_from : ''; ?><?php echo $year_to > 0 ? '&year_to=' . $year_to : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?><?php echo !empty($condition) ? '&condition=' . urlencode($condition) : ''; ?>" class="pagination-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Books Found -->
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h2>No Books Found</h2>
            <p>Try adjusting your filter criteria</p>
            <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-primary">View All Books</a>
        </div>
    <?php endif; ?>
    
    </main>
    
</div>

<script>
function toggleMobileFilters() {
    const sidebar = document.getElementById('filter-sidebar');
    sidebar.classList.toggle('mobile-open');
    document.body.classList.toggle('filter-open');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
