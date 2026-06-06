<?php
require __DIR__ . '/includes/db.php';
try {
    $db = DB::connect();
    $db->exec("ALTER TABLE sellers ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE");
    echo "Column added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
