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
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Store Not Found</title></head>';
    echo '<body style="background:#0b0f19;color:#f3f4f6;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">';
    echo '<div><h1 style="font-size:3rem;margin-bottom:10px;">Store Not Found</h1><p style="color:#9ca3af;">This shop doesn\'t exist.</p><a href="' . BASE_URL . '/" style="color:#818cf8;">Go Home</a></div></body></html>';
    exit;
}

$currency = $seller['currency'] ?? '₦';

// Fetch products (available and sold — hidden are excluded)
$stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? AND status != 'hidden' ORDER BY FIELD(status, 'available', 'sold'), id DESC");
$stmt->execute([$seller['id']]);
$products = $stmt->fetchAll();

// Get unique categories for filtering
$categories = array_unique(array_filter(array_column($products, 'category')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seller['shop_name']) ?> — Storelo</title>
    <meta name="description" content="<?= e($seller['shop_description'] ?? 'Browse products and order via WhatsApp.') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="<?= BASE_URL ?>/assets/js/cart.js" defer></script>
</head>
<body>
    <!-- Hidden input for currency symbol used by cart.js -->
    <input type="hidden" id="cart-currency" value="<?= e($currency) ?>">

    <!-- Store Header -->
    <header class="store-header">
        <div class="store-brand">
            <?php if (!empty($seller['logo_path'])): ?>
                <img src="<?= BASE_URL ?>/<?= $seller['logo_path'] ?>" alt="<?= e($seller['shop_name']) ?>">
            <?php endif; ?>
            <h2><?= e($seller['shop_name']) ?></h2>
        </div>
        <button onclick="openCartDrawer()" class="btn-primary btn-sm">
            🛒 Cart (<span id="cart-count">0</span>)
        </button>
    </header>

    <!-- Main Content -->
    <main style="max-width:1200px; margin:0 auto; padding:32px 20px;">

        <!-- Shop description -->
        <?php if (!empty($seller['shop_description'])): ?>
            <p style="color:var(--text-secondary); margin-bottom:8px; font-size:1rem;"><?= e($seller['shop_description']) ?></p>
        <?php endif; ?>
        <?php if (!empty($seller['delivery_info'])): ?>
            <p style="color:var(--text-muted); margin-bottom:24px; font-size:0.9rem;">🚚 <?= e($seller['delivery_info']) ?></p>
        <?php endif; ?>

        <!-- Category Filter -->
        <?php if (!empty($categories)): ?>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:28px;">
                <button class="btn-secondary btn-sm filter-btn active-filter" data-category="all" onclick="filterCategory('all', this)">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="btn-secondary btn-sm filter-btn" data-category="<?= e($cat) ?>" onclick="filterCategory('<?= e($cat) ?>', this)"><?= e($cat) ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Product Grid -->
        <?php if (empty($products)): ?>
            <div style="text-align:center; padding:80px 20px;">
                <p style="color:var(--text-muted); font-size:1.2rem;">This store has no products yet.</p>
            </div>
        <?php else: ?>
            <div class="product-grid" id="product-grid">
                <?php foreach ($products as $p): ?>
                    <div class="glass-card product-card" data-category="<?= e($p['category'] ?? '') ?>">
                        <img class="product-image" src="<?= BASE_URL ?>/<?= $p['image_path'] ?>" alt="<?= e($p['name']) ?>">

                        <?php if ($p['status'] === 'sold'): ?>
                            <div class="sold-overlay"><span>SOLD</span></div>
                        <?php endif; ?>

                        <div class="product-name"><?= e($p['name']) ?></div>
                        <?php if ($p['category']): ?>
                            <small style="color:var(--text-muted);"><?= e($p['category']) ?></small>
                        <?php endif; ?>
                        <?php if ($p['description']): ?>
                            <p style="color:var(--text-secondary); font-size:0.85rem; margin:6px 0; max-height:40px; overflow:hidden;"><?= e($p['description']) ?></p>
                        <?php endif; ?>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
                            <span class="product-price"><?= $currency ?><?= number_format($p['price'], 2) ?></span>
                            <?php if ($p['status'] === 'available'): ?>
                                <button onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes(e($p['name'])) ?>', <?= $p['price'] ?>, '<?= BASE_URL ?>/<?= $p['image_path'] ?>')" class="btn-primary btn-sm">Add</button>
                            <?php else: ?>
                                <span class="badge badge-danger">Sold</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Cart Overlay -->
    <div id="cart-overlay" class="cart-overlay" onclick="closeCartDrawer()"></div>

    <!-- Cart Drawer -->
    <div id="cart-drawer" class="cart-drawer">
        <div class="cart-drawer-header">
            <h3>🛒 Your Cart</h3>
            <button class="cart-close" onclick="closeCartDrawer()">&times;</button>
        </div>
        <div class="cart-items" id="cart-items"></div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span><?= $currency ?><span id="cart-total">0.00</span></span>
            </div>
            <button onclick="openCheckoutModal()" class="btn-primary" style="width:100%;">Proceed to Checkout</button>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkout-modal" class="modal-overlay">
        <div class="glass-card modal-content no-hover">
            <h3 style="margin-bottom:20px;">Checkout Details</h3>
            <form method="POST" action="<?= BASE_URL ?>/shop/<?= e($seller['username']) ?>/checkout">
                <input type="hidden" name="cart_data" id="cart-data-input">

                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="Full name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="customer_phone" class="form-control" placeholder="e.g. 08031234567" required>
                </div>
                <div class="form-group">
                    <label>Delivery Address</label>
                    <textarea name="delivery_address" class="form-control" placeholder="Where should this be delivered?" required></textarea>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary" style="flex:1;">Confirm Order</button>
                    <button type="button" onclick="closeCheckoutModal()" class="btn-secondary" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer style="border-top:1px solid var(--border-subtle); padding:20px; text-align:center; color:var(--text-muted); font-size:0.8rem; margin-top:60px;">
        Powered by <a href="<?= BASE_URL ?>/" style="color:var(--accent-light);">Storelo</a>
    </footer>

    <script>
        // Category filtering
        function filterCategory(category, btn) {
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active-filter'));
            btn.classList.add('active-filter');
        }
    </script>

    <style>
        .filter-btn.active-filter {
            background: rgba(99, 102, 241, 0.15);
            border-color: var(--accent);
            color: var(--accent-light);
        }
    </style>
</body>
</html>
