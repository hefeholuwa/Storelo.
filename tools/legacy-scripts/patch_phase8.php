<?php
// patch_phase8.php - Database updates for Phase 8 (Email Auth)
require_once dirname(__DIR__, 2) . '/includes/db.php';

$db = DB::connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    echo "Starting Phase 8 DB Migration...<br>";

    // Add email columns
    $db->exec("ALTER TABLE sellers ADD COLUMN email VARCHAR(255) UNIQUE DEFAULT NULL");
    echo "Added email to sellers table.<br>";
    
    $db->exec("ALTER TABLE sellers ADD COLUMN email_verified BOOLEAN DEFAULT FALSE");
    echo "Added email_verified to sellers table.<br>";
    
    $db->exec("ALTER TABLE sellers ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL");
    echo "Added verification_token to sellers table.<br>";
    
    $db->exec("ALTER TABLE sellers ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL");
    echo "Added password_reset_token to sellers table.<br>";

    // For existing testing accounts, set email to something fake so they don't break, and mark them verified.
    $db->exec("UPDATE sellers SET email = CONCAT(username, '@example.com'), email_verified = 1 WHERE email IS NULL");
    echo "Updated existing sellers to be verified so you don't lose access.<br>";

    echo "<b>Migration completed successfully.</b>";

} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<b>Columns already exist.</b>";
    } else {
        echo "<b>Error:</b> " . htmlspecialchars($e->getMessage());
    }
}
