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
    $original_price = floatval($_POST['original_price'] ?? 0);
    $category = sanitize_input($_POST['category'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');

    if (empty($name) || $price <= 0) {
        $error = "Product name and a valid price are required.";
    } elseif (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        $error = "Please upload at least one product image.";
    } else {
        $uploaded_paths = [];
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $upload_error = false;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        foreach ($_FILES['images']['name'] as $key => $file_name) {
            $err_code = $_FILES['images']['error'][$key];
            if ($err_code !== UPLOAD_ERR_OK) {
                if ($err_code === UPLOAD_ERR_NO_FILE) continue; // Skip empty slots
                $error = "Upload failed for $file_name (Error Code: $err_code). File might be too large.";
                $upload_error = true;
                break;
            }
            
            $file_tmp = $_FILES['images']['tmp_name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $mime_type = finfo_file($finfo, $file_tmp);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($file_ext, $allowed) || !in_array($mime_type, $allowed_mimes)) {
                $error = "Invalid file type for $file_name. Use JPG, PNG, or WEBP.";
                $upload_error = true;
                break;
            } elseif ($file_size > 10485760) {
                $error = "Image $file_name must be less than 10MB.";
                $upload_error = true;
                break;
            } else {
                $new_name = uniqid('prod_', true) . '.' . $file_ext;
                $dest = __DIR__ . '/../../uploads/products/' . $new_name;
                if (move_uploaded_file($file_tmp, $dest)) {
                    $uploaded_paths[] = 'uploads/products/' . $new_name;
                } else {
                    $error = "System error: Failed to save the image to the server.";
                    $upload_error = true;
                    break;
                }
            }
        }
        // if (isset($finfo)) finfo_close($finfo); // Removed as it is deprecated in PHP 8.5

        if (!$upload_error && !empty($uploaded_paths)) {
            $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 1;
            $image_paths_json = json_encode($uploaded_paths);

            // Parse product options
            $options_json = null;
            if (!empty($_POST['option_names']) && is_array($_POST['option_names'])) {
                $options = [];
                foreach ($_POST['option_names'] as $i => $opt_name) {
                    $opt_name = trim($opt_name);
                    $opt_values_raw = trim($_POST['option_values'][$i] ?? '');
                    if ($opt_name !== '' && $opt_values_raw !== '') {
                        $vals = array_map('trim', explode(',', $opt_values_raw));
                        $vals = array_filter($vals, fn($v) => $v !== '');
                        if (!empty($vals)) {
                            $options[] = ['name' => $opt_name, 'values' => array_values($vals)];
                        }
                    }
                }
                if (!empty($options)) $options_json = json_encode($options);
            }

            $stmt = $db->prepare("INSERT INTO products (seller_id, name, description, category, price, original_price, image_paths, stock, options) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$seller_id, $name, $description, $category, $price, $original_price, $image_paths_json, $stock, $options_json]);
            $success = "Product added!";
        } elseif (!$upload_error) {
            $error = "Failed to upload images.";
        }
    }
}

// ── Handle Edit Product ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $prod_id = intval($_POST['product_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $original_price = floatval($_POST['original_price'] ?? 0);
    $category = sanitize_input($_POST['category'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 1;

    // Verify ownership
    $stmt = $db->prepare("SELECT id, image_paths FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$prod_id, $seller_id]);
    $existing_product = $stmt->fetch();

    if (!$existing_product) {
        $error = "Product not found or access denied.";
    } elseif (empty($name) || $price <= 0) {
        $error = "Product name and a valid price are required.";
    } else {
        $uploaded_paths = [];
        $upload_error = false;

        // Process new images if uploaded
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            foreach ($_FILES['images']['name'] as $key => $file_name) {
                $err_code = $_FILES['images']['error'][$key];
                if ($err_code !== UPLOAD_ERR_OK) {
                    if ($err_code === UPLOAD_ERR_NO_FILE) continue; // Skip empty slots
                    $error = "Upload failed for $file_name (Error Code: $err_code). File might be too large.";
                    $upload_error = true;
                    break;
                }
                
                $file_tmp = $_FILES['images']['tmp_name'][$key];
                $file_size = $_FILES['images']['size'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $mime_type = finfo_file($finfo, $file_tmp);
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($file_ext, $allowed) || !in_array($mime_type, $allowed_mimes)) {
                    $error = "Invalid file type for $file_name. Use JPG, PNG, or WEBP.";
                    $upload_error = true;
                    break;
                } elseif ($file_size > 10485760) {
                    $error = "Image $file_name must be less than 10MB.";
                    $upload_error = true;
                    break;
                } else {
                    $new_name = uniqid('prod_', true) . '.' . $file_ext;
                    $dest = __DIR__ . '/../../uploads/products/' . $new_name;
                    if (move_uploaded_file($file_tmp, $dest)) {
                        $uploaded_paths[] = 'uploads/products/' . $new_name;
                    }
                }
            }
        }
        // if (isset($finfo)) finfo_close($finfo); // Removed as it is deprecated in PHP 8.5

        if (!$upload_error) {
            // Parse product options for edit
            $options_json = null;
            if (!empty($_POST['option_names']) && is_array($_POST['option_names'])) {
                $options = [];
                foreach ($_POST['option_names'] as $i => $opt_name) {
                    $opt_name = trim($opt_name);
                    $opt_values_raw = trim($_POST['option_values'][$i] ?? '');
                    if ($opt_name !== '' && $opt_values_raw !== '') {
                        $vals = array_map('trim', explode(',', $opt_values_raw));
                        $vals = array_filter($vals, fn($v) => $v !== '');
                        if (!empty($vals)) {
                            $options[] = ['name' => $opt_name, 'values' => array_values($vals)];
                        }
                    }
                }
                if (!empty($options)) $options_json = json_encode($options);
            }

            if (!empty($uploaded_paths)) {
                // Delete old images
                $old_paths = json_decode($existing_product['image_paths'], true) ?: [];
                foreach ($old_paths as $path) {
                    $file_path = __DIR__ . '/../../' . $path;
                    if (file_exists($file_path)) unlink($file_path);
                }
                $image_paths_json = json_encode($uploaded_paths);
                $stmt = $db->prepare("UPDATE products SET name=?, description=?, category=?, price=?, original_price=?, stock=?, image_paths=?, options=? WHERE id=? AND seller_id=?");
                $stmt->execute([$name, $description, $category, $price, $original_price, $stock, $image_paths_json, $options_json, $prod_id, $seller_id]);
            } else {
                $stmt = $db->prepare("UPDATE products SET name=?, description=?, category=?, price=?, original_price=?, stock=?, options=? WHERE id=? AND seller_id=?");
                $stmt->execute([$name, $description, $category, $price, $original_price, $stock, $options_json, $prod_id, $seller_id]);
            }
            $success = "Product updated successfully!";
        }
    }
}

// ── Handle Delete ───────────────────────────────────────────
if (isset($_GET['delete'])) {
    $prod_id = intval($_GET['delete']);
    // Verify ownership
    $stmt = $db->prepare("SELECT image_paths FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$prod_id, $seller_id]);
    $p = $stmt->fetch();
    if ($p) {
        // Delete files from server
        $paths = json_decode($p['image_paths'], true) ?: [];
        foreach ($paths as $path) {
            $file_path = __DIR__ . '/../../' . $path;
            if (file_exists($file_path)) unlink($file_path);
        }
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

// ── Handle Bulk Actions ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk') {
    $bulk_action = $_POST['bulk_action'] ?? '';
    $product_ids = $_POST['product_ids'] ?? [];
    
    if (empty($product_ids)) {
        $error = "Please select at least one product.";
    } elseif ($bulk_action === 'delete') {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $params = array_merge($product_ids, [$seller_id]);
        
        // Fetch images to delete from server
        $stmt = $db->prepare("SELECT image_paths FROM products WHERE id IN ($placeholders) AND seller_id = ?");
        $stmt->execute($params);
        $prods = $stmt->fetchAll();
        foreach ($prods as $p) {
            $paths = json_decode($p['image_paths'], true) ?: [];
            foreach ($paths as $path) {
                $file_path = __DIR__ . '/../../' . $path;
                if (file_exists($file_path)) unlink($file_path);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders) AND seller_id = ?");
        $stmt->execute($params);
        $success = "Selected products deleted.";
    } elseif ($bulk_action === 'out_of_stock') {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $params = array_merge([0], $product_ids, [$seller_id]);
        $stmt = $db->prepare("UPDATE products SET stock = ? WHERE id IN ($placeholders) AND seller_id = ?");
        $stmt->execute($params);
        $success = "Selected products marked as out of stock.";
    } elseif ($bulk_action === 'active' || $bulk_action === 'hidden') {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $params = array_merge([$bulk_action], $product_ids, [$seller_id]);
        $stmt = $db->prepare("UPDATE products SET status = ? WHERE id IN ($placeholders) AND seller_id = ?");
        $stmt->execute($params);
        $success = "Selected products marked as $bulk_action.";
    }
}

// ── Fetch All Products ──────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll();

// ── Fetch Categories ────────────────────────────────────────
$stmt = $db->prepare("SELECT name FROM categories WHERE seller_id = ? ORDER BY name ASC");
$stmt->execute([$seller_id]);
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Products</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Manage your catalog — add items, mark them as sold, or remove them.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Add Product Form -->
            <div class="glass-card" style="margin-bottom:40px;">
                <h3 style="margin-bottom:20px; font-weight: 700;">Add New Product</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">

                    <div class="dashboard-main-grid">
                        <div>
                            <div class="form-group">
                                <label>Product Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Vintage Bomber Jacket" required>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" placeholder="Describe the item, size, condition..."></textarea>
                            </div>
                            
                            <div style="display:flex; gap: 16px;">
                                <div class="form-group" style="flex:1;">
                                    <label>Price (<?= $currency ?>)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                                </div>
                                <div class="form-group" style="flex:1;">
                                    <label>Original Price (Optional) <span style="font-size:0.8rem; color:var(--text-muted);">(Show crossed-out price)</span></label>
                                    <input type="number" step="0.01" name="original_price" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label>Category</label>
                                <?php if (empty($categories)): ?>
                                    <input type="text" name="category" class="form-control" placeholder="e.g. Jackets (or add in Categories)">
                                <?php else: ?>
                                    <select name="category" class="form-control">
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Stock Quantity</label>
                                <input type="number" min="0" name="stock" class="form-control" value="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 12px; padding: 20px; border: 2px dashed var(--border-subtle); border-radius: var(--radius-md); text-align: center; background: #fafafa;">
                        <label style="font-size: 1.1rem; color: var(--text-primary);">Product Images</label>
                        <p style="color:var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">JPG/PNG/WEBP, max 10MB each. The first image will be the cover.</p>
                        <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
                    </div>

                    <!-- Product Options / Variants -->
                    <div class="glass-card" style="margin-top: 16px; padding: 20px; border: 1px solid var(--border-subtle); background: #fafafa;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div>
                                <label style="font-weight: 700; font-size: 1rem; color: var(--text-primary);">Product Options</label>
                                <p style="color:var(--text-muted); font-size: 0.8rem; margin: 4px 0 0;">Add variants like Color, Size, Material. Separate values with commas.</p>
                            </div>
                            <button type="button" class="btn-secondary btn-sm" onclick="addOptionRow('add-options-container')" style="white-space: nowrap;">+ Add Option</button>
                        </div>
                        <div id="add-options-container"></div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 16px;">+ Upload Product</button>
                </form>
            </div>

            <!-- Product Grid -->
            <form method="POST" id="bulk-action-form">
                <input type="hidden" name="action" value="bulk">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-weight: 700; margin: 0;">Your Listed Products (<?= count($products) ?>)</h2>
                    
                    <?php if (!empty($products)): ?>
                        <div style="display:flex; gap:8px;">
                            <select name="bulk_action" class="form-control" style="width: auto; padding: 6px 12px; font-size: 0.9rem;" required>
                                <option value="">Bulk Actions...</option>
                                <option value="active">Mark Active</option>
                                <option value="hidden">Mark Hidden</option>
                                <option value="out_of_stock">Mark Out of Stock</option>
                                <option value="delete">Delete Selected</option>
                            </select>
                            <button type="submit" class="btn-secondary btn-sm" onclick="return confirm('Are you sure you want to apply this action to the selected products?')">Apply</button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($products)): ?>
                    <div class="glass-card" style="text-align:center; padding:40px;">
                        <p style="color:var(--text-muted); font-size:1.1rem;">No products yet. Add your first item above!</p>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $p): ?>
                            <div class="product-card-admin" style="position: relative;">
                                <div style="position: absolute; top: 12px; left: 12px; z-index: 10;">
                                    <input type="checkbox" name="product_ids[]" value="<?= $p['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                </div>
                                <span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-danger' ?> status-badge">
                                    <?= strtoupper($p['status']) ?>
                                </span>
                                <?php 
                                $paths = json_decode($p['image_paths'], true);
                                $cover_image = !empty($paths) ? $paths[0] : 'assets/images/placeholder.png';
                                ?>
                                <img src="<?= BASE_URL ?>/<?= $cover_image ?>" alt="<?= e($p['name']) ?>">
                                <h4><?= e($p['name']) ?></h4>
                                <?php if ($p['category']): ?>
                                    <small style="color:var(--text-muted);"><?= e($p['category']) ?></small>
                                <?php endif; ?>
                                <div style="font-size: 0.85rem; margin-top: 4px; font-weight: 500;">
                                    Stock: <?= $p['stock'] > 0 ? $p['stock'] : '<span style="color:var(--danger)">OUT OF STOCK</span>' ?>
                                </div>
                                <div class="product-price">
                                    <?= $currency ?><?= number_format($p['price'], 2) ?>
                                    <?php if($p['original_price'] > 0): ?>
                                        <span style="font-size:0.8rem; text-decoration:line-through; color:var(--text-muted); margin-left:6px;">
                                            <?= $currency ?><?= number_format($p['original_price'], 2) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions">
                                    <a href="#" class="action-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($p)) ?>); return false;" style="color:#2563EB;">Edit</a>
                                    <a href="?toggle=<?= $p['id'] ?>" class="action-toggle">
                                        <?= $p['status'] === 'active' ? 'Hide' : 'Activate' ?>
                                    </a>
                                    <a href="?delete=<?= $p['id'] ?>" class="action-delete" onclick="return confirm('Delete this product?')">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
        <div class="glass-card" style="width:100%; max-width:600px; max-height:90vh; overflow-y:auto; background:#fff; padding:32px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; font-weight:700;">Edit Product</h3>
                <button onclick="document.getElementById('editModal').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="display:flex; gap: 16px;">
                    <div class="form-group" style="flex:1;">
                        <label>Price (<?= $currency ?>)</label>
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Original Price (<?= $currency ?>)</label>
                        <input type="number" step="0.01" name="original_price" id="edit_original_price" class="form-control">
                    </div>
                </div>
                
                <div style="display:flex; gap: 16px;">
                    <div class="form-group" style="flex:1;">
                        <label>Category</label>
                        <?php if (empty($categories)): ?>
                            <input type="text" name="category" id="edit_category" class="form-control" placeholder="e.g. Jackets">
                        <?php else: ?>
                            <select name="category" id="edit_category" class="form-control">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Stock Quantity</label>
                        <input type="number" min="0" name="stock" id="edit_stock" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 12px; padding: 16px; border: 1px dashed var(--border-subtle); background: #fafafa; border-radius: var(--radius-md);">
                    <label>Update Images (Optional)</label>
                    <p style="color:var(--text-muted); font-size: 0.85rem; margin-bottom: 8px;">Upload new images to completely replace existing ones, or leave blank to keep current images.</p>
                    <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                </div>

                <!-- Edit Product Options / Variants -->
                <div style="margin-top: 16px; padding: 16px; border: 1px solid var(--border-subtle); background: #fafafa; border-radius: var(--radius-md);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <label style="font-weight: 700;">Product Options</label>
                            <p style="color:var(--text-muted); font-size: 0.8rem; margin: 4px 0 0;">Variants like Color, Size. Separate values with commas.</p>
                        </div>
                        <button type="button" class="btn-secondary btn-sm" onclick="addOptionRow('edit-options-container')" style="white-space: nowrap;">+ Add Option</button>
                    </div>
                    <div id="edit-options-container"></div>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addOptionRow(containerId, name = '', values = '') {
            const container = document.getElementById(containerId);
            const row = document.createElement('div');
            row.style.cssText = 'display: flex; gap: 12px; align-items: center; margin-bottom: 10px;';
            row.innerHTML = `
                <input type="text" name="option_names[]" class="form-control" placeholder="e.g. Color" value="${name}" style="flex: 1; min-width: 0;">
                <input type="text" name="option_values[]" class="form-control" placeholder="e.g. Red, Blue, Green" value="${values}" style="flex: 2; min-width: 0;">
                <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger); font-size: 1.3rem; cursor: pointer; padding: 4px 8px; flex-shrink: 0;">&times;</button>
            `;
            container.appendChild(row);
        }

        function openEditModal(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_original_price').value = product.original_price > 0 ? product.original_price : '';
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_stock').value = product.stock;
            
            // Populate existing options
            const editOptContainer = document.getElementById('edit-options-container');
            editOptContainer.innerHTML = '';
            if (product.options) {
                let opts = product.options;
                if (typeof opts === 'string') opts = JSON.parse(opts);
                if (Array.isArray(opts)) {
                    opts.forEach(opt => {
                        addOptionRow('edit-options-container', opt.name, opt.values.join(', '));
                    });
                }
            }

            document.getElementById('editModal').style.display = 'flex';
        }
    </script>
</body>
</html>
