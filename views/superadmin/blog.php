<?php
// views/superadmin/blog.php — Manage Blog Posts
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_superadmin();

$db = DB::connect();
$success = '';
$error = '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('/superadmin/blog?msg=deleted');
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $success = "Blog post deleted successfully.";
    if ($_GET['msg'] === 'saved') $success = "Blog post saved successfully.";
}

// Fetch posts
$stmt = $db->query("SELECT id, title, slug, author_name, status, created_at, updated_at FROM blog_posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blog — Storelo Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .table-container { width: 100%; overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-subtle); }
        .admin-table th { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; display: inline-block; }
        .badge-published { background: #dcfce7; color: #166534; }
        .badge-draft { background: #f3f4f6; color: #4b5563; }
        .action-link { font-size: 0.85rem; font-weight: 600; margin-right: 12px; text-decoration: none; color: var(--accent); transition: 0.2s; }
        .action-link:hover { opacity: 0.8; }
        .action-link.danger { color: #dc2626; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/superadmin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1>Manage Blog</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Write and publish articles to improve your SEO.</p>
                </div>
                <a href="<?= BASE_URL ?>/superadmin/blog/create" class="btn-primary">+ New Post</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="glass-card table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 4px;">
                                        <?= e($p['title']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        /blog/<?= e($p['slug']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary);"><?= e($p['author_name']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $p['status'] === 'published' ? 'badge-published' : 'badge-draft' ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        <?= date('M d, Y', strtotime($p['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <a href="<?= BASE_URL ?>/superadmin/blog/edit?id=<?= $p['id'] ?>" class="action-link">Edit</a>
                                        <?php if ($p['status'] === 'published'): ?>
                                            <a href="<?= BASE_URL ?>/blog/<?= $p['slug'] ?>" target="_blank" class="action-link" style="color: #4f46e5;">View</a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?= $p['id'] ?>" class="action-link danger" onclick="return confirm('Are you sure you want to delete this post?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <div style="font-size: 2rem; margin-bottom: 12px;">✍️</div>
                                    <p>No blog posts yet. Write your first article to attract more traffic!</p>
                                    <a href="<?= BASE_URL ?>/superadmin/blog/create" class="btn-primary" style="display: inline-block; margin-top: 12px;">Create Post</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>
