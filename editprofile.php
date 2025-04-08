<?php
session_start();

// Debug session
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in user data
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    die("User not logged in.");
}

$sql = "SELECT * FROM tbl_users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];

    // Check if any changes were made
    $changes_made = false;
    if ($name !== $userData['name'] || 
        $username !== $userData['username'] || 
        $email !== $userData['email'] || 
        $phone_number !== $userData['phone_number'] || 
        !empty($_FILES['profile_picture']['name'])) {
        $changes_made = true;
    }

    if (!$changes_made) {
        $error_message = "No changes were made to update.";
    } else {
        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $targetDir = "uploads/";
            $fileName = basename($_FILES['profile_picture']['name']);
            $targetFile = $targetDir . $fileName;

            // Move the uploaded file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                // Update with new profile picture
                $updateSql = "UPDATE tbl_users SET name=?, username=?, email=?, phone_number=?, profile_picture=? WHERE user_id=?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("sssssi", $name, $username, $email, $phone_number, $targetFile, $user_id);
            } else {
                $error_message = "Error uploading profile picture.";
            }
        } else {
            // Update without profile picture
            $updateSql = "UPDATE tbl_users SET name=?, username=?, email=?, phone_number=? WHERE user_id=?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("ssssi", $name, $username, $email, $phone_number, $user_id);
        }

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully.";
            header("Refresh:0"); // Refresh the page to show updated data
        } else {
            $error_message = "Error updating profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <!-- <link rel="stylesheet" href="profile.css"> -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Add Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="booking-styles.css">
    <style>
        /* Reset and Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f8fa;
            color: #333;
        }

        /* Profile Container Styles */
        .profile-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 2rem;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .profile-section {
            padding: 2rem;
        }

        .profile-section h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 2rem;
            text-align: center;
        }

        /* Profile Photo Styles */
        .profile-photo {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            cursor: pointer;
        }

        .profile-photo label {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #4CAF50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-photo:hover label {
            opacity: 1;
        }

        .profile-photo img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #4CAF50;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-photo img:hover {
            transform: scale(1.05);
        }

        #profilePictureInput {
            display: none;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #4CAF50;
            outline: none;
        }

        /* Button Styles */
        .buttons-container {
            text-align: center;
            margin-top: 2rem;
        }

        .primary-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.2);
        }

        .primary-button:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }

        /* Message Styles */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .error-text {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .form-group input.valid {
            border-color: #28a745;
            background-color: #fff;
        }

        .form-group input.invalid {
            border-color: #dc3545;
            background-color: #fff;
        }

        .form-group small.show {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="profile-container">
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="message error" id="validationMessage" style="display: none;"></div>

        <div class="profile-section">
            <h2>Edit Profile</h2>
            <form method="POST" enctype="multipart/form-data" id="profileForm" novalidate>
                <div class="profile-photo">
                    <img src="<?php echo htmlspecialchars($userData['profile_picture'] ?? 'uploads/default.jpg'); ?>" 
                         alt="Profile Photo" id="profileImage">
                    <label for="profilePictureInput">Change Photo</label>
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                    <small class="error-text"></small>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                    <small class="error-text"></small>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                    <small class="error-text"></small>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($userData['phone_number']); ?>">
                    <small class="error-text"></small>
                </div>

                <div class="buttons-container">
                    <button type="submit" name="update_profile" class="primary-button">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            const nameInput = form.querySelector('input[name="name"]');
            const usernameInput = form.querySelector('input[name="username"]');
            const emailInput = form.querySelector('input[name="email"]');
            const phoneInput = form.querySelector('input[name="phone_number"]');
            const submitButton = form.querySelector('button[type="submit"]');
            const profileInput = document.getElementById('profilePictureInput');

            // Validation functions
            function validateName(name) {
                return name.length >= 2 && name.length <= 50 && /^[a-zA-Z\s'-]+$/.test(name);
            }

            function validateUsername(username) {
                return username.length >= 3 && username.length <= 30 && /^[a-zA-Z0-9_]+$/.test(username);
            }

            function validateEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            function validatePhone(phone) {
                if (!phone) return true; // Phone is optional
                // Indian phone number format: 
                // - Can start with +91 or 0 or nothing
                // - Must be followed by a number between 6-9
                // - Then 9 more digits
                return /^(?:(?:\+91)|0)?[6-9]\d{9}$/.test(phone.replace(/[-\s]/g, '')); // Indian phone format
            }

            function validateProfilePicture(file) {
                if (!file) return true; // Optional field
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                return validTypes.includes(file.type) && file.size <= maxSize;
            }

            // Show error message
            function showError(input, message) {
                const errorElement = input.nextElementSibling;
                errorElement.textContent = message;
                errorElement.classList.add('show');
                input.classList.add('invalid');
                input.classList.remove('valid');
            }

            // Show success
            function showSuccess(input) {
                const errorElement = input.nextElementSibling;
                errorElement.classList.remove('show');
                input.classList.remove('invalid');
                input.classList.add('valid');
            }

            // Live validation handlers
            nameInput.addEventListener('input', function() {
                if (!validateName(this.value)) {
                    showError(this, 'Name must be 2-50 characters long and contain only letters, spaces, hyphens, and apostrophes');
                } else {
                    showSuccess(this);
                }
                validateForm();
            });

            usernameInput.addEventListener('input', function() {
                if (!validateUsername(this.value)) {
                    showError(this, 'Username must be 3-30 characters long and contain only letters, numbers, and underscores');
                } else {
                    showSuccess(this);
                }
                validateForm();
            });

            emailInput.addEventListener('input', function() {
                if (!validateEmail(this.value)) {
                    showError(this, 'Please enter a valid email address');
                } else {
                    showSuccess(this);
                }
                validateForm();
            });

            phoneInput.addEventListener('input', function() {
                if (!validatePhone(this.value)) {
                    showError(this, 'Please enter a valid phone number ');
                } else {
                    showSuccess(this);
                }
                validateForm();
            });

            profileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    if (!validateProfilePicture(this.files[0])) {
                        showError(this, 'Please select a valid image file (JPEG, PNG, or GIF) under 5MB');
                    } else {
                        showSuccess(this);
                    }
                }
                validateForm();
            });

            // Preview profile picture
            profileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('profileImage').src = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            // Form validation
            function validateForm() {
                const isNameValid = validateName(nameInput.value);
                const isUsernameValid = validateUsername(usernameInput.value);
                const isEmailValid = validateEmail(emailInput.value);
                const isPhoneValid = validatePhone(phoneInput.value);
                const isProfileValid = !profileInput.files.length || validateProfilePicture(profileInput.files[0]);

                submitButton.disabled = !(isNameValid && isUsernameValid && isEmailValid && isPhoneValid && isProfileValid);
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                if (!validateName(nameInput.value) || 
                    !validateUsername(usernameInput.value) || 
                    !validateEmail(emailInput.value) || 
                    !validatePhone(phoneInput.value) ||
                    (profileInput.files.length && !validateProfilePicture(profileInput.files[0]))) {
                    e.preventDefault();
                    alert('Please correct the errors in the form before submitting.');
                }
            });

            // Initial validation
            validateForm();
        });
    </script>
</body>
</html>
