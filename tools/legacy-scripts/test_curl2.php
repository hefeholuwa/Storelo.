<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
session_write_close();

$cookie = 'PHPSESSID=' . session_id();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/superadmin/sellers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIE, $cookie);
$result = curl_exec($ch);

// print the actions column
preg_match_all('/<td>\s*<a href="\?action=verify.*?<\/td>/s', $result, $matches);
print_r($matches[0]);
