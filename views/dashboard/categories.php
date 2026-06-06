<?php
// views/dashboard/categories.php — Manage store categories
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$db = DB::connect();
$seller_id = $_SESSION['seller_id'];
$error = '';
$success = '';

// Handle Create Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = sanitize_input($_POST['name'] ?? '');
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = $db->prepare("INSERT INTO categories (seller_id, name) VALUES (?, ?)");
        $stmt->execute([$seller_id, $name]);
        $success = "Category created.";
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $cat_id = intval($_GET['delete']);
    // Wait, if we delete a category, what happens to products? 
    // They will keep the string name if category in products is just a string, but ideally we should update products to remove it, or just let the string persist.
    // We will just delete it from the list.
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND seller_id = ?");
    $stmt->execute([$cat_id, $seller_id]);
    $success = "Category deleted.";
}

// Fetch Categories
$stmt = $db->prepare("SELECT * FROM categories WHERE seller_id = ? ORDER BY name ASC");
$stmt->execute([$seller_id]);
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Categories</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Organize your products into categories.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="dashboard-split-grid">
                
                <!-- Add Category Form -->
                <div>
                    <form method="POST" class="glass-card">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Sneakers">
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%;">Add Category</button>
                    </form>
                </div>

                <!-- Categories List -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Your Categories</h3>
                    <?php if (empty($categories)): ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">You haven't created any categories yet.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($categories as $cat): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm);">
                                    <span style="font-weight: 600; font-size: 1rem;"><?= e($cat['name']) ?></span>
                                    <a href="?delete=<?= $cat['id'] ?>" class="btn-secondary btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Delete this category?')">Delete</a>
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
