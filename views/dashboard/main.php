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

// Check categories and shipping for setup guide
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM categories WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_categories = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM shipping_zones WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_shipping = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ? AND status = 'active' AND stock > 0");
$stmt->execute([$seller_id]);
$active_products = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ? AND status = 'active' AND stock <= 0");
$stmt->execute([$seller_id]);
$sold_products = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_orders = $stmt->fetch()['cnt'];

$stmt = $db->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE seller_id = ? AND status = 'delivered'");
$stmt->execute([$seller_id]);
$total_revenue = $stmt->fetch()['total'];

// Fetch seller for currency, visits, and shop name
$stmt = $db->prepare("SELECT shop_name, currency, store_visits FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();
$currency = $seller['currency'] ?? '₦';
$store_visits = $seller['store_visits'] ?? 0;

// Check for global announcement
$announcement_banner = '';
try {
    $ann_stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = 'announcement_active'");
    $ann_stmt->execute();
    $ann_active = $ann_stmt->fetchColumn();
    if ($ann_active === '1') {
        $ann_text_stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = 'announcement_text'");
        $ann_text_stmt->execute();
        $announcement_banner = trim($ann_text_stmt->fetchColumn());
    }
} catch (Exception $e) {
    // platform_settings table might not exist yet
}

// 1. Fetch 5 recent orders
$stmt = $db->prepare("SELECT id, customer_name, total_price, status, created_at FROM orders WHERE seller_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$seller_id]);
$recent_orders = $stmt->fetchAll();

// 2. Fetch low stock products (active, stock <= 5 and stock > 0 to differentiate from completely sold out)
$stmt = $db->prepare("SELECT id, name, stock, price, image_paths FROM products WHERE seller_id = ? AND status = 'active' AND stock <= 5 AND stock > 0 ORDER BY stock ASC LIMIT 5");
$stmt->execute([$seller_id]);
$low_stock_products = $stmt->fetchAll();

// 3. Fetch Revenue Over Time (last 7 days)
// Revenue Chart Data (Last 30 Days) - Only delivered
$stmt = $db->prepare("
    SELECT DATE(created_at) as order_date, SUM(total_price) as daily_revenue 
    FROM orders 
    WHERE seller_id = ? AND status = 'delivered' AND created_at >= DATE(NOW()) - INTERVAL 30 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY order_date ASC
");
$stmt->execute([$seller_id]);
$revenue_data = $stmt->fetchAll();

// Generate an array of the last 7 days to ensure gaps are filled with 0s
$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($date));
    
    // Find revenue for this date
    $revenue = 0;
    foreach ($revenue_data as $row) {
        if ($row['order_date'] === $date) {
            $revenue = floatval($row['daily_revenue']);
            break;
        }
    }
    $chart_values[] = $revenue;
}

// 4. Fetch Top Performing Products
$stmt = $db->prepare("
    SELECT p.id, p.name, p.image_paths, p.price, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE o.seller_id = ?
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 3
");
$stmt->execute([$seller_id]);
$top_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .widget-card {
            background: #fff;
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid var(--border-subtle);
        }
        .widget-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-table th, .order-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 0.95rem;
        }
        .order-table th {
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-pending { background: #fef08a; color: #854d0e; }
        .badge-shipped { background: #bfdbfe; color: #1e40af; }
        .badge-delivered { background: #bbf7d0; color: #166534; }
        
        .low-stock-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-subtle);
        }
        .low-stock-item:last-child {
            border-bottom: none;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <?php if ($announcement_banner): ?>
                <div id="announcementBanner" style="background: linear-gradient(135deg, #fef3c7, #fffbeb); border: 1px solid #fde68a; border-radius: 10px; padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div style="font-size: 0.92rem; color: #92400e; font-weight: 500;">📢 <?= e($announcement_banner) ?></div>
                    <button onclick="document.getElementById('announcementBanner').style.display='none'" style="background: none; border: none; color: #92400e; cursor: pointer; font-size: 1.2rem; padding: 0; line-height: 1;">&times;</button>
                </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div>
                    <h1>Welcome, <?= e($seller['shop_name'] ?? $_SESSION['username']) ?> 👋</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Here's a snapshot of your store.</p>
                </div>
                <div class="dashboard-header-actions" style="display:flex; gap:12px; flex-wrap: wrap;">
                    <a href="<?= BASE_URL ?>/dashboard/products" class="btn-primary" style="flex: 1; text-align: center;">+ Add Product</a>
                    <a href="<?= BASE_URL ?>/dashboard/profile" class="btn-secondary" style="flex: 1; text-align: center;">Settings</a>
                </div>
            </div>

            <!-- Store Link Banner -->
            <div class="store-link-banner" style="margin-bottom: 24px;">
                <div>
                    <small style="color:var(--text-secondary);">Your store link:</small><br>
                    <span class="link-text" id="store-link"><?= str_replace(['http://', 'https://'], '', BASE_URL) ?>/shop/<?= e($_SESSION['username']) ?></span>
                </div>
                <button class="btn-primary btn-sm" onclick="copyLink()" id="copy-btn">Copy Link</button>
            </div>

            <!-- Setup Guide / Onboarding Checklist -->
            <?php 
            $setup_steps = [
                ['name' => 'Update your store profile', 'completed' => !empty($seller['shop_name']), 'url' => BASE_URL . '/dashboard/profile'],
                ['name' => 'Create a product category', 'completed' => $total_categories > 0, 'url' => BASE_URL . '/dashboard/categories'],
                ['name' => 'Set up delivery zones', 'completed' => $total_shipping > 0, 'url' => BASE_URL . '/dashboard/shipping'],
                ['name' => 'Add your first product', 'completed' => $total_products > 0, 'url' => BASE_URL . '/dashboard/products']
            ];
            $completed_steps = count(array_filter($setup_steps, fn($step) => $step['completed']));
            ?>
            <?php if ($completed_steps < 4): ?>
                <div class="widget-card" style="margin-bottom: 24px; border: 1px solid var(--accent); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="margin: 0; color: #0f172a; font-size: 1.1rem;">🚀 Complete Your Store Setup (<?= $completed_steps ?>/4)</h3>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Get ready for your first sale!</span>
                    </div>
                    
                    <div style="background: #f1f5f9; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 20px;">
                        <div style="width: <?= ($completed_steps / 4) * 100 ?>%; background: var(--accent); height: 100%; border-radius: 4px; transition: width 0.3s;"></div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <?php foreach ($setup_steps as $step): ?>
                            <a href="<?= $step['url'] ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; border: 1px solid <?= $step['completed'] ? '#e2e8f0' : '#cbd5e1' ?>; background: <?= $step['completed'] ? '#f8fafc' : '#fff' ?>; text-decoration: none; color: <?= $step['completed'] ? '#94a3b8' : '#334155' ?>; transition: transform 0.1s, box-shadow 0.1s;">
                                <div style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; background: <?= $step['completed'] ? '#22c55e' : '#e2e8f0' ?>; color: <?= $step['completed'] ? '#fff' : '#64748b' ?>;">
                                    <?= $step['completed'] ? '✓' : '•' ?>
                                </div>
                                <span style="font-size: 0.9rem; font-weight: 500; <?= $step['completed'] ? 'text-decoration: line-through;' : '' ?>"><?= $step['name'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="dashboard-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-icon">👀</div>
                    <div class="stat-label">Store Visits</div>
                    <div class="stat-value"><?= number_format($store_visits) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= $total_products ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value"><?= $currency ?><?= number_format($total_revenue, 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= $total_orders ?></div>
                </div>
            </div>

            <div class="dashboard-main-grid">
                
                <!-- Left Column -->
                <div class="dashboard-panel-stack">
                    <!-- Revenue Chart -->
                    <div class="widget-card">
                        <div class="widget-title">Revenue (Last 7 Days)</div>
                        <div style="position: relative; width: 100%; height: 250px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Top Performing Products -->
                    <div class="widget-card">
                        <div class="widget-title">🔥 Top Selling Products</div>
                        <?php if (empty($top_products)): ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">No sales data available yet.</p>
                        <?php else: ?>
                            <div>
                                <?php foreach ($top_products as $p): 
                                    $paths = json_decode($p['image_paths'], true) ?: [];
                                    $cover = !empty($paths) ? $paths[0] : 'assets/images/placeholder.png';
                                ?>
                                <div class="low-stock-item">
                                    <img src="<?= BASE_URL ?>/<?= $cover ?>" style="width: 56px; height: 56px; object-fit: cover; border-radius: var(--radius-sm);">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 700; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($p['name']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?= $currency ?><?= number_format($p['price'], 2) ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 800; font-size: 1.2rem; color: var(--success);"><?= $p['total_sold'] ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Sold</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="widget-card">
                        <div class="widget-title">
                            Recent Orders
                            <a href="<?= BASE_URL ?>/dashboard/orders" style="font-size: 0.85rem; color: var(--accent); text-decoration: none;">View All</a>
                        </div>
                        <?php if (empty($recent_orders)): ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">No orders yet.</p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="order-table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?= $order['id'] ?></td>
                                                <td><?= e($order['customer_name']) ?></td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= e($order['status']) ?>">
                                                        <?= ucfirst(e($order['status'])) ?>
                                                    </span>
                                                </td>
                                                <td style="font-weight: 600;"><?= $currency ?><?= number_format($order['total_price'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="dashboard-panel-stack">
                    <!-- Low Stock Alerts -->
                    <div class="widget-card">
                        <div class="widget-title" style="color: var(--danger);">⚠️ Low Stock Alerts</div>
                        <?php if (empty($low_stock_products)): ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem;">All active products have sufficient stock.</p>
                        <?php else: ?>
                            <div>
                                <?php foreach ($low_stock_products as $p): 
                                    $paths = json_decode($p['image_paths'], true) ?: [];
                                    $cover = !empty($paths) ? $paths[0] : 'assets/images/placeholder.png';
                                ?>
                                <div class="low-stock-item">
                                    <img src="<?= BASE_URL ?>/<?= $cover ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($p['name']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--danger); font-weight: bold;">Only <?= $p['stock'] ?> left</div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/dashboard/products" class="btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">Restock</a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Store QR Code -->
                    <div class="widget-card" style="text-align: center;">
                        <div class="widget-title" style="justify-content: center;">Share Your Store</div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">Scan to visit your storefront</p>
                        <div id="qrcode" style="display: flex; justify-content: center; margin-bottom: 16px; padding: 12px; background: #fff; border-radius: 8px; border: 1px solid var(--border-subtle); display: inline-block;"></div>
                        <br>
                        <button class="btn-secondary btn-sm" onclick="downloadQR()">Download QR Code</button>
                    </div>
                </div>
                
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

        // Generate QR Code
        const storeUrl = 'https://' + document.getElementById('store-link').innerText.trim();
        new QRCode(document.getElementById("qrcode"), {
            text: storeUrl,
            width: 160,
            height: 160,
            colorDark : "#111827",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        function downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                const url = canvas.toDataURL("image/png");
                const a = document.createElement('a');
                a.href = url;
                a.download = 'storelo_qrcode.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        }

        // Initialize Chart.js
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: 'Revenue',
                        data: <?= json_encode($chart_values) ?>,
                        borderColor: '#F68B1E',
                        backgroundColor: 'rgba(246, 139, 30, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#F68B1E',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '<?= $currency ?>' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
