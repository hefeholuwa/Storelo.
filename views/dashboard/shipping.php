<?php
// views/dashboard/shipping.php — Manage delivery zones and fees
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

// Handle Create Shipping Zone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = sanitize_input($_POST['name'] ?? '');
    $fee = floatval($_POST['fee'] ?? 0);
    
    if (empty($name)) {
        $error = "Zone name is required.";
    } else {
        $stmt = $db->prepare("INSERT INTO shipping_zones (seller_id, name, fee) VALUES (?, ?, ?)");
        $stmt->execute([$seller_id, $name, $fee]);
        $success = "Shipping zone added.";
    }
}

// Handle Delete Shipping Zone
if (isset($_GET['delete'])) {
    $zone_id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM shipping_zones WHERE id = ? AND seller_id = ?");
    $stmt->execute([$zone_id, $seller_id]);
    $success = "Shipping zone deleted.";
}

// Fetch Shipping Zones
$stmt = $db->prepare("SELECT * FROM shipping_zones WHERE seller_id = ? ORDER BY name ASC");
$stmt->execute([$seller_id]);
$zones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Shipping Zones</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Set dynamic delivery fees based on customer location.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="dashboard-split-grid">
                
                <!-- Add Zone Form -->
                <div>
                    <form method="POST" class="glass-card">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label>Zone Name / Location</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Lagos Island">
                        </div>
                        
                        <div class="form-group">
                            <label>Delivery Fee (<?= $currency ?>)</label>
                            <input type="number" step="0.01" min="0" name="fee" class="form-control" required placeholder="e.g. 2500">
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;">Add Zone</button>
                    </form>
                </div>

                <!-- Zones List -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Your Delivery Zones</h3>
                    <?php if (empty($zones)): ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">You haven't set up any shipping zones yet. Customers will use the generic 'Delivery Info' text.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($zones as $zone): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); background: #fafafa;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary);"><?= e($zone['name']) ?></div>
                                        <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 4px;">
                                            Fee: <span style="font-weight: 600; color: #111827;"><?= $currency ?><?= number_format($zone['fee'], 2) ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <a href="?delete=<?= $zone['id'] ?>" class="btn-secondary btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Delete this shipping zone?')">
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
