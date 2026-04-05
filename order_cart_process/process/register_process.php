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
$full_name = clean_input($_POST['full_name'] ?? '');
$phone = clean_input($_POST['phone'] ?? '');
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

if (empty($full_name)) {
    $errors[] = 'Full name is required.';
} elseif (strlen($full_name) < 3) {
    $errors[] = 'Full name must be at least 3 characters.';
} elseif (strlen($full_name) > 100) {
    $errors[] = 'Full name must not exceed 100 characters.';
} elseif (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $full_name)) {
    $errors[] = 'Full name can only contain letters, spaces, apostrophes, hyphens, and dots.';
} elseif (!preg_match('/^[a-zA-Z]/', $full_name)) {
    $errors[] = 'Full name must start with a letter.';
} elseif (preg_match('/\s{2,}/', $full_name)) {
    $errors[] = 'Full name must not contain multiple consecutive spaces.';
} elseif (str_word_count($full_name) < 2) {
    $errors[] = 'Please enter your full name (first and last name).';
}

if (empty($phone) || !preg_match('/^9[8-9][0-9]{8}$/', $phone)) {
    $errors[] = 'Please enter a valid 10-digit Nepali mobile number (starts with 98 or 99)';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
} else {
    // Validate email domain has valid DNS records
    $domain = substr(strrchr($email, '@'), 1);
    if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        $errors[] = 'Please enter an email with a valid domain';
    } else {
        // Validate email provider is in allowed list
        $allowed_domains = [
            'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com',
            'icloud.com', 'live.com', 'msn.com', 'protonmail.com',
            'yahoo.co.uk', 'yahoo.co.in', 'yahoo.com.np',
            'rediffmail.com',
        ];

        $email_domain = strtolower(explode('@', $email)[1]);

        if (!in_array($email_domain, $allowed_domains)) {
            $errors[] = 'Please use a valid email provider.';
        }
    }
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

// Insert new user with full_name and phone
$stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())");
$stmt->bind_param('sssss', $username, $email, $hashed_password, $full_name, $phone);

if ($stmt->execute()) {
    $stmt->close();
    // Registration successful - auto-login and redirect to homepage
    $new_user_id = $conn->insert_id;
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $full_name;
    redirect(SITE_URL . '/index.php?success=' . urlencode('Welcome! Your account has been created.'));
} else {
    $stmt->close();
    redirect(SITE_URL . '/auth/register.php?error=' . urlencode('Registration failed. Please try again.'));
}
