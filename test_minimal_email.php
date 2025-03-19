<?php
require_once 'config.php';
require_once 'station_owner/send_payment_email.php';

// Test with minimal email 
$result = sendPaymentSuccessEmail(
    'evolve1829@gmail.com',
    'Test User',
    'Test Station',
    1000,
    date('Y-m-d'),
    date('H:i:s'),
    1,
    123
);

echo '<pre>';
print_r($result);
echo '</pre>';
?> 