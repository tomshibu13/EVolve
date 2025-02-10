<?php
session_start();
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    $conn = new mysqli('localhost', 'root', '', 'evolve1');
        
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        $error_message = "An error occurred during login. Please try again later.";
    } else {
        
        $email = $conn->real_escape_string(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)); 
        $sql = "SELECT * FROM tbl_users WHERE email='$email'";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            echo'<form id="form" method="POST" action="send_reset_otp.php">';
            echo '<input type="hidden" name="email" value="' . $email . '">';
            echo'</form>';
            echo'<script>document.getElementById("form").submit();</script>';

        } else {
            $error_message = "Invalid email";
        }
        
               $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVolve - Forgot Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Page Styling */
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(to right, #0f2027, #203a43, #2c5364);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

/* Centered Login Container */
.login-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    text-align: center;
    width: 350px;
}

/* Logo Styling */
.logo {
    font-size: 26px;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 10px;
}

/* Headings */
h2 {
    font-size: 22px;
    margin-bottom: 10px;
    color: #333;
}

.description {
    font-size: 14px;
    color: #666;
}

/* Error Message */
.error-message {
    background: #ffdddd;
    color: #d9534f;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    font-size: 14px;
}

/* Form Group */
.form-group {
    text-align: left;
    margin-bottom: 15px;
}

label {
    font-size: 14px;
    color: #444;
    font-weight: 500;
    display: block;
    margin-bottom: 5px;
}

/* Input Field */
input[type="email"] {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    outline: none;
    transition: 0.3s ease;
}

input[type="email"]:focus {
    border-color: #007bff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
}

/* Submit Button */
.login-btn {
    background: #007bff;
    color: white;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    padding: 10px;
    width: 100%;
    cursor: pointer;
    transition: background 0.3s ease;
}

.login-btn:hover {
    background: #0056b3;
}

/* Back to Login */
.back-to-login {
    margin-top: 10px;
    font-size: 14px;
}

.back-to-login a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.back-to-login a:hover {
    text-decoration: underline;
}

    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span class="task">EVolve</span>
        </div>
        <h2>Forgot Password</h2>
        <p class="description">Enter your email to receive an otp.</p>
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="login-btn">Send An OTP</button>
            <p class="back-to-login">Remember your password? <a href="signin.php">Login here</a></p>
        </form>
    </div>
</body>
</html>