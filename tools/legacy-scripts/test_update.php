<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = DB::connect();

$stmt = $db->prepare("UPDATE sellers SET is_verified = ? WHERE id = ?");
$res = $stmt->execute([1, 1]);
var_dump($res);

$stmt = $db->query("SELECT id, username, is_verified FROM sellers WHERE id = 1");
var_dump($stmt->fetch());
