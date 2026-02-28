<?php
/**
 * auth/login.php
 * 
 * Login form page.
 */

// Load functions first (needed for is_logged_in)
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Login';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../includes/header_navbar.php';

// Add auth styles
echo '<link rel="stylesheet" href="' . SITE_URL . '/auth/css/auth.css">';

// Check for error or success messages from process
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
$saved_username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
?>

<div class="auth-container">
    <div class="auth-card">
        
        <div class="auth-header">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="auth-message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="auth-message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" action="../order_cart_process/process/login_process.php" method="POST">
            
            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <input type="text" id="username" name="username" class="form-input" required 
                       placeholder="Enter your username or email" value="<?php echo $saved_username; ?>">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-input" required 
                           placeholder="Enter your password" style="padding-right: 45px;">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" 
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); 
                                   background: none; border: none; color: #6c757d; cursor: pointer; 
                                   padding: 5px; font-size: 16px;">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-checkbox-group">
                    <input type="checkbox" id="remember" name="remember" value="1" class="form-checkbox">
                    <label for="remember" class="form-checkbox-label">Remember me</label>
                </div>
                <a href="forgot_password.php" class="auth-link" style="float: right;">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            
        </form>
        
        <div class="auth-footer">
            Don't have an account? 
            <a href="register.php" class="auth-link">Create Account</a>
            <div class="legal-links">
                By signing in, you agree to our <a href="<?php echo SITE_URL; ?>/legal/terms.php" class="auth-link">Terms</a> and <a href="<?php echo SITE_URL; ?>/legal/privacy.php" class="auth-link">Privacy Policy</a>
            </div>
        </div>
        
    </div>
</div>

<script>
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
