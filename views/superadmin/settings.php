<?php
// views/superadmin/settings.php — Platform Settings (Maintenance, Announcements, Contact)
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_superadmin();

$db = DB::connect();
$success = '';
$error = '';

// Helper to get a setting value
function get_setting($db, $key, $default = '') {
    $stmt = $db->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $maintenance = isset($_POST['maintenance_mode']) ? '1' : '0';
        $announcement_active = isset($_POST['announcement_active']) ? '1' : '0';
        $announcement_text = trim($_POST['announcement_text'] ?? '');
        $contact_email = trim($_POST['platform_contact_email'] ?? '');

        $stmt = $db->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['maintenance_mode', $maintenance]);
        $stmt->execute(['announcement_active', $announcement_active]);
        $stmt->execute(['announcement_text', $announcement_text]);
        $stmt->execute(['platform_contact_email', $contact_email]);

        $success = "Platform settings saved successfully!";
    }
}

// Load current settings
$maintenance_mode = get_setting($db, 'maintenance_mode', '0');
$announcement_active = get_setting($db, 'announcement_active', '0');
$announcement_text = get_setting($db, 'announcement_text', '');
$platform_contact_email = get_setting($db, 'platform_contact_email', 'admin@storelo.com');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Settings — Storelo Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 24px;
        }
        .settings-card {
            background: #fff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 28px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .settings-card h3 {
            font-size: 1.1rem; font-weight: 700; margin-bottom: 6px;
        }
        .settings-card .card-desc {
            font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5;
        }
        .toggle-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 0; border-bottom: 1px solid var(--border-subtle);
        }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-label { font-weight: 600; font-size: 0.95rem; }
        .toggle-sublabel { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }
        /* Toggle switch */
        .switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #d1d5db; border-radius: 26px; transition: 0.3s;
        }
        .slider:before {
            position: absolute; content: ""; height: 20px; width: 20px;
            left: 3px; bottom: 3px; background-color: white;
            border-radius: 50%; transition: 0.3s;
        }
        input:checked + .slider { background-color: #F68B1E; }
        input:checked + .slider:before { transform: translateX(22px); }
        .maintenance-warning {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            padding: 12px 16px; margin-top: 12px; font-size: 0.85rem; color: #991b1b;
            display: none;
        }
        .maintenance-warning.visible { display: block; }
        .announcement-preview {
            margin-top: 12px; padding: 12px 16px; border-radius: 8px;
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            border: 1px solid #fde68a; font-size: 0.9rem; color: #92400e;
            display: none;
        }
        .announcement-preview.visible { display: block; }
    </style>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
</head>
<body>
    <div class="admin-layout">
        <?php require __DIR__ . '/../../includes/superadmin_header.php'; ?>

        <div class="main-content">
            <div style="margin-bottom: 24px;">
                <h1>⚙️ Platform Settings</h1>
                <p class="page-subtitle" style="margin-bottom: 0;">Control maintenance mode, announcements, and global platform configuration.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="save_settings">

                <div class="settings-grid">

                    <!-- Maintenance Mode -->
                    <div class="settings-card">
                        <h3>🔧 Maintenance Mode</h3>
                        <p class="card-desc">When enabled, all public storefronts will show a "Under Maintenance" message. Sellers can still log into their dashboards.</p>

                        <div class="toggle-row">
                            <div>
                                <div class="toggle-label">Enable Maintenance Mode</div>
                                <div class="toggle-sublabel">Storefronts will be temporarily unavailable</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="maintenance_mode" id="maintenanceToggle" <?= $maintenance_mode === '1' ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="maintenance-warning <?= $maintenance_mode === '1' ? 'visible' : '' ?>" id="maintenanceWarning">
                            ⚠️ <strong>Maintenance mode is ON.</strong> All public storefronts are currently inaccessible to customers.
                        </div>
                    </div>

                    <!-- Global Announcement -->
                    <div class="settings-card">
                        <h3>📢 Global Announcement</h3>
                        <p class="card-desc">Broadcast a message to all sellers. It will appear as a banner at the top of every seller's dashboard.</p>

                        <div class="toggle-row" style="border-bottom: 1px solid var(--border-subtle);">
                            <div>
                                <div class="toggle-label">Enable Announcement</div>
                                <div class="toggle-sublabel">Show banner in seller dashboards</div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="announcement_active" id="announcementToggle" <?= $announcement_active === '1' ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="form-group" style="margin-top: 16px;">
                            <label style="font-weight: 600; font-size: 0.9rem;">Announcement Message</label>
                            <textarea name="announcement_text" id="announcementText" class="form-control" rows="3" placeholder="e.g. System maintenance tonight at 10pm. Your store will be temporarily unavailable."><?= e($announcement_text) ?></textarea>
                        </div>

                        <div class="announcement-preview <?= $announcement_active === '1' && $announcement_text ? 'visible' : '' ?>" id="announcementPreview">
                            <strong>📢 Preview:</strong> <span id="previewText"><?= e($announcement_text) ?></span>
                        </div>
                    </div>

                    <!-- Platform Contact -->
                    <div class="settings-card">
                        <h3>📧 Platform Contact</h3>
                        <p class="card-desc">The primary contact email for the platform. This may be shown to sellers who need support.</p>

                        <div class="form-group">
                            <label style="font-weight: 600; font-size: 0.9rem;">Contact Email</label>
                            <input type="email" name="platform_contact_email" class="form-control" value="<?= e($platform_contact_email) ?>" placeholder="admin@storelo.com">
                        </div>
                    </div>

                </div>

                <div style="margin-top: 28px; display: flex; gap: 12px;">
                    <button type="submit" class="btn-primary" style="padding: 12px 32px; font-size: 1rem;">💾 Save All Settings</button>
                </div>
            </form>
            
        </div>
    </div>

    <script>
        // Live preview for maintenance warning
        const maintenanceToggle = document.getElementById('maintenanceToggle');
        const maintenanceWarning = document.getElementById('maintenanceWarning');
        maintenanceToggle.addEventListener('change', function() {
            maintenanceWarning.classList.toggle('visible', this.checked);
        });

        // Live preview for announcement
        const announcementToggle = document.getElementById('announcementToggle');
        const announcementText = document.getElementById('announcementText');
        const announcementPreview = document.getElementById('announcementPreview');
        const previewText = document.getElementById('previewText');

        function updateAnnouncementPreview() {
            const active = announcementToggle.checked;
            const text = announcementText.value.trim();
            announcementPreview.classList.toggle('visible', active && text.length > 0);
            previewText.textContent = text;
        }

        announcementToggle.addEventListener('change', updateAnnouncementPreview);
        announcementText.addEventListener('input', updateAnnouncementPreview);
    </script>
</body>
</html>
