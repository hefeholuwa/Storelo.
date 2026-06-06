<?php
// views/dashboard/customers.php — Customer list for sellers
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$db = DB::connect();
$seller_id = $_SESSION['seller_id'];

// Fetch seller for currency
$stmt = $db->prepare("SELECT currency FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();
$currency = $seller['currency'] ?? '₦';

// Fetch customers derived from orders
$stmt = $db->prepare("
    SELECT customer_name, customer_phone, 
           COUNT(id) as total_orders, 
           SUM(total_price) as total_spent, 
           MAX(created_at) as last_order 
    FROM orders 
    WHERE seller_id = ? AND status = 'delivered' 
    GROUP BY customer_name, customer_phone 
    ORDER BY last_order DESC
");
$stmt->execute([$seller_id]);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Customers</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">View your customer list and total spending.</p>
                </div>
            </div>

            <?php if (empty($customers)): ?>
                <div class="glass-card" style="text-align:center; padding:40px;">
                    <p style="color:var(--text-muted); font-size:1.1rem;">No customers yet. Start sharing your store link!</p>
                </div>
            <?php else: ?>
                <div class="glass-card" style="overflow-x:auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone / WhatsApp</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Last Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): 
                                $wa_phone = preg_replace('/[^0-9]/', '', $c['customer_phone']);
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['customer_name']) ?></strong>
                                    </td>
                                    <td>
                                        <?= e($c['customer_phone']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info" style="font-size: 0.8rem;"><?= $c['total_orders'] ?></span>
                                    </td>
                                    <td style="font-weight:700;">
                                        <?= $currency ?><?= number_format($c['total_spent'], 2) ?>
                                    </td>
                                    <td>
                                        <small style="color:var(--text-muted);"><?= date('M j, Y', strtotime($c['last_order'])) ?></small>
                                    </td>
                                    <td>
                                        <a href="https://wa.me/<?= $wa_phone ?>" target="_blank" class="btn-primary btn-sm" style="font-size:0.8rem; text-decoration:none; background:#25D366; border-color:#25D366;">💬 WhatsApp</a>
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
