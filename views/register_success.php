<?php
// views/register_success.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$email = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Email — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .auth-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg); padding: 24px; }
        .auth-card { width: 100%; max-width: 400px; padding: 40px; text-align: center; }
        .icon-circle { width: 64px; height: 64px; background: rgba(59, 130, 246, 0.1); color: var(--brand); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="auth-wrapper">
        <div class="glass-card auth-card no-hover">
            <div class="icon-circle">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </div>
            <h2 style="margin-bottom: 12px;">Check your email</h2>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                We've sent a verification link to <strong><?= e($email) ?></strong>. Please check your inbox and click the link to activate your store.
            </p>
            <p style="font-size: 0.85rem; color: var(--text-muted);">
                If you don't see it, check your spam folder.
            </p>
            <div style="margin-top: 32px;">
                <a href="<?= BASE_URL ?>/login" class="btn-primary" style="text-decoration: none; display: inline-block;">Go to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
