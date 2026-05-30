<?php
// index.php — Front Controller & Router
// All requests are funnelled here by .htaccess and dispatched to the correct view.

session_start();

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

} elseif ($request === '/login') {
    require __DIR__ . '/views/login.php';

} elseif ($request === '/register') {
    require __DIR__ . '/views/register.php';

} elseif ($request === '/logout') {
    session_destroy();
    redirect('/');

} elseif ($request === '/dashboard') {
    require_login();
    require __DIR__ . '/views/dashboard/main.php';

} elseif ($request === '/dashboard/products') {
    require_login();
    require __DIR__ . '/views/dashboard/products.php';

} elseif ($request === '/dashboard/orders') {
    require_login();
    require __DIR__ . '/views/dashboard/orders.php';

} elseif ($request === '/dashboard/profile') {
    require_login();
    require __DIR__ . '/views/dashboard/profile.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)/order-success/([0-9]+)$#', $request, $matches)) {
    $shop_username = $matches[1];
    $order_id = (int)$matches[2];
    require __DIR__ . '/views/store/order_success.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)/checkout$#', $request, $matches)) {
    $shop_username = $matches[1];
    require __DIR__ . '/views/store/checkout_handler.php';

} elseif (preg_match('#^/shop/([a-zA-Z0-9_-]+)$#', $request, $matches)) {
    $shop_username = $matches[1];
    require __DIR__ . '/views/store/catalog.php';

} else {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head>';
    echo '<body style="background:#0b0f19;color:#f3f4f6;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div><h1 style="font-size:4rem;margin-bottom:10px;">404</h1><p>Page not found.</p><a href="' . BASE_URL . '/" style="color:#6366f1;">Go Home</a></div></body></html>';
}
