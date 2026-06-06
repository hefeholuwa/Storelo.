<?php
// views/verify_email.php — Handles email verification links
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('/login');
}

$db = DB::connect();
$stmt = $db->prepare("SELECT id FROM sellers WHERE verification_token = ? AND email_verified = 0");
$stmt->execute([$token]);
$seller = $stmt->fetch();

if ($seller) {
    // Valid token! Verify the email.
    $stmt = $db->prepare("UPDATE sellers SET email_verified = 1, verification_token = NULL WHERE id = ?");
    $stmt->execute([$seller['id']]);

    // Redirect to login with a success message
    redirect('/login?msg=verified');
} else {
    // Invalid or already used token
    $error = "This verification link is invalid or has already been used.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Failed — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .auth-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg); padding: 24px; }
        .auth-card { width: 100%; max-width: 400px; padding: 40px; text-align: center; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="auth-wrapper">
        <div class="glass-card auth-card no-hover">
            <h2 style="margin-bottom: 12px; color: #dc2626;">Verification Failed</h2>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">
                <?= e($error) ?>
            </p>
            <div style="margin-top: 32px;">
                <a href="<?= BASE_URL ?>/login" class="btn-primary" style="text-decoration: none; display: inline-block;">Go to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
