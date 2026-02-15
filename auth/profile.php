<?php
/**
 * auth/profile.php
 * 
 * User Profile Page - Display and Edit Profile
 */

require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    require_once __DIR__ . '/../includes/header_navbar.php';
    redirect(SITE_URL . '/auth/login.php');
}

require_once __DIR__ . '/../includes/db.php';

$user_id = get_user_id();
$success = '';
$error = '';

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    redirect(SITE_URL . '/index.php');
}

// Fetch user's order history (delivered orders only)
$order_history = [];
$stmt = $conn->prepare('
    SELECT o.*, oi.book_id, oi.quantity, oi.price_at_time, b.title, b.author, b.cover_image, b.isbn
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN books b ON oi.book_id = b.book_id
    WHERE o.user_id = ? AND o.status = "delivered"
    ORDER BY o.created_at DESC
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_history[] = $row;
}
$stmt->close();

// Handle adding address from profile location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $address_type = clean_input($_POST['address_type'] ?? 'shipping');
    $full_name = clean_input($_POST['full_name'] ?? '');
    $address_line1 = clean_input($_POST['address_line1'] ?? '');
    $address_line2 = clean_input($_POST['address_line2'] ?? '');
    $city = clean_input($_POST['city'] ?? '');
    $state = clean_input($_POST['state'] ?? '');
    $postal_code = clean_input($_POST['postal_code'] ?? '');
    $country = clean_input($_POST['country'] ?? 'Nepal');
    $phone = clean_input($_POST['phone'] ?? '');
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if (!empty($full_name) && !empty($address_line1) && !empty($city)) {
        $stmt = $conn->prepare("
            INSERT INTO user_addresses 
            (user_id, address_type, full_name, address_line1, address_line2, city, state, postal_code, country, phone, is_default, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param('ssssssssssi', 
            $user_id, $address_type, $full_name, $address_line1, $address_line2, 
            $city, $state, $postal_code, $country, $phone, $is_default
        );
        
        if ($stmt->execute()) {
            $success = 'Address added successfully from your profile location!';
        } else {
            $error = 'Failed to add address. Please try again.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all required address fields.';
    }
}

// Handle setting default address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_default_address'])) {
    $address_id = (int)$_POST['set_default_address'];
    
    // First, unset all default addresses for this user
    $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    
    // Then set the new default
    $stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $address_id, $user_id);
    
    if ($stmt->execute()) {
        $success = 'Default address updated successfully!';
    } else {
        $error = 'Failed to update default address.';
    }
    $stmt->close();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $location = clean_input($_POST['location'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $bio = clean_input($_POST['bio'] ?? '');
    $is_autosave = isset($_POST['autosave']);
    
    $profile_image = $user['profile_image'] ?? '';
    
    // Handle profile image upload (works for both manual and autosave)
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['profile_image']['tmp_name'];
            $orig = $_FILES['profile_image']['name'];
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed_ext, true)) {
                $upload_dir = realpath(__DIR__ . '/../assets/images/profiles');
                if ($upload_dir === false) {
                    @mkdir(__DIR__ . '/../assets/images/profiles', 0777, true);
                    $upload_dir = realpath(__DIR__ . '/../assets/images/profiles');
                }
                
                if ($upload_dir !== false) {
                    $filename = safe_filename($orig);
                    $dest = $upload_dir . DIRECTORY_SEPARATOR . $filename;
                    if (move_uploaded_file($tmp, $dest)) {
                        // Delete old profile image if exists
                        if (!empty($profile_image)) {
                            $old_path = realpath(__DIR__ . '/../' . $profile_image);
                            if ($old_path && file_exists($old_path)) {
                                @unlink($old_path);
                            }
                        }
                        $profile_image = 'assets/images/profiles/' . $filename;
                    }
                }
            }
        }
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, location = ?, phone = ?, bio = ?, profile_image = ? WHERE user_id = ?");
    $stmt->bind_param('sssssi', $full_name, $location, $phone, $bio, $profile_image, $user_id);
    
    if ($stmt->execute()) {
        if ($is_autosave) {
            // Silent response for autosave
            http_response_code(200);
            echo 'OK';
            exit;
        }
        $success = 'Profile updated successfully!';
        // Refresh user data
        $stmt->close();
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } else {
        if ($is_autosave) {
            http_response_code(500);
            echo 'Error';
            exit;
        }
        $error = 'Failed to update profile.';
        $stmt->close();
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/profile.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/profile-address.css">

<div class="profile-container">
    <div class="profile-header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        <p>Manage your personal information and settings</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Profile Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-image-container profile-image-clickable" onclick="document.getElementById('profile_image').click()">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($user['profile_image']); ?>" 
                             alt="Profile Picture" 
                             class="profile-image" id="profile-preview">
                        <div class="profile-image-placeholder" id="profile-placeholder" style="display:none;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php else: ?>
                        <img src="" alt="Profile Picture" class="profile-image" id="profile-preview" style="display:none;">
                        <div class="profile-image-placeholder" id="profile-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <span class="profile-image-edit-icon"><i class="fas fa-camera"></i></span>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h2>
                <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                <span class="profile-role <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value">
                            <?php
                            $cart_count = $conn->query("SELECT COUNT(*) as c FROM cart WHERE user_id = $user_id")->fetch_assoc()['c'] ?? 0;
                            echo $cart_count;
                            ?>
                        </span>
                        <span class="stat-label">Cart Items</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">
                            <?php
                            $wishlist_count = $conn->query("SELECT COUNT(*) as c FROM wishlist WHERE user_id = $user_id")->fetch_assoc()['c'] ?? 0;
                            echo $wishlist_count;
                            ?>
                        </span>
                        <span class="stat-label">Wishlist</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">
                            <?php
                            $order_count = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id = $user_id")->fetch_assoc()['c'] ?? 0;
                            echo $order_count;
                            ?>
                        </span>
                        <span class="stat-label">Orders</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Content -->
        <div class="profile-content">
            <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
                <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden-file-input">
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   disabled class="disabled-input">
                            <small>Username cannot be changed</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   disabled class="disabled-input">
                            <small>Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="Enter your phone number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" 
                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                               placeholder="Enter your city/country">
                    </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Shipping Addresses</h3>
                    
                    <?php
                    // Fetch user addresses
                    $addresses = [];
                    $addr_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
                    $addr_stmt->bind_param('i', $user_id);
                    $addr_stmt->execute();
                    $addr_result = $addr_stmt->get_result();
                    while ($addr_row = $addr_result->fetch_assoc()) {
                        $addresses[] = $addr_row;
                    }
                    $addr_stmt->close();
                    ?>
                    
                    <?php if (count($addresses) > 0): ?>
                        <div class="addresses-list">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-card <?php echo $addr['is_default'] ? 'default-address' : ''; ?>">
                                    <div class="address-header">
                                        <span class="address-type"><?php echo ucfirst($addr['address_type']); ?></span>
                                        <?php if ($addr['is_default']): ?>
                                            <span class="default-badge">Default</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-details">
                                        <p><strong><?php echo htmlspecialchars($addr['full_name']); ?></strong></p>
                                        <p><?php echo nl2br(htmlspecialchars($addr['address_line1'])); ?></p>
                                        <?php if (!empty($addr['address_line2'])): ?>
                                            <p><?php echo nl2br(htmlspecialchars($addr['address_line2'])); ?></p>
                                        <?php endif; ?>
                                        <p><?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['state'] ?? ''); ?> <?php echo htmlspecialchars($addr['postal_code']); ?></p>
                                        <p><?php echo htmlspecialchars($addr['country']); ?></p>
                                        <?php if (!empty($addr['phone'])): ?>
                                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($addr['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-actions">
                                        <button type="button" class="btn btn-small btn-secondary" onclick="editAddress(<?php echo $addr['address_id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if (!$addr['is_default']): ?>
                                            <button type="button" class="btn btn-small btn-primary" onclick="setDefaultAddress(<?php echo $addr['address_id']; ?>)">
                                                <i class="fas fa-star"></i> Set Default
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-secondary" onclick="showAddAddressForm()">
                        <i class="fas fa-plus"></i> Add New Address
                    </button>
                    
                    <button type="button" class="btn btn-outline-primary" onclick="useProfileLocation()">
                        <i class="fas fa-map-marker-alt"></i> Use Profile Location
                    </button>
                </div>

                <!-- Order History Section -->
                <div class="form-section">
                    <h3 style="cursor: pointer; user-select: none;" onclick="toggleOrderHistory()">
                        <i class="fas fa-shopping-bag" id="order-history-icon"></i> Order History
                        <i class="fas fa-chevron-down" id="order-history-chevron" style="float: right; font-size: 0.9rem; transition: transform 0.3s ease;"></i>
                    </h3>
                    <div id="order-history-content" style="display: none;">
                    <?php if (empty($order_history)): ?>
                        <div style="text-align: center; padding: 2rem; color: #6c757d;">
                            <i class="fas fa-shopping-bag" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>You haven't received any orders yet.</p>
                            <a href="<?php echo SITE_URL; ?>/page/booklist.php" class="btn btn-primary">Browse Books</a>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 1rem;">
                            <?php 
                            // Group items by order
                            $orders_grouped = [];
                            foreach ($order_history as $item) {
                                $orders_grouped[$item['order_id']][] = $item;
                            }
                            
                            foreach ($orders_grouped as $order_id => $items): 
                                $order = $items[0];
                            ?>
                                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem; background: white;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e9ecef;">
                                        <div>
                                            <div style="font-weight: 600; color: #2c3e50;">Order #<?php echo (int)$order_id; ?></div>
                                            <div style="font-size: 0.85rem; color: #6c757d;">
                                                Delivered on <?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 0.85rem; color: #6c757d;">Total</div>
                                            <div style="font-weight: 600; color: #2c3e50;">
                                                <?php 
                                                $order_total = 0;
                                                foreach ($items as $item) {
                                                    $order_total += (float)$item['price_at_time'] * (int)$item['quantity'];
                                                }
                                                echo 'Rs. ' . number_format($order_total, 2);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: grid; gap: 0.75rem;">
                                        <?php foreach ($items as $item): ?>
                                            <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; background: #f8f9fa; border-radius: 6px;">
                                                <img src="<?php echo SITE_URL . '/' . ($item['cover_image'] ?? 'assets/images/default-book.png'); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                     style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid #e9ecef;"
                                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-book.png'">
                                                
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 600; color: #2c3e50; margin-bottom: 0.25rem;">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </div>
                                                    <div style="font-size: 0.85rem; color: #6c757d; margin-bottom: 0.25rem;">
                                                        by <?php echo htmlspecialchars($item['author'] ?? 'Unknown'); ?>
                                                    </div>
                                                    <?php if (!empty($item['isbn'])): ?>
                                                        <div style="font-size: 0.8rem; color: #6c757d;">
                                                            ISBN: <?php echo htmlspecialchars($item['isbn']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div style="text-align: center;">
                                                    <div style="font-size: 0.85rem; color: #6c757d;">Qty</div>
                                                    <div style="font-weight: 600;"><?php echo (int)$item['quantity']; ?></div>
                                                </div>
                                                
                                                <div style="text-align: right;">
                                                    <div style="font-size: 0.85rem; color: #6c757d;">Price (at order time)</div>
                                                    <div style="font-weight: 600; color: #2c3e50;">
                                                        Rs. <?php echo number_format((float)$item['price_at_time'], 2); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-align-left"></i> About Me</h3>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4" 
                                  placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions" style="justify-content: flex-start; border: none; background: transparent; padding: 0;">
                    <span id="autosave-status" style="color: #27ae60; font-size: 0.9rem; opacity: 0; transition: opacity 0.3s; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-check-circle"></i> Auto-saved
                    </span>
                </div>
            </form>

            <script>
            // Auto-save functionality
            (function() {
                const form = document.querySelector('.profile-form');
                const status = document.getElementById('autosave-status');
                let saveTimeout;
                
                const fields = ['full_name', 'phone', 'location', 'bio'];
                
                function showStatus(message, type = 'success') {
                    status.innerHTML = type === 'success' 
                        ? '<i class="fas fa-check-circle"></i> ' + message
                        : '<i class="fas fa-exclamation-circle"></i> ' + message;
                    status.style.color = type === 'success' ? '#27ae60' : '#e74c3c';
                    status.style.opacity = '1';
                    
                    setTimeout(() => {
                        status.style.opacity = '0';
                    }, 3000);
                }
                
                function autoSave() {
                    const formData = new FormData(form);
                    formData.append('update_profile', '1');
                    formData.append('autosave', '1');
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(() => {
                        showStatus('Auto-saved');
                    })
                    .catch(() => {
                        showStatus('Save failed', 'error');
                    });
                }
                
                fields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('input', function() {
                            clearTimeout(saveTimeout);
                            saveTimeout = setTimeout(autoSave, 1000);
                        });
                        
                        field.addEventListener('blur', function() {
                            clearTimeout(saveTimeout);
                            autoSave();
                        });
                    }
                });
            })();
            
            function useProfileLocation() {
                const location = '<?php echo htmlspecialchars($user['location'] ?? ''); ?>';
                if (!location) {
                    alert('Please set your location in the profile information section first.');
                    return;
                }
                
                // Create a form to submit the address
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                // Add address fields
                const fields = {
                    'add_address': '1',
                    'address_type': 'shipping',
                    'full_name': '<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>',
                    'address_line1': location,
                    'city': location,
                    'postal_code': '',
                    'country': 'Nepal',
                    'phone': '<?php echo htmlspecialchars($user['phone'] ?? ''); ?>',
                    'is_default': '0'
                };
                
                for (const [name, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
            
            function showAddAddressForm() {
                // You can implement this to show a modal or redirect to address add page
                alert('Address form functionality can be implemented here.');
            }
            
            function editAddress(addressId) {
                // You can implement this to show edit modal or redirect to edit page
                alert('Edit address functionality for address ID: ' + addressId);
            }
            
            function setDefaultAddress(addressId) {
                if (confirm('Set this address as your default shipping address?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'set_default_address';
                    input.value = addressId;
                    form.appendChild(input);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
            
            (function() {
                const input = document.getElementById('profile_image');
                const preview = document.getElementById('profile-preview');
                const placeholder = document.getElementById('profile-placeholder');

                if (!input) return;

                input.addEventListener('change', function() {
                    const file = input.files && input.files[0];
                    if (!file) return;

                    // Show preview immediately
                    const url = URL.createObjectURL(file);
                    if (preview) {
                        preview.src = url;
                        preview.style.display = '';
                    }
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    // Auto-upload the image
                    const formData = new FormData();
                    formData.append('update_profile', '1');
                    formData.append('autosave', '1');
                    formData.append('full_name', document.getElementById('full_name').value);
                    formData.append('phone', document.getElementById('phone').value);
                    formData.append('location', document.getElementById('location').value);
                    formData.append('bio', document.getElementById('bio').value);
                    formData.append('profile_image', file);
                    
                    const status = document.getElementById('autosave-status');
                    status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                    status.style.color = '#3498db';
                    status.style.opacity = '1';
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(() => {
                        status.innerHTML = '<i class="fas fa-check-circle"></i> Image saved';
                        status.style.color = '#27ae60';
                        setTimeout(() => {
                            status.style.opacity = '0';
                        }, 3000);
                    })
                    .catch(() => {
                        status.innerHTML = '<i class="fas fa-exclamation-circle"></i> Upload failed';
                        status.style.color = '#e74c3c';
                    });
                });
            })();

            function toggleOrderHistory() {
                const content = document.getElementById('order-history-content');
                const chevron = document.getElementById('order-history-chevron');
                
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    chevron.style.transform = 'rotate(180deg)';
                } else {
                    content.style.display = 'none';
                    chevron.style.transform = 'rotate(0deg)';
                }
            }
            </script>

            <div class="profile-meta">
                <p><i class="fas fa-clock"></i> Member since: <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                <p><i class="fas fa-sync"></i> Last updated: <?php echo date('F d, Y', strtotime($user['updated_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
