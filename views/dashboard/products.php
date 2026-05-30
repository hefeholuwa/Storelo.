<?php
// views/dashboard/products.php — Product inventory CRUD
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

// ── Handle Add Product ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = sanitize_input($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = sanitize_input($_POST['category'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');

    if (empty($name) || $price <= 0) {
        $error = "Product name and a valid price are required.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a product image.";
    } else {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($file_ext, $allowed)) {
            $error = "Invalid file type. Use JPG, PNG, or WEBP.";
        } elseif ($file_size > 2097152) {
            $error = "Image must be less than 2MB.";
        } else {
            $new_name = uniqid('prod_', true) . '.' . $file_ext;
            $dest = __DIR__ . '/../../uploads/products/' . $new_name;
            if (move_uploaded_file($file_tmp, $dest)) {
                $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 1;
                $image_path = 'uploads/products/' . $new_name;
                $stmt = $db->prepare("INSERT INTO products (seller_id, name, description, category, price, image_path, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$seller_id, $name, $description, $category, $price, $image_path, $stock]);
                $success = "Product added!";
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
}

// ── Handle Delete ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $prod_id = intval($_GET['delete']);
    // Verify ownership
    $stmt = $db->prepare("SELECT image_path FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$prod_id, $seller_id]);
    $p = $stmt->fetch();
    if ($p) {
        // Delete file from server
        $file_path = __DIR__ . '/../../' . $p['image_path'];
        if (file_exists($file_path)) unlink($file_path);
        $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$prod_id, $seller_id]);
        $success = "Product deleted.";
    }
}

// ── Handle Status Toggle ────────────────────────────────────
if (isset($_GET['toggle'])) {
    $prod_id = intval($_GET['toggle']);
    $stmt = $db->prepare("SELECT status FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$prod_id, $seller_id]);
    $p = $stmt->fetch();
    if ($p) {
        $new_status = ($p['status'] === 'active') ? 'hidden' : 'active';
        $stmt = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $prod_id]);
        $success = "Status updated to " . $new_status . ".";
    }
}

// ── Fetch All Products ──────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <h1>Products</h1>
            <p class="page-subtitle">Manage your thrift catalog — add items, mark them as sold, or remove them.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Add Product Form -->
            <div class="glass-card" style="margin-bottom:40px; max-width:600px;">
                <h3 style="margin-bottom:20px;">Add New Product</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Vintage Bomber Jacket" required>
                    </div>

                    <div style="display:flex; gap:12px;">
                        <div class="form-group" style="flex:1;">
                            <label>Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Jackets">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Stock Quantity</label>
                            <input type="number" min="0" name="stock" class="form-control" value="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" placeholder="Describe the item, size, condition..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Product Image (JPG/PNG/WEBP, max 2MB)</label>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required style="margin-top:6px;">
                    </div>

                    <button type="submit" class="btn-primary">Upload Product</button>
                </form>
            </div>

            <!-- Product Grid -->
            <h2 style="margin-bottom:20px;">Your Listed Products (<?= count($products) ?>)</h2>

            <?php if (empty($products)): ?>
                <div class="glass-card" style="text-align:center; padding:40px;">
                    <p style="color:var(--text-muted); font-size:1.1rem;">No products yet. Add your first item above!</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="product-card-admin">
                            <span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-danger' ?> status-badge">
                                <?= strtoupper($p['status']) ?>
                            </span>
                            <img src="<?= BASE_URL ?>/<?= $p['image_path'] ?>" alt="<?= e($p['name']) ?>">
                            <h4><?= e($p['name']) ?></h4>
                            <?php if ($p['category']): ?>
                                <small style="color:var(--text-muted);"><?= e($p['category']) ?></small>
                            <?php endif; ?>
                            <div style="font-size: 0.85rem; margin-top: 4px; font-weight: 500;">
                                Stock: <?= $p['stock'] > 0 ? $p['stock'] : '<span style="color:var(--danger)">OUT OF STOCK</span>' ?>
                            </div>
                            <div class="product-price"><?= $currency ?><?= number_format($p['price'], 2) ?></div>
                            <div class="product-actions">
                                <a href="?toggle=<?= $p['id'] ?>" class="action-toggle">
                                    Mark <?= $p['status'] === 'active' ? 'Hidden' : 'Active' ?>
                                </a>
                                <a href="?delete=<?= $p['id'] ?>" class="action-delete" onclick="return confirm('Delete this product?')">
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
