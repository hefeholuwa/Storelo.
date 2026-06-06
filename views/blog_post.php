<?php
// views/blog_post.php — Public Blog Article
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = DB::connect();

$stmt = $db->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo "<h1>Article not found</h1>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SEO Tags -->
    <title><?= e($post['title']) ?> - Storelo Blog</title>
    <meta name="description" content="<?= e($post['excerpt']) ?>">
    <meta property="og:title" content="<?= e($post['title']) ?>">
    <meta property="og:description" content="<?= e($post['excerpt']) ?>">
    <?php if ($post['featured_image']): ?>
    <meta property="og:image" content="<?= BASE_URL . $post['featured_image'] ?>">
    <?php endif; ?>
    
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
        .blog-header-img { width: 100%; max-height: 500px; object-fit: cover; background: #e2e8f0; display: block; }
        .article-container { max-width: 800px; margin: 0 auto; padding: 40px 20px 80px 20px; }
        .article-meta { font-size: 0.95rem; color: var(--text-muted); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .article-title { font-size: 2.8rem; font-weight: 800; color: #0f172a; margin-bottom: 24px; line-height: 1.2; letter-spacing: -1px; }
        
        /* Typography for Blog Content */
        .article-content { font-size: 1.15rem; line-height: 1.8; color: #334155; }
        .article-content p { margin-bottom: 24px; }
        .article-content h2 { font-size: 1.8rem; margin: 48px 0 24px 0; color: #0f172a; font-weight: 700; }
        .article-content h3 { font-size: 1.4rem; margin: 32px 0 16px 0; color: #1e293b; font-weight: 700; }
        .article-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 32px 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .article-content a { color: var(--accent); text-decoration: underline; text-decoration-color: #fbd38d; text-decoration-thickness: 2px; text-underline-offset: 4px; }
        .article-content a:hover { color: #d97706; }
        .article-content blockquote { border-left: 4px solid var(--accent); margin: 32px 0; padding: 16px 24px; background: #fffbeb; color: #92400e; font-style: italic; font-size: 1.25rem; border-radius: 0 8px 8px 0; }
        .article-content ul, .article-content ol { margin-bottom: 24px; padding-left: 24px; }
        .article-content li { margin-bottom: 12px; }
        
        @media (max-width: 768px) {
            .article-title { font-size: 2rem; }
            .article-content { font-size: 1.05rem; }
        }

        /* CTA Box */
        .cta-box { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fde68a; border-radius: 12px; padding: 40px; text-align: center; margin-top: 60px; box-shadow: 0 10px 15px -3px rgba(245, 131, 32, 0.1); }
        .cta-box h3 { font-size: 1.8rem; color: #92400e; margin-bottom: 16px; font-weight: 800; }
        .cta-box p { font-size: 1.1rem; color: #b45309; margin-bottom: 24px; max-width: 500px; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
    
    <div style="background: #fff; border-bottom: 1px solid var(--border-light); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;">
        <a href="<?= BASE_URL ?>/" style="text-decoration: none;">
            <span class="store-brand-text" style="font-weight: 900; font-size: 1.5rem; letter-spacing: 0; color: #111827;"><span style="color: var(--accent);">Store</span>lo.</span>
        </a>
        <div style="display: flex; gap: 16px; align-items: center;">
            <a href="<?= BASE_URL ?>/blog" style="text-decoration: none; color: #475569; font-weight: 600; margin-right: 10px;">More Articles</a>
            <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-sm" style="padding: 8px 16px;">Start Selling</a>
        </div>
    </div>

    <?php if ($post['featured_image']): ?>
        <img src="<?= BASE_URL . $post['featured_image'] ?>" alt="<?= e($post['title']) ?>" class="blog-header-img">
    <?php endif; ?>

    <main class="article-container">
        <a href="<?= BASE_URL ?>/blog" style="color: var(--accent); text-decoration: none; font-weight: 600; font-size: 0.95rem; display: inline-block; margin-bottom: 24px;">&larr; Back to Blog</a>
        
        <h1 class="article-title"><?= e($post['title']) ?></h1>
        
        <div class="article-meta">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #475569;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span style="color: #1e293b; font-weight: 600;"><?= e($post['author_name']) ?></span>
            </div>
            <span>•</span>
            <span><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
        </div>

        <article class="article-content ql-editor">
            <!-- Output raw HTML from editor. Must not be escaped. -->
            <?= $post['content'] ?>
        </article>

        <!-- Call to Action -->
        <div class="cta-box">
            <h3>Start Your E-commerce Journey Today</h3>
            <p>Ready to put these tips into practice? Create your free online store with Storelo and start selling in under 3 minutes. Zero coding required.</p>
            <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-xl" style="display: inline-block; padding: 14px 32px; font-size: 1.1rem; box-shadow: 0 4px 14px var(--accent-glow);">Create My Free Store</a>
        </div>
    </main>

    <footer style="background: #0f172a; padding: 40px 20px; text-align: center; color: #94a3b8;">
        <div style="font-size: 0.85rem;">
            &copy; <?= date('Y') ?> Storelo. All rights reserved.
        </div>
    </footer>

</body>
</html>
