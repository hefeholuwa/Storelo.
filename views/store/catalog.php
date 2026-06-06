<?php
// views/store/catalog.php — Customer-facing storefront
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = DB::connect();

// Fetch seller by username
$stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
$stmt->execute([$shop_username]);
$seller = $stmt->fetch();

if (!$seller) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Store Not Found</title>    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>';
    echo '<body style="background:#0f1015;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div><h1 style="font-size:2rem;margin-bottom:10px;">Store Not Found</h1><p style="color:#A1A1AA;">This shop doesn\'t exist.</p><a href="' . BASE_URL . '/" style="color:#8B5CF6;">Go Home</a></div></body></html>';
    exit;
}

if (!empty($seller['is_suspended']) || !empty($seller['is_deleted'])) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Store Unavailable</title>    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>';
    echo '<body style="background:#F3F4F6;color:#111827;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div style="background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.05);max-width:400px;width:100%;"><h1 style="font-size:1.5rem;margin-bottom:10px;color:#dc2626;">Store Unavailable</h1><p style="color:#6b7280;margin-bottom:24px;">This store is currently unavailable or has been suspended.</p><a href="' . BASE_URL . '/" style="display:inline-block;padding:10px 20px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Go to Storelo</a></div></body></html>';
    exit;
}

$currency = $seller['currency'] ?? '₦';
$theme_color = $seller['theme_color'] ?? '#8B5CF6';

// Increment store visits (basic analytics)
$db->prepare("UPDATE sellers SET store_visits = store_visits + 1 WHERE id = ?")->execute([$seller['id']]);

// Fetch products (active)
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? AND status = 'active' ORDER BY id DESC");
$stmt->execute([$seller['id']]);
$products = $stmt->fetchAll();

// Get unique categories for filtering
$categories = array_unique(array_filter(array_column($products, 'category')));

// Fetch published reviews
$stmt = $db->prepare("SELECT * FROM reviews WHERE seller_id = ? AND is_published = 1 ORDER BY id DESC LIMIT 10");
$stmt->execute([$seller['id']]);
$reviews = $stmt->fetchAll();

// Dynamic SEO Tags
$seo_title = $seller['shop_name'] . ' — Storelo';
$seo_desc = $seller['shop_description'] ?? 'Browse products and order via WhatsApp.';
$seo_img = BASE_URL . '/assets/images/default-store-og.jpg';

if (isset($_GET['product'])) {
    $pid = (int)$_GET['product'];
    $stmt = $db->prepare("SELECT name, description, image_paths, price FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$pid, $seller['id']]);
    if ($prod = $stmt->fetch()) {
        $seo_title = $prod['name'] . ' - ' . $currency . number_format($prod['price']) . ' | ' . $seller['shop_name'];
        if (!empty($prod['description'])) {
            $seo_desc = mb_substr(strip_tags($prod['description']), 0, 160) . '...';
        }
        $paths = json_decode($prod['image_paths'], true) ?: [];
        if (!empty($paths)) {
            $seo_img = BASE_URL . '/' . $paths[0];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seo_title) ?></title>
    <meta name="description" content="<?= e($seo_desc) ?>">
    <meta property="og:title" content="<?= e($seo_title) ?>">
    <meta property="og:description" content="<?= e($seo_desc) ?>">
    <meta property="og:image" content="<?= e($seo_img) ?>">
    <meta property="og:url" content="http://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="<?= BASE_URL ?>/assets/js/cart.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Light Mode Bento CSS */
        :root {
            --brand: <?= e($theme_color) ?>;
            --brand-glow: color-mix(in srgb, var(--brand) 40%, transparent);
            --bg: #FAFAFA;
            --card-bg: #FFFFFF;
            --card-hover: #FFFFFF;
            --text-main: #111827;
            --text-mute: #6B7280;
            --border-light: rgba(0, 0, 0, 0.08);
            --max-width: 1280px;
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
            color: var(--text-main);
            margin: 0; padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Minimal Header */
        .site-header {
            background: rgba(250, 250, 250, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light);
            position: sticky; top: 0; z-index: 1000;
        }
        
        .header-top {
            max-width: var(--max-width); margin: 0 auto; padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }

        .logo-area { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-main); flex-shrink: 0; }
        .logo-text { font-size: 1.2rem; font-weight: 800; margin: 0; letter-spacing: -0.02em; }

        .search-area {
            flex: 1; max-width: 400px; position: relative; display: block;
        }
        
        .search-input {
            width: 100%; padding: 10px 16px 10px 40px; border: 1px solid var(--border-light);
            border-radius: 100px; font-size: 0.9rem; outline: none; transition: all 0.2s;
            box-sizing: border-box; background: rgba(0,0,0,0.03); color: var(--text-main);
        }
        .search-input:focus { border-color: rgba(0,0,0,0.2); background: rgba(0,0,0,0.06); }
        .search-input::placeholder { color: var(--text-mute); }
        .search-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: var(--text-mute); width: 16px; height: 16px;
        }

        .cart-area { flex-shrink: 0; }
        .cart-btn {
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; color: var(--text-mute); transition: color 0.2s;
            position: relative;
        }
        .cart-btn:hover { color: var(--text-main); }
        .cart-badge {
            position: absolute; top: -6px; right: -8px; background: var(--brand); color: #fff;
            font-size: 0.65rem; font-weight: 800; height: 16px; min-width: 16px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid var(--bg);
        }

        /* Product Badges */
        .product-badge {
            position: absolute; top: 12px; left: 12px; z-index: 5;
            padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.05em; color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .badge-sold-out { background: var(--text-main); }
        .badge-sale { background: #ef4444; }
        .badge-low-stock { background: #f59e0b; }
        .badge-new { background: var(--brand); }

        /* Main Content Area */
        .main-content { max-width: var(--max-width); margin: 0 auto; padding: 32px 24px 80px 24px; }

        @keyframes shadowBreathe {
            0% { box-shadow: 0 20px 40px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04); transform: translateY(0); }
            50% { box-shadow: 0 30px 60px rgba(0,0,0,0.12), 0 1px 3px rgba(0,0,0,0.04); transform: translateY(-4px); }
            100% { box-shadow: 0 20px 40px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04); transform: translateY(0); }
        }

        .hero-bento {
            background: var(--card-bg);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 48px;
            margin-bottom: 40px; 
            position: relative; 
            z-index: 2;
            animation: shadowBreathe 4s infinite ease-in-out;
        }

        @media (max-width: 899px) {
            .hero-bento { padding: 32px 20px; }
            .hero-bento-inner { flex-direction: column !important; gap: 24px !important; }
            .hero-profile-group { flex-direction: column !important; text-align: center; gap: 20px !important; }
            .hero-avatar, .hero-avatar-placeholder { width: 100px; height: 100px; font-size: 2.5rem; }
            .hero-title { font-size: 1.8rem; text-align: center; }
            .hero-desc { font-size: 1rem; max-width: 100%; }
            .hero-content { align-items: center; }
            .hero-tags { justify-content: center; }
            .hero-action-group { width: 100%; }
            .hero-contact-btn { width: 100%; justify-content: center; padding: 14px 24px; font-size: 1rem; }
        }
        @media (max-width: 479px) {
            .hero-bento { padding: 24px 16px; border-radius: 16px; }
            .hero-avatar, .hero-avatar-placeholder { width: 80px; height: 80px; font-size: 2rem; border-width: 3px; }
            .hero-title { font-size: 1.5rem; }
            .hero-desc { font-size: 0.9rem; }
            .hero-tag { font-size: 0.75rem; padding: 5px 10px; }
        }
        
        .hero-bento-inner {
            display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 40px;
            position: relative; z-index: 2; width: 100%;
        }
        
        .hero-profile-group {
            display: flex; flex-direction: row; align-items: center; gap: 40px;
        }

        .hero-avatar {
            width: 140px; height: 140px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
            border: 4px solid #fff; margin-bottom: 0;
            position: relative; z-index: 2; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .hero-avatar-placeholder {
            width: 140px; height: 140px; border-radius: 50%; background: var(--brand); color: #fff; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 3.5rem;
            margin-bottom: 0; position: relative; z-index: 2; border: 4px solid #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .hero-content {
            display: flex; flex-direction: column; z-index: 2; position: relative;
        }

        .hero-title {
            font-size: 3rem; font-weight: 800; margin: 0 0 16px 0; letter-spacing: -0.03em; color: var(--brand);
            word-break: break-word;
        }
        .verified-badge {
            display: inline-block; vertical-align: middle; margin-left: 6px; flex-shrink: 0;
        }
        
        .hero-desc {
            font-size: 1.1rem; color: var(--text-main); margin: 0 0 24px 0; max-width: 600px; opacity: 0.9;
            line-height: 1.6; 
        }
        
        .hero-tags {
            display: flex; gap: 12px; flex-wrap: wrap; 
        }
        .hero-tag {
            background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1);
            color: var(--text-mute); font-size: 0.8rem; font-weight: 600;
            padding: 6px 14px; border-radius: 20px; letter-spacing: 0.02em;
        }

        .hero-contact-btn {
            background: var(--text-main);
            color: var(--bg);
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            white-space: nowrap;
        }
        .hero-contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: #000;
        }

        /* Category Tabs */
        .category-tabs {
            display: flex; gap: 12px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 32px;
            scrollbar-width: none; /* Firefox */
        }
        .category-tabs::-webkit-scrollbar { display: none; }
        .cat-tab {
            padding: 8px 20px; border-radius: 100px; font-size: 0.95rem; font-weight: 600; cursor: pointer;
            border: 1px solid var(--border-light); background: var(--card-bg); color: var(--text-mute);
            white-space: nowrap; transition: all 0.2s;
        }
        .cat-tab:hover { background: rgba(0,0,0,0.02); }
        .cat-tab.active { background: var(--brand); color: #fff; border-color: var(--brand); box-shadow: 0 4px 12px var(--brand-glow); }

        /* Bento Grid */
        .bento-grid {
            display: grid;
            grid-template-columns: 1fr; /* Default to 1 column on very small screens */
            gap: 16px;
            grid-auto-flow: dense;
        }
        @media (min-width: 480px) { .bento-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; } }
        @media (min-width: 768px) { .bento-grid { grid-template-columns: repeat(3, 1fr); gap: 24px; } }
        @media (min-width: 1024px) { .bento-grid { grid-template-columns: repeat(4, 1fr); } }

        /* Bento Card */
        .bento-card {
            background: var(--card-bg); border-radius: 16px; border: 1px solid var(--border-light);
            overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; cursor: pointer;
        }
        .bento-card:hover { 
            background: var(--card-hover); border-color: rgba(0,0,0,0.15);
            transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15);
        }
        
        .bento-card.featured {
            grid-column: span 2;
            grid-row: span 2;
        }
        @media (max-width: 639px) {
            .bento-card.featured { grid-column: span 2; grid-row: auto; }
        }
        @media (max-width: 479px) {
            .bento-card.featured { grid-column: span 1; grid-row: auto; }
        }

        .card-img-area { 
            position: relative; width: 100%; padding-top: 100%; 
            background: #f3f4f6; overflow: hidden;
        }
        .bento-card.featured .card-img-area {
            padding-top: 75%; /* More rectangular for featured */
        }
        .card-img { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); opacity: 0.9;
        }
        .bento-card:hover .card-img { transform: scale(1.03); opacity: 1; }
        
        /* Gradient overlay for text readability on featured */
        .bento-card.featured .card-img::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 50%;
            background: linear-gradient(to top, rgba(255, 255, 255, 1), transparent); pointer-events: none;
        }
        
        .badge-tag { 
            position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            color: #fff; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;
            padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1);
        }
        .badge-tag.mint { color: #34D399; }
        .sold-overlay {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-10deg);
            border: 2px solid rgba(255,255,255,0.3); color: rgba(255,255,255,0.5); font-size: 1.5rem; font-weight: 800;
            text-transform: uppercase; padding: 4px 16px; border-radius: 8px; letter-spacing: 0.1em;
            backdrop-filter: blur(2px); z-index: 10;
        }
        
        .card-info { padding: 20px; display: flex; flex-direction: column; flex: 1; z-index: 2; position: relative; }
        .card-title { font-size: 1.1rem; color: var(--text-main); margin: 0 0 6px 0; font-weight: 700; letter-spacing: -0.01em; }
        .bento-card.featured .card-title { font-size: 1.5rem; margin-bottom: 8px; }
        
        .card-desc { font-size: 0.85rem; color: var(--text-mute); margin: 0 0 16px 0; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .bento-card.featured .card-desc { -webkit-line-clamp: 2; font-size: 0.95rem; margin-bottom: 24px; }
        
        .card-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: auto; gap: 12px; width: 100%; }
        .card-price { font-size: 1.2rem; font-weight: 800; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1; }
        .bento-card.featured .card-price { font-size: 1.5rem; }
        
        .old-price { font-size: 0.8rem; color: var(--text-mute); text-decoration: line-through; margin-left: 8px; font-weight: 500; }

        @media (max-width: 480px) {
            .card-info { padding: 12px; }
            .card-title { font-size: 1rem; }
            .card-price { font-size: 1.1rem; }
            .bento-card.featured .card-title { font-size: 1.2rem; }
            .bento-card.featured .card-price { font-size: 1.3rem; }
            .card-desc { margin-bottom: 12px; }
        }

        /* The + Button for standard cards */
        .add-btn-circle {
            width: 32px; height: 32px; border-radius: 16px; background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1);
            color: var(--text-main); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;
            flex-shrink: 0;
        }
        .add-btn-circle:hover { background: rgba(0,0,0,0.1); border-color: rgba(0,0,0,0.2); }
        
        /* The glowing button for featured cards */
        .add-btn-full {
            background: var(--brand); color: #fff; border: none; border-radius: 8px;
            padding: 8px 16px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px var(--brand-glow);
        }
        .add-btn-full:hover { filter: brightness(1.1); transform: translateY(-1px); box-shadow: 0 6px 16px var(--brand-glow); }
        .add-btn-disabled { opacity: 0.5; cursor: not-allowed; }

        /* Footer */
        .site-footer {
            border-top: 1px solid var(--border-light); padding: 40px 24px;
            display: flex; flex-direction: column; align-items: center; gap: 16px;
            margin-top: 60px;
        }
        @media (min-width: 640px) {
            .site-footer { flex-direction: row; justify-content: space-between; max-width: var(--max-width); margin-left: auto; margin-right: auto; padding-left: 24px; padding-right: 100px; }
        }
        .footer-logo { font-size: 1.1rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.02em; }
        .footer-links { display: flex; gap: 16px; }
        .footer-link { font-size: 0.75rem; color: var(--text-mute); text-decoration: none; font-weight: 600; }
        .footer-link:hover { color: var(--text-main); }
        .footer-copy { font-size: 0.75rem; color: var(--text-mute); }

        /* Mobile Adjustments */
        @media (max-width: 639px) {
            .main-content { padding: 24px 16px 80px 16px; }
            .header-top { padding: 12px 16px; }
            .bento-grid { gap: 16px; }
            .card-info { padding: 16px; }
        }

        /* Floating WhatsApp Button */
        .floating-wa {
            position: fixed; bottom: 24px; right: 24px; width: 60px; height: 60px;
            background: #25D366; color: #fff; border-radius: 30px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4); z-index: 900;
            transition: transform 0.2s; cursor: pointer; text-decoration: none;
        }
        .floating-wa:hover { transform: scale(1.1); }
        .floating-wa svg { width: 34px; height: 34px; }
        
        /* Quick View Modal styling */
        .quickview-container {
            display: flex; flex-direction: column; width: 100%; max-width: 900px;
            background: var(--card-bg); border-radius: 20px; overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative;
            max-height: 90vh;
        }
        @media (min-width: 768px) {
            .quickview-container { flex-direction: row; height: 500px; max-height: 90vh; }
        }
        .quickview-img-area {
            flex: none; background: #f3f4f6; position: relative;
            height: 250px; flex-shrink: 0;
        }
        @media (min-width: 768px) {
            .quickview-img-area { flex: 1; height: auto; }
        }
        .quickview-img { width: 100%; height: 100%; object-fit: contain; padding: 24px; }
        .quickview-info-area {
            flex: 1; padding: 24px; display: flex; flex-direction: column;
            overflow-y: auto;
        }
        @media (min-width: 768px) {
            .quickview-info-area { padding: 40px; }
        }
        .quickview-close {
            position: absolute; top: 16px; right: 16px; background: rgba(0,0,0,0.1); border: none;
            width: 32px; height: 32px; border-radius: 16px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--text-main); font-size: 1.2rem; z-index: 10; transition: background 0.2s;
        }
        .quickview-close:hover { background: rgba(0,0,0,0.2); }
        
        .quickview-share {
            position: absolute; top: 16px; right: 56px; background: rgba(0,0,0,0.05); border: none;
            width: 32px; height: 32px; border-radius: 16px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--text-main); font-size: 1rem; z-index: 10; transition: background 0.2s;
        }
        .quickview-share:hover { background: rgba(0,0,0,0.15); }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>

    <input type="hidden" id="cart-currency" value="<?= e($currency) ?>">

    <header class="site-header">
        <div class="header-top">
            <a href="<?= BASE_URL ?>/shop/<?= urlencode($seller['username']) ?>" class="logo-area" style="text-decoration: none;">
                <h1 class="logo-text" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                    <?= e($seller['shop_name']) ?>
                </h1>
            </a>
            
            <div class="search-area">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" class="search-input" id="search-input" placeholder="Search products..." onkeyup="filterProducts()">
            </div>
            
            <div class="cart-area">
                <a href="<?= BASE_URL ?>/shop/<?= urlencode($seller['username']) ?>/cart" class="cart-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <span id="header-cart-badge" class="cart-badge" style="display:none;">0</span>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Premium Bento Hero -->
        <div class="hero-bento">
            <div class="hero-bento-inner">
                <div class="hero-profile-group">
                    <?php if (!empty($seller['logo_path'])): ?>
                        <img src="<?= BASE_URL ?>/<?= $seller['logo_path'] ?>" alt="Logo" class="hero-avatar">
                    <?php else: ?>
                        <div class="hero-avatar-placeholder"><?= strtoupper(substr($seller['shop_name'], 0, 1)) ?></div>
                    <?php endif; ?>
                    
                    <div class="hero-content">
                        <h2 class="hero-title">
                            <?= e($seller['shop_name']) ?><?php if (!empty($seller['is_verified'])): ?><svg class="verified-badge" width="22" height="22" viewBox="0 0 24 24" fill="#3b82f6" stroke="#ffffff" stroke-width="2">
                                    <path d="M22.5 12.854c0 .355-.099.7-.282.993l-1.63 2.658c-.144.234-.236.5-.27.773l-.403 3.107c-.048.373-.207.72-.456.992-.25.272-.572.464-.93.551l-3.05.748c-.27.067-.522.189-.738.358l-2.484 1.94c-.292.228-.646.347-1.01.347s-.718-.119-1.01-.347l-2.484-1.94c-.216-.169-.468-.291-.738-.358l-3.05-.748c-.358-.087-.68-.279-.93-.551-.249-.272-.408-.619-.456-.992l-.403-3.107c-.034-.273-.126-.539-.27-.773L2.28 13.847a1.868 1.868 0 0 1 0-1.986l1.63-2.658c.144-.234.236-.5.27-.773l.403-3.107c.048-.373.207-.72.456-.992.25-.272.572-.464.93-.551l3.05-.748c.27-.067.522-.189.738-.358l2.484-1.94c.292-.228.646-.347 1.01-.347s.718.119 1.01.347l2.484 1.94c.216.169.468.291.738.358l3.05.748c.358.087.68.279.93.551.249.272.408.619.456.992l.403 3.107c.034.273.126.539.27.773l1.63 2.658c.183.293.282.638.282.993Z"></path>
                                    <path d="M16 9.5 10.5 15 8 12.5" stroke-width="2.5"></path>
                                </svg><?php endif; ?>
                        </h2>
                        <p class="hero-desc"><?= e($seller['shop_description'] ?? 'Curated items for the modern aesthetic. Explore our premium collection.') ?></p>
                        
                        <div class="hero-tags">
                            <?php $t = 0; foreach ($categories as $cat): if($t++>=3)break; ?>
                                <span class="hero-tag"><?= e($cat) ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                                <span class="hero-tag">Premium</span>
                                <span class="hero-tag">Curated</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($seller['whatsapp_number'])): ?>
                <div class="hero-action-group">
                    <a href="https://wa.me/<?= e($seller['whatsapp_number']) ?>" target="_blank" class="hero-contact-btn">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        Contact Store
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Tabs -->
        <?php if (!empty($categories)): ?>
        <div class="category-tabs" id="category-tabs">
            <button class="cat-tab active" onclick="filterByCategory('All', this)">All Products</button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-tab" onclick="filterByCategory('<?= addslashes(e($cat)) ?>', this)"><?= e($cat) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Bento Grid -->
        <?php if (empty($products)): ?>
            <div style="text-align:center; padding:80px 20px; border-radius:16px; border:1px dashed var(--border-light);">
                <p style="color:var(--text-mute); font-size:1.1rem;">No products found.</p>
            </div>
        <?php else: ?>
            <div class="bento-grid" id="product-grid">
                <?php foreach ($products as $index => $p): 
                    $paths = json_decode($p['image_paths'], true) ?: [];
                    $cover_image = !empty($paths) ? $paths[0] : 'assets/images/placeholder.png';
                    $json_escaped = htmlspecialchars(json_encode($paths), ENT_QUOTES, 'UTF-8');
                    
                    // Logic for Masonry/Bento styling
                    // Ensure uniform sizes for all product cards
                    $is_featured = false; 
                    $card_class = 'bento-card product-card';
                ?>
                <?php
                    $product_json = json_encode([
                        'id' => $p['id'],
                        'name' => $p['name'],
                        'price' => $p['price'],
                        'original_price' => $p['original_price'] ?? 0,
                        'description' => $p['description'],
                        'category' => $p['category'],
                        'stock' => $p['stock'],
                        'image' => BASE_URL . '/' . $cover_image,
                        'options' => !empty($p['options'] ?? null) ? json_decode($p['options'], true) : null
                    ]);
                    $json_escaped = htmlspecialchars($product_json, ENT_QUOTES, 'UTF-8');
                    
                    $badge = '';
                    $badge_class = '';
                    if ($p['stock'] <= 0) {
                        $badge = 'Sold Out';
                        $badge_class = 'badge-sold-out';
                    } elseif (isset($p['original_price']) && floatval($p['original_price']) > floatval($p['price'])) {
                        $badge = 'Sale';
                        $badge_class = 'badge-sale';
                    } elseif ($p['stock'] > 0 && $p['stock'] <= 5) {
                        $badge = 'Few Left';
                        $badge_class = 'badge-low-stock';
                    } elseif (strtotime($p['created_at']) > strtotime('-7 days')) {
                        $badge = 'New';
                        $badge_class = 'badge-new';
                    }
                ?>
                    <div class="<?= $card_class ?>" data-id="<?= $p['id'] ?>" data-title="<?= strtolower(e($p['name'])) ?>" data-category="<?= strtolower(e($p['category'])) ?>">
                        <div class="card-img-area" onclick="openQuickView(<?= $json_escaped ?>)">
                            <img class="card-img" src="<?= BASE_URL ?>/<?= $cover_image ?>" loading="lazy" alt="<?= e($p['name']) ?>">
                            
                            <?php if ($badge): ?>
                                <div class="product-badge <?= $badge_class ?>"><?= $badge ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-info" onclick="openQuickView(<?= $json_escaped ?>)">
                            <h3 class="card-title"><?= e($p['name']) ?></h3>
                            <?php if ($p['category']): ?>
                                <p class="card-desc"><?= e($p['category']) ?> &bull; <?= e($p['description'] ? mb_substr(strip_tags($p['description']), 0, 50).'...' : 'Premium Item') ?></p>
                            <?php else: ?>
                                <p class="card-desc"><?= e($p['description'] ? mb_substr(strip_tags($p['description']), 0, 50).'...' : 'Premium Item') ?></p>
                            <?php endif; ?>
                            
                            <div class="card-bottom">
                                <div style="min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <span class="card-price"><?= $currency ?><?= number_format($p['price'], 2) ?></span>
                                    <?php if(isset($p['original_price']) && $p['original_price'] > 0): ?>
                                        <span class="old-price"><?= $currency ?><?= number_format($p['original_price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div onclick="event.stopPropagation();">
                                    <?php if ($p['stock'] > 0): ?>
                                        <?php if ($is_featured): ?>
                                            <button class="add-btn-full" data-cart-btn="<?= $p['id'] ?>" onclick="addCatalogToCart(<?= $p['id'] ?>, '<?= addslashes(e($p['name'])) ?>', <?= $p['price'] ?>, '<?= BASE_URL ?>/<?= $cover_image ?>', <?= $p['stock'] ?>, this)">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                                                Add to Cart
                                            </button>
                                        <?php elseif (!empty($p['options'])): ?>
                                            <div class="add-btn-circle" onclick="openQuickView(<?= $json_escaped ?>)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="add-btn-circle" data-cart-btn="<?= $p['id'] ?>" onclick="addCatalogToCart(<?= $p['id'] ?>, '<?= addslashes(e($p['name'])) ?>', <?= $p['price'] ?>, '<?= BASE_URL ?>/<?= $cover_image ?>', <?= $p['stock'] ?>, this)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($is_featured): ?>
                                            <button class="add-btn-full add-btn-disabled" disabled>Sold Out</button>
                                        <?php else: ?>
                                            <div class="add-btn-circle add-btn-disabled" style="opacity:0.3; cursor:not-allowed;">+</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Reviews Section -->
    <?php if (!empty($reviews)): ?>
    <div style="max-width: var(--max-width); margin: 0 auto 60px auto; padding: 0 20px;">
        <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 24px; color: var(--text-main);">What our customers say</h3>
        <div style="display: flex; gap: 20px; overflow-x: auto; padding-bottom: 16px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;">
            <?php foreach ($reviews as $r): ?>
                <div style="flex: 0 0 300px; scroll-snap-align: start; background: var(--card-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 12px;">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $i <= $r['rating'] ? '#fbbf24' : 'none' ?>" stroke="<?= $i <= $r['rating'] ? '#fbbf24' : '#e4e4e7' ?>" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <?php endfor; ?>
                    </div>
                    <p style="font-size: 0.95rem; color: var(--text-main); font-style: italic; margin: 0 0 16px 0; line-height: 1.5; white-space: pre-wrap;">"<?= e($r['comment']) ?>"</p>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">- <?= e($r['customer_name']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="footer-logo"><?= e($seller['shop_name']) ?></div>

        <div class="footer-copy" style="margin-bottom: 8px;">&copy; <?= date('Y') ?> <?= e($seller['shop_name']) ?> Platform</div>
        <div class="footer-powered" style="font-size: 0.8rem; color: var(--text-mute); text-align: center;">
            Powered by <strong>Storelo</strong> &bull; Built by <strong>Evermark Innovations</strong>
        </div>
    </footer>

    <!-- Quick View Modal -->
    <div id="quickview-modal" class="modal-overlay" onclick="closeQuickView(event)">
        <div class="quickview-container" onclick="event.stopPropagation()">
            <button class="quickview-close" onclick="closeQuickView(event)">&times;</button>
            <button class="quickview-share" id="qv-wa-btn" onclick="shareToWhatsApp()" aria-label="Share to WhatsApp" style="right: 96px; color: #25D366;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.888-.788-1.489-1.761-1.663-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
            </button>
            <button class="quickview-share" id="qv-share-btn" onclick="shareProduct()" aria-label="Share Product">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
            </button>
            <div class="quickview-img-area">
                <img id="qv-img" class="quickview-img" src="" alt="Product Image">
            </div>
            <div class="quickview-info-area" style="display: flex; flex-direction: column; height: 100%;">
                <div style="margin-bottom: 16px; padding-right: 48px;">
                    <div id="qv-category" style="font-size: 0.75rem; font-weight: 800; color: var(--brand); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;"></div>
                    <h2 id="qv-title" style="font-size: 1.75rem; font-weight: 800; margin: 0; color: var(--text-main); line-height: 1.2; letter-spacing: -0.02em; word-break: break-word;"></h2>
                </div>
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: baseline; flex-wrap: wrap; gap: 12px;">
                    <span id="qv-price" style="font-size: clamp(1.5rem, 5vw, 2rem); font-weight: 800; color: #0f172a;"></span>
                    <span id="qv-old-price" style="font-size: clamp(0.9rem, 3vw, 1.1rem); color: #94a3b8; text-decoration: line-through;"></span>
                </div>
                
                <div style="font-weight: 700; margin-bottom: 12px; color: var(--text-main); font-size: 1.1rem;">Product Details</div>
                <div id="qv-options" style="margin-bottom: 16px;"></div>
                <div style="margin-bottom: 24px;">
                    <p id="qv-desc" style="font-size: 0.95rem; color: #475569; line-height: 1.6; margin: 0; white-space: pre-line;"></p>
                </div>
                
                <div style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-light);">
                    <div id="qv-btn-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating WhatsApp Button -->
    <?php if (!empty($seller['whatsapp_number'])): ?>
    <a href="https://wa.me/<?= e($seller['whatsapp_number']) ?>?text=Hello%2C%20I%20have%20an%20inquiry%20from%20your%20store" class="floating-wa" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.888-.788-1.489-1.761-1.663-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
        </svg>
    </a>
    <?php endif; ?>

    <script>
        // cart.js handles DOMContentLoaded and renderCart

        let currentProductId = null;
        let currentProductName = null;

        function filterProducts() {
            const query = document.getElementById('search-input').value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                const cat = card.getAttribute('data-category');
                
                const matchesSearch = title.includes(query);
                const matchesCat = (currentCategory === 'All' || cat === currentCategory.toLowerCase());
                
                if (matchesSearch && matchesCat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        let currentCategory = 'All';
        function filterByCategory(category, btnEl) {
            currentCategory = category;
            
            // Update active state on tabs
            document.querySelectorAll('.cat-tab').forEach(btn => btn.classList.remove('active'));
            btnEl.classList.add('active');
            
            filterProducts();
        }

        function addCatalogToCart(id, name, price, img, maxStock, btnEl) {
            const existing = cart.find(item => item.id === id);
            
            const isFull = btnEl.classList.contains('add-btn-full');
            const originalHtml = isFull 
                ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg> Add to Cart' 
                : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';

            if (existing) {
                // Remove from cart
                removeFromCart(id);
                // Revert visuals
                btnEl.innerHTML = originalHtml;
                btnEl.style.background = '';
                if (!isFull) {
                    btnEl.style.color = '';
                    btnEl.style.borderColor = '';
                }
            } else {
                // Add to cart
                addToCart(id, name, price, img, 1, maxStock);
                // Apply 'Added' visuals using a softer, more professional green
                if (isFull) {
                    btnEl.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Added';
                    btnEl.style.background = '#059669'; 
                } else {
                    btnEl.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                    btnEl.style.background = '#059669';
                    btnEl.style.color = '#fff';
                    btnEl.style.borderColor = '#059669';
                }
            }
        }

        // Sync button states on page load or cart updates
        function syncCartButtons() {
            document.querySelectorAll('[data-cart-btn]').forEach(btnEl => {
                const id = parseInt(btnEl.getAttribute('data-cart-btn'));
                const existing = cart.find(item => item.id === id);
                const isFull = btnEl.classList.contains('add-btn-full');
                
                if (existing) {
                    if (isFull) {
                        btnEl.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Added';
                        btnEl.style.background = '#059669'; 
                    } else {
                        btnEl.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                        btnEl.style.background = '#059669';
                        btnEl.style.color = '#fff';
                        btnEl.style.borderColor = '#059669';
                    }
                } else {
                    const originalHtml = isFull 
                        ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg> Add to Cart' 
                        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
                    btnEl.innerHTML = originalHtml;
                    btnEl.style.background = '';
                    if (!isFull) {
                        btnEl.style.color = '';
                        btnEl.style.borderColor = '';
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', syncCartButtons);
        // Also overwrite cart save function globally to sync buttons
        const originalSaveCart = window.saveCart;
        if (typeof originalSaveCart === 'function') {
            window.saveCart = function() {
                originalSaveCart();
                syncCartButtons();
            };
        }

        function openQuickView(product) {
            const modal = document.getElementById('quickview-modal');
            currentProductId = product.id;
            currentProductName = product.name;
            
            // Update URL for deep linking
            history.pushState(null, '', '?product=' + product.id);

            // Track product view (Analytics)
            fetch('<?= BASE_URL ?>/api/track_view.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ product_id: product.id })
            }).catch(e => console.error('Error tracking view', e));

            document.getElementById('qv-img').src = product.image;
            document.getElementById('qv-category').innerText = product.category || '';
            document.getElementById('qv-title').innerText = product.name;
            document.getElementById('qv-price').innerText = '<?= $currency ?>' + parseFloat(product.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            if (product.original_price && product.original_price > 0) {
                document.getElementById('qv-old-price').innerText = '<?= $currency ?>' + parseFloat(product.original_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                document.getElementById('qv-old-price').innerText = '';
            }
            
            document.getElementById('qv-desc').innerText = product.description || 'No description available.';
            
            // Render variant option dropdowns
            const optionsContainer = document.getElementById('qv-options');
            optionsContainer.innerHTML = '';
            if (product.options && Array.isArray(product.options) && product.options.length > 0) {
                product.options.forEach((opt, idx) => {
                    const wrapper = document.createElement('div');
                    wrapper.style.cssText = 'margin-bottom: 12px;';
                    wrapper.innerHTML = `
                        <label style="font-size: 0.85rem; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">${opt.name}</label>
                        <select id="qv-option-${idx}" data-option-name="${opt.name}" class="qv-option-select" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: #fff; color: #0f172a; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill=\\'%23475569\\' viewBox=\\'0 0 24 24\\' xmlns=\\'http://www.w3.org/2000/svg\\'><path d=\\'M7 10l5 5 5-5z\\'/></svg>'); background-repeat: no-repeat; background-position: right 12px center; background-size: 18px;">
                            ${opt.values.map(v => `<option value="${v}">${v}</option>`).join('')}
                        </select>
                    `;
                    optionsContainer.appendChild(wrapper);
                });
            }

            const btnContainer = document.getElementById('qv-btn-container');
            if (product.stock > 0) {
                const escapedName = product.name.replace(/'/g, "\\'");
                btnContainer.innerHTML = `
                    <button class="btn-primary" style="width:100%; padding: 16px; font-size:1.1rem; border-radius: 12px; background: var(--brand);" 
                        onclick="addFromQuickView(${product.id}, '${escapedName}', ${product.price}, '${product.image}', ${product.stock}, this)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px; vertical-align:text-bottom;">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        Add to Cart
                    </button>
                `;
            } else {
                btnContainer.innerHTML = `<button class="btn-primary" style="width:100%; padding: 16px; font-size:1.1rem; border-radius: 12px; opacity:0.5; cursor:not-allowed;" disabled>Sold Out</button>`;
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Collect selected variants and add to cart from quickview
        function addFromQuickView(id, name, price, img, maxStock, btnEl) {
            const selects = document.querySelectorAll('.qv-option-select');
            let variantStr = '';
            let variantParts = [];
            selects.forEach(sel => {
                variantParts.push(sel.getAttribute('data-option-name') + ': ' + sel.value);
            });
            variantStr = variantParts.join(', ');
            
            // Use a unique key combining product id + variant so different variants are separate cart items
            const cartId = variantStr ? id + '_' + variantStr.replace(/[^a-zA-Z0-9]/g, '_') : id;
            
            const existing = cart.find(item => item.id === cartId);
            if (existing) {
                let newQty = existing.quantity + 1;
                if (newQty > maxStock) {
                    alert(`Sorry, only ${maxStock} items available in stock.`);
                    existing.quantity = maxStock;
                } else {
                    existing.quantity = newQty;
                }
            } else {
                cart.push({
                    id: cartId,
                    productId: id,
                    name: name,
                    price: parseFloat(price),
                    image: img,
                    quantity: 1,
                    maxStock: maxStock,
                    variant: variantStr || null
                });
            }
            saveCart();
            
            // Visual feedback
            if (btnEl) {
                btnEl.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right:8px; vertical-align:text-bottom;"><polyline points="20 6 9 17 4 12"></polyline></svg> Added!';
                btnEl.style.background = '#059669';
                setTimeout(() => {
                    btnEl.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px; vertical-align:text-bottom;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg> Add to Cart';
                    btnEl.style.background = 'var(--brand)';
                }, 1500);
            }
        }

        function closeQuickView(e) {
            if (e.target.classList.contains('modal-overlay') || e.target.classList.contains('quickview-close')) {
                document.getElementById('quickview-modal').classList.remove('active');
                document.body.style.overflow = '';
                // Remove product ID from URL
                history.pushState(null, '', window.location.pathname);
                currentProductId = null;
                currentProductName = null;
            }
        }

        function shareProduct() {
            if (!currentProductId) return;
            const url = window.location.origin + window.location.pathname + '?product=' + currentProductId;
            
            if (navigator.share) {
                navigator.share({
                    title: currentProductName,
                    url: url
                }).catch(console.error);
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Product link copied to clipboard!');
                }).catch(() => {
                    alert('Failed to copy link.');
                });
            }
        }

        function shareToWhatsApp() {
            if (!currentProductId) return;
            const url = window.location.origin + window.location.pathname + '?product=' + currentProductId;
            const text = encodeURIComponent(`Check out ${currentProductName} on <?= e($seller['shop_name']) ?>! ` + url);
            window.open('https://wa.me/?text=' + text, '_blank');
        }

        // Handle deep linking on load
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const pid = params.get('product');
            if (pid) {
                const card = document.querySelector('.product-card[data-id="'+pid+'"]');
                if (card) {
                    const imgArea = card.querySelector('.card-img-area');
                    if (imgArea) imgArea.click();
                }
            }
        });
    </script>
</body>
</html>
