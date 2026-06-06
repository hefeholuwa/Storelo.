<?php
// index.php — Front Controller & Router
// All requests are funnelled here by .htaccess and dispatched to the correct view.

session_start();

// Prevent browser caching so users always see fresh data when navigating back/forward
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Extract the clean request path
$request = $_SERVER['REQUEST_URI'];
$base_folder = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$request = str_replace($base_folder, '', $request);
$request = parse_url($request, PHP_URL_PATH);
$request = rtrim($request, '/');
if ($request === '') $request = '/';

// ── Route Table ──────────────────────────────────────────────

if ($request === '/') {
    require __DIR__ . '/views/home.php';

} elseif ($request === '/sitemap.xml') {
    require __DIR__ . '/views/sitemap.php';

} elseif ($request === '/blog') {
    require __DIR__ . '/views/blog_index.php';

} elseif ($request === '/login') {
    require __DIR__ . '/views/login.php';

} elseif ($request === '/register') {
    require __DIR__ . '/views/register.php';

} elseif ($request === '/register-success') {
    require __DIR__ . '/views/register_success.php';

} elseif ($request === '/verify') {
    require __DIR__ . '/views/verify_email.php';

} elseif ($request === '/forgot-password') {
    require __DIR__ . '/views/forgot_password.php';

} elseif ($request === '/reset-password') {
    require __DIR__ . '/views/reset_password.php';

} elseif ($request === '/logout') {
    session_destroy();
    redirect('/');

} elseif ($request === '/superadmin/login') {
    require __DIR__ . '/views/superadmin/login.php';

} elseif ($request === '/superadmin/logout') {
    session_start();
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    redirect('/superadmin/login');

} elseif ($request === '/superadmin/dashboard') {
    require_superadmin();
    require __DIR__ . '/views/superadmin/dashboard.php';

} elseif ($request === '/superadmin/sellers') {
    require_superadmin();
    require __DIR__ . '/views/superadmin/sellers.php';

} elseif ($request === '/superadmin/orders') {
    require_superadmin();
    require __DIR__ . '/views/superadmin/orders.php';

} elseif ($request === '/superadmin/settings') {
    require_superadmin();
    require __DIR__ . '/views/superadmin/settings.php';

} elseif ($request === '/superadmin/blog') {
    require_superadmin();
    require __DIR__ . '/views/superadmin/blog.php';

} elseif ($request === '/superadmin/blog/create' || $request === '/superadmin/blog/edit') {
    require_superadmin();
    require __DIR__ . '/views/superadmin/blog_edit.php';

} elseif ($request === '/dashboard') {
    require_login();
    require __DIR__ . '/views/dashboard/main.php';

} elseif ($request === '/dashboard/products') {
    require_login();
    require __DIR__ . '/views/dashboard/products.php';

} elseif ($request === '/dashboard/orders') {
    require_login();
    require __DIR__ . '/views/dashboard/orders.php';

} elseif ($request === '/dashboard/categories') {
    require_login();
    require __DIR__ . '/views/dashboard/categories.php';

} elseif ($request === '/dashboard/promotions') {
    require_login();
    require __DIR__ . '/views/dashboard/promotions.php';

} elseif ($request === '/dashboard/shipping') {
    require_login();
    require __DIR__ . '/views/dashboard/shipping.php';

} elseif ($request === '/dashboard/reviews') {
    require_login();
    require __DIR__ . '/views/dashboard/reviews.php';

} elseif ($request === '/dashboard/customers') {
    require_login();
    require __DIR__ . '/views/dashboard/customers.php';

} elseif ($request === '/dashboard/profile') {
    require_login();
    require __DIR__ . '/views/dashboard/profile.php';

} elseif (preg_match('#^/blog/([a-zA-Z0-9_-]+)$#', $request, $matches)) {
    $slug = $matches[1];
    require __DIR__ . '/views/blog_post.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)/order-success/([0-9]+)$#', $request, $matches)) {
    $shop_username = $matches[1];
    $order_id = (int)$matches[2];
    require __DIR__ . '/views/store/order_success.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)/checkout$#', $request, $matches)) {
    $shop_username = $matches[1];
    require __DIR__ . '/views/store/checkout_handler.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)/cart$#', $request, $matches)) {
    $shop_username = $matches[1];
    require __DIR__ . '/views/store/cart.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)$#', $request, $matches)) {
    $shop_username = $matches[1];
    // Check if store is banned
    $db_check = DB::connect();
    $ban_stmt = $db_check->prepare("SELECT is_banned FROM sellers WHERE username = ?");
    $ban_stmt->execute([$shop_username]);
    $ban_row = $ban_stmt->fetch();
    if ($ban_row && $ban_row['is_banned']) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Store Unavailable</title></head>';
        echo '<body style="background:#0b0f19;color:#f3f4f6;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
        echo '<div><h1 style="font-size:3rem;margin-bottom:10px;">🚫</h1><h2>Store Unavailable</h2><p style="color:#9ca3af;margin-top:8px;">This store has been suspended for violating our terms of service.</p><a href="' . BASE_URL . '/" style="color:#F68B1E;margin-top:16px;display:inline-block;">Go Home</a></div></body></html>';
        exit;
    }
    // Check maintenance mode
    $maint_stmt = $db_check->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = 'maintenance_mode'");
    $maint_stmt->execute();
    $maint_val = $maint_stmt->fetchColumn();
    if ($maint_val === '1') {
        http_response_code(503);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Under Maintenance</title></head>';
        echo '<body style="background:#0b0f19;color:#f3f4f6;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
        echo '<div><h1 style="font-size:3rem;margin-bottom:10px;">🔧</h1><h2>Under Maintenance</h2><p style="color:#9ca3af;margin-top:8px;">We are currently performing maintenance. Please check back shortly.</p></div></body></html>';
        exit;
    }
    require __DIR__ . '/views/store/catalog.php';

} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head>';
    echo '<body style="background:#0b0f19;color:#f3f4f6;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div><h1 style="font-size:4rem;margin-bottom:10px;">404</h1><p>Page not found.</p><a href="' . BASE_URL . '/" style="color:#6366f1;">Go Home</a></div></body></html>';
}
