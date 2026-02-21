<?php
/**
 * auth/reset_password.php
 *
 * Reset password form - enter new password with valid token.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Reset Password';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$error = '';
$token_valid = false;
$token = '';

// Step 1: Validate token from URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check token in database
    $stmt = $conn->prepare("SELECT user_id, username, email, password_reset_expires FROM users WHERE password_reset_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        // Check if token expired
        $expiry_time = strtotime($user['password_reset_expires']);
        $current_time = time();
        
        if ($current_time > $expiry_time) {
            $error = 'Reset link has expired. Please request a new one.';
        } else {
            $token_valid = true;
        }
    } else {
        $error = 'Invalid reset token. Please request a new one.';
    }
} else {
    $error = 'No reset token provided.';
}

// Step 2: Process new password submission
if ($token_valid && isset($_POST['new_password']) && !empty($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    
    // Validate password strength
    if (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Hash password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE password_reset_token = ?");
        $stmt->bind_param('ss', $hashed, $token);
        
        if ($stmt->execute()) {
            redirect(SITE_URL . '/auth/login.php?success=' . urlencode('Password updated successfully! Please login with your new password.'));
        } else {
            $error = 'Failed to update password. Please try again.';
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/header_navbar.php';
echo '<link rel="stylesheet" href="' . SITE_URL . '/auth/css/auth.css">';
?>

<div class="auth-container">
    <div class="auth-card">
        
        <div class="auth-header">
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-subtitle"><?php echo $token_valid && isset($user) ? 'Create a new password for ' . htmlspecialchars($user['email']) : 'Reset link error'; ?></p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="auth-message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <div class="auth-footer" style="margin-top: 1rem;">
                <a href="forgot_password.php" class="auth-link">Request New Reset Link</a>
            </div>
        <?php endif; ?>
        
        <?php if ($token_valid): ?>
            <form class="auth-form" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input"
                           placeholder="Enter new password (min 6 characters)" required minlength="6">
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-lock"></i> Update Password
                </button>
            </form>
        <?php endif; ?>
        
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
