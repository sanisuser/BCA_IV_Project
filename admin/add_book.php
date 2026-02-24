<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = clean_input($_POST['title'] ?? '');
    $author = clean_input($_POST['author'] ?? '');
    $isbn = clean_input($_POST['isbn'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $genre = clean_input($_POST['genre'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $published_year = (int)($_POST['published_year'] ?? 0);
    $condition_status = clean_input($_POST['condition_status'] ?? 'new');

    if ($title === '' || $author === '') {
        $error = 'Title and author are required.';
    }

    $allowed = ['new', 'used'];
    if (!in_array($condition_status, $allowed, true)) {
        $condition_status = 'new';
    }

    $cover_image = '';
    if ($error === '' && isset($_FILES['cover_image']) && is_array($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
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
                    // Create genre-specific subfolder
                    $genre_folder = !empty($genre) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $genre) : 'Uncategorized';
                    $upload_dir = $assets_images_dir . DIRECTORY_SEPARATOR . 'books' . DIRECTORY_SEPARATOR . $genre_folder;
                    if (!is_dir($upload_dir)) {
                        @mkdir($upload_dir, 0777, true);
                    }
                    if (!is_dir($upload_dir)) {
                        $error = 'Failed to create upload directory.';
                    } else {
                        $filename = safe_filename(preg_replace('/[^a-zA-Z0-9_-]/', '_', $title) . '.' . $ext);
                        $dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
                        if (!move_uploaded_file($tmp, $dest)) {
                            $error = 'Failed to save uploaded cover image.';
                        } else {
                            $cover_image = 'assets/images/books/' . $genre_folder . '/' . $filename;
                        }
                    }
                }
            }
        }
    }

    if ($error === '') {
        $stmt = $conn->prepare(
            "INSERT INTO books (title, author, isbn, description, genre, price, stock, cover_image, published_year, condition_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            $error = 'Failed to prepare insert statement.';
        } else {
            $stmt->bind_param(
                'sssssdissi',
                $title,
                $author,
                $isbn,
                $description,
                $genre,
                $price,
                $stock,
                $cover_image,
                $published_year,
                $condition_status
            );

            if ($stmt->execute()) {
                $success = 'Book added successfully.';
                $stmt->close();
                redirect(SITE_URL . '/admin/manage_books.php?success=' . urlencode($success));
            } else {
                $error = 'Failed to insert book.';
                $stmt->close();
            }
        }
    }
}

$page_title = 'Add Book';
$active_page = 'books';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
            <div class="admin-header">
                <h1>Add Book</h1>
                <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">← Back to List</a>
            </div>

            <?php if (!empty($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="edit-form">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required />
                    </div>

                    <div class="form-group">
                        <label>Author *</label>
                        <input type="text" name="author" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>" required />
                    </div>

                    <div class="form-group">
                        <label>ISBN</label>
                        <input type="text" name="isbn" value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>" />
                    </div>

                    <div class="form-group">
                        <label>Genre</label>
                        <input type="text" name="genre" value="<?php echo htmlspecialchars($_POST['genre'] ?? ''); ?>" />
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Price (Rs)</label>
                            <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($_POST['price'] ?? '0.00'); ?>" />
                        </div>
                        <div class="form-group">
                            <label>Stock</label>
                            <input type="number" name="stock" value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>" />
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Published Year</label>
                            <input type="number" name="published_year" value="<?php echo htmlspecialchars($_POST['published_year'] ?? ''); ?>" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cover Image</label>
                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <img
                                id="addCoverPreview"
                                src=""
                                alt="Cover"
                                style="width: 90px; height: 130px; object-fit: cover; border-radius: 8px; border: 1px solid #e9ecef; display: none;"
                            />
                            <input type="file" name="cover_image" id="addCoverInput" accept="image/*" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Condition</label>
                        <select name="condition_status">
                            <?php $cs = $condition_status ?? 'new'; ?>
                            <option value="new" <?php echo $cs === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="used" <?php echo $cs === 'used' ? 'selected' : ''; ?>>Used</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" name="add_book" class="btn btn-success">Add Book</button>
                        <a href="<?php echo SITE_URL; ?>/admin/manage_books.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            <script>
                (function () {
                    var input = document.getElementById('addCoverInput');
                    var img = document.getElementById('addCoverPreview');
                    if (!input || !img) return;
                    input.addEventListener('change', function () {
                        var file = input.files && input.files[0];
                        if (!file) return;
                        img.style.display = 'block';
                        img.src = URL.createObjectURL(file);
                    });
                })();
            </script>
</body>
</html>
