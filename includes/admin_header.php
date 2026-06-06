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
<div class="mobile-dashboard-bar">
    <a href="<?= BASE_URL ?>/dashboard" class="mobile-dashboard-brand"><span>Store</span>lo.</a>
    <button type="button" class="mobile-menu-button" aria-label="Open dashboard menu" aria-controls="seller-dashboard-sidebar" aria-expanded="false">
        <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="22" y2="7"></line><line x1="4" y1="13" x2="22" y2="13"></line><line x1="4" y1="19" x2="22" y2="19"></line></svg>
    </button>
</div>

<div class="mobile-menu-overlay" hidden></div>

<div class="sidebar" id="seller-dashboard-sidebar">
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>/dashboard" style="text-decoration: none; color: inherit; display: inline-block;">
            <span style="color: var(--accent);">Store</span>lo.
        </a>
        <button type="button" class="mobile-menu-close" aria-label="Close dashboard menu">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <ul class="sidebar-nav">
        <li><a href="<?= BASE_URL ?>/dashboard" class="<?= nav_active('/dashboard') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </span>
            Overview
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/products" class="<?= nav_active('/dashboard/products') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </span>
            Products
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/categories" class="<?= nav_active('/dashboard/categories') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            </span>
            Categories
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/orders" class="<?= nav_active('/dashboard/orders') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </span>
            Orders
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/customers" class="<?= nav_active('/dashboard/customers') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </span>
            Customers
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/promotions" class="<?= nav_active('/dashboard/promotions') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </span>
            Promotions
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/reviews" class="<?= nav_active('/dashboard/reviews') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><polygon points="12 6 13.5 9 17 9.5 14.5 12 15 15.5 12 14 9 15.5 9.5 12 7 9.5 10.5 9 12 6"></polygon></svg>
            </span>
            Reviews
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/shipping" class="<?= nav_active('/dashboard/shipping') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </span>
            Shipping
        </a></li>
        <li><a href="<?= BASE_URL ?>/dashboard/profile" class="<?= nav_active('/dashboard/profile') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </span>
            Shop Settings
        </a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/shop/<?= e($_SESSION['username'] ?? '') ?>" target="_blank" class="footer-link">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
            </span>
            View Store
        </a>
        <a href="<?= BASE_URL ?>/logout" class="footer-link-danger">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </span>
            Logout
        </a>
    </div>
</div>

<script>
    (function () {
        const button = document.querySelector('.mobile-menu-button');
        const closeButton = document.querySelector('.mobile-menu-close');
        const sidebar = document.getElementById('seller-dashboard-sidebar');
        const overlay = document.querySelector('.mobile-menu-overlay');

        if (!button || !closeButton || !sidebar || !overlay) return;

        function setDashboardMenu(open) {
            sidebar.classList.toggle('is-open', open);
            overlay.classList.toggle('is-visible', open);
            overlay.hidden = !open;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('dashboard-menu-open', open);
        }

        button.addEventListener('click', () => setDashboardMenu(true));
        closeButton.addEventListener('click', () => setDashboardMenu(false));
        overlay.addEventListener('click', () => setDashboardMenu(false));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setDashboardMenu(false);
        });

        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setDashboardMenu(false));
        });
    })();
</script>
