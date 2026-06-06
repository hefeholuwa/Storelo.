<?php
// views/dashboard/profile.php — Shop settings (logo, WhatsApp, currency, etc.)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$db = DB::connect();
$seller_id = $_SESSION['seller_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_account'])) {
        $password = $_POST['delete_password'] ?? '';
        $stmt_seller = $db->prepare("SELECT password FROM sellers WHERE id = ?");
        $stmt_seller->execute([$seller_id]);
        $seller_pass = $stmt_seller->fetchColumn();

        if (password_verify($password, $seller_pass)) {
            $stmt = $db->prepare("UPDATE sellers SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$seller_id]);
            session_destroy();
            redirect('/login?msg=deleted');
        } else {
            $error = "Incorrect password. Account deletion failed.";
        }
    } else {
        $shop_name = sanitize_input($_POST['shop_name'] ?? '');
    $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');
    $currency = sanitize_input($_POST['currency'] ?? '₦');
    $shop_description = sanitize_input($_POST['shop_description'] ?? '');
    $theme_color = sanitize_input($_POST['theme_color'] ?? '#F68B1E');
    $payment_instructions = sanitize_input($_POST['payment_instructions'] ?? '');
    // Fetch existing seller data first so we don't overwrite logos with null
    $stmt_seller = $db->prepare("SELECT logo_path, banner_path FROM sellers WHERE id = ?");
    $stmt_seller->execute([$seller_id]);
    $current_seller = $stmt_seller->fetch();

    if (empty($shop_name)) {
        $error = "Shop name is required.";
    } elseif (strlen($whatsapp_number) < 10) {
        $error = "Enter a valid WhatsApp number with country code.";
    } else {
        $logo_path = $current_seller['logo_path'] ?? null;
        $banner_path = $current_seller['banner_path'] ?? null;

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_name = $_FILES['logo']['name'];
            $file_size = $_FILES['logo']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($file_ext, $allowed) || !in_array($mime_type, $allowed_mimes)) {
                $error = "Invalid file type. Use JPG, PNG, or WEBP.";
            } elseif ($file_size > 2097152) {
                $error = "Logo must be less than 2MB.";
            } else {
                $new_name = uniqid('logo_', true) . '.' . $file_ext;
                $dest = __DIR__ . '/../../uploads/logos/' . $new_name;
                if (move_uploaded_file($file_tmp, $dest)) {
                    $logo_path = 'uploads/logos/' . $new_name;
                } else {
                    $error = "Failed to upload logo.";
                }
            }
        }

        // Handle banner upload
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['banner']['tmp_name'];
            $file_name = $_FILES['banner']['name'];
            $file_size = $_FILES['banner']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($file_ext, $allowed) || !in_array($mime_type, $allowed_mimes)) {
                $error = "Invalid file type for banner. Use JPG, PNG, or WEBP.";
            } elseif ($file_size > 5242880) { // 5MB max
                $error = "Banner must be less than 5MB.";
            } else {
                $new_name = uniqid('banner_', true) . '.' . $file_ext;
                $dest = __DIR__ . '/../../uploads/logos/' . $new_name; // Reuse logos folder for banners
                if (move_uploaded_file($file_tmp, $dest)) {
                    $banner_path = 'uploads/logos/' . $new_name;
                } else {
                    $error = "Failed to upload banner.";
                }
            }
        }

        if (empty($error)) {
            // Auto-generate unique username (slug) from new shop_name
            $base_username = preg_replace('/[^a-zA-Z0-9]/', '', trim($shop_name));
            if (empty($base_username)) $base_username = 'store';
            
            $new_username = $base_username;
            $counter = 1;
            while (true) {
                // exclude current seller from uniqueness check
                $stmt_check = $db->prepare("SELECT id FROM sellers WHERE username = ? AND id != ?");
                $stmt_check->execute([$new_username, $seller_id]);
                if (!$stmt_check->fetch()) {
                    break;
                }
                $new_username = $base_username . '-' . $counter;
                $counter++;
            }

            $stmt = $db->prepare("UPDATE sellers SET username=?, shop_name=?, whatsapp_number=?, currency=?, shop_description=?, theme_color=?, payment_instructions=?, logo_path=?, banner_path=? WHERE id=?");
            $stmt->execute([$new_username, $shop_name, $whatsapp_number, $currency, $shop_description, $theme_color, $payment_instructions, $logo_path, $banner_path, $seller_id]);
            $_SESSION['username'] = $new_username; // Update session so it reflects everywhere
            $success = "Profile and Store Link updated successfully!";
        }
        }
    }
}

// Reload seller data
$stmt = $db->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Settings — Storelo</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1>Shop Settings</h1>
                    <p class="page-subtitle" style="margin-bottom: 0;">Configure how your store looks to customers.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="glass-card" style="max-width:800px;">
                
                <div class="dashboard-half-grid">
                    <div>
                        <div class="form-group">
                            <label style="display: flex; align-items: center; justify-content: space-between;">
                                Shop Name
                                <?php if (!empty($seller['is_verified'])): ?>
                                    <span style="color: #3b82f6; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M22.5 12.854c0 .355-.099.7-.282.993l-1.63 2.658c-.144.234-.236.5-.27.773l-.403 3.107c-.048.373-.207.72-.456.992-.25.272-.572.464-.93.551l-3.05.748c-.27.067-.522.189-.738.358l-2.484 1.94c-.292.228-.646.347-1.01.347s-.718-.119-1.01-.347l-2.484-1.94c-.216-.169-.468-.291-.738-.358l-3.05-.748c-.358-.087-.68-.279-.93-.551-.249-.272-.408-.619-.456-.992l-.403-3.107c-.034-.273-.126-.539-.27-.773L2.28 13.847a1.868 1.868 0 0 1 0-1.986l1.63-2.658c.144-.234.236-.5.27-.773l.403-3.107c.048-.373.207-.72.456-.992.25-.272.572-.464.93-.551l3.05-.748c.27-.067.522-.189.738-.358l2.484-1.94c.292-.228.646-.347 1.01-.347s.718.119 1.01.347l2.484 1.94c.216.169.468.291.738.358l3.05.748c.358.087.68.279.93.551.249.272.408.619.456.992l.403 3.107c.034.273.126.539.27.773l1.63 2.658c.183.293.282.638.282.993Z"></path><path d="M16 9.5 10.5 15 8 12.5" stroke="#ffffff" stroke-width="2.5"></path></svg>
                                        VERIFIED
                                    </span>
                                <?php endif; ?>
                            </label>
                            <input type="text" name="shop_name" class="form-control" value="<?= e($seller['shop_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>WhatsApp Number (with country code)</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="<?= e($seller['whatsapp_number']) ?>" required placeholder="e.g. 2348031234567">
                        </div>

                        <div class="form-group">
                            <label>Currency Symbol</label>
                            <select name="currency" class="form-control">
                                <?php
                                $currencies = ['₦' => '₦ Naira', '$' => '$ Dollar', 'R' => 'R Rand', '£' => '£ Pound', '€' => '€ Euro', '₵' => '₵ Cedi', 'KSh' => 'KSh Shilling'];
                                foreach ($currencies as $symbol => $label):
                                ?>
                                    <option value="<?= e($symbol) ?>" <?= ($seller['currency'] ?? '₦') === $symbol ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label>Shop Description</label>
                            <textarea name="shop_description" class="form-control" placeholder="Tell customers what you sell..."><?= e($seller['shop_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Instructions</label>
                            <textarea name="payment_instructions" class="form-control" placeholder="e.g. Please transfer to GTBank 0123456789 - John Doe. Send receipt to WhatsApp."><?= e($seller['payment_instructions'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Store Theme Color</label>
                            <input type="color" name="theme_color" class="form-control" value="<?= e($seller['theme_color'] ?? '#F68B1E') ?>" style="height: 50px; padding: 4px;">
                        </div>
                    </div>
                </div>

                <div class="dashboard-half-grid" style="margin-top: 12px;">
                    <div class="form-group" style="padding: 20px; border: 2px dashed var(--border-subtle); border-radius: var(--radius-md); text-align: center; background: #fafafa;">
                        <label style="font-size: 1.1rem; color: var(--text-primary);">Shop Logo</label>
                        <p style="color:var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">JPG/PNG/WEBP, max 2MB.</p>
                        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">
                        <?php if (!empty($seller['logo_path'])): ?>
                            <div style="margin-top:16px;">
                                <img src="<?= BASE_URL ?>/<?= $seller['logo_path'] ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" style="padding: 20px; border: 2px dashed var(--border-subtle); border-radius: var(--radius-md); text-align: center; background: #fafafa;">
                        <label style="font-size: 1.1rem; color: var(--text-primary);">Storefront Banner</label>
                        <p style="color:var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">1200x400 recommended. JPG/PNG/WEBP, max 5MB.</p>
                        <input type="file" name="banner" accept=".jpg,.jpeg,.png,.webp">
                        <?php if (!empty($seller['banner_path'])): ?>
                            <div style="margin-top:16px;">
                                <img src="<?= BASE_URL ?>/<?= $seller['banner_path'] ?>" style="width:100%; height:80px; border-radius:8px; object-fit:cover; border:3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="width:100%;">Save Changes</button>
            </form>

            <div class="glass-card" style="max-width:800px; margin-top: 40px; border-color: #fecaca; background: #fffcfc;">
                <h3 style="color: #dc2626; margin-bottom: 16px; font-weight: 600;">Danger Zone</h3>
                <p style="color: var(--text-secondary); margin-bottom: 24px; font-size: 0.95rem;">
                    Closing your store will immediately hide your storefront, products, and categories from customers. You will lose access to this dashboard. Your historical order data will be retained for platform records. <strong>This action cannot be undone.</strong>
                </p>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to close your store? This cannot be undone.');" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 250px;">
                        <label style="color: #dc2626;">Confirm Password to Delete</label>
                        <input type="password" name="delete_password" class="form-control" required placeholder="Enter your password">
                    </div>
                    <button type="submit" name="delete_account" class="btn-primary" style="background: #dc2626; color: white;">Close My Store</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
