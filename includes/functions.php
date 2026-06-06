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
    // Prevent HTTP Response Splitting (Header Injection)
    $path = str_replace(["\r", "\n"], '', $path);
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
        header("HTTP/1.1 401 Unauthorized");
        redirect('/login');
    }
    
    require_once __DIR__ . '/db.php';
    $db = DB::connect();
    $stmt = $db->prepare("SELECT is_deleted, is_suspended FROM sellers WHERE id = ?");
    $stmt->execute([$_SESSION['seller_id']]);
    $seller = $stmt->fetch();
    
    if (!$seller || $seller['is_deleted'] || $seller['is_suspended']) {
        session_destroy();
        header("HTTP/1.1 403 Forbidden");
        redirect('/login?error=account_unavailable');
    }
}

/**
 * Check if a super admin is currently logged in via session.
 */
function is_superadmin_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_id']);
}

/**
 * Gate: redirect to /superadmin/login if not authenticated as admin.
 */
function require_superadmin() {
    if (!is_superadmin_logged_in()) {
        header("HTTP/1.1 401 Unauthorized");
        redirect('/superadmin/login');
    }
    
    require_once __DIR__ . '/db.php';
    $db = DB::connect();
    $stmt = $db->prepare("SELECT id FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    
    if (!$stmt->fetch()) {
        session_destroy();
        header("HTTP/1.1 401 Unauthorized");
        redirect('/superadmin/login');
    }
}
