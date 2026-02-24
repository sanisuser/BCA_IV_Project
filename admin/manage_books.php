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
$search_column = isset($_GET['column']) ? clean_input($_GET['column']) : 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'book_id';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
$per_page = ($page === 1) ? 10 : 20;
$offset = ($page === 1) ? 0 : (10 + (20 * ($page - 2)));

// Validate search column
$allowed_columns = ['all', 'title', 'author', 'genre', 'price', 'stock', 'published_year'];
if (!in_array($search_column, $allowed_columns, true)) {
    $search_column = 'all';
}

// Validate sort column
$allowed_sort = ['book_id', 'title', 'author', 'genre', 'price', 'stock', 'published_year'];
if (!in_array($sort, $allowed_sort, true)) {
    $sort = 'book_id';
}

$allowed_order = ['ASC', 'DESC'];
if (!in_array($order, $allowed_order, true)) {
    $order = 'ASC';
}

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
    $cover_image = clean_input($_POST['current_cover_image'] ?? '');
    $published_year = (int)($_POST['published_year'] ?? 0);
    $condition_status = clean_input($_POST['condition_status'] ?? 'new');

    if ($edit_id <= 0) {
        $error = 'Invalid book id.';
    } elseif ($title === '' || $author === '') {
        $error = 'Title and author are required.';
    } else {
        $allowed = ['new', 'used'];
        if (!in_array($condition_status, $allowed, true)) {
            $condition_status = 'new';
        }

        if (isset($_FILES['cover_image']) && is_array($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['cover_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $error = 'Cover image upload failed.';
            } else {
                $tmp = (string)($_FILES['cover_image']['tmp_name'] ?? '');
                $orig = (string)($_FILES['cover_image']['name'] ?? '');
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (!in_array($ext, $allowed_ext, true)) {
                    $error = 'Cover image must be jpg, jpeg, png, webp, or gif.';
                } else {
                    $assets_images_dir = realpath(__DIR__ . '/../assets/images');
                    if ($assets_images_dir === false) {
                        $error = 'Upload directory not found.';
                    } else {
                        $upload_dir = $assets_images_dir . DIRECTORY_SEPARATOR . 'books';
                        if (!is_dir($upload_dir)) {
                            @mkdir($upload_dir, 0777, true);
                        }
                        if (!is_dir($upload_dir)) {
                            $error = 'Failed to create upload directory.';
                        } else {
                            $filename = safe_filename($orig);
                            $dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
                            if (!move_uploaded_file($tmp, $dest)) {
                                $error = 'Failed to save uploaded cover image.';
                            } else {
                                $cover_image = 'assets/images/books/' . $filename;
                            }
                        }
                    }
                }
            }
        }

        if ($error !== '') {
            redirect(SITE_URL . '/admin/manage_books.php?success=' . urlencode($success) . '&error=' . urlencode($error));
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
    if ($search_column === 'all') {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?");
        $stmt->bind_param('sss', $like, $like, $like);
    } elseif ($search_column === 'price') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM books WHERE price = ?");
        $stmt->bind_param('d', (float)$q);
    } elseif ($search_column === 'stock') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM books WHERE stock = ?");
        $stmt->bind_param('i', (int)$q);
    } elseif ($search_column === 'published_year') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM books WHERE published_year = ?");
        $stmt->bind_param('i', (int)$q);
    } else {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM books WHERE $search_column LIKE ?");
        $stmt->bind_param('s', $like);
    }
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

// Fetch books with sorting
$books = [];
if ($action === 'list') {
    if ($q !== '') {
        if ($search_column === 'all') {
            $like = '%' . $q . '%';
            $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ? ORDER BY $sort $order LIMIT ? OFFSET ?");
            $stmt->bind_param('sssii', $like, $like, $like, $per_page, $offset);
        } elseif ($search_column === 'price') {
            $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books WHERE price = ? ORDER BY $sort $order LIMIT ? OFFSET ?");
            $stmt->bind_param('dii', (float)$q, $per_page, $offset);
        } elseif ($search_column === 'stock') {
            $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books WHERE stock = ? ORDER BY $sort $order LIMIT ? OFFSET ?");
            $stmt->bind_param('iii', (int)$q, $per_page, $offset);
        } elseif ($search_column === 'published_year') {
            $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books WHERE published_year = ? ORDER BY $sort $order LIMIT ? OFFSET ?");
            $stmt->bind_param('iii', (int)$q, $per_page, $offset);
        } else {
            $like = '%' . $q . '%';
            $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books WHERE $search_column LIKE ? ORDER BY $sort $order LIMIT ? OFFSET ?");
            $stmt->bind_param('sii', $like, $per_page, $offset);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $books[] = $row; }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT book_id, title, author, genre, price, stock, cover_image, published_year, condition_status, created_at FROM books ORDER BY $sort $order LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $books[] = $row; }
        $stmt->close();
    }
}

// Helper function to generate sort URL
function sort_url(string $col, string $current_sort, string $current_order, string $q, int $page, string $search_column): string {
    $new_order = ($current_sort === $col && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $col,
        'order' => $new_order,
        'page' => 1
    ];
    if ($q !== '') {
        $params['q'] = $q;
        $params['column'] = $search_column;
    }
    return SITE_URL . '/admin/manage_books.php?' . http_build_query($params);
}

// Helper function for sort icon
function sort_icon(string $col, string $current_sort, string $current_order): string {
    if ($current_sort !== $col) {
        return '<i class="fas fa-sort" style="color: #adb5bd; margin-left: 0.25rem;"></i>';
    }
    return $current_order === 'ASC' 
        ? '<i class="fas fa-sort-up" style="color: #3498db; margin-left: 0.25rem;"></i>'
        : '<i class="fas fa-sort-down" style="color: #3498db; margin-left: 0.25rem;"></i>';
}
$active_page = 'books';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
            <?php if ($action === 'list'): ?>
                <div class="admin-header">
                    <h1>Manage Books</h1>
                    <div style="color: #6c757d;">Total: <?php echo (int)$total_books; ?> books</div>
                </div>

                <div class="filter-bar" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <!-- Form -->
                <form method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; flex: 1;">
                    <select name="column" class="admin-input" style="min-width: 120px;">
                        <option value="all" <?php echo $search_column === 'all' ? 'selected' : ''; ?>>All Columns</option>
                        <option value="title" <?php echo $search_column === 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="author" <?php echo $search_column === 'author' ? 'selected' : ''; ?>>Author</option>
                        <option value="genre" <?php echo $search_column === 'genre' ? 'selected' : ''; ?>>Genre</option>
                        <option value="price" <?php echo $search_column === 'price' ? 'selected' : ''; ?>>Price</option>
                        <option value="stock" <?php echo $search_column === 'stock' ? 'selected' : ''; ?>>Stock</option>
                        <option value="published_year" <?php echo $search_column === 'published_year' ? 'selected' : ''; ?>>Year</option>
                    </select>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search..." style="min-width: 200px;" />
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>" />
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>" />
                    <input type="hidden" name="page" value="1" />
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">Clear</a>
                </form>

                <!-- Add Book button -->
                <a href="<?php echo SITE_URL; ?>/admin/add_book.php" 
                class="btn btn-secondary" style="margin-left: auto;">
                    <i class="fa-solid fa-circle-plus"></i> Add Book
                </a>
            </div>

                <?php if (!empty($success)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="data-table-container" style="overflow-x: auto;">
                    <table class="data-table" style="width: 100%; min-width: 900px;">
                        <thead>
                            <tr>
                                <th><a href="<?php echo sort_url('book_id', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">ID<?php echo sort_icon('book_id', $sort, $order); ?></a></th>
                                <th>Cover</th>
                                <th><a href="<?php echo sort_url('title', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">Title<?php echo sort_icon('title', $sort, $order); ?></a></th>
                                <th><a href="<?php echo sort_url('author', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">Author<?php echo sort_icon('author', $sort, $order); ?></a></th>
                                <th><a href="<?php echo sort_url('genre', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">Genre<?php echo sort_icon('genre', $sort, $order); ?></a></th>
                                <th><a href="<?php echo sort_url('price', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">Price<?php echo sort_icon('price', $sort, $order); ?></a></th>
                                <th><a href="<?php echo sort_url('stock', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">Stock<?php echo sort_icon('stock', $sort, $order); ?></a></th>
                                <th>Condition</th>
                                <th><a href="<?php echo sort_url('published_year', $sort, $order, $q, $page, $search_column); ?>" style="text-decoration: none; color: inherit;">Year<?php echo sort_icon('published_year', $sort, $order); ?></a></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $b): ?>
                            <tr>
                                <td><?php echo (int)$b['book_id']; ?></td>
                                <td>
                                    <?php if (!empty($b['cover_image'])): ?>
                                        <img
                                            src="<?php echo SITE_URL . '/' . htmlspecialchars(ltrim($b['cover_image'], '/')); ?>"
                                            alt="Cover"
                                            style="width: 42px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef;"
                                        />
                                    <?php else: ?>
                                        <i class="fas fa-times" style="color: #dc3545;"></i>
                                    <?php endif; ?>
                                </td>
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
                                <td class="actions" style="white-space: nowrap; vertical-align: middle;">
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: nowrap; align-items: center; justify-content: center;">
                                        <a href="<?php echo SITE_URL; ?>/admin/manage_books.php?action=edit&id=<?php echo (int)$b['book_id']; ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>&q=<?php echo urlencode($q); ?>&column=<?php echo urlencode($search_column); ?>&page=<?php echo $page; ?>" class="btn btn-primary btn-small"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="<?php echo SITE_URL; ?>/admin/manage_books.php?action=delete&id=<?php echo (int)$b['book_id']; ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>&q=<?php echo urlencode($q); ?>&column=<?php echo urlencode($search_column); ?>&page=<?php echo $page; ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete this book?')"><i class="fas fa-trash"></i> Delete</a>
                                    </div>
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
                            <a class="btn btn-secondary pagination-prev" href="<?php echo SITE_URL; ?>/admin/manage_books.php?q=<?php echo urlencode($q); ?>&column=<?php echo urlencode($search_column); ?>&page=<?php echo (int)($page - 1); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">← Prev</a>
                        <?php endif; ?>
                        <span style="color: #6c757d;">Page <?php echo (int)$page; ?> of <?php echo (int)$total_pages; ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a class="btn btn-secondary pagination-next" href="<?php echo SITE_URL; ?>/admin/manage_books.php?q=<?php echo urlencode($q); ?>&column=<?php echo urlencode($search_column); ?>&page=<?php echo (int)($page + 1); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($action === 'edit' && $book): ?>
                <div class="admin-header">
                    <h1>Edit Book #<?php echo (int)$book['book_id']; ?></h1>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="edit-form">
                    <form method="POST" action="<?php echo SITE_URL; ?>/admin/manage_books.php?action=edit&id=<?php echo (int)$book['book_id']; ?>" enctype="multipart/form-data">
                        <input type="hidden" name="book_id" value="<?php echo (int)$book['book_id']; ?>" />
                        <input type="hidden" name="current_cover_image" value="<?php echo htmlspecialchars($book['cover_image'] ?? ''); ?>" />
                        
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
                            <label>Cover Image</label>
                            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img
                                        id="editCoverPreview"
                                        src="<?php echo SITE_URL . '/' . htmlspecialchars(ltrim($book['cover_image'], '/')); ?>"
                                        alt="Cover"
                                        style="width: 90px; height: 130px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef;"
                                    />
                                <?php else: ?>
                                    <img
                                        id="editCoverPreview"
                                        src=""
                                        alt="Cover"
                                        style="width: 90px; height: 130px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef; display: none;"
                                    />
                                <?php endif; ?>
                                <input type="file" name="cover_image" id="editCoverInput" accept="image/*" />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Condition</label>
                            <select name="condition_status">
                                <option value="new" <?php echo (($book['condition_status'] ?? '') === 'new') ? 'selected' : ''; ?>>New</option>
                                <option value="used" <?php echo (($book['condition_status'] ?? '') === 'used') ? 'selected' : ''; ?>>Used</option>
                                
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
                <script>
                    (function () {
                        var input = document.getElementById('editCoverInput');
                        var img = document.getElementById('editCoverPreview');
                        if (!input || !img) return;
                        input.addEventListener('change', function () {
                            var file = input.files && input.files[0];
                            if (!file) return;
                            img.style.display = 'block';
                            img.src = URL.createObjectURL(file);
                        });
                    })();
                </script>
            <?php endif; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
