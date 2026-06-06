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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid = intval($_POST['order_id']);
    $status = $_POST['new_status'];
    $valid = ['pending', 'confirmed', 'packed', 'delivered', 'cancelled'];
    if (in_array($status, $valid)) {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$status, $oid, $seller_id]);
    }
    redirect('/dashboard/orders');
}

// Fetch all orders
$stmt = $db->prepare("SELECT * FROM orders WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$orders = $stmt->fetchAll();

// Fetch order items to generate receipts
$order_ids = array_column($orders, 'id');
$order_items = [];
if (!empty($order_ids)) {
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    $stmt = $db->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN ($in)");
    $stmt->execute($order_ids);
    foreach ($stmt->fetchAll() as $row) {
        $order_items[$row['order_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Orders</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Track incoming orders from your customers.</p>
                </div>
            </div>

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
                            <?php foreach ($orders as $o): 
                                $status_class = match($o['status']) {
                                    'delivered' => 'badge-success',
                                    'confirmed' => 'badge-primary',
                                    'packed' => 'badge-info',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-warning',
                                };
                                
                                $wa_phone = preg_replace('/[^0-9]/', '', $o['customer_phone']);
                                $wa_text = urlencode("Hello " . $o['customer_name'] . ", regarding your order #" . $o['id'] . ": ");
                                
                                $items = $order_items[$o['id']] ?? [];
                                $subtotal = floatval($o['total_price']) - floatval($o['shipping_fee'] ?? 0) + floatval($o['discount_amount'] ?? 0);
                                $receipt = "Order #" . $o['id'] . "\n\n";
                                $receipt .= "Customer: " . $o['customer_name'] . "\n";
                                $receipt .= "Phone: " . $o['customer_phone'] . "\n";
                                $receipt .= "Address: " . $o['delivery_address'] . "\n\n";
                                $receipt .= "Items:\n";
                                foreach ($items as $item) {
                                    $line = $item['quantity'] . "x " . $item['name'];
                                    if (!empty($item['variant_details'])) {
                                        $line .= " (" . $item['variant_details'] . ")";
                                    }
                                    $line .= " - " . $currency . number_format(floatval($item['price']) * intval($item['quantity']), 2) . "\n";
                                    $receipt .= $line;
                                }
                                $receipt .= "\nSubtotal: " . $currency . number_format($subtotal, 2) . "\n";
                                if (floatval($o['shipping_fee'] ?? 0) > 0) {
                                    $receipt .= "Delivery: " . $currency . number_format($o['shipping_fee'], 2) . "\n";
                                }
                                if (!empty($o['promo_code']) && floatval($o['discount_amount'] ?? 0) > 0) {
                                    $receipt .= "Discount: " . $currency . number_format($o['discount_amount'], 2) . "\n";
                                }
                                $receipt .= "Total: " . $currency . number_format($o['total_price'], 2);
                                $json_receipt = htmlspecialchars(json_encode($receipt), ENT_QUOTES, 'UTF-8');
                            ?>
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
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="new_status" onchange="this.form.submit()" class="form-control" style="padding: 4px; font-size: 0.85rem; width: 110px; border-radius: 4px; cursor: pointer;">
                                                <option value="pending" <?= $o['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= $o['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="packed" <?= $o['status'] === 'packed' ? 'selected' : '' ?>>Packed</option>
                                                <option value="delivered" <?= $o['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                        <div style="margin-top:4px;">
                                            <span class="badge <?= $status_class ?>" style="font-size:0.7rem;"><?= ucfirst($o['status']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-direction:column; gap:6px;">
                                            <a href="https://wa.me/<?= $wa_phone ?>?text=<?= $wa_text ?>" target="_blank" class="btn-primary btn-sm" style="font-size:0.8rem; text-decoration:none; background:#25D366; border-color:#25D366; text-align:center;">💬 WhatsApp Reply</a>
                                            <button onclick='copyOrderReceipt(<?= $json_receipt ?>)' class="btn-secondary btn-sm" style="font-size:0.8rem; text-align:center;">📄 Copy Receipt</button>
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
    
    <script>
        function copyOrderReceipt(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Order receipt copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy receipt.');
            });
        }
    </script>
</body>
</html>
