<?php
// views/reset_password.php — Set a new password
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/dashboard');

$token = $_GET['token'] ?? '';
if (empty($token)) {
    redirect('/login');
}

$db = DB::connect();
$stmt = $db->prepare("SELECT id FROM sellers WHERE password_reset_token = ?");
$stmt->execute([$token]);
$seller = $stmt->fetch();

if (!$seller) {
    die("Invalid or expired password reset link.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE sellers SET password = ?, password_reset_token = NULL WHERE id = ?");
        $stmt->execute([$hashed, $seller['id']]);

        // Redirect to login with success message
        redirect('/login?msg=password_reset');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="auth-wrapper">
        <div class="glass-card auth-card no-hover">
            <a href="<?= BASE_URL ?>/" style="font-size:1.3rem; font-weight:800; color: #111827; display:inline-block; margin-bottom:24px;">Storelo</a>
            <h2>Set New Password</h2>
            <p class="auth-subtitle">Please enter your new password below.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Type password again" required>
                </div>
                <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>
