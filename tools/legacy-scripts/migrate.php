<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = DB::connect();
$sql = file_get_contents('schema.sql');
$db->exec($sql);
echo "Migration successful.";
?>
