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

if (isset($_POST['token']) && !empty($_POST['token'])) {
    $token = (string)$_POST['token'];
} elseif (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = (string)$_GET['token'];
}

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT user_id, username, email, password_reset_expires FROM users WHERE password_reset_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
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

if ($token_valid && isset($_POST['new_password']) && !empty($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate password match
    if ($new_password !== $confirm_password) {
        $error = 'Passwords do not match. Please try again.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Hash password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE password_reset_token = ?");
        $stmt->bind_param('ss', $hashed, $token);
        
        if ($stmt->execute()) {
            // Build absolute redirect URL for mobile compatibility
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $redirect_url = $scheme . '://' . $host . SITE_URL . '/auth/login.php?success=' . urlencode('Password updated successfully! Please login with your new password.');
            redirect($redirect_url);
        } else {
            $error = 'Failed to update password. Please try again.';
        }
        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/header_navbar.php';
echo '<link rel="stylesheet" href="' . SITE_URL . '/auth/css/reset_password.css">';
?>

<div class="reset-page">
    <div class="reset-card">
        
        <!-- Icon -->
        <div class="reset-icon <?php echo !empty($error) && !$token_valid ? 'error' : ''; ?>">
            <i class="fas <?php echo !empty($error) && !$token_valid ? 'fa-exclamation-triangle' : 'fa-key'; ?>"></i>
        </div>
        
        <!-- Header -->
        <div class="reset-header">
            <h1 class="reset-title">Reset Password</h1>
            <p class="reset-subtitle">
                <?php echo $token_valid && isset($user) 
                    ? 'Create a new password for ' . htmlspecialchars($user['email']) 
                    : 'Reset link error'; ?>
            </p>
        </div>
        
        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <div class="reset-footer" style="border-top: none; padding-top: 0;">
                <a href="forgot_password.php" class="request-link-btn">
                    <i class="fas fa-redo"></i> Request New Reset Link
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <?php if ($token_valid): ?>
            <form class="reset-form" method="POST" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <label class="input-label" for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="input-field" 
                               required 
                               minlength="6"
                               placeholder="Enter new password (min 6 characters)" 
                               autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye" id="new_password-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="input-field" 
                               required 
                               minlength="6"
                               placeholder="Re-enter your password" 
                               autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-check"></i>
                    <span>Update Password</span>
                </button>
            </form>
        <?php endif; ?>
        
    </div>
</div>

<!-- Loading Overlay -->
<div class="loader-overlay" id="loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Updating password...</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetForm');
    const loader = document.getElementById('loader');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Show loader
            loader.classList.add('active');
            
            // Disable submit button
            const btn = form.querySelector('.submit-btn');
            btn.disabled = true;
            btn.style.opacity = '0.7';
        });
    }
});

function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const eyeIcon = document.getElementById(fieldId + '-eye');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>
