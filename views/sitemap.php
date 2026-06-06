<?php
// views/sitemap.php — Dynamic XML Sitemap Generator
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Prevent caching for sitemap
header('Content-Type: application/xml; charset=utf-8');

$db = DB::connect();

// Fetch all published blog posts
$stmt = $db->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

// Define static routes
$static_pages = [
    '/' => '1.0',
    '/blog' => '0.9',
    '/register' => '0.8',
    '/login' => '0.5'
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($static_pages as $url => $priority): ?>
    <url>
        <loc><?= BASE_URL . $url ?></loc>
        <changefreq>weekly</changefreq>
        <priority><?= $priority ?></priority>
    </url>
<?php endforeach; ?>

<?php foreach ($posts as $post): ?>
    <url>
        <loc><?= BASE_URL . '/blog/' . e($post['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($post['updated_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
</urlset>
