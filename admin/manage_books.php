<?php
/**

 *
 * Manage books - tabular list with filter, pagination, and separate edit page.
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
$action = $_GET['action'] ?? 'list';
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q = trim($_GET['q'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ($page === 1) ? 10 : 20;
$offset = ($page === 1) ? 0 : (10 + (20 * ($page - 2)));

// Handle delete
if ($action === 'delete' && $book_id > 0) {
    $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    $stmt->bind_param('i', $book_id);
    if ($stmt->execute()) {
        $success = 'Book deleted successfully.';
        $max_id = 0;
        if ($r = $conn->query("SELECT MAX(book_id) AS max_id FROM books")) {
            $row = $r->fetch_assoc();
            $max_id = (int)($row['max_id'] ?? 0);
            $r->free();
        }
        $next_id = $max_id + 1;
        $conn->query("ALTER TABLE books AUTO_INCREMENT = " . (int)$next_id);
    } else {
        $error = 'Failed to delete book.';
    }
    $stmt->close();
    redirect(SITE_URL . '/admin/manage_books.php?success=' . urlencode($success) . '&error=' . urlencode($error));
}

// Handle edit form save
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    $edit_id = (int)($_POST['book_id'] ?? 0);
    $title = clean_input($_POST['title'] ?? '');
    $author = clean_input($_POST['author'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $genre = clean_input($_POST['genre'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $cover_image = clean_input($_POST['cover_image'] ?? '');
    $published_year = (int)($_POST['published_year'] ?? 0);
    $condition_status = clean_input($_POST['condition_status'] ?? 'new');

    if ($edit_id <= 0) {
        $error = 'Invalid book id.';
    } elseif ($title === '' || $author === '') {
        $error = 'Title and author are required.';
    } else {
        $allowed = ['new', 'used', 'rare'];
        if (!in_array($condition_status, $allowed, true)) {
            $condition_status = 'new';
        }
        $stmt = $conn->prepare(
            "UPDATE books SET title=?, author=?, description=?, genre=?, price=?, stock=?, cover_image=?, published_year=?, condition_status=? WHERE book_id=?"
        );
        $stmt->bind_param('ssssdisisi', $title, $author, $description, $genre, $price, $stock, $cover_image, $published_year, $condition_status, $edit_id);
        if ($stmt->execute()) {
            $success = 'Book updated successfully.';
        } else {
            $error = 'Failed to update book.';
        }
        $stmt->close();
    }
    redirect(SITE_URL . '/admin/manage_books.php?success=' . urlencode($success) . '&error=' . urlencode($error));
}

// Get book for editing
$book = null;
if ($action === 'edit' && $book_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $book = $res->fetch_assoc();
    $stmt->close();
}

// Messages
$success = $success !== '' ? $success : (string)($_GET['success'] ?? '');
$error = $error !== '' ? $error : (string)($_GET['error'] ?? '');

// Count books
$total_books = 0;
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?");
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $total_books = (int)($row['c'] ?? 0);
    $stmt->close();
} else {
    $r = $conn->query("SELECT COUNT(*) AS c FROM books");
    $row = $r ? $r->fetch_assoc() : null;
    $total_books = (int)($row['c'] ?? 0);
    if ($r) { $r->free(); }
}

// Fetch books ascending
$books = [];
if ($action === 'list') {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ? ORDER BY book_id ASC LIMIT ? OFFSET ?");
        $stmt->bind_param('sssii', $like, $like, $like, $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $books[] = $row; }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books ORDER BY book_id ASC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $books[] = $row; }
        $stmt->close();
    }
}

$page_title = 'Manage Books';
$active_page = 'books';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
            <?php if ($action === 'list'): ?>
                <div class="admin-header">
                    <h1>Manage Books</h1>
                    <div style="color: #6c757d;">Total: <?php echo (int)$total_books; ?> books</div>
                </div>

                <div class="filter-bar">
                    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search title, author, genre..." />
                        <input type="hidden" name="page" value="1" />
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">Clear</a>
                    </form>
                </div>

                <?php if (!empty($success)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Genre</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Condition</th>
                                <th>Year</th>
                                <th>Cover</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $b): ?>
                            <tr>
                                <td><?php echo (int)$b['book_id']; ?></td>
                                <td><?php echo htmlspecialchars($b['title']); ?></td>
                                <td><?php echo htmlspecialchars($b['author']); ?></td>
                                <td><?php echo htmlspecialchars($b['genre'] ?? '-'); ?></td>
                                <td><?php echo format_price((float)($b['price'] ?? 0)); ?></td>
                                <td><?php echo (int)($b['stock'] ?? 0); ?></td>
                                <td>
                                    <?php $cond = $b['condition_status'] ?? 'new'; ?>
                                    <span class="badge badge-<?php echo $cond; ?>"><?php echo ucfirst($cond); ?></span>
                                </td>
                                <td><?php echo (int)($b['published_year'] ?? 0) ?: '-'; ?></td>
                                <td><?php echo !empty($b['cover_image']) ? '<i class="fas fa-image" style="color: #28a745;"></i>' : '<i class="fas fa-times" style="color: #dc3545;"></i>'; ?></td>
                                <td class="actions">
                                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php?action=edit&id=<?php echo (int)$b['book_id']; ?>" class="btn btn-primary btn-small">Edit</a>
                                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php?action=delete&id=<?php echo (int)$b['book_id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete this book?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $total_pages = 1;
                if ($total_books > 10) {
                    $remaining = $total_books - 10;
                    $total_pages = 1 + (int)ceil($remaining / 20);
                }
                ?>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/admin/manage_books.php?q=<?php echo urlencode($q); ?>&page=<?php echo (int)($page - 1); ?>">← Prev</a>
                        <?php endif; ?>
                        <span style="color: #6c757d;">Page <?php echo (int)$page; ?> of <?php echo (int)$total_pages; ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/admin/manage_books.php?q=<?php echo urlencode($q); ?>&page=<?php echo (int)($page + 1); ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'edit' && $book): ?>
                <div class="admin-header">
                    <h1>Edit Book #<?php echo (int)$book['book_id']; ?></h1>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="edit-form">
                    <form method="POST" action="<?php echo SITE_URL; ?>/admin/manage_books.php?action=edit&id=<?php echo (int)$book['book_id']; ?>">
                        <input type="hidden" name="book_id" value="<?php echo (int)$book['book_id']; ?>" />
                        
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label>Author *</label>
                            <input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label>Genre</label>
                            <input type="text" name="genre" value="<?php echo htmlspecialchars($book['genre'] ?? ''); ?>" />
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Price (Rs)</label>
                                <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars((string)($book['price'] ?? '0.00')); ?>" />
                            </div>
                            <div class="form-group">
                                <label>Stock</label>
                                <input type="number" name="stock" value="<?php echo htmlspecialchars((string)($book['stock'] ?? 0)); ?>" />
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Published Year</label>
                                <input type="number" name="published_year" value="<?php echo htmlspecialchars((string)($book['published_year'] ?? 0)); ?>" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Cover Image Filename</label>
                            <input type="text" name="cover_image" value="<?php echo htmlspecialchars($book['cover_image'] ?? ''); ?>" placeholder="e.g., book-cover.jpg" />
                        </div>
                        
                        <div class="form-group">
                            <label>Condition</label>
                            <select name="condition_status">
                                <option value="new" <?php echo (($book['condition_status'] ?? '') === 'new') ? 'selected' : ''; ?>>New</option>
                                <option value="used" <?php echo (($book['condition_status'] ?? '') === 'used') ? 'selected' : ''; ?>>Used</option>
                                <option value="rare" <?php echo (($book['condition_status'] ?? '') === 'rare') ? 'selected' : ''; ?>>Rare</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" name="save_book" class="btn btn-success">Save Changes</button>
                            <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
