<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
session_write_close();

$cookie = 'PHPSESSID=' . session_id();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/superadmin/sellers?action=verify&id=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
$result = curl_exec($ch);

// Output the full body to see if there is an alert-danger or alert-success
if (strpos($result, 'alert-success') !== false) {
    echo "SUCCESS ALERT FOUND\n";
} elseif (strpos($result, 'alert-danger') !== false) {
    echo "ERROR ALERT FOUND\n";
    preg_match('/<div class="alert alert-danger">(.*?)<\/div>/s', $result, $matches);
    echo trim(strip_tags($matches[1]));
} else {
    echo "NO ALERT FOUND\n";
}
