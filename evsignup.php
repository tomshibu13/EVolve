<<<<<<< HEAD
<<<<<<< HEAD
<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EV Charging Station Finder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Existing CSS Code Here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-top: 80px;
        }

        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;
            margin: 20px auto;
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
            color: #1da1f2;
            transform: translateY(-1px);
        }

        .tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #1da1f2;
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }

        .tab.active {
            color: #1da1f2;
        }

        .tab.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .social-signup {
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
            transition: transform 0.3s ease;
        }

        .social-icon:hover {
            transform: scale(1.1);
        }

        .facebook { background: #3b5998; }
        .twitter { background: #1da1f2; }
        .google { background: #db4437; }

        .input-group {
            margin-bottom: 20px;
            position: relative;
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
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 2px rgba(255, 75, 110, 0.1);
        }

        .signup-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background:  #1da1f2;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .signup-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 75, 110, 0.2);
        }

        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
            text-align: center;
            animation: shake 0.5s ease;
        }

        .error-text {
            color: #ff0000;
            font-size: 0.8rem;
            display: block;
            margin-top: 5px;
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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .terms {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }

        .terms a {
            color: #1da1f2;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }
        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;  /* Add this line */
        }

        // ... existing code ...

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

<div class="signup-container">
    <div class="tabs">
        <a href="evlogin.php" class="tab">Log In</a>
        <a href="#" class="tab active">Register</a>
    </div>

    <form id="signupForm" method="post">
        <div class="error-message" id="error-message"></div>

        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <small class="error-text" id="username-error"></small>
        </div>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
            <small class="error-text" id="email-error"></small>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small class="error-text" id="password-error"></small>
        </div>

        <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <small class="error-text" id="confirm-password-error"></small>
        </div>

        <button type="submit" class="signup-btn">Create Account</button>

        <div class="terms">
            By signing up, you agree to our 
            <a href="#">Terms of Service</a> and 
            <a href="#">Privacy Policy</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    const usernameError = document.getElementById('username-error');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');
    const errorMessage = document.getElementById('error-message');

    function validateUsername() {
        const value = usernameInput.value.trim();
        if (value === '') {
            usernameError.textContent = 'Username is required';
            return false;
        } else if (value.length < 4 || value.length > 15) {
            usernameError.textContent = 'Username should be between 4 to 15 characters';
            return false;
        }
        usernameError.textContent = '';
        return true;
    }

    function validateEmail() {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const value = emailInput.value.trim();
        if (value === '') {
            emailError.textContent = 'Email is required';
            return false;
        } else if (!emailPattern.test(value)) {
            emailError.textContent = 'Please enter a valid email address';
            return false;
        }
        emailError.textContent = '';
        return true;
    }

    function validatePassword() {
        const value = passwordInput.value.trim();
        if (value === '') {
            passwordError.textContent = 'Password is required';
            return false;
        } else if (value.length < 8) {
            passwordError.textContent = 'Password should be at least 8 characters long';
            return false;
        }
        passwordError.textContent = '';
        return true;
    }

    function validateConfirmPassword() {
        const value = confirmPasswordInput.value.trim();
        if (value === '') {
            confirmPasswordError.textContent = 'Please confirm your password';
            return false;
        } else if (value !== passwordInput.value) {
            confirmPasswordError.textContent = 'Passwords do not match';
            return false;
        }
        confirmPasswordError.textContent = '';
        return true;
    }

    // Add input event listeners
    usernameInput.addEventListener('input', validateUsername);
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validateConfirmPassword);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();

        if (isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            // Form is valid, you can submit it
            this.submit();
        } else {
            errorMessage.textContent = 'Please fix the errors in the form';
        }
    });
});
</script>
</body>
</html> -->
=======
<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EV Charging Station Finder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Existing CSS Code Here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-top: 80px;
        }

        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;
            margin: 20px auto;
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
            color: #1da1f2;
            transform: translateY(-1px);
        }

        .tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #1da1f2;
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }

        .tab.active {
            color: #1da1f2;
        }

        .tab.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .social-signup {
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
            transition: transform 0.3s ease;
        }

        .social-icon:hover {
            transform: scale(1.1);
        }

        .facebook { background: #3b5998; }
        .twitter { background: #1da1f2; }
        .google { background: #db4437; }

        .input-group {
            margin-bottom: 20px;
            position: relative;
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
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 2px rgba(255, 75, 110, 0.1);
        }

        .signup-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background:  #1da1f2;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .signup-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 75, 110, 0.2);
        }

        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
            text-align: center;
            animation: shake 0.5s ease;
        }

        .error-text {
            color: #ff0000;
            font-size: 0.8rem;
            display: block;
            margin-top: 5px;
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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .terms {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }

        .terms a {
            color: #1da1f2;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }
        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;  /* Add this line */
        }

        // ... existing code ...

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

<div class="signup-container">
    <div class="tabs">
        <a href="evlogin.php" class="tab">Log In</a>
        <a href="#" class="tab active">Register</a>
    </div>

    <form id="signupForm" method="post">
        <div class="error-message" id="error-message"></div>

        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <small class="error-text" id="username-error"></small>
        </div>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
            <small class="error-text" id="email-error"></small>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small class="error-text" id="password-error"></small>
        </div>

        <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <small class="error-text" id="confirm-password-error"></small>
        </div>

        <button type="submit" class="signup-btn">Create Account</button>

        <div class="terms">
            By signing up, you agree to our 
            <a href="#">Terms of Service</a> and 
            <a href="#">Privacy Policy</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    const usernameError = document.getElementById('username-error');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');
    const errorMessage = document.getElementById('error-message');

    function validateUsername() {
        const value = usernameInput.value.trim();
        if (value === '') {
            usernameError.textContent = 'Username is required';
            return false;
        } else if (value.length < 4 || value.length > 15) {
            usernameError.textContent = 'Username should be between 4 to 15 characters';
            return false;
        }
        usernameError.textContent = '';
        return true;
    }

    function validateEmail() {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const value = emailInput.value.trim();
        if (value === '') {
            emailError.textContent = 'Email is required';
            return false;
        } else if (!emailPattern.test(value)) {
            emailError.textContent = 'Please enter a valid email address';
            return false;
        }
        emailError.textContent = '';
        return true;
    }

    function validatePassword() {
        const value = passwordInput.value.trim();
        if (value === '') {
            passwordError.textContent = 'Password is required';
            return false;
        } else if (value.length < 8) {
            passwordError.textContent = 'Password should be at least 8 characters long';
            return false;
        }
        passwordError.textContent = '';
        return true;
    }

    function validateConfirmPassword() {
        const value = confirmPasswordInput.value.trim();
        if (value === '') {
            confirmPasswordError.textContent = 'Please confirm your password';
            return false;
        } else if (value !== passwordInput.value) {
            confirmPasswordError.textContent = 'Passwords do not match';
            return false;
        }
        confirmPasswordError.textContent = '';
        return true;
    }

    // Add input event listeners
    usernameInput.addEventListener('input', validateUsername);
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validateConfirmPassword);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();

        if (isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            // Form is valid, you can submit it
            this.submit();
        } else {
            errorMessage.textContent = 'Please fix the errors in the form';
        }
    });
});
</script>
</body>
</html> -->
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
=======
<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EV Charging Station Finder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Existing CSS Code Here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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

        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding-top: 80px;
        }

        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;
            margin: 20px auto;
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
            color: #1da1f2;
            transform: translateY(-1px);
        }

        .tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #1da1f2;
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }

        .tab.active {
            color: #1da1f2;
        }

        .tab.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .social-signup {
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
            transition: transform 0.3s ease;
        }

        .social-icon:hover {
            transform: scale(1.1);
        }

        .facebook { background: #3b5998; }
        .twitter { background: #1da1f2; }
        .google { background: #db4437; }

        .input-group {
            margin-bottom: 20px;
            position: relative;
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
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 2px rgba(255, 75, 110, 0.1);
        }

        .signup-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background:  #1da1f2;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .signup-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 75, 110, 0.2);
        }

        .error-message {
            color: #ff0000;
            margin-bottom: 15px;
            text-align: center;
            animation: shake 0.5s ease;
        }

        .error-text {
            color: #ff0000;
            font-size: 0.8rem;
            display: block;
            margin-top: 5px;
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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .terms {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }

        .terms a {
            color: #1da1f2;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }
        .signup-container {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease;  /* Add this line */
        }

        // ... existing code ...

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

<div class="signup-container">
    <div class="tabs">
        <a href="evlogin.php" class="tab">Log In</a>
        <a href="#" class="tab active">Register</a>
    </div>

    <form id="signupForm" method="post">
        <div class="error-message" id="error-message"></div>

        <div class="input-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <small class="error-text" id="username-error"></small>
        </div>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
            <small class="error-text" id="email-error"></small>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small class="error-text" id="password-error"></small>
        </div>

        <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <small class="error-text" id="confirm-password-error"></small>
        </div>

        <button type="submit" class="signup-btn">Create Account</button>

        <div class="terms">
            By signing up, you agree to our 
            <a href="#">Terms of Service</a> and 
            <a href="#">Privacy Policy</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    const usernameError = document.getElementById('username-error');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');
    const errorMessage = document.getElementById('error-message');

    function validateUsername() {
        const value = usernameInput.value.trim();
        if (value === '') {
            usernameError.textContent = 'Username is required';
            return false;
        } else if (value.length < 4 || value.length > 15) {
            usernameError.textContent = 'Username should be between 4 to 15 characters';
            return false;
        }
        usernameError.textContent = '';
        return true;
    }

    function validateEmail() {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const value = emailInput.value.trim();
        if (value === '') {
            emailError.textContent = 'Email is required';
            return false;
        } else if (!emailPattern.test(value)) {
            emailError.textContent = 'Please enter a valid email address';
            return false;
        }
        emailError.textContent = '';
        return true;
    }

    function validatePassword() {
        const value = passwordInput.value.trim();
        if (value === '') {
            passwordError.textContent = 'Password is required';
            return false;
        } else if (value.length < 8) {
            passwordError.textContent = 'Password should be at least 8 characters long';
            return false;
        }
        passwordError.textContent = '';
        return true;
    }

    function validateConfirmPassword() {
        const value = confirmPasswordInput.value.trim();
        if (value === '') {
            confirmPasswordError.textContent = 'Please confirm your password';
            return false;
        } else if (value !== passwordInput.value) {
            confirmPasswordError.textContent = 'Passwords do not match';
            return false;
        }
        confirmPasswordError.textContent = '';
        return true;
    }

    // Add input event listeners
    usernameInput.addEventListener('input', validateUsername);
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validateConfirmPassword);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const isUsernameValid = validateUsername();
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();

        if (isUsernameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid) {
            // Form is valid, you can submit it
            this.submit();
        } else {
            errorMessage.textContent = 'Please fix the errors in the form';
        }
    });
});
</script>
</body>
</html> -->
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
