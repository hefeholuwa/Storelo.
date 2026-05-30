// assets/js/cart.js — Client-side shopping cart using localStorage

let cart = JSON.parse(localStorage.getItem('storelo_cart')) || [];

function saveCart() {
    localStorage.setItem('storelo_cart', JSON.stringify(cart));
    renderCart();
}

function addToCart(productId, name, price, image, qtyToAdd = 1, maxStock = 1) {
    const existing = cart.find(item => item.id === productId);
    
    if (existing) {
        let newQty = existing.quantity + qtyToAdd;
        if (newQty > maxStock) {
            alert(`Sorry, you cannot add more. Only ${maxStock} items available in stock.`);
            existing.quantity = maxStock;
        } else {
            existing.quantity = newQty;
        }
    } else {
        if (qtyToAdd > maxStock) qtyToAdd = maxStock;
        cart.push({
            id: productId,
            name: name,
            price: parseFloat(price),
            image: image,
            quantity: qtyToAdd,
            maxStock: maxStock
        });
    }
    
    saveCart();
    openCartDrawer();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
}

function changeQuantity(id, change) {
    let item = cart.find(i => i.id === id);
    if (!item) return;

    item.quantity += change;
    if (item.quantity > item.maxStock) {
        alert(`Only ${item.maxStock} items available in stock.`);
        item.quantity = item.maxStock;
    }
    if (item.quantity <= 0) {
        cart = cart.filter(i => i.id !== id);
    }
    saveCart();
}

function clearCart() {
    cart = [];
    saveCart();
}

function getCartTotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

function getCartCount() {
    return cart.reduce((acc, item) => acc + item.quantity, 0);
}

// ── Drawer open/close ───────────────────────────────────────

function openCartDrawer() {
    document.getElementById('cart-drawer').classList.add('open');
    document.getElementById('cart-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeCartDrawer() {
    document.getElementById('cart-drawer').classList.remove('open');
    document.getElementById('cart-overlay').classList.remove('open');
    document.body.style.overflow = '';
}

// ── Render ───────────────────────────────────────────────────

function renderCart() {
    const countEl = document.getElementById('cart-count');
    const itemsEl = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const currencyEl = document.getElementById('cart-currency');
    const currency = currencyEl ? currencyEl.value : '';

    if (countEl) countEl.textContent = getCartCount();

    if (itemsEl) {
        if (cart.length === 0) {
            itemsEl.innerHTML = '<p style="text-align:center; color:var(--text-muted); padding:40px 0;">Your cart is empty.</p>';
        } else {
            itemsEl.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <img src="${item.image}" alt="${item.name}">
                    <div class="cart-item-info">
                        <h5>${item.name}</h5>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                            <span style="font-weight:600; color:var(--accent);">${currency}${item.price.toFixed(2)}</span>
                            <div class="qty-selector" style="display:flex; align-items:center; border:1px solid var(--border-subtle); border-radius:4px; overflow:hidden; background:var(--glass-bg);">
                                <button type="button" onclick="changeQuantity(${item.id}, -1)" style="background:none; border:none; color:var(--text-color); padding:2px 6px; cursor:pointer;">-</button>
                                <span style="font-size:0.85rem; width:20px; text-align:center;">${item.quantity}</span>
                                <button type="button" onclick="changeQuantity(${item.id}, 1)" style="background:none; border:none; color:var(--text-color); padding:2px 6px; cursor:pointer;">+</button>
                            </div>
                        </div>
                    </div>
                    <button class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Remove">&times;</button>
                </div>
            `).join('');
        }
    }

    if (totalEl) totalEl.textContent = getCartTotal().toFixed(2);
}

// ── Checkout Modal ──────────────────────────────────────────

function openCheckoutModal() {
    if (cart.length === 0) {
        alert('Your cart is empty. Add some items first.');
        return;
    }
    document.getElementById('cart-data-input').value = JSON.stringify(cart);
    document.getElementById('checkout-modal').classList.add('active');
    closeCartDrawer();
}

function closeCheckoutModal() {
    document.getElementById('checkout-modal').classList.remove('active');
}

// ── Init ─────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    renderCart();
});
