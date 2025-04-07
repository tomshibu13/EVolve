<?php
session_start();

// Include database configuration
require_once 'config.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Get the booking details from POST
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';
$duration = $_POST['duration'] ?? '';
$station_id = $_POST['station_id'] ?? '';

// Calculate amount based on duration and rates
$hourly_rate = 100; // Set your rate per hour
$amount = $hourly_rate * $duration;

// Create Razorpay Order
require 'vendor/autoload.php';
use Razorpay\Api\Api;

$key_id = "rzp_test_R6h0atxxQ4WsUU";
$key_secret = "5CyNCDCaDKmrRqPWX2K6uLGV";

$api = new Api($key_id, $key_secret);

try {
    // Create order on Razorpay
    $orderData = [
        'receipt'         => 'rcptid_' . time(),
        'amount'          => $amount * 100, // Convert to paise
        'currency'        => 'INR',
        'payment_capture' => 1 // auto capture
    ];

    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];

    // Store booking details in database with pending status
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, 
            station_id, 
            booking_date, 
            booking_time, 
            duration, 
            amount,
            razorpay_order_id,
            status,
            payment_status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $station_id,
        $booking_date,
        $booking_time,
        $duration,
        $amount,
        $razorpayOrderId
    ]);

    $bookingId = $pdo->lastInsertId();

    // Store in session for verification
    $_SESSION['current_booking'] = [
        'booking_id' => $bookingId,
        'order_id' => $razorpayOrderId,
        'amount' => $amount,
        'booking_date' => $booking_date,
        'booking_time' => $booking_time,
        'duration' => $duration,
        'station_id' => $station_id
    ];

} catch (Exception $e) {
    error_log("Razorpay Error: " . $e->getMessage());
    header("Location: error.php?message=" . urlencode("Payment initialization failed"));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4CAF50">
    
    <!-- Add proper manifest -->
    <link rel="manifest" href="manifest.json">
    
    <title>Payment - EVolve</title>
    
    <!-- Preload Razorpay script -->
    <link rel="preload" href="https://checkout.razorpay.com/v1/checkout.js" as="script">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <!-- Optimize font loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Update Razorpay initialization -->
    <script>
        // Add canvas optimization
        const canvas = document.createElement('canvas');
        if (canvas.getContext) {
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
        }
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        
        .payment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .payment-card:hover {
            transform: translateY(-5px);
        }
        
        .payment-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .payment-details {
            background: #f0f9f1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .payment-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e1e1e1;
        }
        
        .payment-info:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .payment-label {
            font-weight: 500;
        }
        
        .payment-value {
            font-weight: 600;
            color: #4CAF50;
        }
        
        .test-card-info {
            background: #f0f0f0;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        
        .pay-button {
            display: block;
            width: 100%;
            padding: 15px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 30px 0 15px;
        }
        
        .pay-button:hover {
            background: #3e8e41;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .pay-button:active {
            transform: translateY(0);
        }
        
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 14px;
        }
        
        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            gap: 8px;
        }
        
        .secure-badge span {
            font-size: 14px;
            color: #666;
        }
        
        .secure-badge i {
            color: #4CAF50;
            font-size: 16px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EVolve Charging</div>
            <p>Complete your electric vehicle charging station booking</p>
        </div>
        
        <div class="payment-card">
            <h2 class="payment-title">Payment Details</h2>
            
            <div class="payment-details">
                <div class="payment-info">
                    <span class="payment-label">Order ID:</span>
                    <span class="payment-value"><?php echo htmlspecialchars($razorpayOrderId); ?></span>
                </div>
                <div class="payment-info">
                    <span class="payment-label">Amount:</span>
                    <span class="payment-value">₹<?php echo number_format((float)$amount, 2); ?></span>
                </div>
            </div>
<!--             
            <div class="test-card-info">
                <h4><i class="fas fa-info-circle"></i> Test Card Information</h4>
                <p>Card Number: 4111 1111 1111 1111</p>
                <p>Expiry: Any future date</p>
                <p>CVV: Any 3 digits</p>
                <p>Name: Any name</p>
            </div> -->
            
            <button id="pay-button" class="pay-button">
                <i class="fas fa-lock"></i> Pay ₹<?php echo number_format((float)$amount, 2); ?>
            </button>
            
            <div class="secure-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Secure payment powered by Razorpay</span>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> EVolve Charging. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        var options = {
            "key": "<?php echo $key_id; ?>",
            "amount": "<?php echo $amount * 100; ?>",
            "currency": "INR",
            "name": "EVolve Charging",
            "description": "Charging Station Booking",
            "image": "path/to/your/logo.png",
            "order_id": "<?php echo $razorpayOrderId; ?>",
            "handler": function (response) {
                // Show loading state
                document.getElementById('pay-button').disabled = true;
                document.getElementById('pay-button').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
                
                // Create a form to submit payment details
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = 'verify_payment.php';

                var fields = {
                    'razorpay_payment_id': response.razorpay_payment_id,
                    'razorpay_order_id': response.razorpay_order_id,
                    'razorpay_signature': response.razorpay_signature
                };

                for (var key in fields) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            },
            "prefill": {
                "name": "<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>",
                "email": "<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>",
                "contact": "<?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>"
            },
            "theme": {
                "color": "#4CAF50"
            }
        };

        document.getElementById('pay-button').onclick = function() {
            var rzp1 = new Razorpay(options);
            rzp1.open();
        };

        // Handle payment failure
        rzp1.on('payment.failed', function (response){
            alert("Payment failed. Please try again. Error: " + response.error.description);
            window.location.href = 'my-bookings.php?status=failed';
        });
    </script>
</body>
</html>
