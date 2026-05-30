<?php
// views/dashboard/orders.php — Order history for sellers
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

// Handle status update
if (isset($_GET['mark_completed'])) {
    $oid = intval($_GET['mark_completed']);
    $stmt = $db->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND seller_id = ?");
    $stmt->execute([$oid, $seller_id]);
}

if (isset($_GET['mark_cancelled'])) {
    $oid = intval($_GET['mark_cancelled']);
    $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND seller_id = ?");
    $stmt->execute([$oid, $seller_id]);
}

// Fetch all orders
$stmt = $db->prepare("SELECT * FROM orders WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <h1>Orders</h1>
            <p class="page-subtitle">Track incoming orders from your customers.</p>

            <?php if (empty($orders)): ?>
                <div class="glass-card" style="text-align:center; padding:40px;">
                    <p style="color:var(--text-muted); font-size:1.1rem;">No orders yet. Share your store link to start receiving orders!</p>
                </div>
            <?php else: ?>
                <div class="glass-card" style="overflow-x:auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Delivery Address</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= $o['id'] ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= date('M j, Y', strtotime($o['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= e($o['customer_name']) ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= e($o['customer_phone']) ?></small>
                                    </td>
                                    <td style="max-width:200px;"><?= e($o['delivery_address']) ?></td>
                                    <td style="font-weight:700;"><?= $currency ?><?= number_format($o['total_price'], 2) ?></td>
                                    <td>
                                        <?php
                                        $status_class = match($o['status']) {
                                            'completed' => 'badge-success',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= ucfirst($o['status']) ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-direction:column; gap:6px;">
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $o['customer_phone']) ?>" target="_blank" class="btn-primary btn-sm" style="font-size:0.8rem; text-decoration:none;">💬 WhatsApp</a>
                                            <?php if ($o['status'] === 'pending'): ?>
                                                <a href="?mark_completed=<?= $o['id'] ?>" class="btn-secondary btn-sm" style="font-size:0.8rem; text-decoration:none;">✅ Complete</a>
                                                <a href="?mark_cancelled=<?= $o['id'] ?>" class="btn-secondary btn-sm" style="font-size:0.8rem; color:var(--danger); text-decoration:none;">✖ Cancel</a>
                                            <?php endif; ?>
                                        </div>
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
