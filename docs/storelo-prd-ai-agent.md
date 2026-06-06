# Storelo Product Requirements Document

## 1. Product Summary

Storelo is a lightweight WhatsApp-first storefront builder for small sellers. Sellers create a public store link, upload products, receive customer carts as organized WhatsApp orders, and manage orders from a simple dashboard.

The product should focus on sellers who already sell through WhatsApp, Instagram, TikTok, or status updates, but need a cleaner catalog, checkout, and order tracking flow.

## 2. Product Positioning

Storelo is not a full Shopify replacement. It is a faster, simpler upgrade for WhatsApp commerce.

Primary positioning:

> Create a beautiful online store in minutes, share one link, and receive clean WhatsApp orders without setting up payment gateways.

Primary audience:

- Instagram and WhatsApp fashion sellers
- Thrift and boutique sellers
- Small Nigerian/African retail businesses
- Sellers who prefer manual payment confirmation through chat

Secondary audience:

- Digital product sellers
- Food vendors
- Small service providers with packaged offers

## 3. Goals

- Help sellers create a usable storefront in under 5 minutes.
- Make product discovery easier than scrolling WhatsApp status or Instagram posts.
- Convert carts into clean, structured WhatsApp order messages.
- Give sellers a dashboard for products, orders, customers, coupons, and shipping.
- Keep the platform simple enough to run on basic PHP/MySQL hosting.

## 4. Non-Goals

- Do not build a full marketplace where buyers browse all sellers.
- Do not require payment gateway setup for the first strong version.
- Do not build complex inventory/accounting software.
- Do not add speculative enterprise features.
- Do not require Composer, Node build tooling, or server-side services unavailable on shared hosting.

## 5. Core User Stories

### Seller

- As a seller, I can register and create a store username so customers can visit my shop link.
- As a seller, I can set my shop name, logo, theme color, bio, payment instructions, and WhatsApp number.
- As a seller, I can create products with multiple images, price, stock quantity, category, and description.
- As a seller, I can hide, show, delete, and edit products.
- As a seller, I can view orders with customer details, cart items, delivery address, discount, shipping fee, and order total.
- As a seller, I can update order status so I know what still needs attention.
- As a seller, I can create coupons and shipping zones.
- As a seller, I can copy or send a WhatsApp reply template to the customer.

### Customer

- As a customer, I can open a seller's store link on mobile.
- As a customer, I can browse products by category.
- As a customer, I can search products.
- As a customer, I can open a product detail view with all images and product information.
- As a customer, I can add products to cart and change quantities up to available stock.
- As a customer, I can enter my name, phone, delivery address, promo code, and delivery zone.
- As a customer, I can submit an order and be redirected to WhatsApp with a clear pre-filled order message.

### Platform Owner

- As the platform owner, I can view sellers and basic platform activity.
- As the platform owner, I can suspend abusive stores.
- As the platform owner, I can manually feature or verify sellers in the future.

## 6. MVP Scope

The MVP should improve the current Storelo project without turning it into a large ecommerce framework.

### Must Have

- Seller registration and login
- Seller profile setup
- Public store page per seller
- Product CRUD with multiple images
- Product categories
- Stock quantity management
- Client-side cart
- Checkout form
- Server-side product and stock validation
- Order creation
- WhatsApp redirect with structured message
- Order dashboard
- Order status updates
- Coupons
- Shipping zones
- Mobile-first storefront design

### Should Have

- Product search
- Product badges: New, Sale, Few Left, Sold Out
- Customer list derived from orders
- One-click copy order summary
- One-click WhatsApp customer reply
- Store QR code
- Share store/product buttons
- Basic analytics: visits, product views, checkout starts, orders

### Could Have

- Store themes
- Product flyer generator
- Customer reviews/testimonials
- Seller verification badge
- Payment proof upload
- Optional Paystack/Flutterwave payment links

## 7. Differentiating Features

### 7.1 Clean WhatsApp Order Message

The WhatsApp message is the core conversion moment. It must be clean, readable, and complete.

Required format:

```text
New Order #1024

Customer: Ada
Phone: 08012345678
Address: Lekki Phase 1, Lagos

Items:
2x Black Hoodie - ₦12,000
1x Denim Skirt - ₦8,500

Subtotal: ₦20,500
Delivery: ₦2,000
Discount: ₦1,000
Total: ₦21,500

Payment Instructions:
Bank: Example Bank
Account: 1234567890
Name: Ada Stores
```

Acceptance criteria:

- Message includes order number, customer details, items, subtotal, delivery fee, discount, total, and payment instructions.
- Message is URL-encoded correctly.
- Customer can manually tap a fallback WhatsApp button if automatic redirect is blocked.
- Seller can edit payment instructions from profile settings.

### 7.2 Order Management

Orders should make Storelo more useful than plain WhatsApp chats.

Required statuses:

- Pending
- Confirmed
- Packed
- Delivered
- Cancelled

Acceptance criteria:

- Seller can update status from the order dashboard.
- Status changes persist in the database.
- Orders show customer details, line items, total, coupon, shipping zone, and date.
- Seller can copy a formatted receipt/order summary.
- Seller can open WhatsApp to message the customer.

### 7.3 Mobile-First Storefront

Most buyers will open Storelo from WhatsApp or Instagram on mobile.

Acceptance criteria:

- Storefront works well at 360px width.
- Product cards do not overflow.
- Cart and checkout are easy to use on mobile.
- Product images load with stable dimensions.
- Search and category filters are usable with touch.

### 7.4 Sharing Tools

Storelo should help sellers promote their store.

Acceptance criteria:

- Seller can copy store link from dashboard.
- Seller can generate or view a QR code for the store.
- Customer can share a product link to WhatsApp.
- Product pages have useful title and meta description.

## 8. Data Model Requirements

Use the existing PHP/MySQL structure as much as possible.

Required entities:

- sellers
- products
- categories
- orders
- order_items
- coupons
- shipping_zones
- customers or derived customer view
- analytics_events, if analytics is implemented

Important rules:

- Each seller owns their own products, orders, categories, coupons, and shipping zones.
- Seller ownership must be checked before editing or deleting records.
- Product stock must be validated on checkout.
- Stock should decrement only after a valid order is created.
- Customer-facing output must be escaped.
- Database writes should use prepared statements.

## 9. Main Flows

### 9.1 Seller Onboarding

1. Seller registers.
2. Seller logs in.
3. Seller lands on dashboard.
4. Seller completes profile: shop name, WhatsApp number, payment instructions, logo, theme color.
5. Seller adds at least one category and product.
6. Seller copies store link to share.

Success criteria:

- A new seller can publish a usable store without admin help.

### 9.2 Customer Checkout

1. Customer opens `/shop/{username}`.
2. Customer browses/searches products.
3. Customer adds products to cart.
4. Customer opens cart and enters checkout details.
5. Backend validates seller, products, stock, coupon, and shipping zone.
6. Backend creates order and order items.
7. Backend decrements stock.
8. Customer lands on order success page.
9. Customer is redirected to WhatsApp with the order message.

Success criteria:

- Seller receives a WhatsApp message that can be acted on immediately.
- Order also appears in seller dashboard.

## 10. Admin and Security Requirements

Security requirements:

- Use PDO prepared statements.
- Escape HTML output.
- Validate uploaded image type and size.
- Rename uploaded files with safe unique names.
- Enforce seller ownership on dashboard actions.
- Protect dashboard routes behind login.
- Do not trust cart prices from the browser; use database prices during checkout.
- Prevent checkout quantities greater than stock.

Admin requirements:

- Add platform-owner admin only after seller/customer flow is stable.
- Admin can view sellers, orders count, and store status.
- Admin can suspend or reactivate a store.

## 11. UX Requirements

General:

- Mobile-first.
- Simple language.
- Fast flows.
- No unnecessary setup steps.

Seller dashboard:

- Prioritize daily actions: add product, view orders, copy store link.
- Use clear empty states.
- Avoid complex settings pages.

Storefront:

- Show shop identity immediately.
- Make products easy to scan.
- Keep cart access visible.
- Make checkout feel like a natural step before WhatsApp.

## 12. Analytics Requirements

Basic analytics should be simple and optional.

Track:

- Store visits
- Product views
- Add-to-cart events
- Checkout starts
- Orders created

Seller dashboard should show:

- Total orders
- Revenue estimate
- Top products
- Recent orders
- Conversion count from checkout to order

Do not add third-party analytics in the first version unless required.

## 13. Success Metrics

Product success:

- Seller can create a store and add first product in under 5 minutes.
- Customer can complete checkout in under 2 minutes.
- WhatsApp message contains all order information without manual rewriting.
- Sellers can understand pending orders at a glance.

Business success:

- Number of active stores
- Number of products uploaded
- Number of orders created
- Weekly active sellers
- Store link shares or QR downloads

## 14. Suggested Implementation Phases

### Phase 1: Core Flow Polish

- Fix checkout message quality.
- Improve order success page.
- Ensure stock validation is reliable.
- Ensure seller profile has payment instructions and WhatsApp number.
- Verify mobile storefront and cart.

### Phase 2: Seller Operations

- Add better order statuses.
- Add customer list from orders.
- Add copy receipt and WhatsApp reply actions.
- Improve dashboard summary.

### Phase 3: Storefront Growth

- Add product search.
- Add product sharing.
- Add QR code.
- Add product badges.
- Improve product detail experience.

### Phase 4: Trust and Analytics

- Add basic analytics events.
- Add customer reviews/testimonials.
- Add seller verification flag.
- Add admin dashboard for platform owner.

### Phase 5: Monetization

- Add plan limits if needed.
- Add optional custom domain instructions.
- Add optional payment integration.

## 15. AI Agent Instructions

When implementing this PRD:

- Read the existing files before editing.
- Keep changes surgical.
- Match the current vanilla PHP, MySQL, CSS, and JavaScript style.
- Do not introduce Composer, frameworks, or build tooling unless explicitly approved.
- Prioritize the checkout/order/WhatsApp flow before secondary features.
- Validate each phase before moving to the next.
- Do not refactor unrelated files.
- Surface any schema mismatch before coding against it.

Definition of done for each feature:

- User flow works manually.
- Database writes are validated.
- Seller ownership is enforced where relevant.
- Mobile layout is checked.
- Errors fail loudly with useful messages.
- Existing behavior is not silently removed.
