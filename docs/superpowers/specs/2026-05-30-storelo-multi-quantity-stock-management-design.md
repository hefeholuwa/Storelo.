# Design Spec: Multi-Quantity Stock Management System

## Overview
This document specifies the design for transitioning the e-commerce platform from a thrift-only (single-item) model to a general multi-quantity retail inventory model. 

## Architectural Changes

### 1. Database Schema Migration
We will run an ALTER script on the `products` table:
* Add a `stock` column: `stock INT UNSIGNED DEFAULT 1`.
* Repurpose the existing `status` column:
  * Current definition: `ENUM('available', 'sold', 'hidden')`
  * New definition: `ENUM('active', 'hidden') DEFAULT 'active'`
* Data Migration Rules:
  * If status was `'available'`, set `stock = 1` and `status = 'active'`.
  * If status was `'sold'`, set `stock = 0` and `status = 'active'`.
  * If status was `'hidden'`, set `stock = 1` and `status = 'hidden'`.

### 2. Admin Dashboard (`views/dashboard/products.php`)
* **Add Product Form**:
  * Include a "Stock" input field (`type="number"`, `min="0"`, `value="1"` by default).
  * Thrift items can remain as stock = 1, while gadgets/accessories can be set to higher quantities.
* **Edit/List View**:
  * Display the current stock count instead of binary status (e.g. "12 in stock", "Out of stock").
  * Update the status toggle button: it will toggle between "Active" and "Hidden".
  * If a product is active but has `stock = 0`, it displays an `OUT OF STOCK` badge.

### 3. Customer Storefront (`views/store/catalog.php`)
* **Product Card UI**:
  * Show a quantity selector containing:
    * Decrement button (`-`)
    * Readonly numeric field showing selected quantity
    * Increment button (`+`)
  * Bind the catalog `Add to Cart` action to submit the selected quantity.
  * Disable buttons and mark the product as `SOLD OUT` if its database stock is `0`.
* **Cart Logic (`assets/js/cart.js`)**:
  * Store the item's available stock limit locally.
  * Allow clients to increment/decrement quantities directly in the cart drawer up to the item's stock limit.

### 4. Checkout Logic (`views/store/checkout_handler.php`)
* For every item checked out, verify inside a database transaction:
  * The product exists and `status = 'active'`.
  * `requested_quantity <= product.stock`.
* If validation succeeds, decrement the stock:
  * `UPDATE products SET stock = stock - ? WHERE id = ?`.
* If any item fails validation, rollback the transaction and show an error modal to the buyer.

## Verification & Testing Plan
1. **Database Migration**: Run the migration script and inspect output.
2. **Product CRUD**: Test adding a product with stock = 10 and status active. Verify edit/toggle visibility states.
3. **Storefront**: Verify that the catalog page correctly shows the quantity selector and respects the stock limit (i.e. cannot increment past available stock).
4. **Checkout**: Place an order for 2 items from a stock of 10. Verify stock is reduced to 8. Place an order for 8 items. Verify stock is reduced to 0 and the item displays as SOLD OUT on the storefront.
