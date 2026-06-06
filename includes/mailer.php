<?php
// includes/mailer.php — Brevo API Transactional Email Client
require_once __DIR__ . '/../config.php';

/**
 * Send a transactional email using the Brevo API.
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name (optional)
 * @param string $subject Email subject
 * @param string $html_content Email HTML body
 * @return bool True if successful, False otherwise
 */
function send_email($to_email, $to_name, $subject, $html_content) {
    if (!defined('BREVO_API_KEY') || empty(BREVO_API_KEY) || BREVO_API_KEY === 'YOUR_BREVO_API_KEY_HERE') {
        error_log("BREVO ERROR: API Key not configured. Mocking email to $to_email.");
        return false;
    }

    $url = "https://api.brevo.com/v3/smtp/email";
    
    $data = [
        "sender" => [
            "name" => BREVO_SENDER_NAME,
            "email" => BREVO_SENDER_EMAIL
        ],
        "to" => [
            [
                "email" => $to_email,
                "name" => empty($to_name) ? $to_email : $to_name
            ]
        ],
        "subject" => $subject,
        "htmlContent" => $html_content
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $headers = [
        "accept: application/json",
        "api-key: " . BREVO_API_KEY,
        "content-type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($http_code === 201) {
        return true;
    } else {
        error_log("BREVO ERROR (HTTP $http_code): $response");
        return false;
    }
}
