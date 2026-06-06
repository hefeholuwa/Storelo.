<?php
// views/superadmin/dashboard.php — Super Admin Main Dashboard
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_superadmin();

$db = DB::connect();

// Stats
$total_sellers = $db->query("SELECT COUNT(*) FROM sellers")->fetchColumn();
$total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$suspended_stores = $db->query("SELECT COUNT(*) FROM sellers WHERE is_suspended = 1")->fetchColumn();

// GMV: Total revenue across ALL stores (delivered orders only)
$gmv = $db->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'delivered'")->fetchColumn();
$total_gmv = $gmv;

// Featured & Banned counts
$featured_count = $db->query("SELECT COUNT(*) FROM sellers WHERE is_featured = 1")->fetchColumn();
$banned_count = $db->query("SELECT COUNT(*) FROM sellers WHERE is_banned = 1")->fetchColumn();

// Analytics: Registrations last 30 days
$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM sellers 
    WHERE created_at >= DATE(NOW()) - INTERVAL 30 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$reg_data_raw = $stmt->fetchAll();

// Analytics: Orders last 30 days
$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM orders 
    WHERE created_at >= DATE(NOW()) - INTERVAL 30 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$orders_data_raw = $stmt->fetchAll();

// Analytics: Revenue last 30 days
$stmt = $db->query("
    SELECT DATE(created_at) as date, COALESCE(SUM(total_price), 0) as total 
    FROM orders 
    WHERE status = 'delivered' AND created_at >= DATE(NOW()) - INTERVAL 30 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$revenue_data_raw = $stmt->fetchAll();

// Helper to fill in missing days with 0 so the chart looks complete
function fill_30_days($raw_data, $value_key = 'count') {
    $filled = [];
    $data_map = [];
    foreach ($raw_data as $row) {
        $data_map[$row['date']] = $value_key === 'count' ? (int)$row[$value_key] : (float)$row[$value_key];
    }
    
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $filled[] = [
            'date' => date('M d', strtotime($date)),
            'value' => isset($data_map[$date]) ? $data_map[$date] : 0
        ];
    }
    return $filled;
}

$reg_data = fill_30_days($reg_data_raw);
$orders_data = fill_30_days($orders_data_raw);
$revenue_data = fill_30_days($revenue_data_raw, 'total');

// Top Performing Stores (by total order revenue)
$top_stores = $db->query("
    SELECT s.shop_name, s.username, s.is_verified, s.is_featured,
           COALESCE(SUM(o.total_price), 0) as total_revenue,
           COUNT(o.id) as order_count
    FROM sellers s
    LEFT JOIN orders o ON o.seller_id = s.id AND o.status = 'delivered'
    GROUP BY s.id
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll();

// Recent Sellers
$recent_sellers = $db->query("SELECT shop_name, username, created_at, is_verified, is_featured FROM sellers ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        }
        .stat-icon { font-size: 1.8rem; margin-bottom: 8px; }
        .stat-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: var(--text-primary); }
        .stat-value.gmv { color: #059669; }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .leaderboard-rank {
            width: 28px; height: 28px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 800; margin-right: 10px;
        }
        .rank-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; }
        .rank-2 { background: linear-gradient(135deg, #d1d5db, #9ca3af); color: #fff; }
        .rank-3 { background: linear-gradient(135deg, #d97706, #b45309); color: #fff; }
        .rank-default { background: #f3f4f6; color: #6b7280; }
        .badge-sm { padding: 2px 6px; border-radius: 8px; font-size: 0.65rem; font-weight: 700; margin-left: 6px; }
        .badge-verified-sm { background: #dbeafe; color: #1e3a8a; }
        .badge-featured-sm { background: #fef3c7; color: #92400e; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/superadmin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1>Platform Overview</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Welcome, <?= e($_SESSION['admin_username']) ?>. Here is the big picture.</p>
                </div>
                <a href="<?= BASE_URL ?>/superadmin/settings" class="btn-secondary" style="font-size: 0.9rem;">⚙️ Platform Settings</a>
            </div>

            <!-- Stats -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-icon">🏪</div>
                    <div class="stat-label">Total Stores</div>
                    <div class="stat-value"><?= number_format($total_sellers) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= number_format($total_products) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= number_format($total_orders) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Platform GMV</div>
                    <div class="stat-value gmv">₦<?= number_format($total_gmv, 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-label">Featured Stores</div>
                    <div class="stat-value"><?= number_format($featured_count) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🚫</div>
                    <div class="stat-label">Banned Stores</div>
                    <div class="stat-value" style="color: #dc2626;"><?= number_format($banned_count) ?></div>
                </div>
            </div>

            <!-- Analytics Charts -->
            <div class="charts-grid">
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Store Registrations (30 Days)</h3>
                    <div style="position: relative; height: 260px;">
                        <canvas id="regChart"></canvas>
                    </div>
                </div>
                
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Orders Processed (30 Days)</h3>
                    <div style="position: relative; height: 260px;">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>

                <div class="glass-card" style="padding: 24px;">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Revenue Flow (30 Days)</h3>
                    <div style="position: relative; height: 260px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Two-column: Top Stores + Recent Sellers -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
                <!-- Top Performing Stores -->
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">🏆 Top Performing Stores</h3>
                    <?php if (empty($top_stores) || $top_stores[0]['total_revenue'] == 0): ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">No sales data yet. Stores will appear here once orders start flowing.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($top_stores as $i => $store): ?>
                                <?php if ($store['total_revenue'] <= 0) continue; ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: <?= $i === 0 ? '#fffbeb' : ($i === 1 ? '#f9fafb' : '#fff') ?>; border-radius: 8px; border: 1px solid var(--border-subtle);">
                                    <div style="display: flex; align-items: center;">
                                        <span class="leaderboard-rank <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : 'rank-default')) ?>"><?= $i + 1 ?></span>
                                        <div>
                                            <div style="font-weight: 700; font-size: 0.95rem;">
                                                <?= e($store['shop_name']) ?>
                                                <?php if ($store['is_verified']): ?><span class="badge-sm badge-verified-sm">✓</span><?php endif; ?>
                                                <?php if ($store['is_featured']): ?><span class="badge-sm badge-featured-sm">★</span><?php endif; ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">@<?= e($store['username']) ?> · <?= $store['order_count'] ?> orders</div>
                                        </div>
                                    </div>
                                    <div style="font-weight: 800; color: #059669; font-size: 0.95rem;">₦<?= number_format($store['total_revenue'], 2) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Sellers -->
                <div class="glass-card" style="padding: 24px;">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">🆕 Recently Registered Stores</h3>
                    <?php if (empty($recent_sellers)): ?>
                        <p style="color: var(--text-muted);">No stores registered yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($recent_sellers as $s): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-subtle);">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.95rem;">
                                            <?= e($s['shop_name']) ?>
                                            <?php if ($s['is_verified']): ?><span class="badge-sm badge-verified-sm">✓</span><?php endif; ?>
                                            <?php if ($s['is_featured']): ?><span class="badge-sm badge-featured-sm">★</span><?php endif; ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">@<?= e($s['username']) ?></div>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= date('M d, Y', strtotime($s['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 16px;">
                            <a href="<?= BASE_URL ?>/superadmin/sellers" style="color: var(--accent); font-size: 0.9rem; font-weight: 600; text-decoration: none;">View all stores &rarr;</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Chart.js and Initialization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const regData = <?= json_encode($reg_data) ?>;
        const ordersData = <?= json_encode($orders_data) ?>;
        const revenueData = <?= json_encode($revenue_data) ?>;
        
        const labels = regData.map(d => d.date);
        
        const chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } }
            }
        };

        // Registrations Chart
        new Chart(document.getElementById('regChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'New Stores',
                    data: regData.map(d => d.value),
                    borderColor: '#F68B1E',
                    backgroundColor: 'rgba(246, 139, 30, 0.1)',
                    borderWidth: 2, fill: true, tension: 0.3,
                    pointRadius: 2, pointBackgroundColor: '#F68B1E'
                }]
            },
            options: chartDefaults
        });

        // Orders Chart
        new Chart(document.getElementById('ordersChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Orders',
                    data: ordersData.map(d => d.value),
                    backgroundColor: '#10b981',
                    borderRadius: 4
                }]
            },
            options: chartDefaults
        });

        // Revenue Chart
        new Chart(document.getElementById('revenueChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(d => d.value),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2, fill: true, tension: 0.3,
                    pointRadius: 2, pointBackgroundColor: '#6366f1'
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    ...chartDefaults.scales,
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₦' + value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
