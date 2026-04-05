
<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Profile';

// Ensure users table has ship_latitude, ship_longitude, ship_place_name columns
$columns_to_check = ['ship_latitude', 'ship_longitude', 'ship_place_name'];
foreach ($columns_to_check as $col) {
    $has_col = false;
    if ($res = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = '$col' LIMIT 1")) {
        $has_col = $res->num_rows > 0;
        $res->free();
    }
    if (!$has_col) {
        try {
            $conn->query("ALTER TABLE users ADD COLUMN $col VARCHAR(100) NULL");
        } catch (mysqli_sql_exception $e) {
            error_log("Failed to add $col column: " . $e->getMessage());
        }
    }
}

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please login to view your profile'));
}

$user_id = (int)get_user_id();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $full_name = ucwords(strtolower($full_name));
    $ship_address = trim($_POST['ship_address'] ?? '');
    if ($ship_address === '') {
        $ship_address = null;
    }
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Map coordinates
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $place_name = trim($_POST['place_name'] ?? '');

    $current_password = (string)($_POST['current_password'] ?? '');
    $new_password = (string)($_POST['new_password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    // Validation for full_name and phone
    if ($full_name !== '') {
        if (strlen($full_name) < 3) {
            $error = 'Full name must be at least 3 characters.';
        } elseif (strlen($full_name) > 100) {
            $error = 'Full name must not exceed 100 characters.';
        } elseif (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $full_name)) {
            $error = 'Full name can only contain letters, spaces, apostrophes, hyphens, and dots.';
        } elseif (!preg_match('/^[a-zA-Z]/', $full_name)) {
            $error = 'Full name must start with a letter.';
        } elseif (preg_match('/\s{2,}/', $full_name)) {
            $error = 'Full name must not contain multiple consecutive spaces.';
        } elseif (str_word_count($full_name) < 2) {
            $error = 'Please enter your full name (first and last name).';
        }
    }
    
    if ($error === '' && $phone !== '' && !preg_match('/^9[8-9][0-9]{8}$/', $phone)) {
        $error = 'Please enter a valid 10-digit mobile number';
    }

    if ($error !== '') {
        // Skip transaction if validation failed
    } else {
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

            $filename = safe_filename('user_' . $user_id . '_' . time() . '.' . $allowed[$mime]);
            $destDir = __DIR__ . '/../uploads/profiles';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $destPath = $destDir . '/' . $filename;
            if (!move_uploaded_file($tmp, $destPath)) {
                throw new Exception('Could not save uploaded image');
            }

            $new_profile_path = 'uploads/profiles/' . $filename;
        }

        // Update basic fields with coordinates
        $sql = 'UPDATE users SET full_name = ?, ship_address = ?, phone = ?, bio = ?, ship_latitude = ?, ship_longitude = ?, ship_place_name = ?';
        $types = 'sssssss';
        $params = [$full_name, $ship_address, $phone, $bio, $latitude, $longitude, $place_name];

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
}

// Fetch user data
$user = null;
$stmt = $conn->prepare('SELECT user_id, username, email, full_name, profile_image, ship_address, ship_latitude, ship_longitude, ship_place_name, phone, bio FROM users WHERE user_id = ?');
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

$display_ship = (string)($user['ship_address'] ?? '');
$display_email = (string)($user['email'] ?? '');
$display_phone = (string)($user['phone'] ?? '');
$display_bio = (string)($user['bio'] ?? '');
$profile_image = (string)($user['profile_image'] ?? '');
$profile_image_url = $profile_image !== '' ? (SITE_URL . '/' . ltrim($profile_image, '/')) : '';

require_once __DIR__ . '/../includes/header_navbar.php';
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/profile-modern.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

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
                         class="profile-image"
                         loading="lazy">
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

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input id="phoneInput" name="phone" type="tel" value="<?php echo htmlspecialchars($display_phone); ?>"
                               placeholder="Enter your phone number"
                               class="form-input">
                    </div>

                    <!-- Full Width Shipping Address with Map -->
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">

                        Shipping Address
                            <span style="color: #6c757d; font-size: 0.8rem; font-weight: normal;">(Click edit to select on map)</span>
                        </label>
                        <div style="position: relative;">
                            <textarea id="shippingInput" name="ship_address" rows="3" readonly
                                   placeholder="No shipping address set"
                                   class="form-input" style="background: #1a1a1a; border: 1px solid #333; color: #fff; resize: none; min-height: 60px; cursor: default; word-wrap: break-word; overflow-wrap: break-word;"><?php echo htmlspecialchars($display_ship); ?></textarea>
                            <button type="button" id="editLocationBtn" onclick="toggleMapSection()" style="position: absolute; top: 8px; right: 8px; background: #007bff; border: none; color: white; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                                <i class="fa-solid fa-map-pin"></i> Edit
                            </button>
                        </div>

                        <!-- Map Location Picker (Hidden by default) -->
                        <div id="mapSection" style="display: none; margin-top: 12px; position: relative;">
                            <!-- Selected location badge -->
                            <div id="selectedBadge" style="display: none; margin-bottom: 8px; padding: 10px 14px; background: #007bff; color: white; border-radius: 6px; font-size: 0.9rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                                <span style="word-wrap: break-word; overflow-wrap: break-word;"><i class="fa-solid fa-location-dot"></i> <span id="selectedPlace"></span></span>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <button type="button" onclick="confirmLocation()" style="background: #28a745; border: none; color: white; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                                        <i class="fa-solid fa-check"></i> Confirm
                                    </button>
                                    <button type="button" onclick="clearLocation()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.75rem;">
                                        <i class="fa-solid fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                            
                            <p style="font-size: 0.8rem; color: #6c757d; margin-bottom: 8px; margin-top: 0;">
                                <i class="fa-solid fa-hand-pointer"></i> Click anywhere on the map to set your delivery location
                            </p>
                            
                            <div id="map" style="height: 350px; border-radius: 8px; border: 1px solid var(--border); width: 100%; max-width: 100%; overflow: hidden;"></div>

                            <!-- Hidden fields for coordinates -->
                            <input type="hidden" name="latitude" id="lat" value="<?php echo htmlspecialchars($user['ship_latitude'] ?? ''); ?>">
                            <input type="hidden" name="longitude" id="lng" value="<?php echo htmlspecialchars($user['ship_longitude'] ?? ''); ?>">
                            <input type="hidden" name="place_name" id="placeName" value="<?php echo htmlspecialchars($user['ship_place_name'] ?? ''); ?>">
                        </div>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
/* Mobile Responsive Styles for Map */
@media (max-width: 768px) {
    /* Stack the location and shipping address columns */
    .profile-grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Make form grid single column */
    #profileForm > div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    /* Adjust map height for mobile */
    #map {
        height: 250px !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    /* Make search input and button stack on very small screens */
    @media (max-width: 480px) {
        #searchInput {
            font-size: 16px !important; /* Prevent zoom on iOS */
        }
        
        #suggestions {
            max-height: 120px !important;
        }
        
        #selectedBadge {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        
        #selectedBadge > div {
            width: 100% !important;
            justify-content: flex-start !important;
        }
    }
}

/* Touch-friendly styles */
@media (hover: none) and (pointer: coarse) {
    #suggestions div {
        padding: 12px !important;
        min-height: 44px; /* Minimum touch target size */
    }
    
    #suggestions div:active {
        background: #e9ecef !important;
    }
}
</style>

<script>
// --- LEAFLET MAP SETUP ---
var map, marker;
var mapInitialized = false;

function toggleMapSection() {
    const mapSection = document.getElementById('mapSection');
    const editBtn = document.getElementById('editLocationBtn');
    
    if (mapSection.style.display === 'none') {
        mapSection.style.display = 'block';
        editBtn.innerHTML = '<i class="fa-solid fa-times"></i> Cancel';
        
        // Initialize map only when first opened
        if (!mapInitialized) {
            initMap();
            mapInitialized = true;
        } else {
            // Refresh map size if already initialized
            setTimeout(function() {
                map.invalidateSize();
            }, 100);
        }
    } else {
        mapSection.style.display = 'none';
        editBtn.innerHTML = '<i class="fa-solid fa-map-pin"></i> Edit';
    }
}

function initMap() {
    // Default to Kathmandu if no coordinates
    var defaultLat = 27.7172;
    var defaultLng = 85.3240;
    
    // Check if user has existing coordinates
    var existingLat = document.getElementById('lat').value;
    var existingLng = document.getElementById('lng').value;
    
    if (existingLat && existingLng) {
        defaultLat = parseFloat(existingLat);
        defaultLng = parseFloat(existingLng);
    }
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // If there's an existing marker, show it and the badge
    if (existingLat && existingLng) {
        var placeName = document.getElementById('placeName').value || 'Saved location';
        setMarker(existingLat, existingLng, placeName);
    } else {
        // No saved location - field will use default styling
    }
    
    // Click on map to set marker
    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lng = e.latlng.lng;
        
        // Reverse geocode to get place name
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var placeName = data.display_name || 'Selected Location';
                setMarker(lat, lng, placeName);
            })
            .catch(function() {
                // Fallback if reverse geocoding fails
                setMarker(lat, lng, 'Location at ' + lat.toFixed(4) + ', ' + lng.toFixed(4));
            });
    });
}

function setMarker(lat, lng, name) {
    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng]).addTo(map);
    map.setView([lat, lng], 16);
    
    document.getElementById('lat').value = parseFloat(lat).toFixed(6);
    document.getElementById('lng').value = parseFloat(lng).toFixed(6);
    document.getElementById('placeName').value = name;
    
    // Show selected badge
    document.getElementById('selectedPlace').textContent = name;
    document.getElementById('selectedBadge').style.display = 'flex';
    
    // Update shipping address input with the selected place name
    document.getElementById('shippingInput').value = name;
    document.getElementById('shippingInput').style.background = '#1a1a1a';
    document.getElementById('shippingInput').style.borderColor = '#28a745';
    document.getElementById('shippingInput').style.color = '#fff';
}

function confirmLocation() {
    // Change badge style to show confirmed
    document.getElementById('selectedBadge').style.background = '#28a745';
    document.getElementById('shippingInput').style.borderColor = '#28a745';
    
    // Hide map section after confirmation
    setTimeout(function() {
        document.getElementById('mapSection').style.display = 'none';
        document.getElementById('editLocationBtn').innerHTML = '<i class="fa-solid fa-map-pin"></i> Edit';
    }, 1000);
    
    // Show confirmation toast
    showToast('Location confirmed!');
}

function clearLocation() {
    document.getElementById('lat').value = '';
    document.getElementById('lng').value = '';
    document.getElementById('placeName').value = '';
    document.getElementById('selectedPlace').textContent = '';
    document.getElementById('selectedBadge').style.display = 'none';
    
    // Reset shipping address
    document.getElementById('shippingInput').value = '';
    document.getElementById('shippingInput').style.background = '#1a1a1a';
    document.getElementById('shippingInput').style.borderColor = '#333';
    document.getElementById('shippingInput').style.color = '#fff';
    
    if (marker) {
        map.removeLayer(marker);
        marker = null;
    }
}

// Initialize map when page loads (but don't show it)
document.addEventListener('DOMContentLoaded', function() {
    // Map will be initialized when user clicks Edit button
});

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

