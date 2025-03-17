<?php
session_start(); // Make sure session is started before including send_payment_email.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/station_owner/send_payment_email.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Email Functionality</h2>";
echo "<p>Attempting to send test email...</p>";

// Test email
$result = sendPaymentSuccessEmail(
    'evolve1829@gmail.com', // Test with your email
    'Test User',
    'Test Station',
    1000,
    date('Y-m-d'),
    date('H:i:s'),
    1
);

echo '<h3>Result:</h3>';
echo '<pre>';
print_r($result);
echo '</pre>';

if ($result['success']) {
    echo '<p style="color: green; font-weight: bold;">Email sent successfully!</p>';
} else {
    echo '<p style="color: red; font-weight: bold;">Email sending failed: ' . $result['message'] . '</p>';
    
    echo '<h3>Debugging Steps:</h3>';
    echo '<ol>';
    echo '<li>Check that your Gmail account has <a href="https://myaccount.google.com/security" target="_blank">2-Step Verification enabled</a></li>';
    echo '<li>Verify that you\'re using an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> for SMTP authentication</li>';
    echo '<li>Confirm that "Less secure app access" is turned off (should be off when using App Passwords)</li>';
    echo '<li>Check PHP error logs for detailed SMTP errors</li>';
    echo '</ol>';
}
?> 