<?php
/**
 * process/login_process.php
 * 
 * Handles user login form submission.
 */

// Start output buffering to prevent header issues
ob_start();

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

// Initialize error variable
$error = '';
$username = '';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . SITE_URL . "/auth/login.php");
    ob_end_flush();
    exit;
}

// Get input (don't clean username yet - we need raw for DB lookup)
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    header("Location: " . SITE_URL . "/auth/login.php?error=" . urlencode('Please enter both username and password') . "&username=" . urlencode($username));
    ob_end_flush();
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? OR email = ?");
if (!$stmt) {
    header("Location: " . SITE_URL . "/auth/login.php?error=" . urlencode('Database error. Please try again later.'));
    ob_end_flush();
    exit;
}

$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Verify user exists
if (!$user) {
    // Don't reveal whether username or password was wrong
    header("Location: " . SITE_URL . "/auth/login.php?error=" . urlencode('Invalid username or password') . "&username=" . urlencode($username));
    ob_end_flush();
    exit;
}

// SECURE: Only use password_verify - NO plain text fallback
if (!password_verify($password, $user['password'])) {
    // Invalid password
    header("Location: " . SITE_URL . "/auth/login.php?error=" . urlencode('Invalid username or password') . "&username=" . urlencode($username));
    ob_end_flush();
    exit;
}

// If old plain text password, rehash it for security
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    if ($updateStmt) {
        $updateStmt->bind_param('si', $newHash, $user['user_id']);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// Login successful - regenerate session ID for security
session_regenerate_id(true);

// Set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'] ?? 'user';

// Set admin panel access flag only if admin logged in
if ($_SESSION['role'] === 'admin') {
    $_SESSION['admin_panel_access'] = true;
}

// Clear output buffer before redirect
ob_end_clean();

// Redirect based on role
if ($_SESSION['role'] === 'admin') {
    header("Location: " . SITE_URL . "/admin/index.php");
} else {
    header("Location: " . SITE_URL . "/index.php");
}
exit;