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
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
        }

        .form-group input.invalid {
            border-color: #dc3545;
        }

        .form-group input.valid {
            border-color: #28a745;
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
        // Profile picture preview
        document.getElementById("profilePictureInput").addEventListener("change", function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById("profileImage").src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form validation
        const form = document.getElementById('profileForm');
        const inputs = form.querySelectorAll('input[required]');
        const phoneInput = form.querySelector('input[name="phone_number"]');
        const validationMessage = document.getElementById('validationMessage');

        const validationRules = {
            name: {
                pattern: /^[a-zA-Z\s]{2,50}$/,
                message: 'Name should be 2-50 characters long and contain only letters'
            },
            username: {
                pattern: /^[a-zA-Z0-9_]{3,20}$/,
                message: 'Username should be 3-20 characters long and contain only letters, numbers, and underscores'
            },
            email: {
                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: 'Please enter a valid email address'
            },
            phone_number: {
                pattern: /^\+?[\d\s-]{10,15}$/,
                message: 'Please enter a valid phone number (10-15 digits)'
            }
        };

        function validateInput(input) {
            const field = input.name;
            const value = input.value.trim();
            const errorElement = input.nextElementSibling;
            
            if (validationRules[field]) {
                const isValid = validationRules[field].pattern.test(value);
                input.classList.toggle('valid', isValid);
                input.classList.toggle('invalid', !isValid);
                
                if (!isValid) {
                    errorElement.textContent = validationRules[field].message;
                    return false;
                }
            }
            
            errorElement.textContent = '';
            return true;
        }

        // Live validation
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                validateInput(input);
            });
        });

        phoneInput.addEventListener('input', () => {
            validateInput(phoneInput);
        });

        // Form submission validation
        form.addEventListener('submit', (e) => {
            let isValid = true;
            let isChanged = false;
            
            // Check if any field has been modified
            inputs.forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
                // Compare with original value
                const originalValue = '<?php echo isset($userData) ? addslashes($userData[input.name]) : ""; ?>';
                if (input.value !== originalValue) {
                    isChanged = true;
                }
            });

            if (phoneInput.value.trim() !== '' && !validateInput(phoneInput)) {
                isValid = false;
            }

            // Check if phone number changed
            const originalPhone = '<?php echo isset($userData["phone_number"]) ? addslashes($userData["phone_number"]) : ""; ?>';
            if (phoneInput.value !== originalPhone) {
                isChanged = true;
            }

            // Check if profile picture is selected
            if (document.getElementById('profilePictureInput').files.length > 0) {
                isChanged = true;
            }

            if (!isValid) {
                e.preventDefault();
                validationMessage.style.display = 'block';
                validationMessage.textContent = 'Please correct the errors before submitting.';
            } else if (!isChanged) {
                e.preventDefault();
                validationMessage.style.display = 'block';
                validationMessage.textContent = 'No changes were made to update.';
            }
        });
    </script>
</body>
</html>
