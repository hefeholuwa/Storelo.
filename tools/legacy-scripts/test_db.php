<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = DB::connect();

$stmt = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM sellers 
    WHERE created_at >= DATE(NOW()) - INTERVAL 30 DAY 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
print_r($stmt->fetchAll());
