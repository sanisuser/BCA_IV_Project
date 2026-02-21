<?php
/**
 * auth/forgot_password_process.php
 *
 * Process forgot password request - generate token and send reset link.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/auth/forgot_password.php');
}

$email = trim($_POST['email'] ?? '');

// Validate email
if (empty($email)) {
    redirect(SITE_URL . '/auth/forgot_password.php?error=' . urlencode('Please enter your email address'));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect(SITE_URL . '/auth/forgot_password.php?error=' . urlencode('Please enter a valid email address') . '&email=' . urlencode($email));
}

// Check if email exists
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Don't reveal if email exists or not for security
    // But show same success message
    redirect(SITE_URL . '/auth/forgot_password.php?success=' . urlencode('If this email is registered, you will receive a password reset link shortly.'));
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store token in database
$stmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE user_id = ?");
$stmt->bind_param('ssi', $token, $expires, $user['user_id']);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    redirect(SITE_URL . '/auth/forgot_password.php?error=' . urlencode('Something went wrong. Please try again later.'));
}

// Build reset URL
$reset_url = SITE_URL . '/auth/reset_password.php?token=' . $token;

// For development: Save token to file instead of sending email
// In production, you would use mail() or PHPMailer
$email_content = "Password Reset Request\n\n";
$email_content .= "Username: " . $user['username'] . "\n";
$email_content .= "Reset Link: " . $reset_url . "\n";
$email_content .= "Expires: " . $expires . "\n\n";
$email_content .= "If you didn't request this, please ignore this email.\n";

// Save to temp file for development
$temp_file = sys_get_temp_dir() . '/password_reset_' . $user['username'] . '_' . time() . '.txt';
file_put_contents($temp_file, $email_content);

// Try to send email
$mail_sent = false;
$mail_error = '';

try {
    $subject = "Password Reset Request - " . SITE_NAME;
    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mail_sent = @mail($email, $subject, $email_content, $headers);
    
    if (!$mail_sent) {
        $mail_error = error_get_last()['message'] ?? 'Unknown mail error';
        error_log("Failed to send password reset email to: " . $email . " - Error: " . $mail_error);
    }
} catch (Exception $e) {
    $mail_error = $e->getMessage();
    error_log("Mail exception: " . $mail_error);
}

// Save to temp file as backup
$temp_file = sys_get_temp_dir() . '/password_reset_' . $user['username'] . '_' . time() . '.txt';
file_put_contents($temp_file, $email_content);

// Show success with the reset link (always show link in case email fails)
$success_msg = $mail_sent 
    ? 'Reset link sent to your email! If you don\'t see it, check the link below:' 
    : 'Email sending failed (common on local servers). Use this reset link:';
    
redirect(SITE_URL . '/auth/forgot_password.php?success=' . urlencode($success_msg) . '&reset_link=' . urlencode($reset_url) . '&email_sent=' . ($mail_sent ? '1' : '0'));
