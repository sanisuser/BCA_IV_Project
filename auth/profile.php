<?php
/**
 * auth/profile.php
 * 
 * User Profile Page - Display and Edit Profile
 */

require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $location = clean_input($_POST['location'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $bio = clean_input($_POST['bio'] ?? '');
    
    $profile_image = $user['profile_image'] ?? '';
    
    // Handle profile image upload
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
        $error = 'Failed to update profile.';
        $stmt->close();
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header_navbar.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/profile.css">

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

                <div class="form-section">
                    <h3><i class="fas fa-align-left"></i> About Me</h3>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4" 
                                  placeholder="Tell us a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </form>

            <script>
            (function() {
                const input = document.getElementById('profile_image');
                const preview = document.getElementById('profile-preview');
                const placeholder = document.getElementById('profile-placeholder');

                if (!input) return;

                input.addEventListener('change', function() {
                    const file = input.files && input.files[0];
                    if (!file) return;

                    const url = URL.createObjectURL(file);
                    if (preview) {
                        preview.src = url;
                        preview.style.display = '';
                    }
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                });
            })();
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
