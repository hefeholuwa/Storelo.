<?php
// includes/functions.php — Global utility functions

/**
 * Escape a string for safe HTML output (prevents XSS).
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to an internal path using BASE_URL.
 */
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

/**
 * Strip tags, trim whitespace, and remove backslashes from input.
 */
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data ?? '')));
}

/**
 * Check if a seller is currently logged in via session.
 */
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['seller_id']);
}

/**
 * Gate: redirect to /login if not authenticated.
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('/login');
    }
}
