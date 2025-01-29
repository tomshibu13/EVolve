<!-- <?php
session_start();

$username = $password = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Add your authentication logic here
    // For now, just showing the basic structure
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EV Charging Station Finder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-top: 80px;
        }

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;
        }

        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
            justify-content: center;
        }

        .tab {
            text-decoration: none;
            color: #888;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 5px 10px;
            transition: color 0.3s ease;
            position: relative;
        }

        .tab:hover {
            color:#007bff;
        }

        .tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color:#007bff;
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }

        .tab.active {
            color:#007bff;
        }

        .tab.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* Add a subtle scale animation on tab hover */
        .tab:hover {
            transform: translateY(-1px);
            transition: transform 0.3s ease;
        }

        .social-login {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 25px 0;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            font-size: 1.2rem;
        }

        .facebook { background: #3b5998; }
        .twitter { background: #1da1f2; }
        .google { background: #db4437; }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color:#007bff;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #666;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background:  #1da1f2;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .login-btn:hover {
            opacity: 0.9;
        }

        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
        }

        /* Add these new styles */
        .input-error {
            border-color: #e74c3c !important;
        }

        .validation-message {
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .validation-message.error {
            color: #e74c3c;
            display: block;
        }

        .validation-message.success {
            color: #2ecc71;
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header and Navigation Styles */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
            font-weight: bold;
            color: #333;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            color: #007bff;
            font-size: 2rem;
            filter: drop-shadow(0 0 8px rgba(0, 123, 255, 0.3));
        }

        .logo-text {
            font-size: 1.5rem;
        }

        .highlight {
            color:#007bff;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
        }

        .nav-link i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .nav-link:hover {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
            transform: translateY(-2px);
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        .nav-link.active {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.15);
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .nav-link {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<header class="header">
        <nav class="nav-container">
            <div class="logo">
                <i class="fas fa-charging-station"></i>
                <span class="logo-text">E<span class="highlight">V</span>olve</span>
            </div>
            <div class="nav-links">
                <a href="#mc" class="nav-link active">
                    <i class="fas fa-search"></i>
                    Find Stations
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Services
                </a>
                <a href="about.html" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
                <a href="evlogin.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    Login/Signup
                </a>
              
                
            </div>
        </nav>
    </header>
    <div class="login-container">
        <div class="tabs">
            <a href="#" class="tab active">Log In</a>
            <a href="evsignup.php" class="tab">Register</a>
        </div>



        <form action="" method="post">
            <?php if($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="input-group">
                <label for="username">User Name</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                <div class="validation-message" id="usernameValidation"></div>
            </div>

            <div class="input-group">
                <label for="password">Enter Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember Password</label>
            </div>

            <button type="submit" class="login-btn">Log in</button>
        </form>
    </div>
    <script>
        document.getElementById('username').addEventListener('input', function(e) {
            const username = e.target.value;
            const validationMessage = document.getElementById('usernameValidation');
            
            // Remove any existing classes
            this.classList.remove('input-error');
            validationMessage.classList.remove('error', 'success');
            
            // Validate username
            if (username.length < 3) {
                validationMessage.textContent = 'Username must be at least 3 characters long';
                validationMessage.classList.add('error');
                this.classList.add('input-error');
            } else if (username.length > 20) {
                validationMessage.textContent = 'Username must be less than 20 characters';
                validationMessage.classList.add('error');
                this.classList.add('input-error');
            } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                validationMessage.textContent = 'Username can only contain letters, numbers, and underscores';
                validationMessage.classList.add('error');
                this.classList.add('input-error');
            } else {
                validationMessage.textContent = 'Username is valid';
                validationMessage.classList.add('success');
            }
        });
    </script>
</body>
</html> -->