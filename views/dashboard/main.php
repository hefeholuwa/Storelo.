<?php
// views/dashboard/main.php — Dashboard overview with stats
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$db = DB::connect();
$seller_id = $_SESSION['seller_id'];

// Stats queries
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_products = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ? AND status = 'active' AND stock > 0");
$stmt->execute([$seller_id]);
$active_products = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ? AND status = 'active' AND stock <= 0");
$stmt->execute([$seller_id]);
$sold_products = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_orders = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_revenue = $stmt->fetch()['total'];

// Fetch seller for currency
$stmt = $db->prepare("SELECT currency FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();
$currency = $seller['currency'] ?? '₦';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <h1>Welcome, <?= e($_SESSION['username']) ?> 👋</h1>
            <p class="page-subtitle">Here's a snapshot of your store.</p>

            <!-- Store Link Banner -->
            <div class="store-link-banner">
                <div>
                    <small style="color:var(--text-secondary);">Your store link:</small><br>
                    <span class="link-text" id="store-link">storelo.page.gd/shop/<?= e($_SESSION['username']) ?></span>
                </div>
                <button class="btn-primary btn-sm" onclick="copyLink()" id="copy-btn">Copy Link</button>
            </div>

            <!-- Stats -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= $total_products ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-label">Available</div>
                    <div class="stat-value" style="color:var(--success);"><?= $active_products ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏷️</div>
                    <div class="stat-label">Out of Stock</div>
                    <div class="stat-value" style="color:var(--danger);"><?= $sold_products ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-label">Orders</div>
                    <div class="stat-value"><?= $total_orders ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value"><?= $currency ?><?= number_format($total_revenue, 2) ?></div>
                </div>
            </div>

            <!-- Quick actions -->
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>/dashboard/products" class="btn-primary">Manage Products</a>
                <a href="<?= BASE_URL ?>/dashboard/orders" class="btn-secondary">View Orders</a>
                <a href="<?= BASE_URL ?>/dashboard/profile" class="btn-secondary">Shop Settings</a>
            </div>
        </div>
    </div>

    <script>
        function copyLink() {
            const text = document.getElementById('store-link').innerText;
            navigator.clipboard.writeText('https://' + text).then(() => {
                const btn = document.getElementById('copy-btn');
                btn.textContent = 'Copied!';
                setTimeout(() => btn.textContent = 'Copy Link', 2000);
            });
        }
    </script>
</body>
</html>
