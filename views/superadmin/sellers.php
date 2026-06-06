<?php
// views/superadmin/sellers.php — Manage Stores (with Ban/Feature)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_superadmin();

$db = DB::connect();
$success = '';
$error = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $seller_id = intval($_GET['id']);
    
    // Check if seller exists
    $stmt = $db->prepare("SELECT id, is_verified, is_suspended, is_banned, is_featured FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch();
    
    if ($seller) {
        if ($action === 'verify') {
            $new_status = $seller['is_verified'] ? 0 : 1;
            $db->prepare("UPDATE sellers SET is_verified = ? WHERE id = ?")->execute([$new_status, $seller_id]);
            $msg = 'updated';
        } elseif ($action === 'suspend') {
            $new_status = $seller['is_suspended'] ? 0 : 1;
            $db->prepare("UPDATE sellers SET is_suspended = ? WHERE id = ?")->execute([$new_status, $seller_id]);
            $msg = 'updated';
        } elseif ($action === 'ban') {
            $new_status = $seller['is_banned'] ? 0 : 1;
            $db->prepare("UPDATE sellers SET is_banned = ? WHERE id = ?")->execute([$new_status, $seller_id]);
            $msg = $new_status ? 'banned' : 'unbanned';
        } elseif ($action === 'feature') {
            $new_status = $seller['is_featured'] ? 0 : 1;
            $db->prepare("UPDATE sellers SET is_featured = ? WHERE id = ?")->execute([$new_status, $seller_id]);
            $msg = $new_status ? 'featured' : 'unfeatured';
        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM sellers WHERE id = ?")->execute([$seller_id]);
            $msg = 'deleted';
        }
        redirect('/superadmin/sellers?msg=' . $msg);
    } else {
        $error = "Seller not found.";
    }
}

if (isset($_GET['msg'])) {
    $msgs = [
        'updated' => 'Store status updated successfully.',
        'deleted' => 'Store and all associated data deleted permanently.',
        'banned' => 'Store has been banned. Their storefront is now inaccessible.',
        'unbanned' => 'Store has been unbanned. Their storefront is now accessible.',
        'featured' => 'Store has been featured. ⭐',
        'unfeatured' => 'Store has been unfeatured.'
    ];
    $success = $msgs[$_GET['msg']] ?? 'Action completed.';
}

// Search / Filter
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "1=1";
$params = [];
if ($filter === 'verified') { $where .= " AND s.is_verified = 1"; }
elseif ($filter === 'suspended') { $where .= " AND s.is_suspended = 1"; }
elseif ($filter === 'banned') { $where .= " AND s.is_banned = 1"; }
elseif ($filter === 'featured') { $where .= " AND s.is_featured = 1"; }

if ($search !== '') {
    $where .= " AND (s.shop_name LIKE ? OR s.username LIKE ? OR s.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM products WHERE seller_id = s.id) as products_count,
           (SELECT COUNT(*) FROM orders WHERE seller_id = s.id) as orders_count,
           (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE seller_id = s.id AND status = 'delivered') as total_revenue
    FROM sellers s
    WHERE $where
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sellers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stores — Storelo Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .table-container { width: 100%; overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 14px 12px; text-align: left; border-bottom: 1px solid var(--border-subtle); }
        .admin-table th { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; }
        .badge-verified { background: #dbeafe; color: #1e3a8a; }
        .badge-unverified { background: #f3f4f6; color: #4b5563; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-suspended { background: #fef3c7; color: #92400e; }
        .badge-banned { background: #fee2e2; color: #991b1b; }
        .badge-featured { background: #fef3c7; color: #92400e; }
        .action-btn {
            font-size: 0.78rem; font-weight: 600; padding: 5px 10px;
            border-radius: 6px; text-decoration: none; display: inline-block;
            margin: 2px; border: 1px solid transparent; transition: all 0.15s;
        }
        .action-btn:hover { opacity: 0.85; transform: translateY(-1px); }
        .btn-verify { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .btn-suspend { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .btn-ban { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .btn-unban { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
        .btn-feature { background: #fefce8; color: #a16207; border-color: #fef08a; }
        .btn-delete { background: #fff; color: #dc2626; border-color: #fca5a5; }
        .filter-bar {
            display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; align-items: center;
        }
        .filter-btn {
            padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
            text-decoration: none; border: 1px solid var(--border-subtle); color: var(--text-secondary);
            background: #fff; transition: all 0.15s;
        }
        .filter-btn:hover { border-color: var(--accent); color: var(--accent); }
        .filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
        .search-box {
            padding: 8px 14px; border-radius: 20px; border: 1px solid var(--border-subtle);
            font-size: 0.85rem; outline: none; min-width: 200px;
        }
        .search-box:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.15); }
        .store-revenue { font-size: 0.85rem; font-weight: 700; color: #059669; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/superadmin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1>Manage Stores</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">View, verify, suspend, ban, and feature sellers. <strong><?= count($sellers) ?></strong> stores found.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Search & Filters -->
            <div class="filter-bar">
                <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" class="search-box" placeholder="Search stores..." value="<?= e($search) ?>">
                    <button type="submit" class="filter-btn active" style="cursor: pointer;">🔍 Search</button>
                </form>
                <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                <a href="?filter=verified" class="filter-btn <?= $filter === 'verified' ? 'active' : '' ?>">✓ Verified</a>
                <a href="?filter=featured" class="filter-btn <?= $filter === 'featured' ? 'active' : '' ?>">⭐ Featured</a>
                <a href="?filter=suspended" class="filter-btn <?= $filter === 'suspended' ? 'active' : '' ?>">⏸ Suspended</a>
                <a href="?filter=banned" class="filter-btn <?= $filter === 'banned' ? 'active' : '' ?>">🚫 Banned</a>
            </div>

            <div class="glass-card table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Store</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers as $s): ?>
                            <tr style="<?= $s['is_banned'] ? 'opacity: 0.6; background: #fef2f2;' : '' ?>">
                                <td>
                                    <div style="font-weight: 700; font-size: 1.02rem;">
                                        <?= e($s['shop_name']) ?>
                                        <?php if ($s['is_featured']): ?> <span style="color: #f59e0b;">⭐</span><?php endif; ?>
                                        <a href="<?= BASE_URL ?>/shop/<?= urlencode($s['username']) ?>" target="_blank" style="margin-left: 6px; font-size: 0.8rem; color: var(--accent); font-weight: normal;">View ↗</a>
                                    </div>
                                    <div style="color: var(--text-secondary); font-size: 0.85rem;">@<?= e($s['username']) ?></div>
                                    <div style="color: var(--text-muted); font-size: 0.8rem; margin-top: 2px;"><?= e($s['email'] ?? '') ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; line-height: 1.6;">
                                        <span style="color: var(--text-secondary);">Products:</span> <?= $s['products_count'] ?><br>
                                        <span style="color: var(--text-secondary);">Orders:</span> <?= $s['orders_count'] ?><br>
                                        <span style="color: var(--text-secondary);">Visits:</span> <?= $s['store_visits'] ?><br>
                                        <span class="store-revenue">₦<?= number_format($s['total_revenue'], 2) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <span class="badge <?= $s['is_verified'] ? 'badge-verified' : 'badge-unverified' ?>">
                                            <?= $s['is_verified'] ? '✓ Verified' : 'Unverified' ?>
                                        </span>
                                        <?php if ($s['is_banned']): ?>
                                            <span class="badge badge-banned">🚫 Banned</span>
                                        <?php elseif ($s['is_suspended']): ?>
                                            <span class="badge badge-suspended">⏸ Suspended</span>
                                        <?php else: ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php endif; ?>
                                        <?php if ($s['is_featured']): ?>
                                            <span class="badge badge-featured">⭐ Featured</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <a href="?action=verify&id=<?= $s['id'] ?>" class="action-btn btn-verify">
                                            <?= $s['is_verified'] ? 'Unverify' : '✓ Verify' ?>
                                        </a>
                                        <a href="?action=feature&id=<?= $s['id'] ?>" class="action-btn btn-feature">
                                            <?= $s['is_featured'] ? 'Unfeature' : '⭐ Feature' ?>
                                        </a>
                                        <?php if (!$s['is_banned']): ?>
                                            <a href="?action=suspend&id=<?= $s['id'] ?>" class="action-btn btn-suspend" onclick="return confirm('Change suspension status?');">
                                                <?= $s['is_suspended'] ? 'Unsuspend' : '⏸ Suspend' ?>
                                            </a>
                                            <a href="?action=ban&id=<?= $s['id'] ?>" class="action-btn btn-ban" onclick="return confirm('WARNING: Banning this store will make their storefront completely inaccessible and block them from logging in. Proceed?');">
                                                🚫 Ban
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=ban&id=<?= $s['id'] ?>" class="action-btn btn-unban" onclick="return confirm('Unban this store? Their storefront will become accessible again.');">
                                                ✅ Unban
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $s['id'] ?>" class="action-btn btn-delete" onclick="return confirm('WARNING: This will permanently delete this store and ALL of its products, orders, and data. This cannot be undone. Are you absolutely sure?');">
                                            🗑 Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($sellers)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">No stores found matching your criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>
