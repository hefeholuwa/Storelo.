// assets/js/cart.js — Client-side shopping cart using localStorage

const storeUsername = window.location.pathname.split('/')[2];
const cartKey = 'storelo_cart_' + storeUsername;
let cart = JSON.parse(localStorage.getItem(cartKey)) || [];

function saveCart() {
    localStorage.setItem(cartKey, JSON.stringify(cart));
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
    
    // Instead of opening drawer, maybe show a quick alert or toast
    // For Jumia feel, we just update the cart badge and maybe redirect to cart page
    // alert("Item added to cart!");
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    saveCart();
}

function changeQuantity(id, change) {
    let item = cart.find(i => i.id == id);
    if (!item) return;

    item.quantity += change;
    if (item.quantity > item.maxStock) {
        alert(`Only ${item.maxStock} items available in stock.`);
        item.quantity = item.maxStock;
    }
    if (item.quantity <= 0) {
        cart = cart.filter(i => i.id != id);
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

// ── Render ───────────────────────────────────────────────────

function renderCart() {
    const countBadge = document.getElementById('cart-count-badge');
    const pageItemsEl = document.getElementById('cart-page-items');
    const pageSummaryEl = document.getElementById('cart-page-summary');
    const currencyEl = document.getElementById('cart-currency');
    const currency = currencyEl ? currencyEl.value : '';

    if (countBadge) countBadge.textContent = getCartCount();

    const headerBadge = document.getElementById('header-cart-badge');
    if (headerBadge) {
        headerBadge.innerText = getCartCount();
        headerBadge.style.display = getCartCount() > 0 ? 'flex' : 'none';
    }

    if (pageItemsEl) {
        if (cart.length === 0) {
            pageItemsEl.innerHTML = `
                <div class="empty-cart-view">
                    <div class="empty-cart-icon">🛒</div>
                    <h3 style="font-size: 1.25rem; color: #111827; margin-bottom: 8px;">Your cart is empty!</h3>
                    <p style="color: #6b7280; margin-bottom: 24px;">Browse our categories and discover our best deals!</p>
                    <a href="/shop/${storeUsername}" class="btn-primary" style="padding: 12px 32px; font-weight: 600; text-transform: uppercase; border-radius: 4px; box-shadow: 0 4px 12px rgba(246, 139, 30, 0.3);">Start Shopping</a>
                </div>
            `;
            if (pageSummaryEl) pageSummaryEl.style.display = 'none';
        } else {
            pageItemsEl.innerHTML = cart.map(item => `
                <div style="display: flex; gap: 16px; padding-bottom: 24px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 24px;">
                    <img src="${item.image}" alt="${item.name}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
                    <div style="flex: 1;">
                        <h4 style="font-size: 1rem; color: #111827; margin-bottom: 4px;">${item.name}</h4>
                        ${item.variant ? `<p style="font-size: 0.8rem; color: #6b7280; margin: 0 0 8px 0; font-style: italic;">${item.variant}</p>` : ''}
                        <div style="font-size: 1.1rem; font-weight: 800; color: #111827; margin-bottom: 16px;">${currency}${item.price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button onclick="removeFromCart(${typeof item.id === 'string' ? `'${item.id}'` : item.id})" style="background: none; border: none; color: #F68B1E; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <span style="font-size: 1.1rem;">🗑</span> Remove
                            </button>
                            
                            <div style="display: flex; align-items: center; border: 1px solid var(--border-subtle); border-radius: 4px; overflow: hidden;">
                                <button type="button" onclick="changeQuantity(${typeof item.id === 'string' ? `'${item.id}'` : item.id}, -1)" style="background: #F68B1E; border: none; color: white; padding: 4px 12px; font-weight: bold; cursor: pointer;">-</button>
                                <span style="width: 32px; text-align: center; font-size: 0.95rem; font-weight: 600;">${item.quantity}</span>
                                <button type="button" onclick="changeQuantity(${typeof item.id === 'string' ? `'${item.id}'` : item.id}, 1)" style="background: #F68B1E; border: none; color: white; padding: 4px 12px; font-weight: bold; cursor: pointer;">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            if (pageSummaryEl) pageSummaryEl.style.display = 'block';
            
            const totalEl = document.getElementById('cart-page-total');
            const btnTotalEl = document.getElementById('cart-page-btn-total');
            if (totalEl) totalEl.textContent = getCartTotal().toFixed(2);
            if (btnTotalEl) btnTotalEl.textContent = getCartTotal().toFixed(2);
        }
    }
}

// ── Init ─────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    renderCart();
});
