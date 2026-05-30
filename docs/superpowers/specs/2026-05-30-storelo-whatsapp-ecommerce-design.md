# Storelo: WhatsApp-Redirect E-Commerce Platform for Thrift Sellers

A multi-tenant e-commerce platform built in vanilla PHP and MySQL, designed specifically for thrift store sellers to showcase products and redirect customers to WhatsApp for checkout and order finalization.

---

## 1. Goal & Objectives

*   **Platform Goal:** Enable multiple thrift sellers to register, set up a mobile-friendly store, list products, track orders, and redirect checkout orders directly to their WhatsApp chats.
*   **Target Hosting:** InfinityFree (free shared web hosting).
    *   **Constraints:** No CLI/Composer, no SSH, PHP/MySQL only, limited storage and resources, Apache web server with `.htaccess` support.
*   **Target Domain:** `storelo.page.gd`

---

## 2. System Architecture & Routing

We use the **Front Controller** pattern with Apache URL rewriting to handle clean, SaaS-like URLs.

### File Structure
```
storelo/
├── .htaccess                 # Reroutes all clean URLs to index.php
├── config.php                # Database credentials & global settings
├── index.php                 # Router & Front Controller
├── assets/                   # Public static files (CSS & JS)
│   ├── css/
│   │   ├── style.css         # Modern typography & styles for store/landing
│   │   └── admin.css         # Styling for the seller dashboard
│   └── js/
│       ├── cart.js           # Client-side cart using localStorage
│       └── admin.js          # Helper scripts for dashboard interactions
├── includes/                 # Shared PHP files
│   ├── db.php                # PDO-based database helper
│   ├── functions.php         # Utility functions (XSS filtering, redirects)
│   ├── admin_header.php      # Dashboard header navbar
│   └── store_header.php      # Customer storefront navbar
├── uploads/                  # User uploads directory
│   ├── logos/                # Shop logos
│   └── products/             # Product images
└── views/                    # Visual layout files
    ├── home.php              # Storelo landing page
    ├── login.php             # Seller login page
    ├── register.php          # Seller signup page
    ├── dashboard/            # Seller management views
    │   ├── main.php          # Dashboard statistics & summary
    │   ├── products.php      # Product CRUD (with status: available/sold/hidden)
    │   ├── orders.php        # Orders listing (history and status)
    │   └── profile.php       # Shop settings (logo, name, phone, currency)
    └── store/                # Public store views
        ├── catalog.php       # Storefront catalog (product listing & cart drawer)
        └── order_success.php # Order completion page (triggers WhatsApp redirect)
```

### URL Routing (`.htaccess`)
All requests except for existing assets are redirected to `index.php`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

`index.php` routes paths dynamically:
*   `/` $\rightarrow$ `views/home.php`
*   `/login` $\rightarrow$ `views/login.php`
*   `/register` $\rightarrow$ `views/register.php`
*   `/logout` $\rightarrow$ Logout handler
*   `/dashboard` $\rightarrow$ `views/dashboard/main.php`
*   `/dashboard/products` $\rightarrow$ `views/dashboard/products.php`
*   `/dashboard/orders` $\rightarrow$ `views/dashboard/orders.php`
*   `/dashboard/profile` $\rightarrow$ `views/dashboard/profile.php`
*   `/shop/{username}` $\rightarrow$ `views/store/catalog.php` (dynamically loads seller by username)
*   `/shop/{username}/order-success/{order_id}` $\rightarrow$ `views/store/order_success.php`

---

## 3. Database Schema (MySQL)

```sql
CREATE TABLE sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    shop_name VARCHAR(100) NOT NULL,
    whatsapp_number VARCHAR(20) NOT NULL,
    currency VARCHAR(10) DEFAULT '₦',
    delivery_info TEXT,
    shop_description TEXT,
    logo_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    status ENUM('available', 'sold', 'hidden') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

---

## 4. Key Functional Workflows

### A. Seller Sign Up & Configuration
1.  Sellers register with a unique username, password, and WhatsApp number (must contain country code, e.g., `2348031234567`).
2.  Upon logging in, they are redirected to their Dashboard.
3.  In "Profile Settings", they set up their shop details: shop name, currency symbol, logo, shop description, and delivery information.

### B. Product Inventory Management
1.  Sellers create product entries with name, price, category, description, and image.
2.  Image file is validated (types: `jpg, jpeg, png, webp`, size: `< 2MB`) and saved in `uploads/products/` with a unique hash filename to prevent overwriting.
3.  Sellers can toggle the product status:
    *   `available` $\rightarrow$ visible and purchasable.
    *   `sold` $\rightarrow$ displays a "SOLD" badge, cannot be added to cart.
    *   `hidden` $\rightarrow$ not visible in catalog.

### C. Shopping & Cart Flow
1.  Customers visit `storelo.page.gd/shop/{username}`.
2.  The storefront pulls seller info and active products from the database.
3.  Customers browse products, click cards to view details, and click "Add to Cart".
4.  The cart is managed dynamically on the client side using JS and `localStorage`.
5.  A slide-out Cart Drawer displays items, quantities, and subtotal.

### D. Checkout & WhatsApp Redirect
1.  From the Cart Drawer, the customer clicks "Checkout".
2.  A checkout modal requests: Name, Phone, and Delivery Address.
3.  Upon submission:
    *   An AJAX `POST` request is sent to `/shop/{username}/checkout`.
    *   The backend validates product availability.
    *   The order is inserted into the `orders` and `order_items` tables.
    *   For thrift items, the ordered products' statuses are updated to `sold`.
    *   The cart is cleared from `localStorage`.
    *   The customer is redirected to `/shop/{username}/order-success/{order_id}`.
4.  The success page displays the order confirmation and formats the WhatsApp API URL:
    `https://wa.me/{whatsapp_number}?text={encoded_message}`
5.  The browser executes an automatic redirect to WhatsApp. A manual fallback button is provided in case the redirect is blocked.

---

## 5. Security & Constraints

1.  **SQL Injection:** All database queries will use PDO prepared statements with parameterized values.
2.  **Cross-Site Scripting (XSS):** All dynamic outputs rendered in HTML will be escaped using `htmlspecialchars()`.
3.  **Authentication:** Sessions will be used to manage seller logins. Session parameters will be configured securely.
4.  **File Uploads:** Uploaded images will be validated by MIME-type and size. File extensions will be sanitized, and files will be renamed using unique hashes.
5.  **InfinityFree Constraints:**
    *   Since database credentials differ between local and production, a local config file will be excluded from git, and credentials will be loaded from a configuration file.
    *   File size uploads will be restricted on the frontend and backend to avoid exceeding server quotas.
