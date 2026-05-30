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
    $shop_name = sanitize_input($_POST['shop_name'] ?? '');
    $whatsapp_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '');
    $currency = sanitize_input($_POST['currency'] ?? '₦');
    $delivery_info = sanitize_input($_POST['delivery_info'] ?? '');
    $shop_description = sanitize_input($_POST['shop_description'] ?? '');

    if (empty($shop_name)) {
        $error = "Shop name is required.";
    } elseif (strlen($whatsapp_number) < 10) {
        $error = "Enter a valid WhatsApp number with country code.";
    } else {
        $logo_path = null;

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_name = $_FILES['logo']['name'];
            $file_size = $_FILES['logo']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed)) {
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

        if (empty($error)) {
            if ($logo_path) {
                $stmt = $db->prepare("UPDATE sellers SET shop_name=?, whatsapp_number=?, currency=?, delivery_info=?, shop_description=?, logo_path=? WHERE id=?");
                $stmt->execute([$shop_name, $whatsapp_number, $currency, $delivery_info, $shop_description, $logo_path, $seller_id]);
            } else {
                $stmt = $db->prepare("UPDATE sellers SET shop_name=?, whatsapp_number=?, currency=?, delivery_info=?, shop_description=? WHERE id=?");
                $stmt->execute([$shop_name, $whatsapp_number, $currency, $delivery_info, $shop_description, $seller_id]);
            }
            $success = "Profile updated successfully!";
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
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/admin_header.php'; ?>

        <div class="main-content">
            <h1>Shop Settings</h1>
            <p class="page-subtitle">Configure how your store looks to customers.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="glass-card" style="max-width:600px;">
                <div class="form-group">
                    <label>Shop Name</label>
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

                <div class="form-group">
                    <label>Shop Description</label>
                    <textarea name="shop_description" class="form-control" placeholder="Tell customers what you sell..."><?= e($seller['shop_description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Delivery / Shipping Info</label>
                    <input type="text" name="delivery_info" class="form-control" value="<?= e($seller['delivery_info'] ?? '') ?>" placeholder="e.g. Flat delivery: ₦2,000 within Lagos">
                </div>

                <div class="form-group">
                    <label>Shop Logo</label>
                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" style="margin-top:6px;">
                    <?php if (!empty($seller['logo_path'])): ?>
                        <div style="margin-top:12px;">
                            <img src="<?= BASE_URL ?>/<?= $seller['logo_path'] ?>" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--border-subtle);">
                            <small style="display:block; color:var(--text-muted); margin-top:4px;">Current logo</small>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-primary" style="width:100%;">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
