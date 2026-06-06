<?php
// views/superadmin/orders.php — Global Orders View for Super Admin
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_superadmin();

$db = DB::connect();

// Fetch latest orders with seller info
$stmt = $db->query("
    SELECT o.*, s.shop_name, s.username, s.currency 
    FROM orders o 
    JOIN sellers s ON o.seller_id = s.id 
    ORDER BY o.created_at DESC 
    LIMIT 200
");
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Orders — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .orders-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .orders-table th { text-align: left; padding: 16px; border-bottom: 2px solid var(--border-light); color: var(--text-muted); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .orders-table td { padding: 16px; border-bottom: 1px solid var(--border-subtle); vertical-align: middle; }
        .orders-table tr:hover { background: rgba(0,0,0,0.01); }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-pending { background: #fef08a; color: #854d0e; }
        .badge-confirmed { background: #bae6fd; color: #0369a1; }
        .badge-packed { background: #e9d5ff; color: #6b21a8; }
        .badge-delivered { background: #bbf7d0; color: #166534; }
        .badge-cancelled { background: #fecaca; color: #991b1b; }
        
        .store-badge { display: inline-flex; align-items: center; background: #f3f4f6; padding: 4px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-decoration: none; border: 1px solid var(--border-subtle); transition: 0.2s; }
        .store-badge:hover { background: #e5e7eb; border-color: #d1d5db; color: var(--text-main); }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/superadmin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Global Orders</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Monitoring the latest 200 orders across all stores.</p>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="glass-card" style="text-align:center; padding:40px;">
                    <p style="color:var(--text-muted); font-size:1.1rem;">No orders found on the platform yet.</p>
                </div>
            <?php else: ?>
                <div class="glass-card" style="overflow-x:auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Store</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="text-align: right;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): 
                                $status_class = match($o['status']) {
                                    'delivered' => 'badge-delivered',
                                    'confirmed' => 'badge-confirmed',
                                    'packed' => 'badge-packed',
                                    'cancelled' => 'badge-cancelled',
                                    default => 'badge-pending',
                                };
                                $subtotal = floatval($o['total_price']) - floatval($o['shipping_fee'] ?? 0) + floatval($o['discount_amount'] ?? 0);
                            ?>
                                <tr>
                                    <td style="font-weight: 700;">#<?= $o['id'] ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/shop/<?= e($o['username']) ?>" target="_blank" class="store-badge">
                                            <?= e($o['shop_name']) ?> &nearr;
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= e($o['customer_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($o['customer_phone']) ?></div>
                                    </td>
                                    <td style="font-weight: 600;">
                                        <?= e($o['currency'] ?? '₦') ?><?= number_format($o['total_price'], 2) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= e($o['status']) ?></span>
                                    </td>
                                    <td style="text-align: right; color: var(--text-secondary); font-size: 0.85rem;">
                                        <?= date('M d, Y h:i A', strtotime($o['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
