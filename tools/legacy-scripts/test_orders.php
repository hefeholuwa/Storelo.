<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
session_write_close();

$cookie = 'PHPSESSID=' . session_id();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/superadmin/orders");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
$result = curl_exec($ch);

if (strpos($result, 'Global Orders') !== false) {
    echo "SUCCESS: PAGE LOADED\n";
    preg_match_all('/<td style="font-weight: 700;">#(\d+)<\/td>/', $result, $matches);
    echo "Found " . count($matches[1]) . " orders.\n";
} else {
    echo "ERROR: \n";
    echo substr(strip_tags($result), 0, 500);
}
