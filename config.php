<?php
// config.php — Database credentials and global settings
// For local development, update these values to match your local MySQL setup.
// For InfinityFree production, update to the credentials from your hosting panel.

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'storelo');

// Base URL — dynamically detected for ease of local testing and deployment
if (isset($_SERVER['HTTP_HOST'])) {
    if ($_SERVER['HTTP_HOST'] === 'localhost:8000') {
        define('BASE_URL', 'http://localhost:8000');
    } elseif (strpos($_SERVER['HTTP_HOST'], 'storelo.page.gd') !== false) {
        define('BASE_URL', 'https://storelo.page.gd');
    } else {
        define('BASE_URL', 'http://localhost/storelo');
    }
} else {
    define('BASE_URL', 'http://localhost/storelo');
}
