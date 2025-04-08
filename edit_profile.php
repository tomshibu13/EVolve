<?php


session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) { // Assuming 'user_id' is set upon login
    header("Location: index.php#LoginForm"); // Redirect to the login page
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

$conn = new mysqli($servername, $username, $password, $dbname);

// Fetch current profile info (for display purposes)
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$profile_picture = $_SESSION['profile_photo'] ?? ''; // Default to empty if not set

// Handle profile updates (assuming form submission)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate and sanitize inputs
    $new_username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $new_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Basic validation
    if (empty($new_username) || empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide valid username and email.";
        header("Location: edit_profile.php");
        exit();
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Invalid file type. Please upload an image file.";
            header("Location: edit_profile.php");
            exit();
        }

        $targetDir = "uploads/profile_pictures/";
        $fileName = uniqid() . "." . $ext; // Generate unique filename
        $targetFilePath = $targetDir . $fileName;

        // Ensure the directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Move the uploaded file
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
            $profile_picture = $targetFilePath;
            $_SESSION['profile_photo'] = $profile_picture; // Update session variable
        }
    }

    // Prepare the SQL query to update the user information in the database
    $updateQuery = "UPDATE tbl_users SET username = ?, email = ?, profile_photo = ? WHERE user_id = ?";
    if ($stmt = $conn->prepare($updateQuery)) {
        // Bind parameters and execute
        $stmt->bind_param("sssi", $new_username, $new_email, $profile_picture, $user_id);

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['username'] = $new_username;
            $_SESSION['email'] = $new_email;

            // Redirect after update
            header("Location: profile.php");
            exit();
        } else {
            echo "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    

    <style>
        /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f7fc;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.profile-container {
    background-color: white;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
    width: 100%;
    max-width: 500px;
    border-radius: 8px;
}

h1 {
    font-size: 2rem;
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

.profile-info {
    text-align: center;
    margin-bottom: 20px;
}

.profile-photo {
    margin-bottom: 15px;
}

.profile-photo i, .profile-photo img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: #e0e0e0;
    display: inline-block;
    object-fit: cover;
}

/* Form Styles */
.edit-form .input-group {
    margin-bottom: 20px;
}

.edit-form .input-group label {
    font-size: 1rem;
    color: #555;
    margin-bottom: 5px;
    display: block;
}

.edit-form .input-group input[type="text"],
.edit-form .input-group input[type="email"],
.edit-form .input-group input[type="file"] {
    width: 100%;
    padding: 10px;
    font-size: 1rem;
    border-radius: 4px;
    border: 1px solid #ddd;
    box-sizing: border-box;
}

.edit-form .input-group input[type="file"] {
    padding: 8px;
}

/* Button Styles */
.update-btn {
    width: 100%;
    padding: 12px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
}

.update-btn:hover {
    background-color: #45a049;
}

/* Hover and Focus Effects */
.input-group input[type="text"]:focus,
.input-group input[type="email"]:focus,
.input-group input[type="file"]:focus {
    border-color: #4CAF50;
    outline: none;
}

/* Responsiveness */
@media (max-width: 600px) {
    .profile-container {
        padding: 20px;
    }

    h1 {
        font-size: 1.5rem;
    }
}

/* Add these new styles */
.input-group .error-message {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 5px;
    display: none;
}

.input-group input.invalid {
    border-color: #dc3545;
}

.input-group input.valid {
    border-color: #28a745;
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

    <div class="profile-container">
        <h1>Edit Your Profile</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="profile-info">
            <!-- Profile Picture -->
            <div class="profile-photo">
                <?php if ($profile_picture): ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i> <!-- Placeholder icon if no profile picture -->
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="edit-form" id="profileForm">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                <div class="error-message" id="username-error"></div>
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <div class="error-message" id="email-error"></div>
            </div>

            <div class="input-group">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <div class="error-message" id="profile-picture-error"></div>
            </div>

            <button type="submit" class="update-btn">Update Profile</button>
        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            const nameInput = form.querySelector('input[name="name"]');
            const usernameInput = form.querySelector('input[name="username"]');
            const emailInput = form.querySelector('input[name="email"]');
            const phoneInput = form.querySelector('input[name="phone_number"]');
            const submitButton = form.querySelector('button[type="submit"]');
            const profileInput = document.getElementById('profile_picture');

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
                return /^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/.test(phone); // Malaysian phone format
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
                    showError(this, 'Please enter a valid Malaysian phone number (e.g., 601X-XXXXXXX)');
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
