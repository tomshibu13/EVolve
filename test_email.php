<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/station_owner/send_payment_email.php';

echo "<h2>Testing Email Functionality</h2>";

try {
    // Test with your email address
    $testEmail = 'evolve1829@gmail.com';
    
    echo "Attempting to send email to: " . $testEmail . "<br>";
    
    $result = sendPaymentSuccessEmail(
        $testEmail,
        'Test User',
        'Test Station',
        1000,
        date('Y-m-d'),
        date('H:i:s'),
        1,
        1
    );
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Check spam folder message
    echo "<p>If you don't see the email in your inbox, please check your spam folder.</p>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<pre>";
    print_r($e->getMessage());
    echo "</pre>";
}
?> 