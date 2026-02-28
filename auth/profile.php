
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
    $ship_address = trim($_POST['ship_address'] ?? '');
    if ($ship_address === '') {
        $ship_address = null;
    }
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $set_as_location = isset($_POST['set_as_location']) && $_POST['set_as_location'] === 'on';
    $set_shipping_as_location = isset($_POST['set_shipping_as_location']) && $_POST['set_shipping_as_location'] === 'on';

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
        $sql = 'UPDATE users SET full_name = ?, ship_address = ?, phone = ?, bio = ?, location = ?';
        $types = 'sssss';
        // If set_as_location is checked, use ship_address as location
        // If set_shipping_as_location is checked, use location as ship_address
        $final_location = $set_as_location && $ship_address ? $ship_address : $location;
        $final_ship_address = $set_shipping_as_location && $location ? $location : $ship_address;
        $params = [$full_name, $final_ship_address, $phone, $bio, $final_location];

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
$stmt = $conn->prepare('SELECT user_id, username, email, full_name, profile_image, location, ship_address, phone, bio FROM users WHERE user_id = ?');
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
$stmt = $conn->prepare("SELECT order_id, total_amount, status, payment_method, created_at, admin_remark, user_note FROM orders WHERE user_id = ? AND status IN ('delivered','cancelled') ORDER BY created_at DESC LIMIT 10");
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
$display_email = (string)($user['email'] ?? '');
$display_phone = (string)($user['phone'] ?? '');
$display_bio = (string)($user['bio'] ?? '');
$profile_image = (string)($user['profile_image'] ?? '');
$profile_image_url = $profile_image !== '' ? (SITE_URL . '/' . ltrim($profile_image, '/')) : '';

require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/profile-modern.css">

<div class="profile-container">

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
                    <?php if ($profile_image_url !== ''): ?>
                    <img id="profileImage"
                         src="<?php echo htmlspecialchars($profile_image_url); ?>"
                         alt="Profile Picture"
                         class="profile-image">
                    <?php else: ?>
                    <div id="profileImage" class="profile-image-placeholder" style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 3rem;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <?php endif; ?>
                    <label for="profileUpload" class="profile-upload-btn">
                        <i class="fa-solid fa-camera"></i>
                        <span>Update</span>
                    </label>
                </div>

                <h2 id="displayName" class="profile-name"><?php echo htmlspecialchars($display_name); ?></h2>
                <p class="profile-email" style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-envelope" style="margin-right: 0.5rem;"></i>
                    <?php echo htmlspecialchars($display_email); ?>
                </p>
                <?php if ($display_phone !== ''): ?>
                <p class="profile-phone" style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-phone" style="margin-right: 0.5rem;"></i>
                    <?php echo htmlspecialchars($display_phone); ?>
                </p>
                <?php endif; ?>
                <?php if ($display_location !== ''): ?>
                <p class="profile-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <span id="displayLocation"><?php echo htmlspecialchars($display_location); ?></span>
                </p>
                <?php endif; ?>
                <?php if ($display_bio !== ''): ?>
                <p class="profile-bio" style="color: var(--text-muted); font-size: 0.875rem; margin-top: 1rem; font-style: italic;">
                    "<?php echo htmlspecialchars($display_bio); ?>"
                </p>
                <?php endif; ?>
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

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input id="locationInput" name="location" type="text" value="<?php echo htmlspecialchars($display_location); ?>"
                                   placeholder="Enter your location (city, country)"
                                   class="form-input">
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary); cursor: pointer;">
                                <input type="checkbox" id="setShippingAsLocation" name="set_shipping_as_location" style="cursor: pointer;">
                                <span>Use location as shipping address</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <input id="shippingInput" name="ship_address" type="text" value="<?php echo htmlspecialchars($display_ship); ?>"
                                   placeholder="Enter shipping address"
                                   class="form-input">
                                                   </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input id="phoneInput" name="phone" type="tel" value="<?php echo htmlspecialchars($display_phone); ?>"
                               placeholder="Enter your phone number"
                               class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio</label>
                        <textarea id="bioInput" name="bio" rows="4"
                                  placeholder="Tell us about yourself..."
                                  class="form-input" style="resize: vertical;"><?php echo htmlspecialchars($display_bio); ?></textarea>
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
                                <div style="position: relative;">
                                    <input id="currentPass" name="current_password" type="password"
                                           class="form-input" style="padding-right: 45px;">
                                    <button type="button" onclick="togglePasswordField('currentPass')"
                                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                                                   background: none; border: none; color: #a1a1aa; cursor: pointer;
                                                   padding: 5px; font-size: 16px;">
                                        <i class="fas fa-eye" id="currentPass-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">New Password</label>
                                <div style="position: relative;">
                                    <input id="newPass" name="new_password" type="password"
                                           class="form-input" style="padding-right: 45px;">
                                    <button type="button" onclick="togglePasswordField('newPass')"
                                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                                                   background: none; border: none; color: #a1a1aa; cursor: pointer;
                                                   padding: 5px; font-size: 16px;">
                                        <i class="fas fa-eye" id="newPass-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Confirm New</label>
                                <div style="position: relative;">
                                    <input id="confirmPass" name="confirm_password" type="password"
                                           class="form-input" style="padding-right: 45px;">
                                    <button type="button" onclick="togglePasswordField('confirmPass')"
                                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                                                   background: none; border: none; color: #a1a1aa; cursor: pointer;
                                                   padding: 5px; font-size: 16px;">
                                        <i class="fas fa-eye" id="confirmPass-eye"></i>
                                    </button>
                                </div>
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
                                                <span class="order-id"><?php echo htmlspecialchars($itemText ?: 'Order items'); ?></span>
                                                <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                            </div>
                                            <p class="order-items-text"><?php echo htmlspecialchars($itemText ?: 'Order items'); ?></p>
                                            <p class="order-meta"><?php echo $orderDate; ?> • <?php echo format_price($order['total_amount']); ?></p>
                                            <?php if (!empty($order['admin_remark'])): ?>
                                            <p class="order-remark" style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; border-radius: 0 4px 4px 0; font-size: 0.875rem; color: #fca5a5;">
                                                <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem; color: #ef4444;"></i>
                                                <strong>Remark:</strong> <?php echo htmlspecialchars($order['admin_remark']); ?>
                                            </p>
                                            <?php endif; ?>
                                            <?php if (!empty($order['user_note'])): ?>
                                            <p class="order-remark" style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(34, 197, 94, 0.1); border-left: 3px solid #22c55e; border-radius: 0 4px 4px 0; font-size: 0.875rem; color: #86efac;">
                                                <i class="fa-solid fa-comment" style="margin-right: 0.5rem; color: #22c55e;"></i>
                                                <strong>Your Note:</strong> <?php echo htmlspecialchars($order['user_note']); ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
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

<script src="<?php echo SITE_URL; ?>/auth/js/profile-modern.js"></script>

<script>
// Handle "Set shipping address as my location" checkbox
document.getElementById('setAsLocation').addEventListener('change', function() {
    const locationInput = document.getElementById('locationInput');
    const shippingInput = document.getElementById('shippingInput');
    const setShippingAsLocation = document.getElementById('setShippingAsLocation');
    
    if (this.checked) {
        // Uncheck the other checkbox
        setShippingAsLocation.checked = false;
        shippingInput.readOnly = false;
        shippingInput.style.opacity = '1';
        // Copy shipping address to location
        locationInput.value = shippingInput.value;
        locationInput.readOnly = true;
        locationInput.style.opacity = '0.6';
    } else {
        locationInput.readOnly = false;
        locationInput.style.opacity = '1';
    }
});

// Handle "Use location as shipping address" checkbox
document.getElementById('setShippingAsLocation').addEventListener('change', function() {
    const locationInput = document.getElementById('locationInput');
    const shippingInput = document.getElementById('shippingInput');
    
    if (this.checked) {
        // Copy location to shipping address
        shippingInput.value = locationInput.value;
        shippingInput.readOnly = true;
        shippingInput.style.opacity = '0.6';
    } else {
        shippingInput.readOnly = false;
        shippingInput.style.opacity = '1';
    }
});

// Update shipping address when location changes if checkbox is checked
document.getElementById('locationInput').addEventListener('input', function() {
    const setShippingAsLocationCheckbox = document.getElementById('setShippingAsLocation');
    const shippingInput = document.getElementById('shippingInput');
    
    if (setShippingAsLocationCheckbox.checked) {
        shippingInput.value = this.value;
    }
});

// Before form submit, ensure shipping address is synced if checkbox is checked
document.getElementById('profileForm').addEventListener('submit', function() {
    const setShippingAsLocationCheckbox = document.getElementById('setShippingAsLocation');
    const locationInput = document.getElementById('locationInput');
    const shippingInput = document.getElementById('shippingInput');
    
    if (setShippingAsLocationCheckbox.checked) {
        shippingInput.value = locationInput.value;
    }
});
</script>

