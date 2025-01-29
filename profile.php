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
