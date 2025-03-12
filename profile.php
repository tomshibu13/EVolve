<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) { // Assuming 'user_id' is set upon login
    header("Location: index.php#LoginForm"); // Redirect to the login page
    exit();
}

// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Fetch user data
$sql = "SELECT * FROM tbl_users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $profilePicture = $_FILES['profile_picture'];
    if ($profilePicture['error'] == 0) {
        $targetDirectory = "uploads/";
        $fileName = basename($profilePicture['name']);
        $targetFile = $targetDirectory . $fileName;

        if (move_uploaded_file($profilePicture['tmp_name'], $targetFile)) {
            $updateSql = "UPDATE tbl_users SET profile_picture = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $targetFile, $_SESSION['user_id']);
            $stmt->execute();
            $success_message = "Profile picture updated successfully.";
        } else {
            $error_message = "Error uploading file.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    $sql = "SELECT passwordhash FROM tbl_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (password_verify($currentPassword, $user['passwordhash'])) {
        if ($newPassword === $confirmPassword) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE tbl_users SET passwordhash = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $newPasswordHash, $_SESSION['user_id']);
            $stmt->execute();
            $success_message = "Password updated successfully.";
        } else {
            $error_message = "New password and confirmation do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

$conn->close();
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --border-radius: 8px;
        }

        body {
            background-color: #f0f2f5;
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profile-container {
            max-width: 800px;
            margin-top: 50px;
            padding: 30px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            border-radius: var(--border-radius);
            background-color: #fff;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
        }

        @media (max-width: 850px) {
            .profile-container {
                margin: 100px 20px;
                width: calc(100% - 40px);
            }
        }

        /* Message Styles */
        .message {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .message.error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        /* Profile Header Styles */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--primary-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .profile-photo:hover {
            transform: scale(1.05);
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info h1 {
            margin: 0;
            color: var(--text-color);
            font-size: 2em;
        }

        /* Section Styles */
        .profile-section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.5em;
            color: var(--text-color);
            margin: 0;
        }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
            transition: transform 0.3s ease;
        }

        .detail-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .detail-item i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .detail-content {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.9em;
            color: #666;
        }

        .detail-value {
            font-weight: 500;
            color: var(--text-color);
        }

        /* Button Styles */
        .action-button {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .primary-button {
            background-color: var(--primary-color);
            color: white;
        }

        .primary-button:hover {
            background-color: #357abd;
            transform: translateY(-2px);
        }

        .secondary-button {
            background-color: var(--secondary-color);
            color: var(--text-color);
        }

        .secondary-button:hover {
            background-color: #e1e5f0;
            transform: translateY(-2px);
        }

        /* Password Form Styles */
        .password-form {
            background-color: var(--secondary-color);
            padding: 20px;
            border-radius: var(--border-radius);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1em;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .password-requirements {
            font-size: 0.9em;
            color: #666;
            margin-top: 8px;
        }

        .buttons-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>




<div class="profile-container">
    <!-- Messages Section -->
    <?php if (isset($success_message)): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Profile Header Section -->
    <div class="profile-section">
        <div class="profile-header">
            <div class="profile-photo">
                <img src="<?php echo htmlspecialchars($userData['profile_picture'] ?? 'placeholder.jpg'); ?>" 
                     alt="Profile Photo" id="profileImage">
                <form id="profilePictureForm" method="POST" enctype="multipart/form-data" style="display: none;">
                    <input type="file" name="profile_picture" id="profilePictureInput">
                </form>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($userData['name']); ?></h1>
                <p class="profile-username">@<?php echo htmlspecialchars($userData['username']); ?></p>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="profile-section">
        <div class="section-header">
            <h2 class="section-title">Profile Details</h2>
            <form method="POST" action="editprofile.php">
                <button type="submit" class="action-button primary-button">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </form>
        </div>
        <div class="details-grid">
            <div class="detail-item">
                <i class="fas fa-envelope"></i>
                <div class="detail-content">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?php echo htmlspecialchars($userData['email']); ?></span>
                </div>
            </div>
            <div class="detail-item">
                <i class="fas fa-phone"></i>
                <div class="detail-content">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value"><?php echo htmlspecialchars($userData['phone_number'] ?? 'Not provided'); ?></span>
                </div>
            </div>
            <div class="detail-item">
                <i class="fas fa-circle-check"></i>
                <div class="detail-content">
                    <span class="detail-label">Status</span>
                    <span class="detail-value"><?php echo htmlspecialchars(ucfirst($userData['status'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Section Button -->
    <div class="buttons-container">
        <button id="toggle-password" class="action-button secondary-button">
            <i class="fas fa-key"></i> Change Password
        </button>
    </div>

    <!-- Password Change Section (Hidden by default) -->
    <div id="password-section" class="profile-section" style="display: none;">
        <div class="section-header">
            <h2 class="section-title">Change Password</h2>
        </div>
        <form method="POST" action="" class="password-form">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <div class="password-requirements">
                    <i class="fas fa-info-circle"></i> Password must be at least 8 characters long and contain letters, numbers, and special characters
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="buttons-container">
                <button type="submit" name="change_password" class="action-button primary-button">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </div>
        </form>
    </div>

    
</div>
<script src="profile.js"></script>
<script>
    function toggleDropdown() {
        var dropdown = document.getElementById("dropdownContent");
        if (dropdown.style.display === "none" || dropdown.style.display === "") {
            dropdown.style.display = "block";
        } else {
            dropdown.style.display = "none";
        }
    }
</script>
</body>
</html>
