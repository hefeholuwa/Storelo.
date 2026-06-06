<?php
// fake a request
$_GET['action'] = 'verify';
$_GET['id'] = 1;

require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = DB::connect();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $seller_id = intval($_GET['id']);
    
    // Check if seller exists
    $stmt = $db->prepare("SELECT id, is_verified, is_suspended FROM sellers WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch();
    
    if ($seller) {
        if ($action === 'verify') {
            $new_status = $seller['is_verified'] ? 0 : 1;
            var_dump($seller['is_verified'], $new_status);
            $db->prepare("UPDATE sellers SET is_verified = ? WHERE id = ?")->execute([$new_status, $seller_id]);
            echo "Store verification status updated.";
        }
    }
}
