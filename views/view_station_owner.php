<?php
session_start(); // Start the session

// Check if the user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php#LoginForm");
    exit();
}

// Database connection credentials
$servername = "localhost";
$username = "root"; 
$password = "";    
$dbname = "evolve1";

// Create a connection with error handling using try-catch
try {
    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Fetch the request details based on request_id
    if (isset($_GET['request_id'])) {
        $request_id = intval($_GET['request_id']);
        $query = "SELECT r.*, u.email as user_email, u.username 
                  FROM station_owner_requests r 
                  JOIN tbl_users u ON r.user_id = u.user_id 
                  WHERE r.request_id = $request_id";
        
        $result = mysqli_query($conn, $query);
        if (!$result) {
            throw new Exception("Error fetching station owner details: " . mysqli_error($conn));
        }

        $request = mysqli_fetch_assoc($result);
        if (!$request) {
            throw new Exception("No request found with the given ID.");
        }
    } else {
        throw new Exception("Request ID is not provided.");
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Note: Don't close the connection here as we need it for the HTML section
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Owner Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .detail-row {
            margin-bottom: 15px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .detail-row:hover {
            background-color: #f8f9fa;
        }

        strong {
            color: #2c3e50;
            min-width: 150px;
            display: inline-block;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #2980b9;
        }

        .back-btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Station Owner Details</h1>
        <div class="detail-row">
            <strong>Owner Name:</strong> <?php echo htmlspecialchars($request['owner_name']); ?>
        </div>
        <div class="detail-row">
            <strong>Business Name:</strong> <?php echo htmlspecialchars($request['business_name']); ?>
        </div>
        <div class="detail-row">
            <strong>Email:</strong> <?php echo htmlspecialchars($request['user_email']); ?>
        </div>
        <div class="detail-row">
            <strong>Phone:</strong> <?php echo htmlspecialchars($request['phone']); ?>
        </div>
        <div class="detail-row">
            <strong>Location:</strong> <?php echo htmlspecialchars($request['address']) . ', ' . htmlspecialchars($request['city']) . ', ' . htmlspecialchars($request['state']) . ' ' . htmlspecialchars($request['postal_code']); ?>
        </div>
        <div class="detail-row">
            <strong>Business Registration:</strong> <?php echo htmlspecialchars($request['business_registration']); ?>
        </div>
        <div class="detail-row">
            <strong>Status:</strong> <span class="status"><?php echo ucfirst(htmlspecialchars($request['status'])); ?></span>
        </div>
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html> 