<<<<<<< HEAD
<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "evolve1";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session to fetch logged-in user data
session_start();
$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT * FROM tbl_users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
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
            $stmt->bind_param("si", $targetFile, $user_id);
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
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (password_verify($currentPassword, $user['passwordhash'])) {
        if ($newPassword === $confirmPassword) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE tbl_users SET passwordhash = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("si", $newPasswordHash, $user_id);
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


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
</head>
<body>
<header class="header">
    <nav class="nav-container">
        <div class="logo">
            <i class="fas fa-charging-station"></i>
            <span class="logo-text">E<span class="highlight">V</span>olve</span>
        </div>
        <div class="nav-links">
            <a href="#searchInput" class="nav-link active">
                <i class="fas fa-search"></i> Find Stations
            </a>
            <a href="#" class="nav-link" onclick="toggleBookingPanel(); return false;">
                <i class="fas fa-calendar-check"></i> My Bookings
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-cog"></i> Services
            </a>
            <a href="#about" class="nav-link">
                <i class="fas fa-info-circle"></i> About Us
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-profile">
                <div class="profile-photo">
                    <?php if (!empty($_SESSION['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                </div>
                <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <i class="fas fa-chevron-down"></i>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <?php else: ?>
            <a href="#" class="nav-link" onclick="showLoginModal(); return false;">
                <i class="fas fa-user"></i> Login/Signup
            </a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function toggleBookingPanel() {
        const panel = document.getElementById('bookingPanel');
        if (panel) {
            panel.classList.toggle('active');
        }
    }

    function showBookingModal() {
        const modal = document.getElementById('mybBookingModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    function hideBookingModal() {
        const modal = document.getElementById('mybBookingModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    const cancelButton = document.querySelector('.myb-cancel-btn');
    if (cancelButton) {
        cancelButton.addEventListener('click', hideBookingModal);
    }

    const bookingForm = document.getElementById('mybBookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            hideBookingModal();
        });
    }
});
</script>
<br><br>
<br>

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
                    <img src="<?php echo htmlspecialchars($userData['profile_picture'] ?? '/api/placeholder/150/150'); ?>" 
                         alt="Profile Photo" id="profileImage">
                    <form id="profilePictureForm" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="file" name="profile_picture" id="profilePictureInput">
                    </form>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($userData['name'] ?? 'User Name'); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($userData['username'] ?? 'username'); ?></p>
                </div>
            </div>
        </div>

        <!-- Details Section -->
        <div class="profile-section">
            <div class="section-header">
                <h2 class="section-title">Profile Details</h2>
                <a href="editprofile.php">
                     <button class="action-button primary-button" onclick="openModal()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
                </a>
               
            </div>
            <div class="details-grid">
                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <div class="detail-content">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userData['email'] ?? 'email@example.com'); ?></span>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fas fa-phone"></i>
                    <div class="detail-content">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userData['phone_number'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
                <!-- <div class="detail-item">
                    <i class="fas fa-circle-check"></i>
                    <div class="detail-content">
                        <span class="detail-label">Status</span>
                        <span class="detail-value"><?php echo $userData['status_at'] ? 'Active' : 'Inactive'; ?></span>
                    </div>
                </div> -->
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
</body>
</html>
=======
<?php
session_start();  // Start the session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirect to login if user is not logged in
    exit();
}

// Retrieve user info from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$profile_picture = $_SESSION['profile_photo'] ?? ''; // Profile picture (default to empty if not set)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="profile.css"> <!-- Your CSS file -->
</head>
<body>

    <div class="profile-container">
        <h1>Welcome to Your Profile</h1>

        <div class="profile-info">
            <div class="profile-photo">
                <?php if ($profile_picture): ?>
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i> <!-- Placeholder icon if no profile picture -->
                <?php endif; ?>
            </div>

            <div class="profile-details">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <!-- Add any additional information you want to display -->
            </div>
        </div>

        <a href="edit_profile.php" class="edit-profile-btn">Edit Profile</a> <!-- Link to edit profile -->
        
        <a href="logout.php" class="logout-btn">Logout</a> <!-- Logout button -->
    </div>

</body>
</html>
>>>>>>> 0c499f78677a34c0d64e35d5565441573d6c2b38
