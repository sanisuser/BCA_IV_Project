
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Profile';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to view your profile'));
}

$user_id = (int)get_user_id();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $ship_address = trim($_POST['ship_address'] ?? '');

    $current_password = (string)($_POST['current_password'] ?? '');
    $new_password = (string)($_POST['new_password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    $conn->begin_transaction();
    try {
        // Handle profile image upload (optional)
        $new_profile_path = null;
        if (isset($_FILES['profile_image']) && is_array($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($_FILES['profile_image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new Exception('Failed to upload profile image');
            }

            $tmp = $_FILES['profile_image']['tmp_name'] ?? '';
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                throw new Exception('Invalid uploaded file');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmp) : '';
            if ($finfo) finfo_close($finfo);

            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            if (!isset($allowed[$mime])) {
                throw new Exception('Invalid image type');
            }

            $filename = safe_filename('profile_' . $user_id . '.' . $allowed[$mime]);
            $destDir = __DIR__ . '/../assets/images/profiles';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $destPath = $destDir . '/' . $filename;
            if (!move_uploaded_file($tmp, $destPath)) {
                throw new Exception('Could not save uploaded image');
            }

            $new_profile_path = 'assets/images/profiles/' . $filename;
        }

        // Update basic fields
        $sql = 'UPDATE users SET full_name = ?, location = ?, ship_address = ?';
        $types = 'sss';
        $params = [$full_name, $location, $ship_address];

        if ($new_profile_path !== null) {
            $sql .= ', profile_image = ?';
            $types .= 's';
            $params[] = $new_profile_path;
        }
        $sql .= ' WHERE user_id = ?';
        $types .= 'i';
        $params[] = $user_id;

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error while updating profile');
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        // Password change (optional)
        if ($current_password !== '' || $new_password !== '' || $confirm_password !== '') {
            if ($current_password === '' || $new_password === '' || $confirm_password === '') {
                throw new Exception('Please fill all password fields to change password');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('New password and confirmation do not match');
            }
            if (strlen($new_password) < 6) {
                throw new Exception('New password must be at least 6 characters');
            }

            $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
            if (!$stmt) {
                throw new Exception('Database error while verifying password');
            }
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            $hash = $row['password'] ?? '';
            if ($hash === '' || !password_verify($current_password, $hash)) {
                throw new Exception('Current password is incorrect');
            }

            $newHash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            if (!$stmt) {
                throw new Exception('Database error while updating password');
            }
            $stmt->bind_param('si', $newHash, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $success = 'Profile updated successfully';
    } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Fetch user data
$user = null;
$stmt = $conn->prepare('SELECT user_id, username, email, full_name, profile_image, location, ship_address FROM users WHERE user_id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

if (!$user) {
    redirect(SITE_URL . '/index.php?error=' . urlencode('User not found'));
}

// Fetch real order history
$orders = [];
$stmt = $conn->prepare('SELECT order_id, total_amount, status, payment_method, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
}

// Get order items for each order
foreach ($orders as &$order) {
    $order['items'] = [];
    $stmt = $conn->prepare('
        SELECT oi.quantity, oi.price_at_time, b.title
        FROM order_items oi
        JOIN books b ON oi.book_id = b.book_id
        WHERE oi.order_id = ?
    ');
    if ($stmt) {
        $stmt->bind_param('i', $order['order_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $order['items'][] = $row;
        }
        $stmt->close();
    }
}
unset($order);

// Helper for status styling
function getOrderStatusClass($status) {
    return match($status) {
        'pending' => 'bg-amber-500/20 text-amber-400',
        'processing' => 'bg-blue-500/20 text-blue-400',
        'shipped' => 'bg-purple-500/20 text-purple-400',
        'delivered' => 'bg-green-500/20 text-green-400',
        'cancelled' => 'bg-red-500/20 text-red-400',
        default => 'bg-zinc-500/20 text-zinc-400'
    };
}

function getOrderStatusLabel($status) {
    return match($status) {
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        default => ucfirst((string)$status)
    };
}

$display_name = (string)($user['full_name'] ?? '');
if ($display_name === '') {
    $display_name = (string)($user['username'] ?? 'User');
}

$display_location = (string)($user['location'] ?? '');
$display_ship = (string)($user['ship_address'] ?? '');
$profile_image = (string)($user['profile_image'] ?? '');
$profile_image_url = $profile_image !== '' ? (SITE_URL . '/' . ltrim($profile_image, '/')) : 'https://i.pravatar.cc/300?u=' . urlencode((string)($user['username'] ?? 'user'));

require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/profile-modern.css">

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-header-left">
            <i class="fa-solid fa-user-circle profile-icon"></i>
            <h1 class="profile-title">My Profile</h1>
        </div>
        <div class="profile-header-right">
            <div class="profile-status-dot"></div>
            Logged in as <?php echo htmlspecialchars((string)($user['username'] ?? '')); ?>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="profile-sidebar">
            <div class="card card-sticky">
                <div class="profile-image-wrapper">
                    <img id="profileImage"
                         src="<?php echo htmlspecialchars($profile_image_url); ?>"
                         alt="Profile Picture"
                         class="profile-image">
                    <label for="profileUpload" class="profile-upload-btn">
                        <i class="fa-solid fa-camera"></i>
                        <span>Update</span>
                    </label>
                </div>

                <h2 id="displayName" class="profile-name"><?php echo htmlspecialchars($display_name); ?></h2>
                <p class="profile-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span id="displayLocation"><?php echo htmlspecialchars($display_location); ?></span>
                </p>

                <div class="profile-genres">
                    <p class="profile-genres-title">Favourite Genres</p>
                    <div id="previewGenres" class="genre-tags"></div>
                </div>
            </div>
        </div>

        <div class="form-container">
            <div class="card">
                <form id="profileForm" method="POST" enctype="multipart/form-data">
                    <input type="file" name="profile_image" id="profileUpload" accept="image/*" class="hidden">

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input id="nameInput" name="full_name" type="text" value="<?php echo htmlspecialchars((string)($user['full_name'] ?? '')); ?>"
                               class="form-input">
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Current Location</label>
                            <input id="locationInput" name="location" type="text" value="<?php echo htmlspecialchars($display_location); ?>"
                                   class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Shipping Location</label>
                            <input id="shippingInput" name="ship_address" type="text" value="<?php echo htmlspecialchars($display_ship); ?>"
                                   placeholder="Enter shipping address"
                                   class="form-input">
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="sameAsLocation" class="checkbox">
                        <label for="sameAsLocation" class="checkbox-label">
                            Set shipping location as my primary location
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Favourite Genres</label>
                        <div id="genreContainer" class="genre-pills-container"></div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <button type="button" onclick="togglePasswordSection()" class="password-toggle">
                            <div class="password-toggle-left">
                                <i class="fa-solid fa-lock"></i>
                                <span>Change Password</span>
                            </div>
                            <i id="passwordChevron" class="fa-solid fa-chevron-down chevron"></i>
                        </button>

                        <div id="passwordSection" class="password-section">
                            <div>
                                <label class="form-label">Current Password</label>
                                <input id="currentPass" name="current_password" type="password"
                                       class="form-input">
                            </div>
                            <div>
                                <label class="form-label">New Password</label>
                                <input id="newPass" name="new_password" type="password"
                                       class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Confirm New</label>
                                <input id="confirmPass" name="confirm_password" type="password"
                                       class="form-input">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        SAVE CHANGES
                    </button>
                </form>
            </div>

            <div class="order-history">
                <details <?php echo count($orders) > 0 ? 'open' : ''; ?>>
                    <summary>
                        <div class="order-history-header">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <div>
                                <p class="order-history-title">Order History</p>
                                <p class="order-count"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?></p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="transition: transform 0.3s;"></i>
                    </summary>
                    <div class="order-history-content">
                        <?php if (count($orders) > 0): ?>
                            <div class="order-list">
                                <?php foreach ($orders as $order): 
                                    $statusClass = 'order-status ' . $order['status'];
                                    $statusLabel = getOrderStatusLabel($order['status']);
                                    $orderDate = date('M d, Y', strtotime($order['created_at']));
                                    $itemCount = count($order['items']);
                                    $itemNames = array_slice(array_column($order['items'], 'title'), 0, 2);
                                    $itemText = implode(', ', $itemNames) . ($itemCount > 2 ? ' + ' . ($itemCount - 2) . ' more' : '');
                                ?>
                                    <div class="order-item-card">
                                        <div class="order-item-main">
                                            <div class="order-item-header">
                                                <span class="order-id">#<?php echo str_pad((string)$order['order_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                                <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                            </div>
                                            <p class="order-items-text"><?php echo htmlspecialchars($itemText ?: 'Order items'); ?></p>
                                            <p class="order-meta"><?php echo $orderDate; ?> • <?php echo format_price($order['total_amount']); ?></p>
                                        </div>
                                        <a href="<?php echo SITE_URL; ?>/order_cart_process/orders.php" class="btn-view-order">
                                            VIEW DETAILS
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-orders">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <p>No orders yet. Start shopping!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<div id="toast">
    <i class="fa-solid fa-check-circle"></i>
    <span id="toastText"></span>
</div>

<script>
window.__PROFILE__ = {
  welcomeName: <?php echo json_encode($display_name); ?>,
  genres: ["Sci-Fi", "Fantasy", "Mystery", "Thriller", "Biography", "Self-Help"],
  selectedGenres: []
};
</script>
<script src="<?php echo SITE_URL; ?>/auth/js/profile-modern.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

