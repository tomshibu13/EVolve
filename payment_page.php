<?php
session_start();
$order_id = $_GET['order_id'];
$amount = $_GET['amount'];  // Amount should already be in rupees
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - EVolve</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        .test-card-info {
            margin: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        .pay-button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .warning-message {
            margin: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h2>Complete Your Payment</h2>
    
    <div class="warning-message">
        <strong>⚠️ Notice:</strong> Axis MasterCard credit card payments are currently facing issues. Please try with other payment options.
    </div>

    <button id="pay-button" class="pay-button">Pay ₹<?php echo number_format((float)$amount, 2); ?></button>

    <div class="test-card-info">
        <h3>Test Card Details:</h3>
        <p>Card Number: 5267 3181 8797 5449</p>
        <p>Expiry: Any future date (e.g., 12/25)</p>
        <p>CVV: Random 3 digits (e.g., 123)</p>
        <p>Name: Any name</p>
        <p>Phone: Any valid Indian phone number</p>
        <p><strong>Note:</strong> Please avoid using Axis MasterCard for payments at this time.</p>
    </div>

    <script>
        var options = {
            "key": "rzp_test_R6h0atxxQ4WsUU",
            "amount": <?php echo (int)($amount * 100); ?>, // Convert to paise and ensure it's an integer
            "currency": "INR",
            "name": "EVolve Charging",
            "description": "Charging Station Booking",
            "order_id": "<?php echo htmlspecialchars($order_id); ?>",
            "handler": function (response) {
                // Show loading state
                document.getElementById('pay-button').disabled = true;
                document.getElementById('pay-button').innerHTML = 'Processing Payment...';
                
                // Redirect to verify payment with all necessary parameters
                window.location.href = "verify_payment.php?" + 
                    "payment_id=" + response.razorpay_payment_id + 
                    "&order_id=" + response.razorpay_order_id + 
                    "&signature=" + response.razorpay_signature;
            },
            "modal": {
                "ondismiss": function() {
                    console.log('Payment modal closed');
                }
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
    </script>
</body>
</html>
