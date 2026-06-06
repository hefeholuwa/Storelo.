<?php
// views/register.php — Seller registration
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect('/dashboard');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(strtolower(trim($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $shop_name = sanitize_input($_POST['shop_name'] ?? '');
    $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (empty($shop_name)) {
        $error = "Shop name is required.";
    } elseif (strlen($whatsapp_number) < 10) {
        $error = "Enter a valid WhatsApp number with country code (e.g. 2348031234567).";
    } else {
        $db = DB::connect();
        
        // Auto-generate unique username (slug) from shop_name
        $base_username = preg_replace('/[^a-zA-Z0-9]/', '', trim($shop_name));
        if (empty($base_username)) $base_username = 'store';
        
        $username = $base_username;
        $counter = 1;
        while (true) {
            $stmt = $db->prepare("SELECT id FROM sellers WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) {
                break;
            }
            $username = $base_username . '-' . $counter;
            $counter++;
        }
        
        // Check if email is taken
        $stmt = $db->prepare("SELECT id FROM sellers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "This email is already registered. Please log in.";
            } else {
                $requires_verification = REQUIRE_EMAIL_VERIFICATION;
                $token = $requires_verification ? bin2hex(random_bytes(32)) : null;
                $email_verified = $requires_verification ? 0 : 1;
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO sellers (username, email, password, shop_name, whatsapp_number, verification_token, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed, $shop_name, $whatsapp_number, $token, $email_verified]);
                $seller_id = $db->lastInsertId();

                if ($requires_verification) {
                    require_once __DIR__ . '/../includes/mailer.php';
                    $verification_link = BASE_URL . "/verify?token=" . urlencode($token);
                    
                    $subject = "Verify your Storelo account";
                    $html = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #F58320;'>Welcome to Storelo!</h2>
                        <p>Hi {$username},</p>
                        <p>Thanks for creating a store. Please click the button below to verify your email address and activate your account:</p>
                        <div style='margin: 30px 0;'>
                            <a href='{$verification_link}' style='background-color: #F58320; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Verify Email</a>
                        </div>
                        <p>If the button doesn't work, copy and paste this link into your browser:</p>
                        <p><a href='{$verification_link}' style='color: #2563EB;'>{$verification_link}</a></p>
                        <hr style='border: none; border-top: 1px solid #eaeaea; margin-top: 30px;' />
                        <p style='color: #6b7280; font-size: 0.85rem;'>The Storelo Team</p>
                    </div>";
                    
                    send_email($email, $username, $subject, $html);

                    redirect('/register-success?email=' . urlencode($email));
                }

                $_SESSION['seller_id'] = $seller_id;
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
            <h2 style="margin-bottom: 8px;">Create your store</h2>
            <p class="auth-subtitle">Set up your online store in under 2 minutes.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. hello@example.com" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" name="shop_name" class="form-control" placeholder="e.g. My Digital Store" required value="<?= e($_POST['shop_name'] ?? '') ?>">
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
