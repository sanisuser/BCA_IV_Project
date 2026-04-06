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

// Normalize username
$username = strtolower(trim($username));

// Validation
$errors = [];

if (empty($username)) {
    $errors[] = 'Username is required.';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters.';
} elseif (strlen($username) > 20) {
    $errors[] = 'Username must be at most 20 characters.';
} elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
    $errors[] = 'Username can only contain letters, numbers, and underscores.';
} elseif (!preg_match('/[a-z]/', $username)) {
    $errors[] = 'Username must contain at least one letter.';
}

if (empty($full_name)) {
    $errors[] = 'Full name is required.';
} elseif (strlen($full_name) < 5 || strlen($full_name) > 50) {
    $errors[] = 'Full name must be between 5 and 50 characters.';
} elseif (!preg_match('/^[a-zA-Z]+ [a-zA-Z]+$/', $full_name)) {
    $errors[] = 'Please enter both first and last name (e.g. Sanish Shrestha).';
}

if (empty($phone) || !preg_match('/^9[7-8][0-9]{8}$/', $phone)) {
    $errors[] = 'Please enter a valid 10-digit Nepali mobile number (starts with 97 or 98)';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address';
} else {
    // Extract local part (before @)
    $local = explode('@', $email)[0];
    
    // Check email contains at least one letter
    if (!preg_match('/[a-zA-Z]/', $local)) {
        $errors[] = 'Email must contain at least one letter';
    }
    
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

// If there are errors, redirect back with form data and error flags
if (!empty($errors)) {
    // Build query string with errors and form data
    $params = [
        'error' => implode(', ', $errors),
        'username' => urlencode($_POST['username'] ?? ''),
        'full_name' => urlencode($_POST['full_name'] ?? ''),
        'phone' => urlencode($_POST['phone'] ?? ''),
        'email' => urlencode($_POST['email'] ?? '')
    ];
    
    // Add field-specific error flags
    $field_errors = [];
    foreach ($errors as $err) {
        if (stripos($err, 'username') !== false) $field_errors[] = 'username';
        if (stripos($err, 'full name') !== false || stripos($err, 'first and last') !== false) $field_errors[] = 'full_name';
        if (stripos($err, 'phone') !== false || stripos($err, 'mobile') !== false) $field_errors[] = 'phone';
        if (stripos($err, 'email') !== false) $field_errors[] = 'email';
        if (stripos($err, 'password') !== false) $field_errors[] = 'password';
    }
    if (!empty($field_errors)) {
        $params['field_errors'] = implode(',', array_unique($field_errors));
    }
    
    $query = http_build_query($params);
    redirect(SITE_URL . '/auth/register.php?' . $query);
}

// Check if username already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $params = [
        'error' => 'Username already exists',
        'username' => urlencode($_POST['username'] ?? ''),
        'full_name' => urlencode($_POST['full_name'] ?? ''),
        'phone' => urlencode($_POST['phone'] ?? ''),
        'email' => urlencode($_POST['email'] ?? ''),
        'field_errors' => 'username'
    ];
    redirect(SITE_URL . '/auth/register.php?' . http_build_query($params));
}
$stmt->close();

// Check if email already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $params = [
        'error' => 'Email already registered',
        'username' => urlencode($_POST['username'] ?? ''),
        'full_name' => urlencode($_POST['full_name'] ?? ''),
        'phone' => urlencode($_POST['phone'] ?? ''),
        'email' => urlencode($_POST['email'] ?? ''),
        'field_errors' => 'email'
    ];
    redirect(SITE_URL . '/auth/register.php?' . http_build_query($params));
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
