<?php
/**
 * auth/logout.php
 * 
 * User logout - destroys session and redirects
 */

require_once __DIR__ . '/../includes/functions.php';

// Clear all session data
$_SESSION = [];

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to home
redirect(SITE_URL . '/index.php');
