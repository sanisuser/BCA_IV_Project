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

// Add auth styles
echo '<link rel="stylesheet" href="' . SITE_URL . '/auth/css/auth.css">';

// Check for error or success messages
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
$reset_link = isset($_GET['reset_link']) ? $_GET['reset_link'] : '';
$email_sent = isset($_GET['email_sent']) ? $_GET['email_sent'] === '1' : false;

// Debug: Log what we received
error_log("Forgot password - reset_link param: " . ($reset_link ?: 'EMPTY'));
?>

<div class="auth-container">
    <div class="auth-card">

        <div class="auth-header">
            <h1 class="auth-title">Forgot Password?</h1>
            <p class="auth-subtitle">Enter your email and we'll send you a reset link</p>
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
            
            <?php if (!empty($reset_link)): ?>
                <div style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #6c757d;">
                        <i class="fas fa-link"></i> 
                        <?php echo $email_sent ? 'Backup link (if email not received):' : 'Your reset link:'; ?>
                    </p>
                    <a href="<?php echo htmlspecialchars(urldecode($reset_link)); ?>" 
                       style="display: block; word-break: break-all; color: #007bff; text-decoration: none; font-weight: 500;"
                       target="_blank">
                        <?php echo htmlspecialchars(urldecode($reset_link)); ?>
                    </a>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.8rem; color: #6c757d;">
                        <i class="fas fa-clock"></i> Link expires in 1 hour
                    </p>
                </div>
            <?php else: ?>
                <div style="margin: 1rem 0; padding: 1rem; background: #fff3cd; border-radius: 8px; border: 1px solid #ffc107; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Debug:</strong> Reset link is empty. Check URL parameters.
                    <br>URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form class="auth-form" action="forgot_password_process.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input"
                       placeholder="Enter your registered email" required
                       value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
            </div>

            <button type="submit" class="auth-btn">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div class="auth-footer">
            <p>Remember your password? <a href="login.php">Sign in</a></p>
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
