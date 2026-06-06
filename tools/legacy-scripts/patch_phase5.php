<?php
// patch_phase5.php - Run once to add super admin tables and seller suspension flag
require_once dirname(__DIR__, 2) . '/includes/db.php';

try {
    $db = DB::connect();

    // 1. Create admins table
    $db->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ admins table created.<br>";

    // 2. Insert default admin if no admins exist
    $stmt = $db->query("SELECT COUNT(*) FROM admins");
    if ($stmt->fetchColumn() == 0) {
        $default_username = 'admin';
        $default_password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$default_username, $default_password]);
        echo "✅ Default admin account created (admin / password123).<br>";
    } else {
        echo "✅ Admin account already exists.<br>";
    }

    // 3. Add is_suspended to sellers if it doesn't exist
    $result = $db->query("SHOW COLUMNS FROM `sellers` LIKE 'is_suspended'");
    if ($result->rowCount() == 0) {
        $db->exec("ALTER TABLE `sellers` ADD `is_suspended` BOOLEAN DEFAULT FALSE");
        echo "✅ Added `is_suspended` column to `sellers`.<br>";
    } else {
        echo "✅ `is_suspended` column already exists.<br>";
    }

    echo "<br><strong>Phase 5 patch applied successfully!</strong>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
