<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking Successful</title>
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #dcfce7;
            border: 1px solid #16a34a;
            border-radius: 8px;
            text-align: center;
        }
        .view-bookings-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #16a34a;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <h2>Booking Successful!</h2>
        <p>Your booking has been confirmed. A confirmation email has been sent to your registered email address.</p>
        <a href="my-bookings.php" class="view-bookings-button">View My Bookings</a>
    </div>
</body>
</html> 