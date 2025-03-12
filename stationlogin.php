<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Signup Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f5f5f5;
            transition: background-color 0.5s ease;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 60px); /* Adjust based on your header height */
            padding: 20px;
        }

        .container {
            display: flex;
            max-width: 900px;
            width: 100%;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            transform: scale(1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: scale(1.01);
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.15);
        }

        .auth-form {
            flex: 1;
            background-color: white;
            padding: 40px;
            position: relative;
            transition: all 0.5s ease;
        }

        .auth-form h1 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
            position: relative;
        }

        .auth-form h1:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #3498db;
            transition: width 0.3s ease;
        }

        .auth-form:hover h1:after {
            width: 80px;
        }

        .form-group {
            margin-bottom: 20px;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .form-slide-in {
            opacity: 0;
            transform: translateY(20px);
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #555;
            text-transform: uppercase;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 30px;
            background-color: #f5f5f5;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .auth-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 30px;
            background-color: #3498db;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
        }

        .auth-btn:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .auth-btn:hover {
            background-color: #2980b9;
        }

        .auth-btn:active:after {
            animation: ripple 0.6s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
        }

        .remember-me input[type="checkbox"] {
            position: relative;
            width: 16px;
            height: 16px;
            cursor: pointer;
            appearance: none;
            border: 1px solid #ddd;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .remember-me input[type="checkbox"]:checked {
            background-color: #3498db;
            border-color: #3498db;
        }

        .remember-me input[type="checkbox"]:checked:after {
            content: '✓';
            position: absolute;
            top: 0;
            left: 3px;
            color: white;
            font-size: 12px;
        }

        .form-footer a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
            position: relative;
        }

        .form-footer a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -2px;
            left: 0;
            background-color: #3498db;
            transition: width 0.3s;
        }

        .form-footer a:hover {
            color: #3498db;
        }

        .form-footer a:hover:after {
            width: 100%;
        }

        .welcome-section {
            flex: 1;
            background-color: #3498db;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.5s ease;
        }

        .welcome-section:before {
            content: '';
            position: absolute;
            top: -20%;
            right: -20%;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .welcome-section:after {
            content: '';
            position: absolute;
            bottom: -10%;
            left: -10%;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            transition: transform 0.5s ease, opacity 0.5s ease;
        }

        .welcome-section h2 {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .welcome-section p {
            margin-bottom: 25px;
            font-size: 16px;
        }

        .toggle-btn {
            padding: 10px 25px;
            border: 2px solid white;
            border-radius: 30px;
            background-color: transparent;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .toggle-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: white;
            transition: all 0.3s ease;
            z-index: -1;
        }

        .toggle-btn:hover {
            color: #3498db;
        }

        .toggle-btn:hover:before {
            left: 0;
        }

        .slide-out {
            transform: translateX(-100%);
            opacity: 0;
        }

        .slide-in {
            transform: translateX(0);
            opacity: 1;
        }

        #loginForm, #signupForm {
            display: block;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .hidden {
            display: none !important;
        }

        .terms {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            text-align: center;
            transition: opacity 0.3s;
        }

        .terms a {
            color: #3498db;
            text-decoration: none;
            position: relative;
        }

        .terms a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 1px;
            bottom: -2px;
            left: 0;
            background-color: #3498db;
            transition: width 0.3s;
        }

        .terms a:hover:after {
            width: 100%;
        }

        .error-message {
            color: #ff3a57;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            transition: all 0.3s ease;
            padding-left: 15px;
        }

        .form-group input.error {
            border-color: #ff3a57;
        }

        .password-strength {
            height: 5px;
            background-color: #f5f5f5;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }

        .password-strength-meter {
            height: 100%;
            width: 0;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .loading {
            position: relative;
        }

        .loading:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .loading:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            z-index: 11;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 95%;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
        }

        #stationOwnerFields {
            max-height: none;
            opacity: 1;
            overflow: visible;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body>
    <?php
    // Check if user is logged in
    $userLoggedIn = isset($_SESSION['user_id']);
    $userData = null;
    
    if ($userLoggedIn) {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "evolve1";
        
        try {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Fetch user data
            $stmt = $conn->prepare("SELECT name, email, username, phone_number FROM tbl_users WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
            
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            // Handle error silently
            error_log("Error fetching user data: " . $e->getMessage());
        }
    }
    ?>

    <div class="page-wrapper">
        <div class="container">
            <!-- Signup Form -->
            <div class="auth-form" id="signupContainer">
                <h1>Sign Up</h1>
                <form id="signupForm">
                    <div class="form-group">
                        <label for="signupFullName">Full Name</label>
                        <input type="text" id="signupFullName" placeholder="Full Name" required 
                               value="<?php echo $userLoggedIn ? htmlspecialchars($userData['name']) : ''; ?>" 
                               <?php echo $userLoggedIn ? 'readonly' : ''; ?>>
                        <div class="error-message" id="signupFullName-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="signupEmail">Email Address</label>
                        <input type="email" id="signupEmail" placeholder="Email Address" required 
                               value="<?php echo $userLoggedIn ? htmlspecialchars($userData['email']) : ''; ?>"
                               <?php echo $userLoggedIn ? 'readonly' : ''; ?>>
                        <div class="error-message" id="signupEmail-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="signupUsername">Username</label>
                        <input type="text" id="signupUsername" placeholder="Username" required 
                               value="<?php echo $userLoggedIn ? htmlspecialchars($userData['username']) : ''; ?>"
                               <?php echo $userLoggedIn ? 'readonly' : ''; ?>>
                        <div class="error-message" id="signupUsername-error"></div>
                    </div>

                    <?php if (!$userLoggedIn): ?>
                    <div class="form-group">
                        <label for="signupPassword">Password</label>
                        <input type="password" id="signupPassword" placeholder="Password" required>
                        <div class="password-strength">
                            <div class="password-strength-meter" id="passwordStrength"></div>
                        </div>
                        <div class="error-message" id="signupPassword-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" placeholder="Confirm Password" required>
                        <div class="error-message" id="confirmPassword-error"></div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <div class="remember-me">
                            <input type="checkbox" id="isStationOwner" checked disabled>
                            <label for="isStationOwner">Register as Station Owner</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <h3>Station Owner Details</h3>
                    </div>
                    <div id="stationOwnerFields">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" placeholder="Phone Number" required 
                                   value="<?php echo $userLoggedIn ? htmlspecialchars($userData['phone_number']) : ''; ?>">
                            <div class="error-message" id="phone-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" placeholder="Full Address" required></textarea>
                            <div class="error-message" id="address-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" placeholder="City" required>
                            <div class="error-message" id="city-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" placeholder="State" required>
                            <div class="error-message" id="state-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="postalCode">Postal Code</label>
                            <input type="text" id="postalCode" placeholder="Postal Code" required>
                            <div class="error-message" id="postalCode-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="businessRegistration">Business Registration Number</label>
                            <input type="text" id="businessRegistration" placeholder="Business Registration Number" required>
                            <div class="error-message" id="businessRegistration-error"></div>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn" id="signupButton">Register as Station Owner</button>
                    <div class="terms">
                        By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </div>
                </form>
            </div>

            <!-- Welcome Section -->
            <div class="welcome-section" id="welcomeSection">
                <div class="welcome-content" id="welcomeContent">
                    <h2>Register Your Station</h2>
                    <p>Join our network of charging stations</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const signupContainer = document.getElementById('signupContainer');
        const welcomeSection = document.getElementById('welcomeSection');
        const welcomeContent = document.getElementById('welcomeContent');
        const signupForm = document.getElementById('signupForm');
        const passwordInput = document.getElementById('signupPassword');
        const passwordStrength = document.getElementById('passwordStrength');
        const confirmPassword = document.getElementById('confirmPassword');
        const signupButton = document.getElementById('signupButton');

        // State variable to track current form
        let isAnimating = false;

        // Function to show error message
        function showError(inputId, message) {
            const errorElement = document.getElementById(`${inputId}-error`);
            const inputElement = document.getElementById(inputId);
            
            if (errorElement && inputElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                inputElement.classList.add('error');
                inputElement.classList.add('shake');
                
                // Remove shake animation after it completes
                setTimeout(() => {
                    inputElement.classList.remove('shake');
                }, 500);
            }
            return false;
        }

        // Function to clear error message
        function clearError(inputId) {
            const errorElement = document.getElementById(`${inputId}-error`);
            const inputElement = document.getElementById(inputId);
            
            if (errorElement && inputElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                inputElement.classList.remove('error');
            }
        }

        // Function to validate email format
        function isValidEmail(email) {
            // Basic email format validation
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!email || !emailRegex.test(email)) {
                return false;
            }

            // Split email into local part and domain
            const parts = email.toLowerCase().split('@');
            if (parts.length !== 2) return false;

            const [localPart, domain] = parts;

            // Check if domain is gmail.com
            if (domain !== 'gmail.com') {
                // Check if it's a common misspelling of gmail.com
                const commonMisspellings = {
                    'gmail.co': 'Did you mean gmail.com?',
                    'gmai.com': 'Did you mean gmail.com?',
                    'gmil.com': 'Did you mean gmail.com?',
                    'gmal.com': 'Did you mean gmail.com?',
                    'gmail.comm': 'Did you mean gmail.com?',
                    'gmail.con': 'Did you mean gmail.com?',
                    'gmail.om': 'Did you mean gmail.com?',
                    'gmail.cm': 'Did you mean gmail.com?',
                    'gmail.cpm': 'Did you mean gmail.com?',
                    'gmail.vom': 'Did you mean gmail.com?',
                    'gmail.cim': 'Did you mean gmail.com?',
                    'gamil.com': 'Did you mean gmail.com?',
                    'gnail.com': 'Did you mean gmail.com?',
                    'gmaill.com': 'Did you mean gmail.com?',
                };

                if (commonMisspellings[domain]) {
                    showError('signupEmail', commonMisspellings[domain]);
                    return false;
                }
                showError('signupEmail', "Please use a Gmail address (example@gmail.com)");
                return false;
            }

            return true;
        }

        // Function to check password strength
        function checkPasswordStrength(password) {
            // Initialize strength score
            let strength = 0;
            
            // If password length is less than 6 chars, return 0
            if (password.length < 6) {
                passwordStrength.style.width = '20%';
                passwordStrength.style.backgroundColor = '#ff3a57';
                return;
            }
            
            // If password length is >= 8 chars, +1
            if (password.length >= 8) strength += 1;
            
            // If password contains lowercase and uppercase, +1
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            
            // If password contains letters and numbers, +1
            if (/[a-zA-Z]/.test(password) && /[0-9]/.test(password)) strength += 1;
            
            // If password contains special chars, +1
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Update password strength meter
            switch (strength) {
                case 0:
                    passwordStrength.style.width = '20%';
                    passwordStrength.style.backgroundColor = '#ff3a57';
                    break;
                case 1:
                    passwordStrength.style.width = '40%';
                    passwordStrength.style.backgroundColor = '#ffa500';
                    break;
                case 2:
                    passwordStrength.style.width = '60%';
                    passwordStrength.style.backgroundColor = '#ffff00';
                    break;
                case 3:
                    passwordStrength.style.width = '80%';
                    passwordStrength.style.backgroundColor = '#aaff00';
                    break;
                case 4:
                    passwordStrength.style.width = '100%';
                    passwordStrength.style.backgroundColor = '#00ff00';
                    break;
            }
        }

        // Function to animate form groups
        function animateFormGroups(formId, delay = 100) {
            const formGroups = document.querySelectorAll(`#${formId} .form-group`);
            formGroups.forEach((group, index) => {
                group.classList.add('form-slide-in');
                setTimeout(() => {
                    group.classList.remove('form-slide-in');
                }, delay * (index + 1));
            });
        }

        // Toggle animation for welcome section
        function animateWelcomeSection() {
            if (isAnimating) return;
            isAnimating = true;
            
            // Slide out current content
            welcomeContent.classList.add('slide-out');
            
            setTimeout(() => {
                // Update content
                welcomeContent.querySelector('h2').textContent = 'Register Your Station';
                welcomeContent.querySelector('p').textContent = 'Join our network of charging stations';
                
                // Prepare for slide in
                welcomeContent.classList.remove('slide-out');
                welcomeContent.classList.add('slide-in');
                
                setTimeout(() => {
                    welcomeContent.classList.remove('slide-in');
                    isAnimating = false;
                }, 300);
            }, 300);
        }

        // Function to simulate API call with loading state
        function simulateAPICall(button, successCallback) {
            // Add loading state
            button.disabled = true;
            button.parentElement.classList.add('loading');
            
            // Simulate API delay
            setTimeout(() => {
                // Remove loading state
                button.disabled = false;
                button.parentElement.classList.remove('loading');
                
                // Call success callback
                successCallback();
            }, 1500);
        }

        // Initialize form animations on page load
        window.addEventListener('DOMContentLoaded', () => {
            animateFormGroups('signupForm');
        });

        // Password strength meter
   

        // Function to validate password
        function validatePassword(password) {
            if (password.length < 6) {
                return 'Password must be at least 6 characters';
            }
            if (!/[A-Z]/.test(password)) {
                return 'Password must contain at least one uppercase letter';
            }
            if (!/[a-z]/.test(password)) {
                return 'Password must contain at least one lowercase letter';
            }
            if (!/[0-9]/.test(password)) {
                return 'Password must contain at least one number';
            }
            return '';
        }

        // Function to validate username
        function validateUsername(username) {
            if (username.length < 4) {
                return 'Username must be at least 4 characters';
            }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                return 'Username can only contain letters, numbers, and underscores';
            }
            return '';
        }

        // Function to validate phone number
        function validatePhone(phone) {
            // Remove any spaces, dashes or plus signs
            const cleanPhone = phone.replace(/[\s-+]/g, '');
            
            // Check if number starts with 7, 8, or 9 and has exactly 10 digits
            const phoneRegex = /^[7-9]\d{9}$/;
            
            if (!phoneRegex.test(cleanPhone)) {
                return 'Phone number must start with 7, 8, or 9 and be 10 digits long';
            }
            return '';
        }

        // Function to validate postal code
        function validatePostalCode(code) {
            const postalRegex = /^\d{6}(-\d{4})?$/;  // US format, adjust for your country
            if (!postalRegex.test(code)) {
                return 'Please enter a valid postal code';
            }
            return '';
        }

        // Function to validate business registration number
        function validateBusinessRegistration(number) {
            if (number.length < 5) {
                return 'Business registration number must be at least 5 characters';
            }
            return '';
        }

        // Add these validation functions after the existing validation functions
        function validateBusinessName(name) {
            if (name.length < 2) {
                return 'Business name must be at least 2 characters long';
            }
            if (!/^[a-zA-Z0-9\s&'-]+$/.test(name)) {
                return 'Business name can only contain letters, numbers, spaces, &, \', and -';
            }
            return '';
        }

        function validateAddress(address) {
            if (address.length < 5) {
                return 'Please enter a complete address';
            }
            return '';
        }

        function validateCity(city) {
            if (!/^[a-zA-Z\s-]+$/.test(city)) {
                return 'City name can only contain letters, spaces, and hyphens';
            }
            return '';
        }

        function validateState(state) {
            if (!/^[a-zA-Z\s]+$/.test(state)) {
                return 'State name can only contain letters and spaces';
            }
            return '';
        }

        // Update the input event listeners section
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', function() {
                let errorMessage = '';
                const value = this.value.trim();
                
                switch(this.id) {
                    case 'signupEmail':
                        if (value && !isValidEmail(value)) {
                            errorMessage = 'Please enter a valid Gmail address';
                        }
                        break;
                    
                    case 'signupUsername':
                        if (value.length < 4) {
                            errorMessage = 'Username must be at least 4 characters';
                        } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                            errorMessage = 'Username can only contain letters, numbers, and underscores';
                        }
                        break;
                    
                    case 'signupPassword':
                        errorMessage = validatePassword(value);
                        break;
                    
                    case 'confirmPassword':
                        const password = document.getElementById('signupPassword').value;
                        if (value && value !== password) {
                            errorMessage = 'Passwords do not match';
                        }
                        break;
                    
                    case 'signupFullName':
                        if (value && value.length < 2) {
                            errorMessage = 'Please enter your full name';
                        }
                        break;
                    
                    case 'phone':
                        if (value) {
                            errorMessage = validatePhone(value);
                        }
                        break;
                    
                    case 'postalCode':
                        if (value) {
                            errorMessage = validatePostalCode(value);
                        }
                        break;
                    
                    case 'businessName':
                        errorMessage = validateBusinessName(value);
                        break;
                    
                    case 'address':
                        errorMessage = validateAddress(value);
                        break;
                    
                    case 'city':
                        errorMessage = validateCity(value);
                        break;
                    
                    case 'state':
                        errorMessage = validateState(value);
                        break;
                    
                    case 'businessRegistration':
                        errorMessage = validateBusinessRegistration(value);
                        break;
                }
                
                if (errorMessage) {
                    showError(this.id, errorMessage);
                } else {
                    clearError(this.id);
                }
            });

            // Enhanced blur validation with specific messages
            input.addEventListener('blur', function() {
                const value = this.value.trim();
                const isStationOwner = document.getElementById('isStationOwner').checked;
                
                if ((this.required || (isStationOwner && this.closest('#stationOwnerFields'))) && !value) {
                    let fieldName = this.previousElementSibling.textContent;
                    let customMessage;
                    
                    switch(this.id) {
                        case 'businessName':
                            customMessage = 'Please enter your business name';
                            break;
                        case 'phone':
                            customMessage = 'Please enter a valid phone number';
                            break;
                        case 'address':
                            customMessage = 'Please enter your business address';
                            break;
                        case 'city':
                            customMessage = 'Please enter your city';
                            break;
                        case 'state':
                            customMessage = 'Please enter your state';
                            break;
                        case 'postalCode':
                            customMessage = 'Please enter your postal code';
                            break;
                        case 'businessRegistration':
                            customMessage = 'Please enter your business registration number';
                            break;
                        default:
                            customMessage = `${fieldName} is required`;
                    }
                    
                    showError(this.id, customMessage);
                } else {
                    // Trigger input validation on blur
                    this.dispatchEvent(new Event('input'));
                }
            });
        });

        // Add real-time phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + '-' + value.slice(3);
                } else {
                    value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                }
                this.value = value;
            }
        });

        // Update the isStationOwner change handler
        document.getElementById('isStationOwner').addEventListener('change', function() {
            const stationOwnerFields = document.getElementById('stationOwnerFields');
            const fields = stationOwnerFields.querySelectorAll('input, textarea');
            
            if (this.checked) {
                stationOwnerFields.style.display = 'block';
                fields.forEach(field => {
                    field.required = true;
                    // Clear any existing values
                    field.value = '';
                    // Remove any existing error states
                    clearError(field.id);
                });
                
                // Add slide-down animation
                stationOwnerFields.style.maxHeight = stationOwnerFields.scrollHeight + 'px';
                stationOwnerFields.style.opacity = '1';
            } else {
                // Add slide-up animation
                stationOwnerFields.style.maxHeight = '0';
                stationOwnerFields.style.opacity = '0';
                
                setTimeout(() => {
                    stationOwnerFields.style.display = 'none';
                    fields.forEach(field => {
                        field.required = false;
                        field.value = '';
                        clearError(field.id);
                    });
                }, 300);
            }
        });

        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Get form element
            const signupForm = document.getElementById('signupForm');
            const passwordInput = document.getElementById('signupPassword');
            const passwordStrength = document.getElementById('passwordStrength');
            const confirmPassword = document.getElementById('confirmPassword');
            const signupButton = document.getElementById('signupButton');

            if (signupForm) {
                // Signup form submit handler
                signupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    let isValid = true;
                    
                    // Clear all previous error messages
                    document.querySelectorAll('.error-message').forEach(error => {
                        error.style.display = 'none';
                        error.textContent = '';
                    });
                    
                    // Validate required fields
                    const requiredFields = <?php echo $userLoggedIn ? 
                        "['phone', 'address', 'city', 'state', 'postalCode', 'businessRegistration']" : 
                        "['signupFullName', 'signupEmail', 'signupUsername', 'signupPassword', 'confirmPassword', 'phone', 'address', 'city', 'state', 'postalCode', 'businessRegistration']"; ?>;
                    
                    requiredFields.forEach(field => {
                        const element = document.getElementById(field);
                        if (element && !element.value.trim()) {
                            isValid = false;
                            showError(field, `${element.placeholder || field} is required`);
                        }
                    });
                    
                    // Only validate email and password if user is not logged in
                    <?php if (!$userLoggedIn): ?>
                    // Email validation
                    const email = document.getElementById('signupEmail').value.trim();
                    if (email && !isValidEmail(email)) {
                        isValid = false;
                    }
                    
                    const password = document.getElementById('signupPassword').value;
                    const confirmPass = document.getElementById('confirmPassword').value;
                    if (password !== confirmPass) {
                        isValid = showError('confirmPassword', 'Passwords do not match');
                    }
                    <?php endif; ?>
                    
                    if (isValid) {
                        const formData = {
                            <?php if (!$userLoggedIn): ?>
                            fullName: document.getElementById('signupFullName').value.trim(),
                            email: document.getElementById('signupEmail').value.trim(),
                            username: document.getElementById('signupUsername').value.trim(),
                            password: document.getElementById('signupPassword').value,
                            <?php else: ?>
                            user_id: <?php echo $_SESSION['user_id']; ?>,
                            owner_name: document.getElementById('signupFullName').value.trim(),
                            email: document.getElementById('signupEmail').value.trim(),
                            <?php endif; ?>
                            phone: document.getElementById('phone').value.trim(),
                            address: document.getElementById('address').value.trim(),
                            city: document.getElementById('city').value.trim(),
                            state: document.getElementById('state').value.trim(),
                            postalCode: document.getElementById('postalCode').value.trim(),
                            businessRegistration: document.getElementById('businessRegistration').value.trim()
                        };

                        console.log('Sending data:', formData); // Debug log

                        // Show loading state
                        signupButton.disabled = true;
                        signupButton.textContent = 'Registering...';

                        fetch('register_station_owner.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(formData)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                alert('Your station owner registration request has been submitted and is pending approval.');
                                window.location.href = 'dashboard.php'; // Redirect to dashboard after successful registration
                            } else {
                                showError('signupUsername', data.message || 'Registration failed. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Registration error:', error);
                            showError('signupUsername', 'Unable to connect to the server. Please try again.');
                        })
                        .finally(() => {
                            signupButton.disabled = false;
                            signupButton.textContent = 'Register as Station Owner';
                        });
                    }
                });
            }

            // Password strength meter
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
            }

            // Rest of your existing event listeners with null checks
            document.querySelectorAll('input, textarea').forEach(input => {
                if (input) {
                    input.addEventListener('input', function() {
                        // ... existing input handler code ...
                    });

                    input.addEventListener('blur', function() {
                        // ... existing blur handler code ...
                    });
                }
            });

            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    // ... existing phone formatting code ...
                });
            }

            // Station owner checkbox
            const isStationOwner = document.getElementById('isStationOwner');
            if (isStationOwner) {
                isStationOwner.addEventListener('change', function() {
                    // ... existing station owner handler code ...
                });
            }
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusedElement = document.activeElement;
                
                // If tab pressed on last element of current form, focus signup button
                if (!e.shiftKey) {
                    const lastElement = document.querySelector('.terms a:last-child');
                        
                    if (focusedElement === lastElement) {
                        e.preventDefault();
                        signupButton.focus();
                    }
                }
            }
        });
    </script>
</body>
</html>