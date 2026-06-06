<?php
// tools/test_email.php — Quick test script for Brevo integration
require_once __DIR__ . '/../includes/mailer.php';

echo "Enter your email address to send a test email: ";
$handle = fopen("php://stdin", "r");
$email = trim(fgets($handle));

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Sending test email to $email...\n";
    
    $subject = "Test Email from Storelo";
    $html = "
    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #F58320;'>It Works! 🎉</h2>
        <p>If you are reading this, your Brevo email integration is perfectly configured and ready to go.</p>
        <hr style='border: none; border-top: 1px solid #eaeaea; margin-top: 30px;' />
        <p style='color: #6b7280; font-size: 0.85rem;'>The Storelo Team</p>
    </div>";
    
    $success = send_email($email, "Test User", $subject, $html);
    
    if ($success) {
        echo "✅ SUCCESS: The email was accepted by Brevo and should arrive shortly!\n";
    } else {
        echo "❌ FAILED: The email failed to send. Check your API key and sender domain.\n";
    }
} else {
    echo "Invalid email address.\n";
}
