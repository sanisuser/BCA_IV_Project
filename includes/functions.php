<?php
/**
 * functions.php
 * 
 * Common helper functions used across the site.
 * Include this after db.php if you need database + helpers.
 * 
 * Usage:
 *   require_once __DIR__ . '/../includes/functions.php';
 *   $clean = clean_input($_POST['name']);
 */

// Define site constants if not already defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'BookHub');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', '');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Clean user input to prevent XSS attacks
 * Removes dangerous HTML and trims whitespace
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Display error message
 */
function show_error($message) {
    return '<div class="error-message">' . $message . '</div>';
}

/**
 * Display success message
 */
function show_success($message) {
    return '<div class="success-message">' . $message . '</div>';
}

/**
 * Check if user is logged in
 * Returns true if user has an active session
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_admin_panel_access() {
    return is_admin() && isset($_SESSION['admin_panel_access']) && $_SESSION['admin_panel_access'] === true;
}

/**
 * Get current user ID
 * Returns user_id from session or false if not logged in
 */
function get_user_id() {
    return is_logged_in() ? $_SESSION['user_id'] : false;
}

/**
 * Redirect to another page
 * Stops script execution after redirect
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Format price for display
 * Example: format_price(25.5) returns "Rs 25.50"
 */
function format_price($price) {
    return 'Rs ' . number_format($price, 2);
}

/**
 * Truncate text to a specific length
 * Adds "..." if text is longer than limit
 */
function truncate($text, $limit = 100) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

/**
 * Generate safe file name for uploads
 * Removes special characters and adds timestamp
 */
function safe_filename($original_name) {
    // Remove any path info
    $name = basename($original_name);
    // Remove special characters
    $name = preg_replace('/[^a-zA-Z0-9.-]/', '_', $name);
    // Add timestamp to make unique
    $name = time() . '_' . $name;
    return strtolower($name);
}

/**
 * Display star rating
 * Returns HTML for star rating display
 */
function show_stars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '★';  // Filled star
        } else {
            $stars .= '☆';  // Empty star
        }
    }
    return $stars;
}

/**
 * Get book cover image path
 * Returns path to cover or default image if not found
 */
function get_book_cover($cover_image) {
    if (!empty($cover_image)) {
        $ci = (string)$cover_image;
        if (strpos($ci, '/') !== false || strpos($ci, '\\') !== false) {
            $abs = realpath(__DIR__ . '/../' . ltrim(str_replace('\\', '/', $ci), '/'));
            if ($abs !== false && is_file($abs)) {
                return $ci;
            }
        } else {
            if (file_exists(__DIR__ . '/../assets/images/books/' . $ci)) {
                return 'assets/images/books/' . $ci;
            }
            if (file_exists(__DIR__ . '/../images/books/' . $ci)) {
                return 'images/books/' . $ci;
            }
        }
    }
    if (file_exists(__DIR__ . '/../assets/images/default-book.png')) {
        return 'assets/images/default-book.png';
    }
    return 'images/default-book.png';
}

/**
 * Display pagination
 * Simple pagination links for book listings
 */
function show_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Previous link
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '" class="prev">← Previous</a>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $base_url . '?page=' . $i . '">' . $i . '</a>';
        }
    }
    
    // Next link
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '" class="next">Next →</a>';
    }
    
    $html .= '</div>';
    return $html;
}
