<?php
require_once dirname(__DIR__, 2) . '/includes/db.php';
$db = DB::connect();

// Mock registration
$username = 'testuser123';
$email = 'test1234@example.com';
$password = 'password123';
$hashed = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));

$stmt = $db->prepare("INSERT INTO sellers (username, email, password, shop_name, whatsapp_number, verification_token, email_verified) VALUES (?, ?, ?, 'Test Shop', '1234567890', ?, 0)");
$stmt->execute([$username, $email, $hashed, $token]);

echo "Created test user with token: $token\n";

// Attempt to login without verification
$stmt = $db->prepare("SELECT * FROM sellers WHERE username = ? OR email = ?");
$stmt->execute([$email, $email]);
$seller = $stmt->fetch();
if ($seller['email_verified'] == 0) {
    echo "Login blocked! (Expected)\n";
}

// Verify
$stmt = $db->prepare("UPDATE sellers SET email_verified = 1, verification_token = NULL WHERE verification_token = ?");
$stmt->execute([$token]);
echo "Token verified.\n";

// Login again
$stmt = $db->prepare("SELECT * FROM sellers WHERE username = ? OR email = ?");
$stmt->execute([$email, $email]);
$seller = $stmt->fetch();
if ($seller['email_verified'] == 1) {
    echo "Login success! (Expected)\n";
}

// Cleanup
$db->prepare("DELETE FROM sellers WHERE username = 'testuser123'")->execute();
