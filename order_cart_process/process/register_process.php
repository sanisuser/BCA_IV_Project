<?php
/**
 * process/register_process.php
 * 
 * Handles user registration form submission.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/auth/register.php');
}

// Get and clean input
$username = clean_input($_POST['username'] ?? '');
$email = clean_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
$errors = [];

if (empty($username) || strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters long';
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain letters, numbers, and underscores';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
}

if (empty($password) || strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters long';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

// If there are errors, redirect back
if (!empty($errors)) {
    $error_string = implode(', ', $errors);
    redirect(SITE_URL . '/auth/register.php?error=' . urlencode($error_string));
}

// Check if username already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    redirect(SITE_URL . '/auth/register.php?error=' . urlencode('Username already exists'));
}
$stmt->close();

// Check if email already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    redirect(SITE_URL . '/auth/register.php?error=' . urlencode('Email already registered'));
}
$stmt->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
$stmt->bind_param('sss', $username, $email, $hashed_password);

if ($stmt->execute()) {
    $stmt->close();
    // Registration successful - redirect to login with success message
    redirect(SITE_URL . '/auth/login.php?success=' . urlencode('Registration successful! Please sign in.'));
} else {
    $stmt->close();
    redirect(SITE_URL . '/auth/register.php?error=' . urlencode('Registration failed. Please try again.'));
}
