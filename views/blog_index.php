<?php
// views/blog_index.php — Public Blog List
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = DB::connect();

// Fetch published posts
$stmt = $db->query("SELECT title, slug, excerpt, featured_image, author_name, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storelo Blog - E-commerce Tips & News</title>
    <meta name="description" content="Learn how to start, grow, and manage your online business in Africa with Storelo's expert guides and e-commerce tips.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-C2HE39BXGE"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-C2HE39BXGE');
    </script>
    <style>
        .blog-header { background: #f8fafc; padding: 60px 20px; text-align: center; border-bottom: 1px solid var(--border-light); }
        .blog-header h1 { font-size: 2.5rem; color: #0f172a; margin-bottom: 16px; }
        .blog-header p { font-size: 1.1rem; color: #475569; max-width: 600px; margin: 0 auto; }
        
        .blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 32px; padding: 60px 20px; max-width: 1200px; margin: 0 auto; }
        .blog-card { background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; text-decoration: none; color: inherit; }
        .blog-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.06); }
        .blog-card-img { width: 100%; height: 200px; object-fit: cover; background: #e2e8f0; }
        .blog-card-content { padding: 24px; display: flex; flex-direction: column; flex: 1; }
        .blog-meta { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .blog-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 12px 0; line-height: 1.4; }
        .blog-excerpt { font-size: 0.95rem; color: #475569; line-height: 1.6; margin: 0; flex: 1; }
        .blog-read-more { margin-top: 20px; font-weight: 600; color: var(--accent); display: flex; align-items: center; gap: 4px; }
    </style>
</head>
<body>
    
    <div style="background: #fff; border-bottom: 1px solid var(--border-light); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="<?= BASE_URL ?>/" style="text-decoration: none;">
            <span class="store-brand-text" style="font-weight: 900; font-size: 1.5rem; letter-spacing: 0; color: #111827;"><span style="color: var(--accent);">Store</span>lo.</span>
        </a>
        <div style="display: flex; gap: 16px; align-items: center;">
            <a href="<?= BASE_URL ?>/login" style="text-decoration: none; color: #475569; font-weight: 600;">Log In</a>
            <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-sm" style="padding: 8px 16px;">Start Selling</a>
        </div>
    </div>

    <header class="blog-header">
        <h1>Storelo Blog</h1>
        <p>Insights, guides, and tips to help you build and grow a successful online business in Africa.</p>
    </header>

    <div class="blog-grid">
        <?php foreach ($posts as $post): ?>
            <a href="<?= BASE_URL ?>/blog/<?= e($post['slug']) ?>" class="blog-card">
                <?php if ($post['featured_image']): ?>
                    <img src="<?= BASE_URL . e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="blog-card-img">
                <?php else: ?>
                    <div class="blog-card-img" style="display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                <?php endif; ?>
                
                <div class="blog-card-content">
                    <div class="blog-meta">
                        <span><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                        <span>•</span>
                        <span><?= e($post['author_name']) ?></span>
                    </div>
                    <h2 class="blog-title"><?= e($post['title']) ?></h2>
                    <p class="blog-excerpt"><?= e($post['excerpt']) ?></p>
                    <div class="blog-read-more">Read article &rarr;</div>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (empty($posts)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--text-muted);">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 16px; opacity: 0.5;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                <h2>Check back soon!</h2>
                <p>We are currently writing some amazing content for you.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer style="background: #0f172a; padding: 40px 20px; text-align: center; color: #94a3b8;">
        <h3 style="color: white; margin-bottom: 16px;">Ready to start your online store?</h3>
        <p style="margin-bottom: 24px; max-width: 500px; margin-left: auto; margin-right: auto;">Join thousands of sellers who trust Storelo to power their e-commerce business.</p>
        <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-xl" style="display: inline-block;">Create Free Store</a>
        <div style="margin-top: 40px; font-size: 0.85rem; border-top: 1px solid #1e293b; padding-top: 24px;">
            &copy; <?= date('Y') ?> Storelo. All rights reserved.
        </div>
    </footer>

</body>
</html>
