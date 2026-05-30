# Storelo WhatsApp E-Commerce Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete, multi-tenant e-commerce platform in PHP/MySQL designed for thrift sellers where checkout redirects customers to finalize orders on WhatsApp.

**Architecture:** Use Front Controller routing via `.htaccess` and `index.php` to serve clean URLs. Use dynamic CSS and client-side JavaScript for cart state, and standard PHP/PDO session-based backend for seller administration.

**Tech Stack:** PHP (Vanilla), MySQL (PDO), CSS (Vanilla with Custom Variables), JavaScript (Vanilla).

---

### Task 1: Database Setup and Configuration Helper

**Files:**
- Create: `schema.sql`
- Create: `config.php`
- Create: `includes/db.php`
- Create: `includes/functions.php`

- [ ] **Step 1: Create the SQL schema file**
  Create `schema.sql` with tables for sellers, products, orders, and order items.
  ```sql
  -- schema.sql
  CREATE TABLE IF NOT EXISTS sellers (
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

  CREATE TABLE IF NOT EXISTS products (
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

  CREATE TABLE IF NOT EXISTS orders (
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

  CREATE TABLE IF NOT EXISTS order_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id INT NOT NULL,
      product_id INT NOT NULL,
      quantity INT DEFAULT 1,
      price DECIMAL(10, 2) NOT NULL,
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
  );
  ```

- [ ] **Step 2: Create the database configuration file**
  Create `config.php` to define environment-specific database credentials. Include a local check so credentials work both locally and in production.
  ```php
  <?php
  // config.php
  define('DB_HOST', 'localhost');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_NAME', 'storelo');

  define('BASE_URL', 'http://localhost/storelo'); // Update for production hosting
  ```

- [ ] **Step 3: Create the PDO database helper class**
  Create `includes/db.php` containing a thread-safe static database connection method.
  ```php
  <?php
  // includes/db.php
  require_once __DIR__ . '/../config.php';

  class DB {
      private static $pdo = null;

      public static function connect() {
          if (self::$pdo === null) {
              try {
                  $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                  $options = [
                      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                      PDO::ATTR_EMULATE_PREPARES   => false,
                  ];
                  self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
              } catch (PDOException $e) {
                  die("Database connection failed: " . $e->getMessage());
              }
          }
          return self::$pdo;
      }
  }
  ```

- [ ] **Step 4: Create the functions helper file**
  Create `includes/functions.php` to house validation, sanitization, and output escaping methods.
  ```php
  <?php
  // includes/functions.php
  function e($string) {
      return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
  }

  function redirect($path) {
      header("Location: " . BASE_URL . $path);
      exit;
  }

  function sanitize_input($data) {
      return htmlspecialchars(stripslashes(trim($data)));
  }

  function is_logged_in() {
      if (session_status() === PHP_SESSION_NONE) {
          session_start();
      }
      return isset($_SESSION['seller_id']);
  }

  function require_login() {
      if (!is_logged_in()) {
          redirect('/login');
      }
  }
  ```

- [ ] **Step 5: Create local uploads directories**
  Run commands to create the `uploads/logos/` and `uploads/products/` folders with write permissions.
  Run: `mkdir -p uploads/logos uploads/products`

- [ ] **Step 6: Commit**
  Add files and commit.
  Run: `git add schema.sql config.php includes/db.php includes/functions.php`
  Run: `git commit -m "feat: database config, helper classes, and directory structures"`

---

### Task 2: URL Routing and Front Controller Setup

**Files:**
- Create: `.htaccess`
- Create: `index.php`

- [ ] **Step 1: Create the `.htaccess` rewrite rules**
  Write rewrite conditions in the root `.htaccess` redirecting non-asset paths to `index.php`.
  ```apache
  RewriteEngine On

  # Stop rewrites for folders/files that physically exist
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d

  # Rewrite everything else to index.php
  RewriteRule ^(.*)$ index.php [QSA,L]
  ```

- [ ] **Step 2: Create the Front Controller routing table**
  Create `index.php` to extract pathing from the request URI and render the correct view from the `views/` folder.
  ```php
  <?php
  // index.php
  require_once __DIR__ . '/includes/db.php';
  require_once __DIR__ . '/includes/functions.php';

  $request = $_SERVER['REQUEST_URI'];
  // Remove folder path if running in a subdirectory (e.g. /storelo/)
  $base_folder = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
  $request = str_replace($base_folder, '', $request);
  $request = parse_url($request, PHP_URL_PATH);

  // Router matching logic
  if ($request === '' || $request === '/') {
      require __DIR__ . '/views/home.php';
  } elseif ($request === '/login') {
      require __DIR__ . '/views/login.php';
  } elseif ($request === '/register') {
      require __DIR__ . '/views/register.php';
  } elseif ($request === '/logout') {
      if (session_status() === PHP_SESSION_NONE) session_start();
      session_destroy();
      redirect('/');
  } elseif ($request === '/dashboard') {
      require_login();
      require __DIR__ . '/views/dashboard/main.php';
  } elseif ($request === '/dashboard/products') {
      require_login();
      require __DIR__ . '/views/dashboard/products.php';
  } elseif ($request === '/dashboard/orders') {
      require_login();
      require __DIR__ . '/views/dashboard/orders.php';
  } elseif ($request === '/dashboard/profile') {
      require_login();
      require __DIR__ . '/views/dashboard/profile.php';
  } elseif (preg_match('#^/shop/([^/]+)/order-success/([0-9]+)$#', $request, $matches)) {
      $shop_username = $matches[1];
      $order_id = $matches[2];
      require __DIR__ . '/views/store/order_success.php';
  } elseif (preg_match('#^/shop/([^/]+)/checkout$#', $request, $matches)) {
      $shop_username = $matches[1];
      // Handled via POST route
      require __DIR__ . '/views/store/checkout_handler.php';
  } elseif (preg_match('#^/shop/([^/]+)$#', $request, $matches)) {
      $shop_username = $matches[1];
      require __DIR__ . '/views/store/catalog.php';
  } else {
      http_response_code(404);
      echo "<h1>404 Not Found</h1>";
  }
  ```

- [ ] **Step 3: Commit**
  Add files and commit.
  Run: `git add .htaccess index.php`
  Run: `git commit -m "feat: implement Front Controller routing and htaccess rules"`

---

### Task 3: Styling System (Aesthetics)

**Files:**
- Create: `assets/css/style.css`
- Create: `assets/css/admin.css`

- [ ] **Step 1: Create global store and landing page stylesheet**
  Add styles in `assets/css/style.css` containing variables for responsive grids, typography, custom layouts, glassmorphism modal design, and badges.
  ```css
  /* assets/css/style.css */
  @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap');

  :root {
      --bg-color: #0b0f19;
      --card-bg: rgba(255, 255, 255, 0.03);
      --card-border: rgba(255, 255, 255, 0.08);
      --accent-color: #6366f1;
      --accent-glow: rgba(99, 102, 241, 0.35);
      --text-color: #f3f4f6;
      --text-muted: #9ca3af;
      --success: #10b981;
      --danger: #ef4444;
  }

  body {
      margin: 0;
      font-family: 'Outfit', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      line-height: 1.6;
  }

  /* Glassmorphism panel styling */
  .glass-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 24px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .glass-card:hover {
      border-color: rgba(99, 102, 241, 0.3);
      box-shadow: 0 10px 30px -10px var(--accent-glow);
      transform: translateY(-4px);
  }

  /* Beautiful layout and buttons */
  .btn-primary {
      background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
      color: #fff;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 4px 14px var(--accent-glow);
      transition: transform 0.2s;
  }
  .btn-primary:hover {
      transform: scale(1.02);
  }
  ```

- [ ] **Step 2: Create admin panel stylesheet**
  Add dashboard styles in `assets/css/admin.css` matching the dark mode, tabular dashboards, forms, and alerts.
  ```css
  /* assets/css/admin.css */
  @import url('style.css');

  .admin-layout {
      display: flex;
      min-height: 100vh;
  }

  .sidebar {
      width: 260px;
      background: #0f172a;
      border-right: 1px solid rgba(255, 255, 255, 0.05);
      padding: 24px;
  }

  .main-content {
      flex: 1;
      padding: 40px;
  }

  .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
  }

  .stat-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 24px;
  }
  ```

- [ ] **Step 3: Commit**
  Add files and commit.
  Run: `git add assets/css/style.css assets/css/admin.css`
  Run: `git commit -m "design: establish CSS theme variables and global assets design system"`

---

### Task 4: Landing and Authentication Views

**Files:**
- Create: `views/home.php`
- Create: `views/register.php`
- Create: `views/login.php`

- [ ] **Step 1: Create the Storelo Landing Page**
  Create `views/home.php` containing marketing materials, details on the service, registration CTA links, and user logins.
  ```php
  <?php
  // views/home.php
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Storelo - WhatsApp Storefront Creator</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  </head>
  <body>
      <div class="landing-container" style="max-width: 1000px; margin: 0 auto; padding: 80px 20px; text-align: center;">
          <h1 style="font-size: 3.5rem; margin-bottom: 20px; font-weight: 800; background: linear-gradient(to right, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
              Storelo
          </h1>
          <p style="font-size: 1.25rem; color: var(--text-muted); margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">
              Launch your online store in 2 minutes. Let customers add thrift items to their carts and checkout directly to your WhatsApp.
          </p>
          <div style="display: flex; gap: 20px; justify-content: center; margin-bottom: 80px;">
              <a href="<?= BASE_URL ?>/register" class="btn-primary" style="text-decoration: none;">Create Your Store</a>
              <a href="<?= BASE_URL ?>/login" class="btn-primary" style="background: rgba(255,255,255,0.05); box-shadow: none; border: 1px solid var(--card-border); text-decoration: none;">Seller Login</a>
          </div>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 2: Create registration page with verification logic**
  Create `views/register.php` supporting database registration, hashing passwords using `password_hash()`, and checking for username collisions.
  ```php
  <?php
  // views/register.php
  require_once __DIR__ . '/../includes/db.php';
  require_once __DIR__ . '/../includes/functions.php';

  if (is_logged_in()) redirect('/dashboard');

  $error = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $username = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['username']));
      $password = $_POST['password'];
      $shop_name = sanitize_input($_POST['shop_name']);
      $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number']);

      if (strlen($username) < 3) {
          $error = "Username must be at least 3 alphanumeric characters.";
      } elseif (empty($password) || empty($shop_name) || empty($whatsapp_number)) {
          $error = "All fields are required.";
      } else {
          $db = DB::connect();
          $stmt = $db->prepare("SELECT id FROM sellers WHERE username = ?");
          $stmt->execute([$username]);
          if ($stmt->fetch()) {
              $error = "Shop link username is already taken.";
          } else {
              $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
              $stmt = $db->prepare("INSERT INTO sellers (username, password, shop_name, whatsapp_number) VALUES (?, ?, ?, ?)");
              $stmt->execute([$username, $hashed_pass, $shop_name, $whatsapp_number]);

              session_start();
              $_SESSION['seller_id'] = $db->lastInsertId();
              $_SESSION['username'] = $username;
              redirect('/dashboard');
          }
      }
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Create Store - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  </head>
  <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
      <div class="glass-card" style="width: 100%; max-width: 400px;">
          <h2>Register Shop</h2>
          <?php if($error): ?><p style="color: var(--danger)"><?= e($error) ?></p><?php endif; ?>
          <form method="POST">
              <div style="margin-bottom:15px">
                  <label>Store Username (URL slug)</label>
                  <input type="text" name="username" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:15px">
                  <label>Shop Name</label>
                  <input type="text" name="shop_name" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:15px">
                  <label>WhatsApp Number (With country code, e.g. 234803...)</label>
                  <input type="text" name="whatsapp_number" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:20px">
                  <label>Password</label>
                  <input type="password" name="password" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <button type="submit" class="btn-primary" style="width:100%">Create Store</button>
          </form>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 3: Create login page**
  Create `views/login.php` supporting authentication via password verification.
  ```php
  <?php
  // views/login.php
  require_once __DIR__ . '/../includes/db.php';
  require_once __DIR__ . '/../includes/functions.php';

  if (is_logged_in()) redirect('/dashboard');

  $error = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $username = strtolower($_POST['username']);
      $password = $_POST['password'];

      $db = DB::connect();
      $stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
      $stmt->execute([$username]);
      $seller = $stmt->fetch();

      if ($seller && password_verify($password, $seller['password'])) {
          session_start();
          $_SESSION['seller_id'] = $seller['id'];
          $_SESSION['username'] = $seller['username'];
          redirect('/dashboard');
      } else {
          $error = "Invalid username or password.";
      }
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Login - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  </head>
  <body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
      <div class="glass-card" style="width: 100%; max-width: 400px;">
          <h2>Seller Login</h2>
          <?php if($error): ?><p style="color: var(--danger)"><?= e($error) ?></p><?php endif; ?>
          <form method="POST">
              <div style="margin-bottom:15px">
                  <label>Username</label>
                  <input type="text" name="username" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:20px">
                  <label>Password</label>
                  <input type="password" name="password" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <button type="submit" class="btn-primary" style="width:100%">Log In</button>
          </form>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 4: Commit**
  Add files and commit.
  Run: `git add views/home.php views/register.php views/login.php`
  Run: `git commit -m "feat: build landing, login, and registration views with authorization validation"`

---

### Task 5: Admin Panel Layout & Profile Setup

**Files:**
- Create: `includes/admin_header.php`
- Create: `views/dashboard/main.php`
- Create: `views/dashboard/profile.php`

- [ ] **Step 1: Create navigation header for seller panel**
  Create `includes/admin_header.php` containing side navigation and branding logouts.
  ```php
  <?php
  // includes/admin_header.php
  ?>
  <div class="sidebar">
      <h2>Storelo</h2>
      <ul style="list-style:none; padding:0; margin: 40px 0 0 0;">
          <li style="margin-bottom:20px"><a href="<?= BASE_URL ?>/dashboard" style="color:var(--text-color); text-decoration:none">Overview</a></li>
          <li style="margin-bottom:20px"><a href="<?= BASE_URL ?>/dashboard/products" style="color:var(--text-color); text-decoration:none">Products</a></li>
          <li style="margin-bottom:20px"><a href="<?= BASE_URL ?>/dashboard/orders" style="color:var(--text-color); text-decoration:none">Orders Log</a></li>
          <li style="margin-bottom:20px"><a href="<?= BASE_URL ?>/dashboard/profile" style="color:var(--text-color); text-decoration:none">Shop Profile</a></li>
          <li style="margin-top:60px"><a href="<?= BASE_URL ?>/logout" style="color:var(--danger); text-decoration:none">Logout</a></li>
      </ul>
  </div>
  ```

- [ ] **Step 2: Create Dashboard Main Overview**
  Create `views/dashboard/main.php` showing store statistics.
  ```php
  <?php
  // views/dashboard/main.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';
  require_login();

  $db = DB::connect();
  $seller_id = $_SESSION['seller_id'];

  // Query Stats
  $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE seller_id = ?");
  $stmt->execute([$seller_id]);
  $total_products = $stmt->fetch()['cnt'];

  $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE seller_id = ?");
  $stmt->execute([$seller_id]);
  $total_orders = $stmt->fetch()['cnt'];
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Dashboard - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  </head>
  <body class="admin-layout">
      <?php require __DIR__ . '/../../includes/admin_header.php'; ?>
      <div class="main-content">
          <h1>Welcome, <?= e($_SESSION['username']) ?></h1>
          <p>Configure your shop layout and list items below.</p>
          <div class="dashboard-grid">
              <div class="stat-card">
                  <h3>Products</h3>
                  <p style="font-size: 2rem; font-weight:800; margin:10px 0;"><?= $total_products ?></p>
              </div>
              <div class="stat-card">
                  <h3>Orders Received</h3>
                  <p style="font-size: 2rem; font-weight:800; margin:10px 0;"><?= $total_orders ?></p>
              </div>
          </div>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 3: Create Seller Profile Editing View**
  Create `views/dashboard/profile.php` to handle logo uploads (with size restriction and file rename hashing), currency select settings, shipping information, and basic profiles.
  ```php
  <?php
  // views/dashboard/profile.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';
  require_login();

  $db = DB::connect();
  $seller_id = $_SESSION['seller_id'];

  $error = '';
  $success = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $shop_name = sanitize_input($_POST['shop_name']);
      $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number']);
      $currency = sanitize_input($_POST['currency']);
      $delivery_info = sanitize_input($_POST['delivery_info']);
      $shop_description = sanitize_input($_POST['shop_description']);

      $logo_path = null;
      if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
          $file_tmp = $_FILES['logo']['tmp_name'];
          $file_name = $_FILES['logo']['name'];
          $file_size = $_FILES['logo']['size'];
          $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
          $allowed = ['jpg', 'jpeg', 'png', 'webp'];

          if (in_array($file_ext, $allowed) && $file_size <= 2097152) { // 2MB
              $new_name = uniqid('logo_', true) . '.' . $file_ext;
              $dest = __DIR__ . '/../../uploads/logos/' . $new_name;
              if (move_uploaded_file($file_tmp, $dest)) {
                  $logo_path = 'uploads/logos/' . $new_name;
              }
          } else {
              $error = "Invalid logo file. Use PNG/JPG < 2MB.";
          }
      }

      if (empty($error)) {
          if ($logo_path) {
              $stmt = $db->prepare("UPDATE sellers SET shop_name = ?, whatsapp_number = ?, currency = ?, delivery_info = ?, shop_description = ?, logo_path = ? WHERE id = ?");
              $stmt->execute([$shop_name, $whatsapp_number, $currency, $delivery_info, $shop_description, $logo_path, $seller_id]);
          } else {
              $stmt = $db->prepare("UPDATE sellers SET shop_name = ?, whatsapp_number = ?, currency = ?, delivery_info = ?, shop_description = ? WHERE id = ?");
              $stmt->execute([$shop_name, $whatsapp_number, $currency, $delivery_info, $shop_description, $seller_id]);
          }
          $success = "Profile updated successfully.";
      }
  }

  $stmt = $db->prepare("SELECT * FROM sellers WHERE id = ?");
  $stmt->execute([$seller_id]);
  $seller = $stmt->fetch();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Shop Profile - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  </head>
  <body class="admin-layout">
      <?php require __DIR__ . '/../../includes/admin_header.php'; ?>
      <div class="main-content">
          <h1>Shop Settings</h1>
          <?php if($error): ?><p style="color:var(--danger)"><?= e($error) ?></p><?php endif; ?>
          <?php if($success): ?><p style="color:var(--success)"><?= e($success) ?></p><?php endif; ?>
          <form method="POST" enctype="multipart/form-data" class="glass-card" style="max-width: 600px;">
              <div style="margin-bottom:15px">
                  <label>Shop Name</label>
                  <input type="text" name="shop_name" value="<?= e($seller['shop_name']) ?>" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:15px">
                  <label>WhatsApp Contact Number (No space/signs, e.g. 234803...)</label>
                  <input type="text" name="whatsapp_number" value="<?= e($seller['whatsapp_number']) ?>" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:15px">
                  <label>Currency Symbol</label>
                  <select name="currency" style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
                      <option value="₦" <?= $seller['currency'] === '₦' ? 'selected' : '' ?>>₦ (Naira)</option>
                      <option value="$" <?= $seller['currency'] === '$' ? 'selected' : '' ?>>$ (Dollar)</option>
                      <option value="R" <?= $seller['currency'] === 'R' ? 'selected' : '' ?>>R (Rand)</option>
                      <option value="£" <?= $seller['currency'] === '£' ? 'selected' : '' ?>>£ (Pound)</option>
                      <option value="€" <?= $seller['currency'] === '€' ? 'selected' : '' ?>>€ (Euro)</option>
                  </select>
              </div>
              <div style="margin-bottom:15px">
                  <label>Shop Description</label>
                  <textarea name="shop_description" style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border); height:100px;"><?= e($seller['shop_description']) ?></textarea>
              </div>
              <div style="margin-bottom:15px">
                  <label>Delivery/Shipping Info</label>
                  <input type="text" name="delivery_info" value="<?= e($seller['delivery_info']) ?>" placeholder="e.g. Flat delivery: ₦2,000" style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
              </div>
              <div style="margin-bottom:20px">
                  <label>Shop Logo</label>
                  <input type="file" name="logo" style="display:block; margin-top:5px;">
                  <?php if($seller['logo_path']): ?>
                      <img src="<?= BASE_URL ?>/<?= $seller['logo_path'] ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-top:10px;">
                  <?php endif; ?>
              </div>
              <button type="submit" class="btn-primary">Save Changes</button>
          </form>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 4: Commit**
  Add files and commit.
  Run: `git add includes/admin_header.php views/dashboard/main.php views/dashboard/profile.php`
  Run: `git commit -m "feat: establish administrator skeleton layout and profile/logo configurators"`

---

### Task 6: Inventory Management Panel

**Files:**
- Create: `views/dashboard/products.php`

- [ ] **Step 1: Create inventory view supporting product CRUD**
  Create `views/dashboard/products.php` allowing add/edit/delete/status toggle. Ensure image sizes are restricted to `< 2MB` and file extensions verified.
  ```php
  <?php
  // views/dashboard/products.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';
  require_login();

  $db = DB::connect();
  $seller_id = $_SESSION['seller_id'];

  $error = '';
  $success = '';

  // Add Product action
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
      $name = sanitize_input($_POST['name']);
      $price = floatval($_POST['price']);
      $category = sanitize_input($_POST['category']);
      $description = sanitize_input($_POST['description']);

      if (empty($name) || $price <= 0 || !isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
          $error = "Please provide valid product details and image.";
      } else {
          $file_tmp = $_FILES['image']['tmp_name'];
          $file_name = $_FILES['image']['name'];
          $file_size = $_FILES['image']['size'];
          $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
          $allowed = ['jpg', 'jpeg', 'png', 'webp'];

          if (in_array($file_ext, $allowed) && $file_size <= 2097152) { // 2MB
              $new_name = uniqid('prod_', true) . '.' . $file_ext;
              $dest = __DIR__ . '/../../uploads/products/' . $new_name;
              if (move_uploaded_file($file_tmp, $dest)) {
                  $image_path = 'uploads/products/' . $new_name;
                  $stmt = $db->prepare("INSERT INTO products (seller_id, name, description, category, price, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                  $stmt->execute([$seller_id, $name, $description, $category, $price, $image_path]);
                  $success = "Product added successfully.";
              } else {
                  $error = "Failed to upload image.";
              }
          } else {
              $error = "Invalid file. JPG/PNG/WEBP files < 2MB only.";
          }
      }
  }

  // Toggle/Delete status action
  if (isset($_GET['delete'])) {
      $prod_id = intval($_GET['delete']);
      $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
      $stmt->execute([$prod_id, $seller_id]);
      $success = "Product deleted.";
  }

  if (isset($_GET['toggle_status'])) {
      $prod_id = intval($_GET['toggle_status']);
      $stmt = $db->prepare("SELECT status FROM products WHERE id = ? AND seller_id = ?");
      $stmt->execute([$prod_id, $seller_id]);
      $prod = $stmt->fetch();
      if ($prod) {
          $new_status = $prod['status'] === 'available' ? 'sold' : 'available';
          $stmt = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
          $stmt->execute([$new_status, $prod_id]);
          $success = "Product status updated.";
      }
  }

  $stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY id DESC");
  $stmt->execute([$seller_id]);
  $products = $stmt->fetchAll();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Manage Products - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  </head>
  <body class="admin-layout">
      <?php require __DIR__ . '/../../includes/admin_header.php'; ?>
      <div class="main-content">
          <h1>Product Catalog</h1>
          <?php if($error): ?><p style="color:var(--danger)"><?= e($error) ?></p><?php endif; ?>
          <?php if($success): ?><p style="color:var(--success)"><?= e($success) ?></p><?php endif; ?>

          <!-- Add Product Form -->
          <div class="glass-card" style="margin-bottom:40px; max-width:600px;">
              <h3>Add New Product</h3>
              <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="add">
                  <div style="margin-bottom:10px">
                      <label>Product Name</label>
                      <input type="text" name="name" required style="width:100%; padding:8px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
                  </div>
                  <div style="margin-bottom:10px; display:flex; gap:10px;">
                      <div style="flex:1">
                          <label>Price</label>
                          <input type="number" step="0.01" name="price" required style="width:100%; padding:8px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
                      </div>
                      <div style="flex:1">
                          <label>Category</label>
                          <input type="text" name="category" placeholder="e.g. Tops, Pants" style="width:100%; padding:8px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
                      </div>
                  </div>
                  <div style="margin-bottom:10px">
                      <label>Description</label>
                      <textarea name="description" style="width:100%; padding:8px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border); height:60px;"></textarea>
                  </div>
                  <div style="margin-bottom:15px">
                      <label>Product Image</label>
                      <input type="file" name="image" required style="display:block; margin-top:5px;">
                  </div>
                  <button type="submit" class="btn-primary">Upload Product</button>
              </form>
          </div>

          <!-- Product Grid -->
          <h2>Listed Products</h2>
          <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:20px;">
              <?php foreach($products as $p): ?>
                  <div class="glass-card" style="padding:15px; position:relative;">
                      <img src="<?= BASE_URL ?>/<?= $p['image_path'] ?>" style="width:100%; height:150px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
                      <h4><?= e($p['name']) ?></h4>
                      <p style="font-weight:600; margin:5px 0;"><?= e($p['price']) ?></p>
                      <div style="display:flex; gap:5px; margin-top:10px; font-size:0.85rem;">
                          <a href="?toggle_status=<?= $p['id'] ?>" style="color:var(--accent-color); text-decoration:none;">
                              Mark <?= $p['status'] === 'available' ? 'Sold' : 'Available' ?>
                          </a>
                          |
                          <a href="?delete=<?= $p['id'] ?>" style="color:var(--danger); text-decoration:none;" onclick="return confirm('Delete item?')">Delete</a>
                      </div>
                      <span style="position:absolute; top:10px; right:10px; background:<?= $p['status'] === 'available' ? 'var(--success)' : 'var(--danger)' ?>; padding:4px 8px; border-radius:4px; font-size:0.75rem;">
                          <?= strtoupper($p['status']) ?>
                      </span>
                  </div>
              <?php endforeach; ?>
          </div>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 2: Commit**
  Add files and commit.
  Run: `git add views/dashboard/products.php`
  Run: `git commit -m "feat: product inventory view and image upload pipeline"`

---

### Task 7: Shopping Cart and Client Drawer Component

**Files:**
- Create: `assets/js/cart.js`

- [ ] **Step 1: Write client-side shopping cart script**
  Create `assets/js/cart.js` managing `localStorage` cart state, drawers slide-ins, rendering dynamic items inside drawers, and handling quantities.
  ```javascript
  // assets/js/cart.js
  let cart = JSON.parse(localStorage.getItem('storelo_cart')) || [];

  function saveCart() {
      localStorage.setItem('storelo_cart', JSON.stringify(cart));
      renderCart();
  }

  function addToCart(productId, name, price, image) {
      const existing = cart.find(item => item.id === productId);
      if (existing) {
          // Thrift items usually have Qty = 1, but we support incrementing general items
          existing.quantity += 1;
      } else {
          cart.push({ id: productId, name, price: parseFloat(price), image, quantity: 1 });
      }
      openCartDrawer();
      saveCart();
  }

  function removeFromCart(productId) {
      cart = cart.filter(item => item.id !== productId);
      saveCart();
  }

  function changeQuantity(productId, amount) {
      const item = cart.find(item => item.id === productId);
      if (item) {
          item.quantity += amount;
          if (item.quantity <= 0) {
              removeFromCart(productId);
          } else {
              saveCart();
          }
      }
  }

  function clearCart() {
      cart = [];
      saveCart();
  }

  function getCartTotal() {
      return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
  }

  function openCartDrawer() {
      document.getElementById('cart-drawer').classList.add('open');
  }

  function closeCartDrawer() {
      document.getElementById('cart-drawer').classList.remove('open');
  }

  function renderCart() {
      const cartCountEl = document.getElementById('cart-count');
      const cartItemsEl = document.getElementById('cart-items');
      const cartTotalEl = document.getElementById('cart-total');

      if(cartCountEl) cartCountEl.innerText = cart.reduce((acc, item) => acc + item.quantity, 0);

      if (cartItemsEl) {
          cartItemsEl.innerHTML = '';
          if (cart.length === 0) {
              cartItemsEl.innerHTML = '<p style="text-align:center; color:var(--text-muted)">Your cart is empty.</p>';
          } else {
              cart.forEach(item => {
                  cartItemsEl.innerHTML += `
                      <div style="display:flex; gap:10px; margin-bottom:15px; border-bottom:1px solid var(--card-border); padding-bottom:10px;">
                          <img src="${item.image}" style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
                          <div style="flex:1">
                              <h5 style="margin:0">${item.name}</h5>
                              <p style="margin:5px 0 0 0; color:var(--text-muted); font-size:0.9rem;">${item.price} x ${item.quantity}</p>
                          </div>
                          <div>
                              <button onclick="removeFromCart(${item.id})" style="background:none; border:none; color:var(--danger); cursor:pointer;">&times;</button>
                          </div>
                      </div>
                  `;
              });
          }
      }

      if(cartTotalEl) cartTotalEl.innerText = getCartTotal().toFixed(2);
  }

  document.addEventListener('DOMContentLoaded', () => {
      renderCart();
  });
  ```

- [ ] **Step 2: Commit**
  Add files and commit.
  Run: `git add assets/js/cart.js`
  Run: `git commit -m "feat: javascript storage cart client drawer modules"`

---

### Task 8: Public Storefront View

**Files:**
- Create: `views/store/catalog.php`

- [ ] **Step 1: Create the storefront template**
  Create `views/store/catalog.php` to fetch and render the shop profile and active product list. Connect the storefront layout to the cart drawer scripts.
  ```php
  <?php
  // views/store/catalog.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';

  $db = DB::connect();
  $stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
  $stmt->execute([$shop_username]);
  $seller = $stmt->fetch();

  if (!$seller) {
      http_response_code(404);
      die("<h1>Shop Not Found</h1>");
  }

  // Fetch only active/available/sold items
  $stmt = $db->prepare("SELECT * FROM products WHERE seller_id = ? AND status != 'hidden' ORDER BY status ASC, id DESC");
  $stmt->execute([$seller['id']]);
  $products = $stmt->fetchAll();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= e($seller['shop_name']) ?> - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
      <script src="<?= BASE_URL ?>/assets/js/cart.js" defer></script>
      <style>
          .cart-drawer {
              position: fixed; right: -350px; top: 0; width: 320px; height: 100%;
              background: #0f172a; border-left: 1px solid var(--card-border);
              padding: 20px; transition: right 0.3s; z-index: 1000; box-shadow: -10px 0 30px rgba(0,0,0,0.5);
          }
          .cart-drawer.open { right: 0; }
      </style>
  </head>
  <body>
      <!-- Header -->
      <div style="background:rgba(255,255,255,0.01); border-bottom:1px solid var(--card-border); padding:20px; display:flex; justify-content:space-between; align-items:center;">
          <div style="display:flex; align-items:center; gap:15px;">
              <?php if($seller['logo_path']): ?>
                  <img src="<?= BASE_URL ?>/<?= $seller['logo_path'] ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
              <?php endif; ?>
              <h2><?= e($seller['shop_name']) ?></h2>
          </div>
          <button onclick="openCartDrawer()" class="btn-primary" style="padding:8px 16px;">Cart (<span id="cart-count">0</span>)</button>
      </div>

      <!-- Catalog Layout -->
      <div style="max-width:1200px; margin: 40px auto; padding: 0 20px;">
          <p style="color:var(--text-muted)"><?= e($seller['shop_description']) ?></p>
          <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px; margin-top:30px;">
              <?php foreach($products as $p): ?>
                  <div class="glass-card" style="padding:15px; position:relative;">
                      <img src="<?= BASE_URL ?>/<?= $p['image_path'] ?>" style="width:100%; height:180px; object-fit:cover; border-radius:8px; margin-bottom:15px;">
                      <h3><?= e($p['name']) ?></h3>
                      <p style="color:var(--text-muted); font-size:0.9rem; height:40px; overflow:hidden;"><?= e($p['description']) ?></p>
                      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px;">
                          <span style="font-weight:600; font-size:1.1rem;"><?= $seller['currency'] ?><?= number_format($p['price'], 2) ?></span>
                          <?php if($p['status'] === 'available'): ?>
                              <button onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes(e($p['name'])) ?>', <?= $p['price'] ?>, '<?= BASE_URL ?>/<?= $p['image_path'] ?>')" class="btn-primary" style="padding:6px 12px; font-size:0.85rem;">Add</button>
                          <?php else: ?>
                              <span style="color:var(--danger); font-weight:600; font-size:0.9rem;">SOLD</span>
                          <?php endif; ?>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      </div>

      <!-- Cart Drawer -->
      <div id="cart-drawer" class="cart-drawer">
          <div style="display:flex; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid var(--card-border); padding-bottom:10px;">
              <h3>My Cart</h3>
              <button onclick="closeCartDrawer()" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
          </div>
          <div id="cart-items" style="height:calc(100% - 220px); overflow-y:auto;"></div>
          <div style="border-top:1px solid var(--card-border); padding-top:15px; margin-top:10px;">
              <h4>Total: <?= $seller['currency'] ?><span id="cart-total">0.00</span></h4>
              <button onclick="openCheckoutModal()" class="btn-primary" style="width:100%; margin-top:10px;">Proceed to Checkout</button>
          </div>
      </div>

      <!-- Checkout Modal -->
      <div id="checkout-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); align-items:center; justify-content:center; z-index:2000;">
          <div class="glass-card" style="width:100%; max-width:400px;">
              <h3>Checkout Details</h3>
              <form id="checkout-form" method="POST" action="<?= BASE_URL ?>/shop/<?= $seller['username'] ?>/checkout">
                  <input type="hidden" name="cart_data" id="cart-data-input">
                  <div style="margin-bottom:15px">
                      <label>Your Name</label>
                      <input type="text" name="customer_name" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
                  </div>
                  <div style="margin-bottom:15px">
                      <label>Phone Number</label>
                      <input type="text" name="customer_phone" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border)">
                  </div>
                  <div style="margin-bottom:20px">
                      <label>Delivery Address</label>
                      <textarea name="delivery_address" required style="width:100%; padding:10px; border-radius:6px; background:#1e293b; color:#fff; border:1px solid var(--card-border); height:80px;"></textarea>
                  </div>
                  <div style="display:flex; gap:10px;">
                      <button type="submit" class="btn-primary" style="flex:1">Confirm Order</button>
                      <button type="button" onclick="closeCheckoutModal()" class="btn-primary" style="background:rgba(255,255,255,0.05); box-shadow:none; border:1px solid var(--card-border); flex:1">Cancel</button>
                  </div>
              </form>
          </div>
      </div>

      <script>
          function openCheckoutModal() {
              if (cart.length === 0) return alert('Your cart is empty.');
              document.getElementById('cart-data-input').value = JSON.stringify(cart);
              document.getElementById('checkout-modal').style.display = 'flex';
          }
          function closeCheckoutModal() {
              document.getElementById('checkout-modal').style.display = 'none';
          }
      </script>
  </body>
  </html>
  ```

- [ ] **Step 2: Commit**
  Add files and commit.
  Run: `git add views/store/catalog.php`
  Run: `git commit -m "feat: user-facing catalog layout storefront view"`

---

### Task 9: Checkout Order POST Handler

**Files:**
- Create: `views/store/checkout_handler.php`
- Create: `views/dashboard/orders.php`

- [ ] **Step 1: Create the Checkout POST submission script**
  Create `views/store/checkout_handler.php` to handle checkout submissions. This page decodes the JSON cart payload, inserts the order and item records, marks thrift products as `sold` to prevent double buying, and redirects the customer to the success page.
  ```php
  <?php
  // views/store/checkout_handler.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $customer_name = sanitize_input($_POST['customer_name']);
      $customer_phone = sanitize_input($_POST['customer_phone']);
      $delivery_address = sanitize_input($_POST['delivery_address']);
      $cart_items = json_decode($_POST['cart_data'], true);

      if (empty($customer_name) || empty($customer_phone) || empty($delivery_address) || empty($cart_items)) {
          die("Invalid checkout data.");
      }

      $db = DB::connect();
      // Fetch seller
      $stmt = $db->prepare("SELECT id FROM sellers WHERE username = ?");
      $stmt->execute([$shop_username]);
      $seller = $stmt->fetch();
      if (!$seller) die("Seller not found.");

      $db->beginTransaction();
      try {
          // Check product status to prevent race conditions on sold products
          $total_price = 0;
          foreach ($cart_items as $item) {
              $stmt = $db->prepare("SELECT price, status FROM products WHERE id = ? AND seller_id = ? FOR UPDATE");
              $stmt->execute([$item['id'], $seller['id']]);
              $p = $stmt->fetch();
              if (!$p || $p['status'] !== 'available') {
                  throw new Exception("Product " . $item['name'] . " is sold out or unavailable.");
              }
              $total_price += floatval($p['price']) * intval($item['quantity']);
          }

          // Insert order
          $stmt = $db->prepare("INSERT INTO orders (seller_id, customer_name, customer_phone, delivery_address, total_price) VALUES (?, ?, ?, ?, ?)");
          $stmt->execute([$seller['id'], $customer_name, $customer_phone, $delivery_address, $total_price]);
          $order_id = $db->lastInsertId();

          // Insert items & set status = sold
          foreach ($cart_items as $item) {
              $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
              $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);

              $stmt = $db->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
              $stmt->execute([$item['id']]);
          }

          $db->commit();

          // Redirect to success page which handles the JS cart clearing and redirect
          redirect("/shop/" . $shop_username . "/order-success/" . $order_id);
      } catch (Exception $e) {
          $db->rollBack();
          die("Error processing order: " . $e->getMessage());
      }
  }
  ```

- [ ] **Step 2: Create the Admin Orders log page**
  Create `views/dashboard/orders.php` so sellers can review order history, client address logs, order items, and open a direct customer WhatsApp chat window.
  ```php
  <?php
  // views/dashboard/orders.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';
  require_login();

  $db = DB::connect();
  $seller_id = $_SESSION['seller_id'];

  $stmt = $db->prepare("SELECT * FROM orders WHERE seller_id = ? ORDER BY id DESC");
  $stmt->execute([$seller_id]);
  $orders = $stmt->fetchAll();
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Orders Log - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
  </head>
  <body class="admin-layout">
      <?php require __DIR__ . '/../../includes/admin_header.php'; ?>
      <div class="main-content">
          <h1>Orders Received</h1>
          <div class="glass-card" style="margin-top:20px;">
              <table style="width:100%; border-collapse:collapse; text-align:left;">
                  <thead>
                      <tr style="border-bottom:1px solid var(--card-border);">
                          <th style="padding:12px;">Order ID</th>
                          <th style="padding:12px;">Customer</th>
                          <th style="padding:12px;">Delivery Address</th>
                          <th style="padding:12px;">Total Price</th>
                          <th style="padding:12px;">Action</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach($orders as $o): ?>
                          <tr style="border-bottom:1px solid rgba(255,255,255,0.02)">
                              <td style="padding:12px;">#<?= $o['id'] ?></td>
                              <td style="padding:12px;">
                                  <strong><?= e($o['customer_name']) ?></strong><br>
                                  <span style="color:var(--text-muted); font-size:0.85rem;"><?= e($o['customer_phone']) ?></span>
                              </td>
                              <td style="padding:12px;"><?= e($o['delivery_address']) ?></td>
                              <td style="padding:12px; font-weight:600;"><?= number_format($o['total_price'], 2) ?></td>
                              <td style="padding:12px;">
                                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $o['customer_phone']) ?>" target="_blank" class="btn-primary" style="padding:6px 12px; font-size:0.75rem; text-decoration:none;">Message</a>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 3: Commit**
  Add files and commit.
  Run: `git add views/store/checkout_handler.php views/dashboard/orders.php`
  Run: `git commit -m "feat: order processing handlers and dashboard orders logger"`

---

### Task 10: WhatsApp Redirect Success Screen

**Files:**
- Create: `views/store/order_success.php`

- [ ] **Step 1: Create the WhatsApp redirect template**
  Create `views/store/order_success.php`. This page reads the order details, displays a loading interface, clears the `localStorage` cart, formats a WhatsApp string template, and automatically redirects the customer.
  ```php
  <?php
  // views/store/order_success.php
  require_once __DIR__ . '/../../includes/db.php';
  require_once __DIR__ . '/../../includes/functions.php';

  $db = DB::connect();

  // Fetch shop metadata
  $stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
  $stmt->execute([$shop_username]);
  $seller = $stmt->fetch();

  if (!$seller) die("Store not found.");

  // Fetch order metadata
  $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND seller_id = ?");
  $stmt->execute([$order_id, $seller['id']]);
  $order = $stmt->fetch();

  if (!$order) die("Order not found.");

  // Fetch order items
  $stmt = $db->prepare("
      SELECT oi.*, p.name 
      FROM order_items oi 
      JOIN products p ON oi.product_id = p.id 
      WHERE oi.order_id = ?
  ");
  $stmt->execute([$order['id']]);
  $items = $stmt->fetchAll();

  // Format message text
  $msg = "Hello " . $seller['shop_name'] . ", I'd like to place an order:\n\n";
  $msg .= "🛒 ORDER DETAILS (ID: #" . $order['id'] . ")\n";
  $msg .= "-----------------------------\n";
  foreach ($items as $item) {
      $msg .= "• " . $item['quantity'] . "x " . $item['name'] . " - " . $seller['currency'] . number_format($item['price'], 2) . "\n";
  }
  $msg .= "\n💰 TOTAL: " . $seller['currency'] . number_format($order['total_price'], 2) . "\n\n";
  $msg .= "🚚 CUSTOMER INFO\n";
  $msg .= "-----------------------------\n";
  $msg .= "Name: " . $order['customer_name'] . "\n";
  $msg .= "Phone: " . $order['customer_phone'] . "\n";
  $msg .= "Delivery Address: " . $order['delivery_address'] . "\n\n";
  $msg .= "Click this link to confirm: " . BASE_URL . "/shop/" . $seller['username'] . "/order-success/" . $order['id'] . "\n";

  $wa_url = "https://wa.me/" . $seller['whatsapp_number'] . "?text=" . urlencode($msg);
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <title>Order Confirmed - Storelo</title>
      <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
      <script>
          // Clear cart on success
          localStorage.removeItem('storelo_cart');

          // Auto redirect
          window.addEventListener('DOMContentLoaded', () => {
              setTimeout(() => {
                  window.location.href = "<?= $wa_url ?>";
              }, 2500);
          });
      </script>
  </head>
  <body style="display:flex; align-items:center; justify-content:center; min-height:100vh; text-align:center;">
      <div class="glass-card" style="max-width:500px; padding:40px;">
          <div style="font-size:4rem; color:var(--success); margin-bottom:20px;">✓</div>
          <h2>Order Saved Successfully!</h2>
          <p style="color:var(--text-muted); margin: 15px 0 30px 0;">
              Your order has been logged. Redirecting you to WhatsApp to finalize your order with <strong><?= e($seller['shop_name']) ?></strong>...
          </p>
          <a href="<?= $wa_url ?>" class="btn-primary" style="text-decoration:none;">Go to WhatsApp Now</a>
      </div>
  </body>
  </html>
  ```

- [ ] **Step 2: Commit**
  Add files and commit.
  Run: `git add views/store/order_success.php`
  Run: `git commit -m "feat: complete order success redirection handlers"`
