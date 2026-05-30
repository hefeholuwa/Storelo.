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
$cart_items = json_decode($cart_data, true);

if (empty($customer_name) || empty($customer_phone) || empty($delivery_address) || empty($cart_items)) {
    die("Invalid checkout data. Please go back and try again.");
}

$db = DB::connect();

// Fetch seller
$stmt = $db->prepare("SELECT id FROM sellers WHERE username = ?");
$stmt->execute([$shop_username]);
$seller = $stmt->fetch();

if (!$seller) {
    die("Store not found.");
}

$db->beginTransaction();

try {
    $total_price = 0;

    // Validate all products are still available
    foreach ($cart_items as $item) {
        $stmt = $db->prepare("SELECT price, status FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$item['id'], $seller['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Product not found: " . ($item['name'] ?? 'Unknown'));
        }
        if ($product['status'] !== 'available') {
            throw new Exception("Sorry, '" . ($item['name'] ?? 'an item') . "' has already been sold.");
        }

        $total_price += floatval($product['price']) * intval($item['quantity'] ?? 1);
    }

    // Insert order
    $stmt = $db->prepare("INSERT INTO orders (seller_id, customer_name, customer_phone, delivery_address, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$seller['id'], $customer_name, $customer_phone, $delivery_address, $total_price]);
    $order_id = $db->lastInsertId();

    // Insert order items and mark products as sold
    foreach ($cart_items as $item) {
        $qty = intval($item['quantity'] ?? 1);
        $price = floatval($item['price']);

        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['id'], $qty, $price]);

        // Mark thrift item as sold
        $stmt = $db->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
        $stmt->execute([$item['id']]);
    }

    $db->commit();

    // Redirect to success page
    redirect("/shop/" . $shop_username . "/order-success/" . $order_id);

} catch (Exception $e) {
    $db->rollBack();
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Order Error</title>';
    echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css"></head>';
    echo '<body class="auth-wrapper"><div class="glass-card no-hover" style="max-width:450px; text-align:center;">';
    echo '<h2 style="color:var(--danger); margin-bottom:12px;">Order Failed</h2>';
    echo '<p style="color:var(--text-secondary); margin-bottom:20px;">' . e($e->getMessage()) . '</p>';
    echo '<a href="' . BASE_URL . '/shop/' . e($shop_username) . '" class="btn-primary">Back to Store</a>';
    echo '</div></body></html>';
    exit;
}
