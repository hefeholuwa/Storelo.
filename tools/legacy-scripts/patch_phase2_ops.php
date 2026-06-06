<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';

try {
    $db = DB::connect();

    echo "Starting Phase 2 Database Migration...\n";

    // 1. Temporarily add all values to ENUM so we don't lose data
    $db->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'completed', 'confirmed', 'packed', 'delivered', 'cancelled') DEFAULT 'pending'");
    echo "1. Expanded ENUM temporarily.\n";

    // 2. Migrate existing 'completed' to 'delivered'
    $affected = $db->exec("UPDATE orders SET status = 'delivered' WHERE status = 'completed'");
    echo "2. Migrated $affected 'completed' orders to 'delivered'.\n";

    // 3. Finalize the strict ENUM without 'completed'
    $db->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'packed', 'delivered', 'cancelled') DEFAULT 'pending'");
    echo "3. Finalized ENUM strictly.\n";

    echo "\nMigration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
