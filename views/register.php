<?php
// views/register.php — Seller registration
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/dashboard');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', trim($_POST['username'] ?? '')));
    $password = $_POST['password'] ?? '';
    $shop_name = sanitize_input($_POST['shop_name'] ?? '');
    $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');

    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters (letters, numbers, hyphens, underscores).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (empty($shop_name)) {
        $error = "Shop name is required.";
    } elseif (strlen($whatsapp_number) < 10) {
        $error = "Enter a valid WhatsApp number with country code (e.g. 2348031234567).";
    } else {
        $db = DB::connect();
        $stmt = $db->prepare("SELECT id FROM sellers WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "This username is already taken. Try another.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO sellers (username, password, shop_name, whatsapp_number) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed, $shop_name, $whatsapp_number]);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Store — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="glass-card auth-card no-hover">
            <a href="<?= BASE_URL ?>/" style="font-size:1.3rem; font-weight:800; background:var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; display:inline-block; margin-bottom:24px;">Storelo</a>
            <h2>Create your store</h2>
            <p class="auth-subtitle">Set up your thrift catalog in under 2 minutes.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label>Store Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. retro-thrift" required value="<?= e($_POST['username'] ?? '') ?>">
                    <small style="color:var(--text-muted); font-size:0.8rem; margin-top:4px; display:block;">
                        Your store link: storelo.page.gd/shop/<strong>your-username</strong>
                    </small>
                </div>
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" name="shop_name" class="form-control" placeholder="e.g. Retro Thrift Store" required value="<?= e($_POST['shop_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="form-control" placeholder="e.g. 2348031234567" required value="<?= e($_POST['whatsapp_number'] ?? '') ?>">
                    <small style="color:var(--text-muted); font-size:0.8rem; margin-top:4px; display:block;">
                        Include country code, no spaces or signs.
                    </small>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Create Store</button>
            </form>

            <p class="auth-footer">
                Already have a store? <a href="<?= BASE_URL ?>/login">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>
