<?php
// views/forgot_password.php — Request password reset
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/dashboard');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(strtolower(trim($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $db = DB::connect();
        $stmt = $db->prepare("SELECT id FROM sellers WHERE email = ?");
        $stmt->execute([$email]);
        $seller = $stmt->fetch();

        if ($seller) {
            $token = bin2hex(random_bytes(32));
            $stmt = $db->prepare("UPDATE sellers SET password_reset_token = ? WHERE id = ?");
            $stmt->execute([$token, $seller['id']]);

            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/reset-password?token=" . $token;
            require_once __DIR__ . '/../includes/mailer.php';
            $subject = "Reset your Storelo password";
            $html = "
            <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #F58320;'>Password Reset Request</h2>
                <p>We received a request to reset your password. Click the button below to choose a new one:</p>
                <div style='margin: 30px 0;'>
                    <a href='{$reset_link}' style='background-color: #F58320; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Reset Password</a>
                </div>
                <p>If you didn't request this, you can safely ignore this email.</p>
                <hr style='border: none; border-top: 1px solid #eaeaea; margin-top: 30px;' />
                <p style='color: #6b7280; font-size: 0.85rem;'>The Storelo Team</p>
            </div>";
            
            send_email($email, "", $subject, $html);
        }
        
        // Always show success message to prevent email enumeration attacks
        $success = "If your email is registered, you will receive a password reset link shortly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Storelo</title>
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
            <h2 style="margin-bottom: 8px;">Reset Password</h2>
            <p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
                <div style="margin-top: 24px;">
                    <a href="<?= BASE_URL ?>/login" class="btn-primary" style="display: block; text-decoration: none;">Return to Login</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. hello@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Send Reset Link</button>
                </form>
            <?php endif; ?>

            <p class="auth-footer" style="margin-top: 24px;">
                Remembered your password? <a href="<?= BASE_URL ?>/login">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>
