<?php

$root = dirname(__DIR__);
$schemaPath = $root . '/schema.sql';
$failures = [];

function fail_if_missing_column(array &$failures, string $schema, string $table, string $column): void
{
    $pattern = '/CREATE TABLE IF NOT EXISTS\s+' . preg_quote($table, '/') . '\s*\((.*?)\);/is';
    if (!preg_match($pattern, $schema, $matches)) {
        $failures[] = "Missing table: {$table}";
        return;
    }

    if (!preg_match('/^\s*' . preg_quote($column, '/') . '\s+/im', $matches[1])) {
        $failures[] = "Missing column: {$table}.{$column}";
    }
}

function fail_if_missing_table(array &$failures, string $schema, string $table): void
{
    if (!preg_match('/CREATE TABLE IF NOT EXISTS\s+' . preg_quote($table, '/') . '\s*\(/i', $schema)) {
        $failures[] = "Missing table: {$table}";
    }
}

if (!is_file($schemaPath)) {
    fwrite(STDERR, "schema.sql not found\n");
    exit(1);
}

$schema = file_get_contents($schemaPath);

$requiredColumns = [
    'sellers' => [
        'email',
        'email_verified',
        'verification_token',
        'password_reset_token',
        'store_visits',
        'is_verified',
        'is_suspended',
    ],
    'products' => [
        'original_price',
        'views',
    ],
];

foreach ($requiredColumns as $table => $columns) {
    foreach ($columns as $column) {
        fail_if_missing_column($failures, $schema, $table, $column);
    }
}

foreach (['admins', 'reviews'] as $table) {
    fail_if_missing_table($failures, $schema, $table);
}

if (!preg_match("/status\s+ENUM\('pending',\s*'confirmed',\s*'packed',\s*'delivered',\s*'cancelled'\)/i", $schema)) {
    $failures[] = 'orders.status enum must match dashboard workflow';
}

$publicRootScripts = glob($root . '/{test_*.php,patch_*.php,patch_*.sql}', GLOB_BRACE) ?: [];
if ($publicRootScripts) {
    foreach ($publicRootScripts as $script) {
        $failures[] = 'Public root contains one-off script: ' . basename($script);
    }
}

$checkoutHandler = file_get_contents($root . '/views/store/checkout_handler.php') ?: '';
if (str_contains($checkoutHandler, "floatval(\$item['price'])")) {
    $failures[] = 'Checkout handler must persist database product prices, not browser cart prices';
}

$registerView = file_get_contents($root . '/views/register.php') ?: '';
if (str_contains($registerView, '"http://" . $_SERVER[\'HTTP_HOST\'] . BASE_URL')) {
    $failures[] = 'Registration verification link must not duplicate scheme/host before BASE_URL';
}

$config = file_get_contents($root . '/config.php') ?: '';
if (!str_contains($config, "define('REQUIRE_EMAIL_VERIFICATION'")) {
    $failures[] = 'Email verification must be explicitly configurable';
}

$loginView = file_get_contents($root . '/views/login.php') ?: '';
if (str_contains($loginView, "if (isset(\$seller['email_verified']) && \$seller['email_verified'] == 0)")) {
    $failures[] = 'Login must not block unverified sellers unless email verification is enabled';
}

if ($failures) {
    fwrite(STDERR, "Project readiness check failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Project readiness check passed.\n";
