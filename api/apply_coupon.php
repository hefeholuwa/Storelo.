<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');
$seller_id = intval($input['seller_id'] ?? 0);

if (empty($code) || $seller_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$db = DB::connect();
$stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND seller_id = ? AND status = 'active'");
$stmt->execute([$code, $seller_id]);
$coupon = $stmt->fetch();

if ($coupon) {
    echo json_encode([
        'success' => true,
        'discount_type' => $coupon['discount_type'],
        'discount_value' => floatval($coupon['discount_value'])
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired promo code']);
}
