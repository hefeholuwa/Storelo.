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
    SELECT oi.quantity, oi.price, oi.variant_details, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

// Calculate subtotal
$subtotal = floatval($order['total_price']) - floatval($order['shipping_fee']) + floatval($order['discount_amount']);

// Format WhatsApp message
$msg = "New Order #" . $order['id'] . "\n\n";
$msg .= "Customer: " . $order['customer_name'] . "\n";
$msg .= "Phone: " . $order['customer_phone'] . "\n";
$msg .= "Address: " . $order['delivery_address'] . "\n\n";

$msg .= "Items:\n";
foreach ($items as $item) {
    $line = $item['quantity'] . "x " . $item['name'];
    if (!empty($item['variant_details'])) {
        $line .= " (" . $item['variant_details'] . ")";
    }
    $line .= " - " . $currency . number_format(floatval($item['price']) * intval($item['quantity']), 2) . "\n";
    $msg .= $line;
}

$msg .= "\nSubtotal: " . $currency . number_format($subtotal, 2) . "\n";
if (floatval($order['shipping_fee']) > 0) {
    $msg .= "Delivery: " . $currency . number_format($order['shipping_fee'], 2) . "\n";
}
if (!empty($order['promo_code']) && floatval($order['discount_amount']) > 0) {
    $msg .= "Discount: " . $currency . number_format($order['discount_amount'], 2) . "\n";
}
$msg .= "Total: " . $currency . number_format($order['total_price'], 2) . "\n";

if (!empty($seller['payment_instructions'])) {
    $msg .= "\nPayment Instructions:\n";
    $msg .= $seller['payment_instructions'] . "\n";
}

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
        localStorage.removeItem('storelo_cart_<?= e($shop_username) ?>');

        // Auto-redirect to WhatsApp after a brief delay
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.location.href = "<?= $wa_url ?>";
            }, 2500);
        });
    </script>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
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
                <?php if (floatval($order['shipping_fee']) > 0): ?>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:0.9rem; color:var(--text-secondary);">
                        <span>Shipping</span>
                        <span>+<?= $currency ?><?= number_format($order['shipping_fee'], 2) ?></span>
                    </div>
                <?php endif; ?>
                <div style="border-top:1px solid var(--border-subtle); padding-top:10px; margin-top:10px; display:flex; justify-content:space-between; font-weight:700; font-size:1rem;">
                    <span>Total</span>
                    <span><?= $currency ?><?= number_format($order['total_price'], 2) ?></span>
                </div>
            </div>

            <?php if (!empty($seller['payment_instructions'])): ?>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:var(--radius-md); padding:20px; margin-bottom:24px; text-align:left;">
                <h4 style="margin-top:0; margin-bottom:8px; color:#1e293b; font-size:1rem;">Payment Instructions</h4>
                <p style="color:#475569; font-size:0.95rem; margin:0; line-height:1.5;">
                    <?= nl2br(e($seller['payment_instructions'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Loading spinner -->
            <div style="margin-bottom:20px;">
                <div style="width:28px; height:28px; border:3px solid var(--border-subtle); border-top-color:var(--accent); border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto;"></div>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:10px;">Opening WhatsApp...</p>
            </div>

            <button onclick="copyReceipt()" class="btn-secondary" style="width:100%; margin-bottom:12px; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; font-weight: 600;">
                📄 Copy Order Details
            </button>

            <a href="<?= $wa_url ?>" target="_blank" class="btn-primary" style="width:100%; text-decoration:none; background: #25D366; border-color: #25D366;">
                💬 Open WhatsApp Now
            </a>

            <p style="color:var(--text-muted); font-size:0.8rem; margin-top:16px;">
                If WhatsApp doesn't open automatically, tap the button above to send your order.
            </p>
        </div>
    </div>

    <script>
        function copyReceipt() {
            const msg = <?= json_encode($msg) ?>;
            navigator.clipboard.writeText(msg).then(() => {
                alert('Order details copied to clipboard! You can paste them manually into WhatsApp if needed.');
            }).catch(err => {
                alert('Failed to copy. Please click the WhatsApp button directly.');
            });
        }
    </script>
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
