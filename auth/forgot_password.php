<?php
/**
 * auth/forgot_password.php
 *
 * Forgot password form - enter email to receive reset link.
 */

require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Forgot Password';

// If already logged in, redirect to home
if (is_logged_in()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

require_once __DIR__ . '/../includes/header_navbar.php';

// Add custom forgot password styles
echo '<link rel="stylesheet" href="' . SITE_URL . '/auth/css/forgot_password.css">';

// Check for error or success messages
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<div class="forgot-page">
    <div class="forgot-card">
        
        <!-- Back Link -->
        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
        
        <!-- Icon -->
        <div class="forgot-icon">
            <i class="fas fa-lock-open"></i>
        </div>
        
        <!-- Header -->
        <div class="forgot-header">
            <h1 class="forgot-title">Forgot Password?</h1>
            <p class="forgot-subtitle">No worries! Enter your email and we'll send you a link to reset your password.</p>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form class="forgot-form" action="forgot_password_process.php" method="POST">
            <div class="input-group">
                <label class="input-label" for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="input-field"
                           placeholder="Enter your registered email" 
                           required
                           value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <span>Send Reset Link</span>
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>

        <!-- Footer -->
        <div class="forgot-footer">
            <p>Remember your password? <a href="login.php">Sign in</a></p>
            <p>Don't have an account? <a href="register.php">Create one</a></p>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loader-overlay" id="loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Sending reset link...</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.forgot-form');
    const loader = document.getElementById('loader');
    
    form.addEventListener('submit', function(e) {
        // Show loader
        loader.classList.add('active');
        
        // Disable submit button
        const btn = form.querySelector('.submit-btn');
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
    });
});
</script>

