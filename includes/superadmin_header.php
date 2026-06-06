<?php
// includes/superadmin_header.php
function nav_active($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) === 0 ? 'active' : '';
}
?>
<div class="mobile-dashboard-bar">
    <a href="<?= BASE_URL ?>/superadmin/dashboard" class="mobile-dashboard-brand"><span>Store</span>lo. Admin</a>
    <button type="button" class="mobile-menu-button" aria-label="Open dashboard menu" aria-controls="superadmin-sidebar" aria-expanded="false">
        <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="22" y2="7"></line><line x1="4" y1="13" x2="22" y2="13"></line><line x1="4" y1="19" x2="22" y2="19"></line></svg>
    </button>
</div>

<div class="mobile-menu-overlay" hidden></div>

<aside class="sidebar" id="superadmin-sidebar">
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>/superadmin/dashboard" style="text-decoration: none; color: inherit; display: inline-block;">
            <span style="color: var(--accent);">Store</span>lo. Admin
        </a>
        <button type="button" class="mobile-menu-close" aria-label="Close dashboard menu">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <ul class="sidebar-nav">
        <li><a href="<?= BASE_URL ?>/superadmin/dashboard" class="<?= nav_active('/superadmin/dashboard') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </span>
            Dashboard
        </a></li>
        <li><a href="<?= BASE_URL ?>/superadmin/sellers" class="<?= nav_active('/superadmin/sellers') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </span>
            Stores
        </a></li>
        <li><a href="<?= BASE_URL ?>/superadmin/orders" class="<?= nav_active('/superadmin/orders') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </span>
            All Orders
        </a></li>
        <li><a href="<?= BASE_URL ?>/superadmin/settings" class="<?= nav_active('/superadmin/settings') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </span>
            Settings
        </a></li>
        <li><a href="<?= BASE_URL ?>/superadmin/blog" class="<?= nav_active('/superadmin/blog') ?>">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
            </span>
            Blog
        </a></li>
    </ul>

    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/superadmin/logout" class="footer-link-danger">
            <span class="nav-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </span>
            Logout
        </a>
    </div>
</aside>

<script>
    (function () {
        const button = document.querySelector('.mobile-menu-button');
        const closeButton = document.querySelector('.mobile-menu-close');
        const sidebar = document.getElementById('superadmin-sidebar');
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
