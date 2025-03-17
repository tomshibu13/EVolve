<?php
session_start();
require 'vendor/autoload.php';
use Razorpay\Api\Api;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/station_owner/send_payment_email.php';

$key_id = "rzp_test_R6h0atxxQ4WsUU";  // Your Razorpay Key ID
$key_secret = "5CyNCDCaDKmrRqPWX2K6uLGV";  // Your Razorpay Key Secret
$api = new Api($key_id, $key_secret);

$payment_id = $_GET['payment_id'];
$order_id = $_GET['order_id'];
$signature = $_GET['signature'];

try {
    $attributes = [
        'razorpay_order_id' => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ];
    
    $api->utility->verifyPaymentSignature($attributes);
    
    // Fetch payment details from Razorpay
    $payment = $api->payment->fetch($payment_id);
    $amount = $payment->amount / 100; // Convert amount from paise to rupees
    $payment_date = date('Y-m-d H:i:s', $payment->created_at);
    $payment_method = $payment->method;
    
    // Start transaction
    $pdo->beginTransaction();

    try {
        // First, get the booking and user details needed for email
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                u.email as user_email,
                u.name as user_name,
                cs.name as station_name
            FROM bookings b
            JOIN tbl_users u ON b.user_id = u.user_id
            JOIN charging_stations cs ON b.station_id = cs.station_id
            WHERE b.razorpay_order_id = ?
        ");
        $stmt->execute([$order_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("Booking not found for order ID: $order_id");
        }

        // Log the booking details
        error_log("Found booking: " . print_r($booking, true));

        // Update bookings table
        $stmt = $pdo->prepare("UPDATE bookings SET 
            payment_status = 'completed',
            status = 'confirmed',
            razorpay_payment_id = ?,
            amount = ?,
            updated_at = NOW()
            WHERE razorpay_order_id = ?");
        
        $stmt->execute([$payment_id, $amount, $order_id]);

        // Insert into payment_details table
        $stmt = $pdo->prepare("INSERT INTO payment_details 
            (booking_id, user_id, station_id, amount, payment_method, transaction_id, status)
            SELECT 
                booking_id,
                user_id,
                station_id,
                ?,
                ?,
                ?,
                'completed'
            FROM bookings 
            WHERE razorpay_order_id = ?");
        
        $stmt->execute([$amount, $payment_method, $payment_id, $order_id]);

        // Send confirmation email
        $emailResult = sendPaymentSuccessEmail(
            $booking['user_email'],
            $booking['user_name'],
            $booking['station_name'],
            $amount,
            $booking['booking_date'],
            $booking['booking_time'],
            $booking['user_id']
        );

        // Log email result
        error_log("Email sending result: " . print_r($emailResult, true));

        // Commit transaction
        $pdo->commit();

        // Redirect to success page
        header("Location: my-bookings.php?status=success&payment=completed");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Transaction Error: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Payment Verification Error: " . $e->getMessage());
    
    // Update payment status to failed
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET 
            payment_status = 'failed',
            status = 'cancelled',
            updated_at = NOW()
            WHERE razorpay_order_id = ?");
        $stmt->execute([$order_id]);
    } catch (Exception $updateError) {
        error_log("Error updating failed payment status: " . $updateError->getMessage());
    }
    
    header("Location: my-bookings.php?status=failed");
    exit();
}

// Fetch payment details
$stmt = $conn->prepare("SELECT 
    b.*, 
    u.name as customer_name,
    u.email as customer_email
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    WHERE b.payment_status = 'completed'
    ORDER BY b.payment_date DESC");
$stmt->execute();
$result = $stmt->get_result();

// Display payment details in a table
?>
<div class="payment-details">
    <h2>Payment Details</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Method</th>
                <th>Payment ID</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td>₹<?php echo htmlspecialchars(number_format($row['payment_amount'], 2)); ?></td>
                    <td><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($row['payment_date']))); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($row['razorpay_payment_id']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<style>
.payment-details {
    margin: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.payment-details table {
    width: 100%;
    border-collapse: collapse;
}

.payment-details th,
.payment-details td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.payment-details th {
    background-color: #f5f5f5;
}
</style>
