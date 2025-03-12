<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'evolve1');
$message = '';
$status = 'error';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and update user status
    $stmt = $conn->prepare("SELECT id, email, token_expiry FROM tbl_users WHERE verification_token = ? AND status = 'pending'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if token is expired
        if (strtotime($user['token_expiry']) < time()) {
            $message = "Verification link has expired. Please request a new one.";
        } else {
            // Update user status to verified
            $update = $conn->prepare("UPDATE tbl_users SET status = 'active', verification_token = NULL WHERE id = ?");
            $update->bind_param("i", $user['id']);
            
            if ($update->execute()) {
                $status = 'success';
                $message = "Email verified successfully! You can now login to your account.";
            } else {
                $message = "Error verifying email. Please try again.";
            }
        }
    } else {
        $message = "Invalid verification link or account already verified.";
    }
} else {
    $message = "Invalid verification link.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - EVolve</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .verification-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2>Email Verification</h2>
        <p class="<?php echo $status; ?>"><?php echo $message; ?></p>
        <a href="index.php" class="button">Return to Homepage</a>
    </div>
</body>
</html> 