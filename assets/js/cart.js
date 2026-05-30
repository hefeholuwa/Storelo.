// assets/js/cart.js — Client-side shopping cart using localStorage

let cart = JSON.parse(localStorage.getItem('storelo_cart')) || [];

function saveCart() {
    localStorage.setItem('storelo_cart', JSON.stringify(cart));
    renderCart();
}

function addToCart(productId, name, price, image) {
    // For thrift items, quantity is typically 1 — but we allow increment for general use
    const existing = cart.find(item => item.id === productId);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: name,
            price: parseFloat(price),
            image: image,
            quantity: 1
        });
    }
    openCartDrawer();
    saveCart();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
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
                        <p>${currency}${item.price.toFixed(2)} × ${item.quantity}</p>
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
