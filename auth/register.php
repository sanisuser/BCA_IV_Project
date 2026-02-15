<?php
/**
 * auth/register.php
 * 
 * Registration form page.
 */

// Load functions first
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Register';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../includes/header_navbar.php';
?>

<!-- Auth Styles -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/auth/css/auth.css">

<?php
// Check for error message
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="auth-container">
    <div class="auth-card">
        
        <div class="auth-header">
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join BookHub today</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="auth-message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" action="../order_cart_process/process/register_process.php" method="POST">
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input" required 
                       placeholder="Choose a username" minlength="3" maxlength="20">
                <small class="form-help">3-20 characters, letters and numbers only</small>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" required 
                       placeholder="Enter your email address">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" required 
                       placeholder="Create a password" minlength="6">
                <small class="form-help">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required 
                       placeholder="Confirm your password">
            </div>
            
            <div class="form-group">
                <div class="form-checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required class="form-checkbox">
                    <label for="terms" class="form-checkbox-label">
                        I agree to the <a href="#" class="auth-link">Terms of Service</a> and <a href="#" class="auth-link">Privacy Policy</a>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            
        </form>
        
        <div class="auth-footer">
            Already have an account? 
            <a href="login.php" class="auth-link">Sign in</a>
        </div>
        
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
