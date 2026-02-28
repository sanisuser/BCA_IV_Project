<?php
/**
 * auth/forgot_password_process.php
 *
 * Process forgot password request - generate token and send reset link.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// PHPMailer classes
require_once __DIR__ . '/../phpmailer/PHPMailer-6.9.1/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/PHPMailer-6.9.1/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/PHPMailer-6.9.1/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
    // Email not found - show explicit error
    redirect(SITE_URL . '/auth/forgot_password.php?error=' . urlencode('Email not found. Please check your email address or register a new account.') . '&email=' . urlencode($email));
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+3 minutes'));

// Store token in database
$stmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE user_id = ?");
$stmt->bind_param('ssi', $token, $expires, $user['user_id']);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    redirect(SITE_URL . '/auth/forgot_password.php?error=' . urlencode('Something went wrong. Please try again later.'));
}

// Build reset URL (absolute)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$reset_url = $scheme . '://' . $host . SITE_URL . '/auth/reset_password.php?token=' . $token;

// Email content
$email_subject = "Password Reset Request - " . SITE_NAME;
$email_body = "<h2>Password Reset Request</h2>";
$email_body .= "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
$email_body .= "<p><strong>Click the link below to reset your password:</strong></p>";
$email_body .= "<p><a href='" . $reset_url . "' style='display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;'>Reset Password</a></p>";
$email_body .= "<p>Or copy this link: " . $reset_url . "</p>";
$email_body .= "<p><strong>Expires:</strong> " . $expires . "</p>";
$email_body .= "<p>If you didn't request this, please ignore this email.</p>";

$email_text = "Password Reset Request\n\n";
$email_text .= "Username: " . $user['username'] . "\n";
$email_text .= "Reset Link: " . $reset_url . "\n";
$email_text .= "Expires: " . $expires . "\n\n";
$email_text .= "If you didn't request this, please ignore this email.\n";

// Save to temp file for development backup
$temp_file = sys_get_temp_dir() . '/password_reset_' . $user['username'] . '_' . time() . '.txt';
file_put_contents($temp_file, $email_text);

// Send email using PHPMailer with Gmail SMTP
$mail_sent = false;
$mail_error = '';

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->SMTPDebug = 0;                      // Disable debug output (set to 2 for debugging)
    $mail->isSMTP();                           // Use SMTP
    $mail->Host       = 'smtp.gmail.com';      // Gmail SMTP server
    $mail->SMTPAuth   = true;                  // Enable authentication
    $mail->Username   = SMTP_USERNAME;         // Gmail address (from config)
    $mail->Password   = SMTP_PASSWORD;         // Gmail app password (from config)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Recipients
    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addReplyTo(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addAddress($email, $user['username']);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = $email_subject;
    $mail->Body    = $email_body;
    $mail->AltBody = $email_text;
    
    $mail_sent = $mail->send();
    
    if (!$mail_sent) {
        error_log("Failed to send password reset email to: " . $email);
    }
} catch (Exception $e) {
    $mail_sent = false;
    $mail_error = $mail->ErrorInfo;
    error_log("PHPMailer Error: " . $mail_error);
}

// Show success message without reset link
redirect(SITE_URL . '/auth/forgot_password.php?success=' . urlencode('Reset link sent to your email! Please check your inbox and spam folder.'));
