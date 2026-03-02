<?php
/**
 * admin/settings.php
 * 
 * Admin Settings - Manage website details, contact info, and admin profile.
 */

require_once __DIR__ . '/../includes/functions.php';

// Check admin access
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';

$active_page = 'settings';
$page_title = 'Admin Settings';

$error = '';
$success = '';

// Ensure site_settings table exists
$conn->query("CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Default settings
$default_settings = [
    'contact_email' => 'support@' . strtolower(str_replace(' ', '', SITE_NAME)) . '.com',
    'contact_phone' => '+1 (555) 123-4567',
    'contact_address' => '123 Book Street, Reading City, RC 12345',
    'site_description' => 'Your one-stop destination for books. Discover, buy, and enjoy reading!'
];

// Initialize default settings if empty
foreach ($default_settings as $key => $value) {
    $check = $conn->prepare("SELECT 1 FROM site_settings WHERE setting_key = ? LIMIT 1");
    $check->bind_param('s', $key);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
        $insert->bind_param('ss', $key, $value);
        $insert->execute();
        $insert->close();
    }
    $check->close();
}

// Handle Website Settings Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $settings_to_update = [
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'contact_address' => trim($_POST['contact_address'] ?? ''),
        'site_description' => trim($_POST['site_description'] ?? '')
    ];
    
    $all_success = true;
    foreach ($settings_to_update as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param('sss', $key, $value, $value);
        if (!$stmt->execute()) {
            $all_success = false;
        }
        $stmt->close();
    }
    
    if ($all_success) {
        $success = 'Website settings updated successfully!';
    } else {
        $error = 'Some settings could not be updated. Please try again.';
    }
}

// Fetch current settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/css/settings.css">

<div class="admin-content">
    <h1><i class="fas fa-cog"></i> Admin Settings</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="settings-container">
        <div class="settings-card">
            <h2><i class="fas fa-globe"></i> Website Details</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label>Contact Address</label>
                    <textarea name="contact_address" class="form-input" rows="2"><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Site Description</label>
                    <textarea name="site_description" class="form-input" rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Website Settings
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
