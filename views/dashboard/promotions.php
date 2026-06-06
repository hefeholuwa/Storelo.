<?php
// views/dashboard/promotions.php — Manage discount codes
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

$error = '';
$success = '';

// Handle Create Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $code = strtoupper(sanitize_input($_POST['code'] ?? ''));
    $discount_type = $_POST['discount_type'] ?? 'percentage';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    
    if (empty($code) || $discount_value <= 0) {
        $error = "Valid code and discount value are required.";
    } else {
        $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ? AND seller_id = ?");
        $stmt->execute([$code, $seller_id]);
        if ($stmt->fetch()) {
            $error = "Coupon code already exists.";
        } else {
            $stmt = $db->prepare("INSERT INTO coupons (seller_id, code, discount_type, discount_value) VALUES (?, ?, ?, ?)");
            $stmt->execute([$seller_id, $code, $discount_type, $discount_value]);
            $success = "Coupon created.";
        }
    }
}

// Handle Delete Coupon
if (isset($_GET['delete'])) {
    $coupon_id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND seller_id = ?");
    $stmt->execute([$coupon_id, $seller_id]);
    $success = "Coupon deleted.";
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $coupon_id = intval($_GET['toggle']);
    $stmt = $db->prepare("SELECT status FROM coupons WHERE id = ? AND seller_id = ?");
    $stmt->execute([$coupon_id, $seller_id]);
    $c = $stmt->fetch();
    if ($c) {
        $new_status = ($c['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $db->prepare("UPDATE coupons SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $coupon_id]);
        $success = "Coupon marked as " . $new_status . ".";
    }
}

// Fetch Coupons
$stmt = $db->prepare("SELECT * FROM coupons WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$coupons = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Promotions</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Create discount codes to share with your customers.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="dashboard-split-grid">
                
                <!-- Add Coupon Form -->
                <div>
                    <form method="POST" class="glass-card">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label>Promo Code</label>
                            <input type="text" name="code" class="form-control" required placeholder="e.g. SUMMER20" style="text-transform: uppercase;">
                        </div>
                        
                        <div class="form-group">
                            <label>Discount Type</label>
                            <select name="discount_type" class="form-control" id="discount-type-select" onchange="updatePlaceholder()">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (<?= $currency ?>)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Discount Value</label>
                            <input type="number" step="0.01" min="0.01" name="discount_value" id="discount-value-input" class="form-control" required placeholder="e.g. 20">
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;">Create Code</button>
                    </form>
                </div>

                <!-- Coupons List -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Active & Inactive Codes</h3>
                    <?php if (empty($coupons)): ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">You haven't created any promo codes yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($coupons as $c): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); background: #fafafa;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 1.2rem; font-family: monospace; letter-spacing: 1px; color: var(--accent);"><?= e($c['code']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                                            <?= $c['discount_type'] === 'percentage' ? floatval($c['discount_value']) . '% OFF' : $currency . number_format($c['discount_value'], 2) . ' OFF' ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="badge <?= $c['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.7rem;">
                                            <?= strtoupper($c['status']) ?>
                                        </span>
                                        <div class="product-actions" style="margin-top: 0; padding-top: 0; border-top: none;">
                                            <a href="?toggle=<?= $c['id'] ?>" class="action-toggle" style="padding: 4px 8px; font-size: 0.8rem;">
                                                <?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                            </a>
                                            <a href="?delete=<?= $c['id'] ?>" class="action-delete" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Delete this code?')">
                                                Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    
    <script>
        function updatePlaceholder() {
            const select = document.getElementById('discount-type-select');
            const input = document.getElementById('discount-value-input');
            if (select.value === 'percentage') {
                input.placeholder = 'e.g. 20 (for 20%)';
            } else {
                input.placeholder = 'e.g. 1500 (for fixed amount)';
            }
        }
    </script>
</body>
</html>
