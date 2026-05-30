<?php
// views/store/order_success.php — Order confirmation & WhatsApp redirect
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = DB::connect();

// Fetch seller
$stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
$stmt->execute([$shop_username]);
$seller = $stmt->fetch();

if (!$seller) {
    die("Store not found.");
}

// Fetch order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND seller_id = ?");
$stmt->execute([$order_id, $seller['id']]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

$currency = $seller['currency'] ?? '₦';

// Fetch order items with product names
$stmt = $db->prepare("
    SELECT oi.quantity, oi.price, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

// Format WhatsApp message
$msg = "Hello " . $seller['shop_name'] . ", I'd like to place an order:\n\n";
$msg .= "🛒 ORDER DETAILS (ID: #" . $order['id'] . ")\n";
$msg .= "-----------------------------\n";
foreach ($items as $item) {
    $msg .= "• " . $item['quantity'] . "x " . $item['name'] . " - " . $currency . number_format($item['price'], 2) . "\n";
}
$msg .= "\n💰 TOTAL: " . $currency . number_format($order['total_price'], 2) . "\n\n";
$msg .= "🚚 CUSTOMER INFO\n";
$msg .= "-----------------------------\n";
$msg .= "Name: " . $order['customer_name'] . "\n";
$msg .= "Phone: " . $order['customer_phone'] . "\n";
$msg .= "Address: " . $order['delivery_address'] . "\n";

$wa_url = "https://wa.me/" . $seller['whatsapp_number'] . "?text=" . urlencode($msg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        // Clear the cart on success
        localStorage.removeItem('storelo_cart');

        // Auto-redirect to WhatsApp after a brief delay
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.location.href = "<?= $wa_url ?>";
            }, 2500);
        });
    </script>
</head>
<body>
    <div class="success-wrapper">
        <div class="glass-card no-hover" style="max-width:500px; padding:48px 32px;">
            <div class="success-icon">✓</div>

            <h2 style="margin-bottom:8px;">Order Placed!</h2>
            <p style="color:var(--text-secondary); margin-bottom:24px;">
                Your order <strong>#<?= $order['id'] ?></strong> has been saved. Redirecting you to WhatsApp to finalize with <strong><?= e($seller['shop_name']) ?></strong>...
            </p>

            <!-- Order Summary -->
            <div style="background:var(--bg-secondary); border-radius:var(--radius-md); padding:20px; margin-bottom:24px; text-align:left;">
                <?php foreach ($items as $item): ?>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:0.9rem;">
                        <span><?= $item['quantity'] ?>x <?= e($item['name']) ?></span>
                        <span style="color:var(--accent-light);"><?= $currency ?><?= number_format($item['price'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                <div style="border-top:1px solid var(--border-subtle); padding-top:10px; margin-top:10px; display:flex; justify-content:space-between; font-weight:700; font-size:1rem;">
                    <span>Total</span>
                    <span><?= $currency ?><?= number_format($order['total_price'], 2) ?></span>
                </div>
            </div>

            <!-- Loading spinner -->
            <div style="margin-bottom:20px;">
                <div style="width:28px; height:28px; border:3px solid var(--border-subtle); border-top-color:var(--accent); border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto;"></div>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:10px;">Opening WhatsApp...</p>
            </div>

            <a href="<?= $wa_url ?>" class="btn-primary" style="width:100%; text-decoration:none;">
                💬 Open WhatsApp Now
            </a>

            <p style="color:var(--text-muted); font-size:0.8rem; margin-top:16px;">
                If WhatsApp doesn't open automatically, tap the button above.
            </p>
        </div>
    </div>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
