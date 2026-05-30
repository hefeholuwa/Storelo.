<?php
// includes/admin_header.php — Sidebar navigation for seller dashboard
$current_page = $_SERVER['REQUEST_URI'];
$base = BASE_URL;

// Helper to check if a nav link is active
function nav_active($path) {
    global $current_page, $base;
    $check = str_replace($base, '', $current_page);
    $check = rtrim(parse_url($check, PHP_URL_PATH), '/');
    return ($check === $path) ? 'active' : '';
}
?>
<div class="sidebar">
    <div class="sidebar-brand">Storelo</div>
    <ul class="sidebar-nav">
        <li><a href="<?= BASE_URL ?>/dashboard" class="<?= nav_active('/dashboard') ?>">
            <span class="nav-icon">📊</span> Overview
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/products" class="<?= nav_active('/dashboard/products') ?>">
            <span class="nav-icon">📦</span> Products
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/orders" class="<?= nav_active('/dashboard/orders') ?>">
            <span class="nav-icon">🛒</span> Orders
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/profile" class="<?= nav_active('/dashboard/profile') ?>">
            <span class="nav-icon">⚙️</span> Shop Settings
        </a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/shop/<?= e($_SESSION['username'] ?? '') ?>" target="_blank">
            <span class="nav-icon">🔗</span> View Store
        </a>
        <a href="<?= BASE_URL ?>/logout">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </div>
</div>
