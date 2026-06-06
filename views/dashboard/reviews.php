<?php
// views/dashboard/reviews.php — Manage customer testimonials
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$db = DB::connect();
$seller_id = $_SESSION['seller_id'];

$error = '';
$success = '';

// Handle Create Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $customer_name = sanitize_input($_POST['customer_name'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    $comment = sanitize_input($_POST['comment'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($customer_name) || empty($comment)) {
        $error = "Customer name and comment are required.";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5.";
    } else {
        $stmt = $db->prepare("INSERT INTO reviews (seller_id, customer_name, rating, comment, is_published) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$seller_id, $customer_name, $rating, $comment, $is_published]);
        $success = "Review added successfully.";
    }
}

// Handle Delete Review
if (isset($_GET['delete'])) {
    $review_id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ? AND seller_id = ?");
    $stmt->execute([$review_id, $seller_id]);
    $success = "Review deleted.";
}

// Handle Toggle Publish
if (isset($_GET['toggle'])) {
    $review_id = intval($_GET['toggle']);
    $stmt = $db->prepare("SELECT is_published FROM reviews WHERE id = ? AND seller_id = ?");
    $stmt->execute([$review_id, $seller_id]);
    $r = $stmt->fetch();
    if ($r) {
        $new_status = $r['is_published'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE reviews SET is_published = ? WHERE id = ?");
        $stmt->execute([$new_status, $review_id]);
        $success = "Review " . ($new_status ? "published" : "hidden") . " on storefront.";
    }
}

// Fetch Reviews
$stmt = $db->prepare("SELECT * FROM reviews WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .star {
            color: #fbbf24;
            font-size: 1.1rem;
        }
        .star.empty {
            color: #e4e4e7;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Customer Reviews</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Add testimonials to build trust on your store.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="dashboard-split-grid">
                
                <!-- Add Review Form -->
                <div>
                    <form method="POST" class="glass-card">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" required placeholder="e.g. John Doe">
                        </div>
                        
                        <div class="form-group">
                            <label>Rating (1-5)</label>
                            <select name="rating" class="form-control">
                                <option value="5" selected>5 Stars ⭐️⭐️⭐️⭐️⭐️</option>
                                <option value="4">4 Stars ⭐️⭐️⭐️⭐️</option>
                                <option value="3">3 Stars ⭐️⭐️⭐️</option>
                                <option value="2">2 Stars ⭐️⭐️</option>
                                <option value="1">1 Star ⭐️</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Comment</label>
                            <textarea name="comment" class="form-control" rows="4" required placeholder="What did the customer say?"></textarea>
                        </div>

                        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_published" id="is_published" checked style="width: auto; margin: 0;">
                            <label for="is_published" style="margin: 0; font-weight: normal; cursor: pointer;">Publish to storefront immediately</label>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%;">Add Review</button>
                    </form>
                </div>

                <!-- Reviews List -->
                <div class="glass-card">
                    <h3 style="margin-bottom: 16px; font-weight: 600;">Your Reviews</h3>
                    <?php if (empty($reviews)): ?>
                        <p style="color: var(--text-muted); font-size: 0.95rem;">No reviews added yet. Start by adding one from a happy customer!</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <?php foreach ($reviews as $r): ?>
                                <div style="padding: 16px; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); background: #fafafa;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <div>
                                            <div style="font-weight: 700; font-size: 1.05rem;"><?= e($r['customer_name']) ?></div>
                                            <div>
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <span class="star <?= $i <= $r['rating'] ? '' : 'empty' ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span class="badge <?= $r['is_published'] ? 'badge-success' : 'badge-pending' ?>" style="font-size: 0.7rem;">
                                                <?= $r['is_published'] ? 'PUBLISHED' : 'HIDDEN' ?>
                                            </span>
                                            <div class="product-actions" style="margin-top: 0; padding-top: 0; border-top: none;">
                                                <a href="?toggle=<?= $r['id'] ?>" class="action-toggle" style="padding: 4px 8px; font-size: 0.8rem;">
                                                    <?= $r['is_published'] ? 'Hide' : 'Publish' ?>
                                                </a>
                                                <a href="?delete=<?= $r['id'] ?>" class="action-delete" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('Delete this review?')">
                                                    Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0; white-space: pre-wrap;">"<?= e($r['comment']) ?>"</p>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Added <?= date('M d, Y', strtotime($r['created_at'])) ?></div>
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
