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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="page-wrapper">
        <div class="container">
            <!-- Login Form -->
            <div class="auth-form" id="loginContainer">
                <h1>Sign In</h1>
                <form id="loginForm">
                    <div class="form-group">
                        <label for="loginEmail">Email</label>
                        <input type="email" id="loginEmail" placeholder="Email Address" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" placeholder="Password" required>
                    </div>
                    <button type="submit" class="auth-btn" id="loginButton">Sign In</button>
                    <div class="form-footer">
                        <div class="remember-me">
                            <input type="checkbox" id="remember">
                            <label for="remember">Remember Me</label>
                        </div>
                        <a href="#" id="forgotPassword">Forgot Password</a>
                    </div>
                </form>
            </div>

            <!-- Signup Form (initially hidden) -->
            <div class="auth-form hidden" id="signupContainer">
                <h1>Sign Up</h1>
                <form id="signupForm">
                    <div class="form-group">
                        <label for="signupFullName">Full Name</label>
                        <input type="text" id="signupFullName" placeholder="Full Name" required>
                        <div class="error-message" id="signupFullName-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="signupEmail">Email Address</label>
                        <input type="email" id="signupEmail" placeholder="Email Address" required>
                        <div class="error-message" id="signupEmail-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="signupUsername">Username</label>
                        <input type="text" id="signupUsername" placeholder="Username" required>
                        <div class="error-message" id="signupUsername-error"></div>
                    </div>
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
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="isStationOwner"> I want to register as a Station Owner
                        </label>
                    </div>
                    <div id="stationOwnerFields" style="display: none;">
                        <div class="form-group">
                            <label for="businessName">Business Name</label>
                            <input type="text" id="businessName" placeholder="Business Name">
                            <div class="error-message" id="businessName-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" placeholder="Phone Number">
                            <div class="error-message" id="phone-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" placeholder="Full Address"></textarea>
                            <div class="error-message" id="address-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" placeholder="City">
                            <div class="error-message" id="city-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" placeholder="State">
                            <div class="error-message" id="state-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="postalCode">Postal Code</label>
                            <input type="text" id="postalCode" placeholder="Postal Code">
                            <div class="error-message" id="postalCode-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="businessRegistration">Business Registration Number</label>
                            <input type="text" id="businessRegistration" placeholder="Business Registration Number">
                            <div class="error-message" id="businessRegistration-error"></div>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn" id="signupButton">Create Account</button>
                    <div class="terms">
                        By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </div>
                </form>
            </div>

            <!-- Welcome Section - Changes based on current form -->
            <div class="welcome-section" id="welcomeSection">
                <div class="welcome-content" id="welcomeContent">
                    <h2>Welcome to login</h2>
                    <p>Don't have an account?</p>
                    <button class="toggle-btn" id="toggleSignup">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const loginContainer = document.getElementById('loginContainer');
        const signupContainer = document.getElementById('signupContainer');
        const welcomeSection = document.getElementById('welcomeSection');
        const welcomeContent = document.getElementById('welcomeContent');
        const toggleBtn = document.getElementById('toggleSignup');
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        const passwordInput = document.getElementById('signupPassword');
        const passwordStrength = document.getElementById('passwordStrength');
        const confirmPassword = document.getElementById('confirmPassword');
        const loginButton = document.getElementById('loginButton');
        const signupButton = document.getElementById('signupButton');

        // State variable to track current form
        let isLoginForm = true;
        let isAnimating = false;

        // Function to show error message
        function showError(inputId, message) {
            const errorElement = document.getElementById(`${inputId}-error`);
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            document.getElementById(inputId).classList.add('shake');
            
            // Remove shake animation after it completes
            setTimeout(() => {
                document.getElementById(inputId).classList.remove('shake');
            }, 500);
            
            return false;
        }

        // Function to clear error message
        function clearError(inputId) {
            const errorElement = document.getElementById(`${inputId}-error`);
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        }

        // Function to validate email format
        function isValidEmail(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
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
                if (isLoginForm) {
                    welcomeContent.querySelector('h2').textContent = 'Welcome to login';
                    welcomeContent.querySelector('p').textContent = "Don't have an account?";
                    toggleBtn.textContent = 'Sign Up';
                } else {
                    welcomeContent.querySelector('h2').textContent = 'Welcome Back';
                    welcomeContent.querySelector('p').textContent = 'Already have an account?';
                    toggleBtn.textContent = 'Sign In';
                }
                
                // Prepare for slide in
                welcomeContent.classList.remove('slide-out');
                welcomeContent.classList.add('slide-in');
                
                setTimeout(() => {
                    welcomeContent.classList.remove('slide-in');
                    isAnimating = false;
                }, 300);
            }, 300);
        }

        // Function to toggle between login and signup forms
        function toggleForms() {
            if (isAnimating) return;
            isLoginForm = !isLoginForm;
            
            // Animate welcome section
            animateWelcomeSection();
            
            // Change background color based on form
            document.body.style.backgroundColor = isLoginForm ? '#f5f5f5' : '#f8f8f8';
            
            if (isLoginForm) {
                // Hide signup, then show login
                signupContainer.style.opacity = '0';
                signupContainer.style.transform = 'translateX(50px)';
                
                setTimeout(() => {
                    signupContainer.classList.add('hidden');
                    loginContainer.classList.remove('hidden');
                    
                    // Set initial state for login container
                    loginContainer.style.opacity = '0';
                    loginContainer.style.transform = 'translateX(-50px)';
                    
                    // Trigger reflow
                    void loginContainer.offsetWidth;
                    
                    // Animate login container in
                    loginContainer.style.opacity = '1';
                    loginContainer.style.transform = 'translateX(0)';
                    
                    // Animate form groups
                    animateFormGroups('loginForm');
                }, 300);
            } else {
                // Hide login, then show signup
                loginContainer.style.opacity = '0';
                loginContainer.style.transform = 'translateX(-50px)';
                
                setTimeout(() => {
                    loginContainer.classList.add('hidden');
                    signupContainer.classList.remove('hidden');
                    
                    // Set initial state for signup container
                    signupContainer.style.opacity = '0';
                    signupContainer.style.transform = 'translateX(50px)';
                    
                    // Trigger reflow
                    void signupContainer.offsetWidth;
                    
                    // Animate signup container in
                    signupContainer.style.opacity = '1';
                    signupContainer.style.transform = 'translateX(0)';
                    
                    // Animate form groups
                    animateFormGroups('signupForm');
                }, 300);
            }
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
            animateFormGroups('loginForm');
        });

        // Toggle button event listener
        toggleBtn.addEventListener('click', toggleForms);

        // Password strength meter
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

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
            const postalRegex = /^\d{5}(-\d{4})?$/;  // US format, adjust for your country
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

        // Add live validation event listeners
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', function() {
                let errorMessage = '';
                
                switch(this.id) {
                    case 'loginEmail':
                        errorMessage = validateUsername(this.value.trim());
                        break;
                    
                    case 'signupUsername':
                        errorMessage = validateUsername(this.value.trim());
                        break;
                    
                    case 'signupPassword':
                        errorMessage = validatePassword(this.value);
                        break;
                    
                    case 'confirmPassword':
                        if (this.value && this.value !== passwordInput.value) {
                            errorMessage = 'Passwords do not match';
                        }
                        break;
                    
                    case 'signupFullName':
                        if (this.value.trim() && this.value.trim().length < 2) {
                            errorMessage = 'Please enter your full name';
                        }
                        break;
                    
                    case 'businessName':
                        if (this.value.trim().length < 2) {
                            errorMessage = 'Business name must be at least 2 characters';
                        }
                        break;
                    
                    case 'phone':
                        errorMessage = validatePhone(this.value.trim());
                        break;
                    
                    case 'address':
                        if (this.value.trim().length < 10) {
                            errorMessage = 'Please enter a complete address';
                        }
                        break;
                    
                    case 'city':
                        if (this.value.trim().length < 2) {
                            errorMessage = 'City name must be at least 2 characters';
                        }
                        break;
                    
                    case 'state':
                        if (this.value.trim().length < 2) {
                            errorMessage = 'Please enter a valid state';
                        }
                        break;
                    
                    case 'postalCode':
                        errorMessage = validatePostalCode(this.value.trim());
                        break;
                    
                    case 'businessRegistration':
                        errorMessage = validateBusinessRegistration(this.value.trim());
                        break;
                }
                
                const errorElement = document.getElementById(`${this.id}-error`);
                if (errorElement) {
                    if (errorMessage && this.value) {
                        errorElement.textContent = errorMessage;
                        errorElement.style.display = 'block';
                        this.style.borderColor = '#ff3a57';
                    } else {
                        errorElement.style.display = 'none';
                        this.style.borderColor = '#ddd';
                    }
                }
            });

            // Enhanced blur validation
            input.addEventListener('blur', function() {
                if (this.required || document.getElementById('isStationOwner').checked) {
                    const fieldName = this.previousElementSibling.textContent;
                    if (!this.value.trim()) {
                        showError(this.id, `${fieldName} is required`);
                        this.style.borderColor = '#ff3a57';
                    } else {
                        // Trigger input validation on blur
                        this.dispatchEvent(new Event('input'));
                    }
                }
            });
        });

        // Login form submit handler
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail');
            const password = document.getElementById('loginPassword');
            const remember = document.getElementById('remember');
            
            // Create form data
            const formData = {
                action: 'login',
                email: email.value.trim(),
                password: password.value,
                remember: remember.checked
            };
            
            // Debug log
            console.log('Sending login request with data:', formData);
            
            simulateAPICall(loginButton, () => {
                fetch('auth.php', {
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
                    console.log('Server response:', data);
                    
                    if (data.success) {
                        // Check user status and role
                        if (data.user.role === 'station_owner') {
                            if (data.user.status === 'pending') {
                                alert('Your station owner account is pending approval. Please wait for admin verification.');
                                return;
                            } else if (data.user.status === 'approved') {
                                window.location.href = 'station-owner-dashboard.php';
                                return;
                            }
                        } else if (data.user.role === 'admin') {
                            window.location.href = 'admin-dashboard.php';
                            return;
                        }
                        // Default redirect for regular users
                        window.location.href = 'index.php';
                    } else {
                        alert(data.message || 'Invalid email or password');
                    }
                })
                .catch(error => {
                    console.error('Login error:', error);
                    alert('An error occurred during login. Please try again.');
                });
            });
        });

        // Signup form submit handler
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let isValid = true;
            const isStationOwner = document.getElementById('isStationOwner').checked;
            
            document.querySelectorAll('.error-message').forEach(error => {
                error.style.display = 'none';
                error.textContent = '';
            });
            
            const requiredFields = ['signupFullName', 'signupEmail', 'signupUsername', 'signupPassword', 'confirmPassword'];
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    isValid = showError(field, `${element.placeholder} is required`);
                }
            });
            
            const email = document.getElementById('signupEmail').value;
            if (email && !isValidEmail(email)) {
                isValid = showError('signupEmail', 'Please enter a valid email address');
            }
            
            const password = document.getElementById('signupPassword').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            if (password !== confirmPass) {
                isValid = showError('confirmPassword', 'Passwords do not match');
            }
            
            if (isStationOwner) {
                const ownerFields = ['businessName', 'phone', 'address', 'city', 'state', 'postalCode'];
                ownerFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (!element.value.trim()) {
                        isValid = showError(field, `${element.placeholder} is required`);
                    }
                });
            }
            
            if (isValid) {
                const formData = {
                    action: 'register',
                    fullName: document.getElementById('signupFullName').value.trim(),
                    email: document.getElementById('signupEmail').value.trim(),
                    username: document.getElementById('signupUsername').value.trim(),
                    password: document.getElementById('signupPassword').value,
                    isStationOwner: isStationOwner
                };

                if (isStationOwner) {
                    formData.stationOwner = {
                        owner_name: document.getElementById('signupFullName').value.trim(),
                        business_name: document.getElementById('businessName').value.trim(),
                        email: document.getElementById('signupEmail').value.trim(),
                        phone: document.getElementById('phone').value.trim(),
                        address: document.getElementById('address').value.trim(),
                        city: document.getElementById('city').value.trim(),
                        state: document.getElementById('state').value.trim(),
                        postal_code: document.getElementById('postalCode').value.trim(),
                        business_registration: document.getElementById('businessRegistration').value.trim()
                    };
                }

                simulateAPICall(signupButton, () => {
                    fetch('auth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (isStationOwner) {
                                alert('Your station owner registration request has been submitted and is pending approval.');
                            } else {
                                alert('Registration successful! Please login.');
                            }
                            signupForm.reset();
                            passwordStrength.style.width = '0';
                            if (!isLoginForm) {
                                toggleForms();
                            }
                        } else {
                            showError('signupUsername', data.message || 'Registration failed. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Registration error:', error);
                        showError('signupUsername', 'Unable to connect to the server. Please check your internet connection and try again.');
                    });
                });
            }
        });

        // Forgot password handler
        document.getElementById('forgotPassword').addEventListener('click', function(e) {
            e.preventDefault();
            
            const email = prompt('Enter your email address to reset your password:');
            if (email) {
                if (!isValidEmail(email)) {
                    alert('Please enter a valid email address.');
                    return;
                }
                
                alert(`Password reset instructions have been sent to ${email}. Please check your inbox.`);
            }
        });

        // Keyboard navigation support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const focusedElement = document.activeElement;
                
                // If tab pressed on last element of current form, focus toggle button
                if (!e.shiftKey) {
                    const lastElement = isLoginForm
                        ? document.getElementById('forgotPassword')
                        : document.querySelector('.terms a:last-child');
                        
                    if (focusedElement === lastElement) {
                        e.preventDefault();
                        toggleBtn.focus();
                    }
                }
            }
        });

        // Toggle station owner fields
        document.getElementById('isStationOwner').addEventListener('change', function() {
            const stationOwnerFields = document.getElementById('stationOwnerFields');
            const fields = stationOwnerFields.querySelectorAll('input, textarea');
            
            if (this.checked) {
                stationOwnerFields.style.display = 'block';
                fields.forEach(field => {
                    field.required = true;
                    // Trigger validation for existing values
                    field.dispatchEvent(new Event('input'));
                });
            } else {
                stationOwnerFields.style.display = 'none';
                fields.forEach(field => {
                    field.required = false;
                    // Clear any existing errors
                    const errorElement = document.getElementById(`${field.id}-error`);
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                    field.style.borderColor = '#ddd';
                });
            }
        });
    </script>
</body>
</html>