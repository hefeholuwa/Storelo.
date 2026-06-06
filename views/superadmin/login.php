<?php
// views/superadmin/login.php — Super Admin Login
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as superadmin
if (isset($_SESSION['admin_id'])) {
    redirect('/superadmin/dashboard');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password.";
    } else {
        $db = DB::connect();
        $stmt = $db->prepare("SELECT id, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $username;
            redirect('/superadmin/dashboard');
        } else {
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body style="background: #F3F4F6; display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div style="background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px;">
        
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2rem; margin: 0 0 8px 0;">
                <span style="color: var(--accent);">Store</span><span style="color: #1A1F2B;">lo Admin</span>
            </h1>
            <p style="color: var(--text-mute); margin: 0;">Platform Owner Access</p>
        </div>

        <?php if ($error): ?>
            <div style="background: #FEF2F2; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem; border: 1px solid #F87171;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px;">Admin Email</label>
                <input type="email" name="username" required 
                       style="width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid var(--border-light); font-family: inherit; font-size: 1rem; outline: none; transition: border-color 0.2s;"
                       placeholder="Enter your email address">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 12px 16px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='#E5E7EB'">
            </div>

            <button type="submit" style="width: 100%; padding: 14px; background: var(--accent); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 1.05rem; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='var(--accent-light)'" onmouseout="this.style.background='var(--accent)'">
                Login to Admin Panel
            </button>
        </form>
    </div>

</body>
</html>
