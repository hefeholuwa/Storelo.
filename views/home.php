<?php
// views/home.php — Storelo landing page
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$is_logged_in = is_logged_in();

// Fetch latest 3 published blog posts
$db = DB::connect();
$stmt = $db->query("SELECT title, slug, excerpt, featured_image, author_name, created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
$latest_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storelo — Build Your Online Store in Minutes | Nigeria & Beyond</title>
    <meta name="description" content="Create a free WhatsApp online store without coding. The easiest ecommerce storefront builder for Nigerian and African small businesses. No payment gateway required.">
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
    
    <!-- Structured Data (Schema.org) for SEO/GEO -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "Storelo",
      "operatingSystem": "Web Browser",
      "applicationCategory": "BusinessApplication",
      "description": "A free online store builder designed for WhatsApp vendors and small businesses in Nigeria and Africa to sell physical products without coding.",
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "NGN"
      }
    }
    </script>
</head>
<body class="premium-light">
    <!-- Clean Minimal Nav -->
    <nav class="clean-nav">
        <a href="<?= BASE_URL ?>/" style="text-decoration: none;">
            <span class="store-brand-text" style="font-weight: 900; font-size: 1.75rem; letter-spacing: 0; color: #111827;"><span style="color: var(--accent);">Store</span>lo.</span>
        </a>
        <div class="nav-actions">
            <a href="<?= BASE_URL ?>/blog" class="nav-link" style="margin-right: 16px;">Blog</a>
            <?php if ($is_logged_in): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="btn-vibrant btn-sm">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login" class="nav-link">Login</a>
                <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-sm">Create Store</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Product-led Hero -->
    <section class="minimal-hero landing-hero-product">
        <div class="hero-copy-stack">
            <div class="badge-subtle mb-24">Early access for WhatsApp sellers</div>
            <h1 class="hero-title-giant">Create a WhatsApp store customers can actually shop from.</h1>
            <p class="hero-subtitle-clean">
                Add products, share one clean link, and receive organized cart orders in WhatsApp. No payment gateway setup, no complicated website builder.
            </p>
            <div class="landing-cta-block">
                <?php if ($is_logged_in): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-vibrant btn-xl">
                        Go to Dashboard
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-xl">
                        Create Your Store Free
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                <?php endif; ?>
                <a href="#product-preview" class="btn-outline btn-xl">See Product Flow</a>
            </div>
            <div class="trust-strip">
                <span>No coding</span>
                <span>No gateway required</span>
                <span>Built for mobile buyers</span>
            </div>
        </div>

        <div class="hero-product-preview" id="product-preview" aria-label="Storelo product preview">
            <div class="preview-store">
                <div class="preview-store-top">
                    <div>
                        <span class="preview-logo">S</span>
                    </div>
                    <div>
                        <strong>Sade's Closet</strong>
                        <small>storelo.page.gd/shop/sadescloset</small>
                    </div>
                    <span class="verified-pill">Verified</span>
                </div>
                <div class="preview-tabs">
                    <span class="active">All</span>
                    <span>Dresses</span>
                    <span>Bags</span>
                </div>
                <div class="preview-product-grid">
                    <div class="preview-product-card">
                        <div class="preview-product-image one"></div>
                        <strong>Silk Midi Dress</strong>
                        <span>₦18,500</span>
                    </div>
                    <div class="preview-product-card">
                        <div class="preview-product-image two"></div>
                        <strong>Mini Tote Bag</strong>
                        <span>₦12,000</span>
                    </div>
                </div>
            </div>
            <div class="preview-whatsapp">
                <div class="whatsapp-top">WhatsApp order</div>
                <pre>New Order #1024

Customer: Ada
Phone: 08012345678

Items:
1x Silk Midi Dress - ₦18,500
1x Mini Tote Bag - ₦12,000

Delivery: ₦2,000
Total: ₦32,500</pre>
            </div>
        </div>
    </section>

    <!-- How It Works (Grid) -->
    <section id="how-it-works" class="clean-section bg-gray">
        <div class="section-header-clean">
            <h2 class="section-title-clean">From scattered DMs to clean orders</h2>
            <p class="section-desc-clean">Storelo keeps the WhatsApp selling style your customers already know, but gives them a proper shopping flow first.</p>
        </div>
        
        <div class="clean-grid">
            <div class="minimal-card text-center">
                <div class="icon-circle">1</div>
                <h3>Create your store link</h3>
                <p>Set your shop name, WhatsApp number, logo, payment instructions, and delivery zones.</p>
            </div>
            <div class="minimal-card text-center">
                <div class="icon-circle">2</div>
                <h3>Upload products</h3>
                <p>Add photos, prices, stock, categories, discounts, and products your buyers can browse anytime.</p>
            </div>
            <div class="minimal-card text-center">
                <div class="icon-circle">3</div>
                <h3>Receive WhatsApp orders</h3>
                <p>Customers checkout through Storelo, then send you a complete WhatsApp order summary.</p>
            </div>
        </div>
    </section>

    <!-- Seller Pain Points -->
    <section class="clean-section">
        <div class="split-proof-section">
            <div>
                <div class="badge-subtle">Built for real sellers</div>
                <h2 class="section-title-clean text-left">Stop answering the same product questions all day.</h2>
                <p class="section-desc-clean text-left">Your buyers want prices, photos, delivery fees, stock status, and payment details. Storelo puts those answers in one link before the chat starts.</p>
            </div>
            <div class="seller-pain-list">
                <div><strong>Before</strong><span>“Price?” “Still available?” “Send pictures?” “Where are you located?”</span></div>
                <div><strong>After</strong><span>Customers browse, add to cart, choose delivery, and message you with the full order.</span></div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="clean-section bg-gray">
        <div class="section-header-clean">
            <h2 class="section-title-clean">Everything needed for WhatsApp commerce</h2>
            <p class="section-desc-clean">Simple enough for a small seller, organized enough to stop losing orders in chat.</p>
        </div>

        <div class="feature-bento-clean">
            <div class="minimal-card feature-item-tall">
                <div class="feature-content-clean">
                    <h4>Clean checkout messages</h4>
                    <p>Every order includes customer details, items, quantities, delivery fee, discount, total, and payment instructions.</p>
                </div>
            </div>
            <div class="minimal-card feature-item-tall">
                <div class="feature-content-clean">
                    <h4>Seller dashboard</h4>
                    <p>Manage products, stock, orders, categories, customers, coupons, shipping zones, and store profile from one place.</p>
                </div>
            </div>
            <div class="minimal-card feature-item-wide">
                <div class="feature-content-clean">
                    <h4>Shareable storefronts</h4>
                    <p>Give customers one public link they can open from WhatsApp, Instagram, TikTok, QR codes, or your bio.</p>
                </div>
                <div class="feature-visual-clean">
                    <div class="share-card">
                        <span>storelo.page.gd/shop/sadescloset</span>
                        <div class="share-buttons">
                            <button>Copy Link</button>
                            <button>QR Code</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="clean-section">
        <div class="section-header-clean">
            <h2 class="section-title-clean">Questions sellers ask first</h2>
            <p class="section-desc-clean">Storelo is intentionally simple: your catalog lives online, your closing conversation stays on WhatsApp.</p>
        </div>
        <div class="faq-grid">
            <div class="faq-item">
                <h3>Do I need Paystack or Flutterwave?</h3>
                <p>No. Storelo can show your payment instructions and send the order to WhatsApp so you confirm payment manually.</p>
            </div>
            <div class="faq-item">
                <h3>Can customers buy without downloading an app?</h3>
                <p>Yes. They open your store link in their browser, add items to cart, and continue to WhatsApp at checkout.</p>
            </div>
            <div class="faq-item">
                <h3>Can I manage stock?</h3>
                <p>Yes. Add quantities, update availability, and prevent customers from ordering more than you have.</p>
            </div>
            <div class="faq-item">
                <h3>Is this only for fashion sellers?</h3>
                <p>No, but it is especially useful for WhatsApp and Instagram sellers who need organized product browsing and order messages.</p>
            </div>
        </div>
    </section>

    </section>

    <!-- Latest Blog Posts -->
    <?php if (!empty($latest_posts)): ?>
    <section class="clean-section bg-gray" style="padding-top: 60px; padding-bottom: 60px;">
        <div class="section-header-clean" style="margin-bottom: 40px; text-align: center;">
            <h2 class="section-title-clean">Latest from the Storelo Blog</h2>
            <p class="section-desc-clean">Tips, strategies, and guides to grow your online business.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <?php foreach ($latest_posts as $post): ?>
                <a href="<?= BASE_URL ?>/blog/<?= e($post['slug']) ?>" class="minimal-card" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; padding: 0; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;">
                    <?php if ($post['featured_image']): ?>
                        <img src="<?= BASE_URL . e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" style="width: 100%; height: 180px; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 180px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        </div>
                    <?php endif; ?>
                    <div style="padding: 24px; display: flex; flex-direction: column; flex: 1;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;"><?= date('M d, Y', strtotime($post['created_at'])) ?> • <?= e($post['author_name']) ?></span>
                        <h3 style="font-size: 1.15rem; margin-bottom: 12px; color: #0f172a; line-height: 1.4;"><?= e($post['title']) ?></h3>
                        <p style="font-size: 0.9rem; color: #475569; margin: 0; line-height: 1.6; flex: 1;"><?= e(mb_strimwidth($post['excerpt'], 0, 100, '...')) ?></p>
                        <span style="margin-top: 16px; font-weight: 600; font-size: 0.9rem; color: var(--accent); display: inline-flex; align-items: center; gap: 4px;">Read &rarr;</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="<?= BASE_URL ?>/blog" class="btn-outline">View All Articles</a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Final CTA -->
    <section class="clean-section cta-banner">
        <div class="cta-content">
            <h2>Turn your WhatsApp sales into a proper store flow.</h2>
            <p>Start with a free storefront, add your products, and share one link with your buyers today.</p>
            <?php if ($is_logged_in): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="btn-vibrant btn-xl">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/register" class="btn-vibrant btn-xl">Create Your Free Store</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="clean-footer" style="padding: 40px 20px; text-align: center; border-top: 1px solid var(--border-light);">
        <div style="margin-bottom: 16px; display: flex; justify-content: center; gap: 24px;">
            <a href="<?= BASE_URL ?>/blog" style="color: var(--text-muted); text-decoration: none; font-weight: 500;">Blog</a>
            <a href="<?= BASE_URL ?>/login" style="color: var(--text-muted); text-decoration: none; font-weight: 500;">Login</a>
        </div>
        <p style="color: var(--text-muted); font-size: 0.9rem;">&copy; <?= date('Y') ?> Storelo. Premium storefronts for WhatsApp sellers.</p>
    </footer>
</body>
</html>
