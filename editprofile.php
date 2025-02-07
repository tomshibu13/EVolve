<?php
session_start();

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
    <link rel="stylesheet" href="profile.css">
    <style>
        /* Header Styling */
.header {
    width: 100%;
    background-color: #222;
    padding: 15px 0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
}

/* Navigation Container */
.nav-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 90%;
    margin: 0 auto;
}

/* Logo */
.logo {
    display: flex;
    align-items: center;
    font-size: 22px;
    font-weight: bold;
    color: #fff;
}

.logo i {
    font-size: 24px;
    margin-right: 8px;
    color: #4caf50;
}

.logo-text {
    font-size: 22px;
    font-weight: bold;
}

.highlight {
    color: #4caf50;
}

/* Navigation Links */
.nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.nav-link {
    text-decoration: none;
    color: #ddd;
    font-size: 16px;
    padding: 10px 15px;
    transition: color 0.3s ease, background 0.3s ease;
    border-radius: 5px;
}

.nav-link:hover,
.nav-link.active {
    color: #4caf50;
    background: rgba(255, 255, 255, 0.1);
}

/* User Profile Section */
.user-profile {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
    color: #ddd;
}

.profile-photo {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #444;
}

.profile-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-photo i {
    font-size: 28px;
    color: #aaa;
}

.username {
    font-size: 16px;
    font-weight: bold;
    color: #ddd;
    margin-right: 8px;
}

.user-profile i.fa-chevron-down {
    font-size: 14px;
}

/* Dropdown Menu */
.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 45px;
    background-color: #333;
    min-width: 160px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    overflow: hidden;
}

.user-profile:hover .dropdown-content {
    display: block;
}

.dropdown-content a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #ddd;
    text-decoration: none;
    transition: background 0.3s ease;
}

.dropdown-content a:hover {
    background: #444;
}

.dropdown-content i {
    margin-right: 10px;
}

/* Dropdown Divider */
.dropdown-divider {
    height: 1px;
    background: #555;
    margin: 5px 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-container {
        flex-direction: column;
        align-items: center;
    }

    .nav-links {
        flex-direction: column;
        gap: 10px;
    }
}

    </style>
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
                    <i class="fas fa-search"></i>
                    Find Stations
                </a>
                <a href="#" class="nav-link" onclick="toggleBookingPanel(); return false;">
                    <i class="fas fa-calendar-check"></i>
                    My Bookings
                </a>
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Services
                </a>
                <a href="#about" class="nav-link">
                    <i class="fas fa-info-circle"></i>
                    About Us
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
    <div class="user-profile">
        <div class="profile-photo">
            <?php if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])): ?>
                <!-- Display the user's profile photo -->
                <img src="<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="Profile Photo">
            <?php else: ?>
                <!-- Display a default icon if no profile picture is set -->
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </div>
        <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <i class="fas fa-chevron-down"></i>
        <div class="dropdown-content">
            <a href="profile.php">
                <i class="fas fa-user"></i>
                My Profile
            </a>
            <a href="my-bookings.php">
                <i class="fas fa-calendar-check"></i>
                My Bookings
            </a>
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                Settings
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
<?php else: ?>
    <a href="#" class="nav-link" onclick="showLoginModal(); return false;">
        <i class="fas fa-user"></i>
        Login/Signup
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
                    <input type="file" name="profile_picture" id="profilePictureInput">
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

    </script>
</body>
</html>
