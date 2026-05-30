<?php
// views/home.php — Storelo landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storelo — Create Your WhatsApp Thrift Store</title>
    <meta name="description" content="Launch your online thrift store in 2 minutes. Let customers browse, add to cart, and checkout directly to your WhatsApp.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <!-- Nav -->
    <nav style="display:flex; justify-content:space-between; align-items:center; padding:20px 32px; border-bottom:1px solid var(--border-subtle);">
        <span style="font-size:1.4rem; font-weight:800; background:var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">Storelo</span>
        <div style="display:flex; gap:12px;">
            <a href="<?= BASE_URL ?>/login" class="btn-secondary btn-sm">Login</a>
            <a href="<?= BASE_URL ?>/register" class="btn-primary btn-sm">Create Store</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="landing-hero">
        <h1>Your thrift store,<br>one link away</h1>
        <p class="subtitle">
            Create a beautiful product catalog, share a single link on WhatsApp, and let your customers add items to their cart and checkout — straight into your DMs.
        </p>
        <div class="landing-cta">
            <a href="<?= BASE_URL ?>/register" class="btn-primary" style="padding:14px 36px; font-size:1.05rem;">Create Your Store — Free</a>
            <a href="#how-it-works" class="btn-secondary" style="padding:14px 36px; font-size:1.05rem;">See How It Works</a>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="steps-section">
        <h2 style="text-align:center; font-size:1.8rem;">How it works</h2>
        <div class="steps-grid">
            <div class="glass-card step-card">
                <div class="step-icon">🏪</div>
                <h3>1. Create Your Store</h3>
                <p>Sign up, pick a username, and set your WhatsApp number. Your store is live instantly.</p>
            </div>
            <div class="glass-card step-card">
                <div class="step-icon">📸</div>
                <h3>2. Upload Products</h3>
                <p>Add photos, prices, and descriptions for your thrift items. Mark items as sold when they go.</p>
            </div>
            <div class="glass-card step-card">
                <div class="step-icon">💬</div>
                <h3>3. Share & Sell on WhatsApp</h3>
                <p>Send your store link to customers. They browse, add to cart, and checkout directly to your WhatsApp.</p>
            </div>
        </div>
    </section>

    <!-- Why Storelo -->
    <section style="max-width:900px; margin:0 auto; padding:60px 24px 80px;">
        <h2 style="text-align:center; font-size:1.6rem; margin-bottom:40px;">Built for thrift sellers</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px;">
            <div class="glass-card no-hover" style="padding:24px;">
                <h4 style="margin-bottom:8px;">🏷️ One-of-a-Kind Inventory</h4>
                <p style="color:var(--text-secondary); font-size:0.9rem;">Items are auto-marked as "SOLD" once ordered — no double-selling.</p>
            </div>
            <div class="glass-card no-hover" style="padding:24px;">
                <h4 style="margin-bottom:8px;">📱 Mobile-First Design</h4>
                <p style="color:var(--text-secondary); font-size:0.9rem;">Your customers shop from WhatsApp links on their phones — the store looks perfect on mobile.</p>
            </div>
            <div class="glass-card no-hover" style="padding:24px;">
                <h4 style="margin-bottom:8px;">💰 No Payment Gateway Needed</h4>
                <p style="color:var(--text-secondary); font-size:0.9rem;">Checkout goes to WhatsApp where you finalize payment, delivery, and any negotiation — the way thrift works.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="border-top:1px solid var(--border-subtle); padding:24px; text-align:center; color:var(--text-muted); font-size:0.85rem;">
        &copy; <?= date('Y') ?> Storelo. Built for thrift sellers.
    </footer>
</body>
</html>
