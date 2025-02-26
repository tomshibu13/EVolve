<?php
session_start();
require_once 'config.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.phplogin');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $required_fields = ['owner_name', 'business_name', 'email', 'phone', 'address', 'city', 'state', 'postal_code'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = ucfirst(str_replace('_', ' ', $field));
        }
    }

    if (!empty($missing_fields)) {
        $error_message = "Please fill in all required fields: " . implode(', ', $missing_fields);
    } else {
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address";
        } else {
            // Check if user already has a pending request
            $stmt = $mysqli->prepare("SELECT request_id FROM station_owner_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error_message = "You already have a pending station owner request";
            } else {
                // Insert the request into the database
                $stmt = $mysqli->prepare("
                    INSERT INTO station_owner_requests (
                        user_id, owner_name, business_name, email, phone, 
                        address, city, state, postal_code, business_registration
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $business_registration = $_POST['business_registration'] ?? null;
                $stmt->bind_param("isssssssss", $_SESSION['user_id'], $_POST['owner_name'], $_POST['business_name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['city'], $_POST['state'], $_POST['postal_code'], $business_registration);
                
                if ($stmt->execute()) {
                    $success_message = "Your request to become a station owner has been submitted successfully!";
                } else {
                    $error_message = "Error submitting your request. Please try again.";
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Station Owner - EVolve</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Add these styles to your existing CSS */
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            background: #3498db;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background: #2980b9;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .error {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Become a Station Owner</h1>
            <p>Join our network of EV charging station owners and help build a sustainable future</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="owner_name">Full Name *</label>
                    <input type="text" id="owner_name" name="owner_name" required>
                    <div id="owner_name-error" class="error"></div>
                </div>
                <div class="form-group">
                    <label for="business_name">Business Name *</label>
                    <input type="text" id="business_name" name="business_name" required>
                    <div id="business_name-error" class="error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required>
                    <div id="email-error" class="error"></div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required>
                    <div id="phone-error" class="error"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Street Address *</label>
                <input type="text" id="address" name="address" required>
                <div id="address-error" class="error"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" id="city" name="city" required>
                    <div id="city-error" class="error"></div>
                </div>
                <div class="form-group">
                    <label for="state">State *</label>
                    <input type="text" id="state" name="state" required>
                    <div id="state-error" class="error"></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="postal_code">Postal Code *</label>
                    <input type="text" id="postal_code" name="postal_code" required>
                    <div id="postal_code-error" class="error"></div>
                </div>
                <div class="form-group">
                    <label for="business_registration">Business Registration Number</label>
                    <input type="text" id="business_registration" name="business_registration">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <div id="password-error" class="error"></div>
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Application</button>
        </form>
        <button onclick="openLoginModal()" class="login-button">Log In</button>
    </div>

    <script>
        // Live validation function
        function validateField(field, regex, errorMessage) {
            const value = field.value.trim();
            const errorElement = document.getElementById(field.id + '-error');
            
            if (!value) {
                errorElement.textContent = "This field is required.";
                return false;
            } else if (regex && !regex.test(value)) {
                errorElement.textContent = errorMessage;
                return false;
            } else {
                errorElement.textContent = ""; // Clear error message
                return true;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ownerNameField = document.getElementById('owner_name');
            const businessNameField = document.getElementById('business_name');
            const emailField = document.getElementById('email');
            const phoneField = document.getElementById('phone');
            const addressField = document.getElementById('address');
            const cityField = document.getElementById('city');
            const stateField = document.getElementById('state');
            const postalCodeField = document.getElementById('postal_code');
            const passwordField = document.getElementById('password');

            ownerNameField.addEventListener('input', function() {
                validateField(ownerNameField, /^[a-zA-Z\s'-]+$/, "Full name can only contain letters, spaces, hyphens and apostrophes");
            });

            businessNameField.addEventListener('input', function() {
                validateField(businessNameField, null, null);
            });

            emailField.addEventListener('input', function() {
                validateField(emailField, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, "Please enter a valid email address");
            });

            phoneField.addEventListener('input', function() {
                validateField(phoneField, /^\d{10}$/, "Please enter a valid 10-digit phone number");
            });

            addressField.addEventListener('input', function() {
                validateField(addressField, null, null);
            });

            cityField.addEventListener('input', function() {
                validateField(cityField, /^[a-zA-Z\s'-]+$/, "City can only contain letters, spaces, hyphens and apostrophes");
            });

            stateField.addEventListener('input', function() {
                validateField(stateField, /^[a-zA-Z\s'-]+$/, "State can only contain letters, spaces, hyphens and apostrophes");
            });

            postalCodeField.addEventListener('input', function() {
                validateField(postalCodeField, /^\d{5}$/, "Please enter a valid 5-digit postal code");
            });

            passwordField.addEventListener('input', function() {
                validateField(passwordField, /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/, "Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters");
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                // Prevent form submission if there are validation errors
                const isValid = [
                    validateField(ownerNameField, /^[a-zA-Z\s'-]+$/, "Full name can only contain letters, spaces, hyphens and apostrophes"),
                    validateField(businessNameField, null, null),
                    validateField(emailField, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, "Please enter a valid email address"),
                    validateField(phoneField, /^\d{10}$/, "Please enter a valid 10-digit phone number"),
                    validateField(addressField, null, null),
                    validateField(cityField, /^[a-zA-Z\s'-]+$/, "City can only contain letters, spaces, hyphens and apostrophes"),
                    validateField(stateField, /^[a-zA-Z\s'-]+$/, "State can only contain letters, spaces, hyphens and apostrophes"),
                    validateField(postalCodeField, /^\d{5}$/, "Please enter a valid 5-digit postal code"),
                    validateField(passwordField, /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/, "Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters")
                ].every(Boolean);

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fix the errors in the form before submitting.');
                }
            });
        });

        function openLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
        }

        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }
    </script>
</body>
</html>