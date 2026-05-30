<?php
// views/login.php — Seller login
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/dashboard');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $db = DB::connect();
        $stmt = $db->prepare("SELECT * FROM sellers WHERE username = ?");
        $stmt->execute([$username]);
        $seller = $stmt->fetch();

        if ($seller && password_verify($password, $seller['password'])) {
            $_SESSION['seller_id'] = $seller['id'];
            $_SESSION['username'] = $seller['username'];
            redirect('/dashboard');
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="glass-card auth-card no-hover">
            <a href="<?= BASE_URL ?>/" style="font-size:1.3rem; font-weight:800; background:var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; display:inline-block; margin-bottom:24px;">Storelo</a>
            <h2>Welcome back</h2>
            <p class="auth-subtitle">Log in to manage your thrift store.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Your store username" required value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Your password" required>
                </div>
                <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Log In</button>
            </form>

            <p class="auth-footer">
                Don't have a store? <a href="<?= BASE_URL ?>/register">Create one free</a>
            </p>
        </div>
    </div>
</body>
</html>
