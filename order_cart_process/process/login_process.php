<?php
/**
 * process/login_process.php
 * 
 * Handles user login form submission.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/auth/login.php');
}

// Get and clean input
$username = clean_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Please enter both username and password'));
}

// Check if user exists
$stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? OR email = ?");
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Verify user exists and password is correct
if (!$user) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Invalid username or password'));
}

// Verify password (use password_verify if password is hashed)
$password_valid = false;

// Check if password uses modern hashing
if (password_verify($password, $user['password'])) {
    $password_valid = true;
} 
// Fallback for plain text passwords (not recommended, but for compatibility)
elseif ($password === $user['password']) {
    $password_valid = true;
}

if (!$password_valid) {
    redirect(SITE_URL . '/auth/login.php?error=' . urlencode('Invalid username or password'));
}

// Login successful - set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'] ?? 'user';

// Redirect based on role
if ($_SESSION['role'] === 'admin') {
    redirect(SITE_URL . '/admin/index.php');
} else {
    redirect(SITE_URL . '/index.php');
}
