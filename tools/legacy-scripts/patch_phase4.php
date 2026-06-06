<?php
// patch_phase4.php - Database updates for Phase 4 (Analytics, Reviews, Verification)
require_once dirname(__DIR__, 2) . '/includes/db.php';

$db = DB::connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    echo "Starting Phase 4 DB Migration...<br>";

    // 1. Add store_visits to sellers if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM sellers LIKE 'store_visits'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE sellers ADD COLUMN store_visits INT DEFAULT 0");
        echo "Added store_visits to sellers table.<br>";
    } else {
        echo "store_visits already exists in sellers table.<br>";
    }

    // 2. Add is_verified to sellers if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM sellers LIKE 'is_verified'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE sellers ADD COLUMN is_verified BOOLEAN DEFAULT FALSE");
        echo "Added is_verified to sellers table.<br>";
    } else {
        echo "is_verified already exists in sellers table.<br>";
    }

    // 3. Add views to products if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'views'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE products ADD COLUMN views INT DEFAULT 0");
        echo "Added views to products table.<br>";
    } else {
        echo "views already exists in products table.<br>";
    }

    // 4. Create reviews table
    $db->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            customer_name VARCHAR(100) NOT NULL,
            rating INT DEFAULT 5,
            comment TEXT,
            is_published BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
        )
    ");
    echo "Ensured reviews table exists.<br>";

    echo "<b>Migration completed successfully.</b>";

} catch (Exception $e) {
    echo "<b>Error:</b> " . htmlspecialchars($e->getMessage());
}
