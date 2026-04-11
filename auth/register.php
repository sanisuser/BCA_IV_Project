<?php
/**
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
// Check for error message and form data
$error = isset($_GET['error']) ? $_GET['error'] : '';
$field_errors = isset($_GET['field_errors']) ? explode(',', $_GET['field_errors']) : [];

// Get saved form data
$saved_data = [
    'username' => isset($_GET['username']) ? urldecode($_GET['username']) : '',
    'full_name' => isset($_GET['full_name']) ? urldecode($_GET['full_name']) : '',
    'phone' => isset($_GET['phone']) ? urldecode($_GET['phone']) : '',
    'email' => isset($_GET['email']) ? urldecode($_GET['email']) : ''
];

// Helper function to check if field has error
function hasFieldError($field, $field_errors) {
    return in_array($field, $field_errors);
}

// Helper function to get input style
function getInputStyle($field, $field_errors) {
    return hasFieldError($field, $field_errors) ? 'border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);' : '';
}
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
                       placeholder="Choose a username" minlength="3" maxlength="20"
                       value="<?php echo htmlspecialchars($saved_data['username']); ?>"
                       style="<?php echo getInputStyle('username', $field_errors); ?>">
                <span id="username-error" class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                    <?php if (hasFieldError('username', $field_errors)): ?>Please enter a valid username (3-20 chars, letters, numbers, underscores, at least one letter).<?php endif; ?>
                </span>
                <small class="form-help">3-20 characters, letters, numbers and underscores only</small>
            </div>

            <div class="form-group">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-input" required
                       placeholder="Enter your first and last name (e.g. Sanish Shrestha)" minlength="5" maxlength="50"
                       value="<?php echo htmlspecialchars($saved_data['full_name']); ?>"
                       style="<?php echo getInputStyle('full_name', $field_errors); ?>">
                <span id="fullname-error" class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                    <?php if (hasFieldError('full_name', $field_errors)): ?>Please enter both first and last name (5-50 characters, letters only).<?php endif; ?>
                </span>
                <small class="form-help">Enter both first and last name, 5-50 characters, letters only</small>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text" id="phone" name="phone" class="form-input" required
                       placeholder="Enter your 10-digit Nepali mobile number" pattern="9[7-8][0-9]{8}"
                       value="<?php echo htmlspecialchars($saved_data['phone']); ?>"
                       style="<?php echo getInputStyle('phone', $field_errors); ?>">
                <span id="phone-error" class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                    <?php if (hasFieldError('phone', $field_errors)): ?>Please enter a valid 10-digit Nepali mobile number starting with 97 or 98.<?php endif; ?>
                </span>
                <small class="form-help">10-digit mobile number starting with 97 or 98</small>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="text" id="email" name="email" class="form-input" required
                       placeholder="Enter your email address"
                       value="<?php echo htmlspecialchars($saved_data['email']); ?>"
                       style="<?php echo getInputStyle('email', $field_errors); ?>">
                <span id="email-error" class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                    <?php if (hasFieldError('email', $field_errors)): ?>Please enter a valid email address with a valid domain.<?php endif; ?>
                </span>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-input" required
                           placeholder="Create a password" minlength="6" style="padding-right: 45px; <?php echo getInputStyle('password', $field_errors); ?>">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                                   background: none; border: none; color: #6c757d; cursor: pointer;
                                   padding: 5px; font-size: 16px;">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </button>
                </div>
                <small class="form-help">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required
                           placeholder="Confirm your password" style="padding-right: 45px; <?php echo getInputStyle('password', $field_errors); ?>">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                                   background: none; border: none; color: #6c757d; cursor: pointer;
                                   padding: 5px; font-size: 16px;">
                        <i class="fas fa-eye" id="confirm_password-eye"></i>
                    </button>
                </div>
                <span id="confirm-password-error" class="error-message" style="color: #ef4444; font-size: 0.875rem; margin-top: 0.25rem; display: block;">
                    <?php if (hasFieldError('password', $field_errors)): ?>Password must be at least 6 characters and passwords must match.<?php endif; ?>
                </span>
            </div>

            <div class="form-group">
                <div class="form-checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required class="form-checkbox">
                    <label for="terms" class="form-checkbox-label">
                        I agree to the <a href="<?php echo SITE_URL; ?>/legal/terms.php" class="auth-link">Terms of Service</a> and <a href="<?php echo SITE_URL; ?>/legal/privacy.php" class="auth-link">Privacy Policy</a>
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

document.addEventListener('DOMContentLoaded', function () {

    // Helper functions for input styling
    function setValid(input) {
        input.style.borderColor = '#22c55e'; // green-500
        input.style.boxShadow = '0 0 0 3px rgba(34, 197, 94, 0.1)';
    }

    function setInvalid(input) {
        input.style.borderColor = '#ef4444'; // red-500
        input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
    }

    function clearValidation(input) {
        input.style.borderColor = '';
        input.style.boxShadow = '';
    }

    document.getElementById('email').addEventListener('blur', function () {
        const val = this.value.trim().toLowerCase();
        this.value = val;
        const err = document.getElementById('email-error');
        const pattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
        const hasLetter = /[a-z]/.test(val);

        if (!val) {
            err.textContent = 'Email is required.';
            setInvalid(this);
        } else if (!hasLetter) {
            err.textContent = 'Email must contain at least one letter.';
            setInvalid(this);
        } else if (!pattern.test(val)) {
            err.textContent = 'Please enter a valid email address (lowercase only).';
            setInvalid(this);
        } else {
            err.textContent = '';
            setValid(this);
        }
    });

    document.getElementById('email').addEventListener('input', function () {
        this.value = this.value.toLowerCase();
        clearValidation(this);
    });

    // Username validation
    document.getElementById('username').addEventListener('blur', function () {
        const val = this.value.trim();
        const err = document.getElementById('username-error');
        const pattern = /^[a-zA-Z0-9_]+$/;

        if (!val) {
            err.textContent = 'Username is required.';
            setInvalid(this);
        } else if (val.length < 3) {
            err.textContent = 'Username must be at least 3 characters.';
            setInvalid(this);
        } else if (val.length > 20) {
            err.textContent = 'Username must be at most 20 characters.';
            setInvalid(this);
        } else if (!pattern.test(val)) {
            err.textContent = 'Username can only contain letters, numbers, and underscores.';
            setInvalid(this);
        } else if (!/[a-zA-Z]/.test(val)) {
            err.textContent = 'Username must contain at least one letter.';
            setInvalid(this);
        } else {
            err.textContent = '';
            setValid(this);
        }
    });

    // Real-time username validation
    document.getElementById('username').addEventListener('input', function () {
        const val = this.value;
        const err = document.getElementById('username-error');
        
        // Auto-convert to lowercase
        this.value = val.toLowerCase();
        
        // Clear validation styling while typing
        clearValidation(this);
        
        // Only allow letters, numbers, and underscores
        if (/[^a-z0-9_]/.test(val)) {
            err.textContent = 'Only letters, numbers, and underscores allowed.';
        } else {
            err.textContent = '';
        }
    });

    // Phone number validation
    document.getElementById('phone').addEventListener('blur', function () {
        const val = this.value.trim();
        const err = document.getElementById('phone-error');
        const pattern = /^9[7-8][0-9]{8}$/;

        if (!val) {
            err.textContent = 'Please enter a valid 10-digit Nepali mobile number (starts with 97 or 98).';
            setInvalid(this);
        } else if (!/^\d+$/.test(val)) {
            err.textContent = 'Phone number must contain only digits.';
            setInvalid(this);
        } else if (val.length !== 10) {
            err.textContent = 'Phone number must be exactly 10 digits.';
            setInvalid(this);
        } else if (!pattern.test(val)) {
            err.textContent = 'Phone number must start with 97 or 98.';
            setInvalid(this);
        } else {
            err.textContent = '';
            setValid(this);
        }
    });

    // Real-time phone validation - only allow digits
    document.getElementById('phone').addEventListener('input', function () {
        const val = this.value;
        const err = document.getElementById('phone-error');
        
        // Clear validation styling while typing
        clearValidation(this);
        
        // Remove non-digit characters
        this.value = val.replace(/\D/g, '');
        
        // Limit to 10 digits
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
        
        // Show error for non-digit input
        if (/\D/.test(val)) {
            err.textContent = 'Only digits allowed (0-9).';
        } else {
            err.textContent = '';
        }
    });

    // Full name validation
    document.getElementById('full_name').addEventListener('blur', function () {
        const val = this.value.trim();
        const err = document.getElementById('fullname-error');
        const pattern = /^[a-zA-Z]+ [a-zA-Z]+$/;

        if (!val) {
            err.textContent = 'Full name is required.';
            setInvalid(this);
        } else if (val.length < 5) {
            err.textContent = 'Full name must be at least 5 characters.';
            setInvalid(this);
        } else if (val.length > 50) {
            err.textContent = 'Full name must be at most 50 characters.';
            setInvalid(this);
        } else if (!pattern.test(val)) {
            err.textContent = 'Please enter both first and last name (e.g. Sanish Shrestha). Letters and spaces only.';
            setInvalid(this);
        } else {
            err.textContent = '';
            setValid(this);
        }
    });

    // Real-time validation as user types
    document.getElementById('full_name').addEventListener('input', function () {
        const val = this.value;
        const err = document.getElementById('fullname-error');
        
        // Clear validation styling while typing
        clearValidation(this);
        
        // Only allow letters and spaces
        if (/[^a-zA-Z\s]/.test(val)) {
            err.textContent = 'Only letters and spaces allowed.';
        } else {
            err.textContent = '';
        }
    });

    // Password validation
    document.getElementById('password').addEventListener('blur', function () {
        const val = this.value;
        if (val.length >= 6) {
            setValid(this);
        } else if (val.length > 0) {
            setInvalid(this);
        } else {
            clearValidation(this);
        }
    });

    // Confirm password validation
    document.getElementById('confirm_password').addEventListener('blur', function () {
        const password = document.getElementById('password').value;
        const confirmVal = this.value;
        const err = document.getElementById('confirm-password-error');

        if (!confirmVal) {
            clearValidation(this);
        } else if (confirmVal === password && confirmVal.length >= 6) {
            err.textContent = '';
            setValid(this);
        } else {
            err.textContent = 'Passwords do not match.';
            setInvalid(this);
        }
    });

    // Form submission validation
    document.querySelector('.auth-form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const fullName = document.getElementById('full_name').value.trim();
    const username = document.getElementById('username').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
    const namePattern = /^[a-zA-Z]+ [a-zA-Z]+$/;
    const usernamePattern = /^[a-z0-9_]{3,20}$/;
    const phonePattern = /^9[7-8][0-9]{8}$/;
    
    let hasError = false;
    
    if (!usernamePattern.test(username)) {
        document.getElementById('username-error').textContent = 'Please enter a valid username (3-20 chars, letters, numbers, underscores only).';
        hasError = true;
    }
    
    if (!phonePattern.test(phone)) {
        document.getElementById('phone-error').textContent = 'Please enter a valid 10-digit Nepali mobile number (starts with 97 or 98).';
        hasError = true;
    }
    
    const hasLetter = /[a-z]/.test(email);
    if (!hasLetter || !emailPattern.test(email)) {
        document.getElementById('email-error').textContent = 'Please enter a valid email address with at least one letter.';
        hasError = true;
    }
    
    if (!namePattern.test(fullName)) {
        document.getElementById('fullname-error').textContent = 'Please enter both first and last name (e.g. Sanish Shrestha). Letters and spaces only.';
        hasError = true;
    }
    
    // Password match validation
    if (password !== confirmPassword) {
        document.getElementById('confirm-password-error').textContent = 'Passwords do not match. Please re-enter your password.';
        hasError = true;
    } else if (password.length < 6) {
        document.getElementById('confirm-password-error').textContent = 'Password must be at least 6 characters.';
        hasError = true;
    } else {
        document.getElementById('confirm-password-error').textContent = '';
    }
    
    if (hasError) {
        e.preventDefault();
    }
});

});
