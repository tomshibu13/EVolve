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
            padding-top: 60px; /* Added to account for fixed header */
        }

        /* Updated Header Styles */
        .header {
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 0 2rem;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 60px;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .logo .highlight {
            color: #4CAF50;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-link {
            text-decoration: none;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #4CAF50;
        }

        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .user-profile .profile-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-profile .username {
            font-size: 0.9rem;
            color: #333;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }

        .user-profile:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #666;
            text-decoration: none;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .dropdown-content a:hover {
            background-color: #f5f5f5;
            color: #4CAF50;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #e1e1e1;
            margin: 0.5rem 0;
        }

        .logout-link {
            color: #dc3545 !important;
        }

        .logout-link:hover {
            background-color: #fff5f5;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .nav-links {
                gap: 1rem;
            }

            .nav-link span {
                display: none;
            }

            .header {
                padding: 0 1rem;
            }
        }

        /* User Profile Styles - Modified */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-profile .profile-photo img {
            width: 32px; /* Smaller profile photo */
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-profile .username {
            font-size: 0.9rem;
        }

        /* Dropdown modifications */
        .dropdown-content {
            top: 45px; /* Adjusted dropdown position */
        }

        /* Profile Container Styles */
        .profile-container {
            max-width: 800px;
            margin: 100px auto 50px;
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
    </style>
</head>
<body>
<header class="header">
    <nav class="nav-container">
        <div class="logo">
            <span class="logo-text">E<span class="highlight">V</span>olve</span>
        </div>
        <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="stations.php" class="nav-link">
                <i class="fas fa-charging-station"></i>
                <span>Stations</span>
            </a>
            <a href="bookings.php" class="nav-link">
                <i class="fas fa-calendar-check"></i>
                <span>Bookings</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($userData['profile_picture'] ?? 'uploads/default.jpg'); ?>" 
                         alt="Profile" class="profile-img">
                    <span class="username"><?php echo htmlspecialchars($userData['username']); ?></span>
                    <div class="dropdown-content">
                        <!-- <a href="editprofile.php">
                            <i class="fas fa-user"></i>
                            Edit Profile
                        </a> -->
                        <a href="bookings.php">
                            <i class="fas fa-calendar-check"></i>
                            My Bookings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>
</header>
    <div class="profile-container">
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-section">
            <h2>Edit Profile</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="profile-photo">
                    <img src="<?php echo htmlspecialchars($userData['profile_picture'] ?? 'uploads/default.jpg'); ?>" 
                         alt="Profile Photo" id="profileImage">
                    <label for="profilePictureInput">Change Photo</label>
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($userData['phone_number']); ?>">
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

        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.querySelector('.user-profile');
            const dropdownContent = document.querySelector('.dropdown-content');

            // Toggle dropdown on click
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target)) {
                    dropdownContent.style.display = 'none';
                }
            });

            // Prevent dropdown from closing when clicking inside it
            dropdownContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>
