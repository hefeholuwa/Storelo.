<?php
// views/store/checkout_handler.php — Handles POST checkout submission
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$customer_name = sanitize_input($_POST['customer_name'] ?? '');
$customer_phone = sanitize_input($_POST['customer_phone'] ?? '');
$delivery_address = sanitize_input($_POST['delivery_address'] ?? '');
$cart_data = $_POST['cart_data'] ?? '';
$applied_promo = trim(sanitize_input($_POST['applied_promo'] ?? ''));
$shipping_zone_id = intval($_POST['shipping_zone_id'] ?? 0);
$cart_items = json_decode($cart_data, true);

if (empty($customer_name) || empty($customer_phone) || empty($delivery_address) || empty($cart_items)) {
    die("Invalid checkout data. Please go back and try again.");
}

$db = DB::connect();

// Fetch seller
$stmt = $db->prepare("SELECT id, is_suspended, is_deleted FROM sellers WHERE username = ?");
$stmt->execute([$shop_username]);
$seller = $stmt->fetch();

if (!$seller || !empty($seller['is_deleted'])) {
    die("Store not found or has been closed.");
}

if (!empty($seller['is_suspended'])) {
    die("This store is currently unavailable. Checkout is disabled.");
}

$db->beginTransaction();

try {
    $total_price = 0;
    $validated_items = [];

    // Validate all products are still available
    foreach ($cart_items as $item) {
        // For variant items, productId is the real DB id; for simple items, id is used
        $product_id = intval($item['productId'] ?? $item['id'] ?? 0);
        $requested_qty = intval($item['quantity'] ?? 1);
        $variant = isset($item['variant']) ? trim($item['variant']) : '';

        if ($product_id <= 0 || $requested_qty <= 0) {
            throw new Exception("Invalid cart item. Please review your cart and try again.");
        }

        $stmt = $db->prepare("SELECT name, price, status, stock FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$product_id, $seller['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Product not found: " . ($item['name'] ?? 'Unknown'));
        }
        if ($product['status'] !== 'active') {
            throw new Exception("Sorry, '" . ($item['name'] ?? 'an item') . "' is currently unavailable.");
        }
        
        if ($product['stock'] < $requested_qty) {
            throw new Exception("Sorry, only " . $product['stock'] . " units of '" . ($item['name'] ?? 'item') . "' are available.");
        }

        $price = floatval($product['price']);
        $total_price += $price * $requested_qty;
        $validated_items[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'quantity' => $requested_qty,
            'price' => $price,
            'variant' => $variant ?: null,
        ];
    }

    $discount_amount = 0;
    if (!empty($applied_promo)) {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND seller_id = ? AND status = 'active'");
        $stmt->execute([$applied_promo, $seller['id']]);
        $coupon = $stmt->fetch();
        if ($coupon) {
            if ($coupon['discount_type'] === 'percentage') {
                $discount_amount = $total_price * (floatval($coupon['discount_value']) / 100);
            } else {
                $discount_amount = floatval($coupon['discount_value']);
            }
            if ($discount_amount > $total_price) {
                $discount_amount = $total_price;
            }
        } else {
            $applied_promo = null; // invalid promo
        }
    }
    
    $final_price = $total_price - $discount_amount;
    
    $shipping_fee = 0;
    if ($shipping_zone_id > 0) {
        $stmt = $db->prepare("SELECT fee FROM shipping_zones WHERE id = ? AND seller_id = ?");
        $stmt->execute([$shipping_zone_id, $seller['id']]);
        $zone = $stmt->fetch();
        if ($zone) {
            $shipping_fee = floatval($zone['fee']);
        }
    }
    
    $final_price += $shipping_fee;

    // Insert order
    $stmt = $db->prepare("INSERT INTO orders (seller_id, customer_name, customer_phone, delivery_address, total_price, promo_code, discount_amount, shipping_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$seller['id'], $customer_name, $customer_phone, $delivery_address, $final_price, $applied_promo, $discount_amount, $shipping_fee]);
    $order_id = $db->lastInsertId();

    // Insert order items and mark products as sold
    foreach ($validated_items as $item) {
        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, variant_details) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price'], $item['variant']]);

        // Decrement stock quantity atomically
        $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Sorry, the item '" . $item['name'] . "' just went out of stock!");
        }
    }

    $db->commit();

    // Send New Order Notification to Seller
    require_once __DIR__ . '/../../includes/mailer.php';
    $seller_email = $seller['email'] ?? null;
    if ($seller_email) {
        $subject = "New Order #{$order_id} received!";
        $html = "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #F58320;'>New Order Received!</h2>
            <p>Great news! You just received a new order on your store.</p>
            <ul>
                <li><strong>Order ID:</strong> #{$order_id}</li>
                <li><strong>Customer:</strong> " . e($customer_name) . "</li>
                <li><strong>Total:</strong> {$seller['currency']}" . number_format($final_price, 2) . "</li>
            </ul>
            <p>Log in to your dashboard to view the full details and process the order.</p>
            <div style='margin: 30px 0;'>
                <a href='" . BASE_URL . "/login' style='background-color: #F58320; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Go to Dashboard</a>
            </div>
        </div>";
        send_email($seller_email, $seller['shop_name'], $subject, $html);
    }

    // Redirect to success page
    redirect("/shop/" . $shop_username . "/order-success/" . $order_id);

} catch (Exception $e) {
    $db->rollBack();
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Error</title>';
    echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css">    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>';
    echo '<body class="auth-wrapper"><div class="glass-card no-hover" style="max-width:450px; text-align:center;">';
    echo '<h2 style="color:var(--danger); margin-bottom:12px;">Order Failed</h2>';
    echo '<p style="color:var(--text-secondary); margin-bottom:20px;">' . e($e->getMessage()) . '</p>';
    echo '<a href="' . BASE_URL . '/shop/' . e($shop_username) . '" class="btn-primary">Back to Store</a>';
    echo '</div></body></html>';
    exit;
}
