<?php
// views/store/cart.php — Dedicated Cart Page
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = DB::connect();

// Fetch seller by username
$stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
$stmt->execute([$shop_username]);
$seller = $stmt->fetch();

if (!$seller || !empty($seller['is_deleted'])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Store Not Found</title>    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>';
    echo '<body style="background:#F9FAFB;color:#111827;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div><h1 style="font-size:2rem;margin-bottom:10px;">Store Not Found</h1><p style="color:#6B7280;">This shop doesn\'t exist or has been closed.</p><a href="' . BASE_URL . '/" style="color:#2563EB;">Go Home</a></div></body></html>';
    exit;
}

if (!empty($seller['is_suspended'])) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Store Unavailable</title>    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>';
    echo '<body style="background:#F3F4F6;color:#111827;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div style="background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.05);max-width:400px;width:100%;"><h1 style="font-size:1.5rem;margin-bottom:10px;color:#dc2626;">Store Unavailable</h1><p style="color:#6b7280;margin-bottom:24px;">This store is currently unavailable or has been suspended.</p><a href="' . BASE_URL . '/" style="display:inline-block;padding:10px 20px;background:#3b82f6;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Go to Storelo</a></div></body></html>';
    exit;
}

$theme_color = $seller['theme_color'] ?? '#8B5CF6';
$currency = $seller['currency'] ?? '₦';

// Fetch recent products for "Recently Viewed" section
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? AND status = 'active' ORDER BY id DESC LIMIT 4");
$stmt->execute([$seller['id']]);
$recent_products = $stmt->fetchAll();

// Fetch shipping zones
$stmt = $db->prepare("SELECT * FROM shipping_zones WHERE seller_id = ? ORDER BY name ASC");
$stmt->execute([$seller['id']]);
$shipping_zones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart — <?= e($seller['shop_name']) ?> — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="<?= BASE_URL ?>/assets/js/cart.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Standard Website CSS */
        :root {
            --brand: <?= e($theme_color) ?>;
            --brand-dark: color-mix(in srgb, var(--brand) 80%, black);
            --brand-glow: color-mix(in srgb, var(--brand) 40%, transparent);
            --bg: #FAFAFA;
            --card-bg: #FFFFFF;
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
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }

        /* Standard Header */
        .site-header {
            background: rgba(250, 250, 250, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light);
            position: sticky; top: 0; z-index: 1000;
        }
        
        .header-top {
            max-width: var(--max-width); margin: 0 auto; padding: 12px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }

        .logo-area { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--text-main); flex-shrink: 0; }
        .logo-text { font-size: 1.2rem; font-weight: 800; margin: 0; letter-spacing: -0.02em; }

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

        /* Cart Page Layout */
        .cart-page-container {
            max-width: var(--max-width); margin: 32px auto 60px auto; padding: 0 20px;
            display: flex; gap: 24px; align-items: flex-start;
        }
        .cart-main {
            flex: 1; background: #fff; border-radius: 8px; border: 1px solid var(--border-light);
            padding: 24px; min-height: 400px;
        }
        .cart-summary {
            width: 340px; background: #fff; border-radius: 8px; border: 1px solid var(--border-light);
            padding: 24px; position: sticky; top: 100px;
        }

        @media (max-width: 768px) {
            .cart-page-container { flex-direction: column; }
            .cart-summary { width: 100%; position: static; box-sizing: border-box; }
            .header-top { padding: 12px 16px; }
            .logo-text { font-size: 1.1rem; }
            .cart-main { padding: 16px; width: 100%; box-sizing: border-box; }
        }

        .btn-brand {
            width: 100%; background: var(--brand); color: #fff; border: none; border-radius: 6px;
            padding: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .btn-brand:hover { background: var(--brand-dark); }
        .btn-outline {
            background: #fff; color: var(--brand); border: 1px solid var(--brand); border-radius: 6px;
            padding: 8px 16px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;
        }

        .checkout-form-container {
            margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-light); display: none;
        }
        
        .form-control {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 6px;
            font-size: 0.95rem; margin-bottom: 12px; box-sizing: border-box; outline: none;
        }
        .form-control:focus { border-color: var(--brand); }

        .qty-btn {
            background: #F3F4F6; border: 1px solid var(--border-light); color: var(--text-main);
            width: 28px; height: 28px; border-radius: 4px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-weight: bold;
        }
        .qty-btn:hover { background: #E5E7EB; }

        .recent-grid {
            display: grid; gap: 16px;
            grid-template-columns: 1fr;
        }
        @media (min-width: 640px) {
            .recent-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .recent-grid { grid-template-columns: repeat(4, 1fr); }
        }

        /* Site Footer */
        .site-footer {
            background: transparent; border-top: 1px solid var(--border-light); padding: 40px 20px;
            text-align: center; color: var(--text-mute); margin-top: auto;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <input type="hidden" id="cart-currency" value="<?= e($currency) ?>">

    <!-- 1. Proper Website Header -->
    <header class="site-header">
        <div class="header-top">
            <a href="<?= BASE_URL ?>/shop/<?= urlencode($seller['username']) ?>" class="logo-area" style="text-decoration: none;">
                <h1 class="logo-text" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                    <?= e($seller['shop_name']) ?>
                </h1>
            </a>
            
            <div style="flex:1;"></div>
            
            <div class="cart-area">
                <a href="<?= BASE_URL ?>/shop/<?= urlencode($seller['username']) ?>/cart" class="cart-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <span id="header-cart-badge" class="cart-badge" style="display:none;">0</span>
                </a>
            </div>
        </div>
    </header>

    <div class="cart-page-container">
        <!-- Cart Items List -->
        <div class="cart-main">
            <h2 style="margin: 0 0 20px 0; font-size: 1.25rem; font-weight: 700;">Shopping Cart</h2>
            <div id="cart-page-items">
                <!-- Rendered by JS -->
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="cart-summary" id="cart-page-summary" style="display:none;">
            <h3 style="font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid var(--border-light); padding-bottom: 12px; margin: 0 0 16px 0;">Order Summary</h3>
            
            <div style="display: flex; justify-content: space-between; font-weight: 500; font-size: 0.95rem; margin-bottom: 8px; color: var(--text-mute);">
                <span>Subtotal</span>
                <span style="color: var(--text-main);"><?= $currency ?><span id="cart-page-subtotal">0.00</span></span>
            </div>

            <div id="discount-row" style="display: none; justify-content: space-between; font-weight: 500; font-size: 0.95rem; margin-bottom: 8px; color: #10B981;">
                <span>Discount (<span id="applied-promo-code"></span>)</span>
                <span>-<?= $currency ?><span id="cart-page-discount">0.00</span></span>
            </div>

            <div id="shipping-fee-row" style="display: none; justify-content: space-between; font-weight: 500; font-size: 0.95rem; margin-bottom: 8px; color: var(--text-mute);">
                <span>Shipping</span>
                <span style="color: var(--text-main);">+<?= $currency ?><span id="cart-page-shipping">0.00</span></span>
            </div>

            <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.25rem; margin: 16px 0; border-top: 1px solid var(--border-light); padding-top: 16px;">
                <span>Total</span>
                <span><?= $currency ?><span id="cart-page-total">0.00</span></span>
            </div>

            <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                <input type="text" id="promo-code-input" class="form-control" placeholder="Promo code" style="margin-bottom:0; text-transform: uppercase; font-size: 0.9rem;">
                <button onclick="applyPromoCode()" class="btn-outline" id="promo-btn" style="padding: 0 16px;">Apply</button>
            </div>
            <div id="promo-msg" style="font-size: 0.85rem; margin-top: -8px; margin-bottom: 16px; display: none;"></div>

            <button onclick="showCheckoutForm()" class="btn-brand">Checkout (<?= $currency ?><span id="cart-page-btn-total">0.00</span>)</button>

            <!-- Checkout Form -->
            <div id="inline-checkout-form" class="checkout-form-container">
                <h4 style="margin: 0 0 16px 0; font-size: 1.1rem;">Delivery Details</h4>
                <form method="POST" action="<?= BASE_URL ?>/shop/<?= e($seller['username']) ?>/checkout" id="final-checkout-form" target="_blank">
                    <input type="hidden" name="cart_data" id="cart-data-input">
                    <input type="hidden" name="applied_promo" id="applied-promo-input">

                    <input type="text" name="customer_name" class="form-control" placeholder="Full name" required>
                    <input type="text" name="customer_phone" class="form-control" placeholder="Phone Number (e.g. 08031234567)" required>
                    
                    <?php if (!empty($shipping_zones)): ?>
                        <select name="shipping_zone_id" id="shipping-zone-select" class="form-control" required onchange="calculateShippingFee()">
                            <option value="" data-fee="0">Select Delivery Area</option>
                            <?php foreach ($shipping_zones as $zone): ?>
                                <option value="<?= $zone['id'] ?>" data-fee="<?= $zone['fee'] ?>"><?= e($zone['name']) ?> - <?= $currency ?><?= number_format($zone['fee'], 2) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    
                    <textarea name="delivery_address" class="form-control" placeholder="Detailed Delivery Address" rows="3" required></textarea>

                    <button type="submit" class="btn-brand" style="background: #25D366;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px; vertical-align:text-bottom;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                        Order via WhatsApp
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recently Viewed (Mocked as Recent Products) -->
    <?php if (!empty($recent_products)): ?>
    <div style="max-width: var(--max-width); margin: 0 auto 60px auto; padding: 0 20px;">
        <div style="background: #fff; border: 1px solid var(--border-light); border-radius: 8px; padding: 24px;">
            <h3 style="font-size: 1.1rem; margin: 0 0 20px 0; font-weight: 700;">You might also like</h3>
            <div class="recent-grid">
                <?php foreach ($recent_products as $p): 
                    $paths = json_decode($p['image_paths'], true) ?: [];
                    $cover_image = !empty($paths) ? $paths[0] : 'assets/images/placeholder.png';
                ?>
                    <a href="<?= BASE_URL ?>/shop/<?= urlencode($seller['username']) ?>" style="text-decoration:none; color:inherit; display:flex; gap:12px; align-items:center; border:1px solid var(--border-light); padding:8px; border-radius:6px; transition:border-color 0.2s;">
                        <img src="<?= BASE_URL ?>/<?= $cover_image ?>" style="width:60px; height:60px; object-fit:cover; border-radius:4px; flex-shrink:0;" alt="<?= e($p['name']) ?>">
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-size:0.9rem; font-weight:500; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($p['name']) ?></div>
                            <div style="font-size:0.95rem; font-weight:700; color:var(--brand); margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?= $currency ?><?= number_format($p['price'], 2) ?>
                                <?php if(isset($p['original_price']) && $p['original_price'] > 0): ?>
                                    <span style="font-size:0.8rem; color:var(--text-mute); text-decoration:line-through; margin-left:6px; font-weight:normal;">
                                        <?= $currency ?><?= number_format($p['original_price'], 2) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <footer class="site-footer">
        <div style="max-width: var(--max-width); margin: 0 auto; text-align: center;">
            <p style="margin:0; font-size:0.9rem;">&copy; <?= date('Y') ?> <?= e($seller['shop_name']) ?>. All rights reserved.</p>
            <p style="margin:8px 0 0 0; font-size:0.8rem; color:var(--text-mute);">
                Powered by <strong>Storelo</strong> &bull; Built by <strong>Evermark Innovations</strong>
            </p>
        </div>
    </footer>

    <script>
        // Update header badge
        const originalUpdateBadge = window.updateCartBadge;
        if(typeof window.updateCartBadge !== 'undefined') {
            window.updateCartBadge = function(count) {
                originalUpdateBadge(count);
                const headerBadge = document.getElementById('header-cart-badge');
                if(headerBadge) {
                    headerBadge.innerText = count;
                    headerBadge.style.display = count > 0 ? 'flex' : 'none';
                }
            };
        } else {
            function updateCartBadge(count) {
                const headerBadge = document.getElementById('header-cart-badge');
                if(headerBadge) {
                    headerBadge.innerText = count;
                    headerBadge.style.display = count > 0 ? 'flex' : 'none';
                }
            }
            window.updateCartBadge = updateCartBadge;
        }

        function updateCartSummaryPage() {
            const container = document.getElementById('cart-page-items');
            if (!container) return;
            
            container.innerHTML = '';
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding: 60px 20px; color: var(--text-mute);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:16px; opacity:0.5;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        <div style="font-size:1.1rem; margin-bottom:24px;">Your cart is currently empty.</div>
                        <a href="<?= BASE_URL ?>/shop/<?= e($seller['username']) ?>" class="btn-brand" style="display:inline-block; width:auto; padding:10px 24px; text-decoration:none;">Return to Shop</a>
                    </div>
                `;
                document.getElementById('cart-page-summary').style.display = 'none';
                if(window.updateCartBadge) window.updateCartBadge(0);
                return;
            }
            
            document.getElementById('cart-page-summary').style.display = 'block';
            let subtotal = 0;
            let count = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = parseFloat(item.price) * item.quantity;
                subtotal += itemTotal;
                count += item.quantity;
                container.innerHTML += `
                    <div style="display:flex; gap: 16px; padding: 16px; border: 1px solid var(--border-light); border-radius: 8px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
                        <img src="<?= BASE_URL ?>/${item.image}" alt="${item.name}" style="width:80px; height:80px; object-fit:cover; border-radius:6px; background:#F9FAFB;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight: 500; font-size:1rem; color:var(--text-main); margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.name}</div>
                            <div style="color:var(--text-mute); font-size:0.9rem;"><?= $currency ?>${parseFloat(item.price).toFixed(2)}</div>
                            <div style="margin-top:12px; display:flex; align-items:center; gap:20px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <button onclick="updateQty(${index}, -1)" class="qty-btn" style="width:28px;height:28px;">-</button>
                                    <span style="font-weight:500; width:24px; text-align:center;">${item.quantity}</span>
                                    <button onclick="updateQty(${index}, 1)" class="qty-btn" style="width:28px;height:28px;">+</button>
                                </div>
                                <button onclick="removeFromCart(${index}); updateCartSummaryPage();" style="background:none; border:none; color:#EF4444; cursor:pointer; font-size:0.9rem; font-weight:500; padding:0;">Remove</button>
                            </div>
                        </div>
                        <div style="font-weight:700; font-size:1.1rem; color:var(--text-main); text-align:right; width: 100%; min-width: max-content;">
                            Total: <?= $currency ?>${itemTotal.toFixed(2)}
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('cart-page-subtotal').innerText = subtotal.toFixed(2);
            calculateFinalTotal(subtotal);
            if(window.updateCartBadge) window.updateCartBadge(count);
        }

        let appliedDiscount = 0;
        let discountType = '';
        let discountValue = 0;

        function applyPromoCode() {
            const code = document.getElementById('promo-code-input').value.trim().toUpperCase();
            const msg = document.getElementById('promo-msg');
            const btn = document.getElementById('promo-btn');
            
            if (!code) return;
            
            btn.innerText = '...';
            btn.disabled = true;

            fetch('<?= BASE_URL ?>/api/apply_coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, seller_id: <?= $seller['id'] ?> })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerText = 'Apply';
                btn.disabled = false;
                msg.style.display = 'block';
                
                if (data.success) {
                    discountType = data.discount_type;
                    discountValue = data.discount_value;
                    document.getElementById('applied-promo-code').innerText = code;
                    document.getElementById('applied-promo-input').value = code;
                    msg.style.color = '#10B981';
                    msg.innerText = 'Promo code applied!';
                    
                    const subtotalText = document.getElementById('cart-page-subtotal').innerText;
                    calculateFinalTotal(parseFloat(subtotalText));
                } else {
                    msg.style.color = '#EF4444';
                    msg.innerText = data.error;
                    removePromo();
                }
            })
            .catch(err => {
                btn.innerText = 'Apply';
                btn.disabled = false;
                console.error(err);
            });
        }

        function removePromo() {
            discountType = '';
            discountValue = 0;
            document.getElementById('applied-promo-input').value = '';
            document.getElementById('promo-code-input').value = '';
            const subtotalText = document.getElementById('cart-page-subtotal').innerText;
            calculateFinalTotal(parseFloat(subtotalText));
        }

        let shippingFee = 0;

        function calculateShippingFee() {
            const select = document.getElementById('shipping-zone-select');
            if (select) {
                const selectedOption = select.options[select.selectedIndex];
                shippingFee = parseFloat(selectedOption.getAttribute('data-fee') || 0);
                
                if (shippingFee > 0) {
                    document.getElementById('shipping-fee-row').style.display = 'flex';
                    document.getElementById('cart-page-shipping').innerText = shippingFee.toFixed(2);
                } else {
                    document.getElementById('shipping-fee-row').style.display = 'none';
                }
                
                const subtotalText = document.getElementById('cart-page-subtotal').innerText;
                calculateFinalTotal(parseFloat(subtotalText));
            }
        }

        function calculateFinalTotal(subtotal) {
            let discountAmount = 0;
            if (discountType === 'percentage') {
                discountAmount = subtotal * (discountValue / 100);
            } else if (discountType === 'fixed') {
                discountAmount = discountValue;
            }

            if (discountAmount > subtotal) discountAmount = subtotal;

            appliedDiscount = discountAmount;

            if (discountAmount > 0) {
                document.getElementById('discount-row').style.display = 'flex';
                document.getElementById('cart-page-discount').innerText = discountAmount.toFixed(2);
            } else {
                document.getElementById('discount-row').style.display = 'none';
            }

            const finalTotal = subtotal - discountAmount + shippingFee;
            
            document.getElementById('cart-page-total').innerText = finalTotal.toFixed(2);
            document.getElementById('cart-page-btn-total').innerText = finalTotal.toFixed(2);
        }

        function showCheckoutForm() {
            document.getElementById('inline-checkout-form').style.display = 'block';
            document.getElementById('cart-data-input').value = JSON.stringify(cart);
            document.getElementById('inline-checkout-form').scrollIntoView({behavior: "smooth"});
        }

        const originalUpdateQty = window.updateQty;
        window.updateQty = function(index, change) {
            originalUpdateQty(index, change);
            updateCartSummaryPage();
        };

        document.addEventListener('DOMContentLoaded', updateCartSummaryPage);
        
        document.getElementById('final-checkout-form')?.addEventListener('submit', function(e) {
            document.getElementById('cart-data-input').value = JSON.stringify(cart);
            
            // Clear cart in this original tab after a slight delay to allow form submission to process
            setTimeout(function() {
                localStorage.removeItem('storelo_cart_<?= e($seller['username']) ?>');
                cart = [];
                updateCartSummaryPage();
            }, 800);
        });
    </script>
</body>
</html>
