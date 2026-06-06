<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'storelo');

define('BREVO_API_KEY', 'your-api-key-here');
define('BREVO_SENDER_EMAIL', 'hello@example.com');
define('BREVO_SENDER_NAME', 'Storelo');

define('REQUIRE_EMAIL_VERIFICATION', true);

if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    define('BASE_URL', $protocol . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', 'http://localhost');
}
