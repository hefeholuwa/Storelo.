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
        $error = "Please enter both your email/username and password.";
    } else {
        $db = DB::connect();
        $stmt = $db->prepare("SELECT * FROM sellers WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $seller = $stmt->fetch();

        if ($seller && password_verify($password, $seller['password'])) {
            if (!empty($seller['is_banned'])) {
                $error = "Your account has been banned. Contact support if you believe this is a mistake.";
            } elseif (!empty($seller['is_deleted'])) {
                $error = "This account has been closed.";
            } elseif (REQUIRE_EMAIL_VERIFICATION && isset($seller['email_verified']) && $seller['email_verified'] == 0) {
                $error = "Please check your email and verify your account before logging in.";
            } else {
                $_SESSION['seller_id'] = $seller['id'];
                $_SESSION['username'] = $seller['username'];
                redirect('/dashboard');
            }
        } else {
            $error = "Invalid username/email or password.";
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
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="auth-wrapper">
        <div class="glass-card auth-card no-hover">
            <div style="margin-bottom: 24px;">
                <a href="<?= BASE_URL ?>/" style="font-size: 0.9rem; color: #6b7280; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Home
                </a>
            </div>
            <h2 style="margin-bottom: 8px;">Welcome back</h2>
            <p class="auth-subtitle">Log in to manage your online store.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'verified'): ?>
                <div class="alert alert-success">Email verified successfully! You can now log in.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'password_reset'): ?>
                <div class="alert alert-success">Password reset successfully! You can now log in.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                <div class="alert alert-success">Your account has been successfully closed.</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address or Store Handle</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. hello@example.com or retro-thrift" required value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="margin-bottom: 0;">Password</label>
                        <a href="<?= BASE_URL ?>/forgot-password" style="font-size: 0.8rem; color: #3b82f6; text-decoration: none;">Forgot password?</a>
                    </div>
                    <input type="password" name="password" class="form-control" placeholder="Your password" required style="margin-top: 6px;">
                </div>
                <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Log In</button>
            </form>

            <p class="auth-footer">
                Don't have a store yet? <a href="<?= BASE_URL ?>/register">Create one</a>
            </p>
        </div>
    </div>
</body>
</html>
