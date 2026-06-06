<?php
// Verifies seller dashboard pages have the shared mobile layout primitives.

$root = dirname(__DIR__);
$header = file_get_contents($root . '/includes/admin_header.php');
$css = file_get_contents($root . '/assets/css/admin.css');
$main = file_get_contents($root . '/views/dashboard/main.php');

$failures = [];

function expect_contains($haystack, $needle, $message) {
    global $failures;
    if (strpos($haystack, $needle) === false) {
        $failures[] = $message;
    }
}

expect_contains($header, 'class="mobile-dashboard-bar"', 'Missing mobile dashboard top bar.');
expect_contains($header, 'class="mobile-menu-button"', 'Missing mobile menu button.');
expect_contains($header, 'id="seller-dashboard-sidebar"', 'Sidebar needs a stable drawer id.');
expect_contains($header, 'class="mobile-menu-overlay"', 'Missing mobile menu overlay.');
expect_contains($header, 'dashboard-menu-open', 'Missing drawer open/close script state.');

expect_contains($css, 'body.dashboard-menu-open', 'Missing body scroll lock when menu is open.');
expect_contains($css, '.sidebar.is-open', 'Missing open state for mobile drawer.');
expect_contains($css, 'transform: translateX(-100%)', 'Mobile sidebar should hide off-canvas.');
expect_contains($css, '.mobile-menu-overlay.is-visible', 'Missing visible overlay state.');
expect_contains($css, '[style*="grid-template-columns"]', 'Inline desktop grids need mobile override.');
expect_contains($css, '[style*="display:flex"]', 'Inline flex rows need mobile wrapping override.');
expect_contains($css, '.product-grid', 'Product grid needs mobile treatment.');
expect_contains($css, '.orders-table', 'Tables need mobile treatment.');
expect_contains($css, '/* ── Secondary Button', 'Admin secondary button needs a base style.');

expect_contains($main, 'dashboard-main-grid', 'Overview should use a classed dashboard grid.');
expect_contains($main, 'dashboard-panel-stack', 'Overview columns should use classed stacks.');

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . PHP_EOL);
    }
    exit(1);
}

echo "Seller dashboard mobile checks passed." . PHP_EOL;
