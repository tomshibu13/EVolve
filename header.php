<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .nav-link {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            text-decoration: none;
            transform: translateY(-2px);
            color: #0066FF;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #0066FF;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .logo {
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .logo i {
            transition: transform 0.3s ease;
        }
        
        .logo:hover i {
            transform: rotate(360deg);
        }
        
        .login-btn {
            background: #0066FF;
            border: none;
            border-radius: 25px;
            padding: 10px 24px;
            cursor: pointer;
            font-size: 1em;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .login-btn:hover {
            background: #0052cc;
            color: white;
            text-decoration: none;
        }
        
        /* Dropdown styles */
        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .user-profile:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .username {
            font-weight: 500;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 8px 0;
            z-index: 1000;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.3s ease;
        }
        
        .dropdown-content a:hover {
            background-color: #f5f5f5;
            color: #0066FF;
        }
        
        .dropdown-content i {
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #eee;
            margin: 8px 0;
        }
        
        .logout-link {
            color: #ff4444 !important;
            
        }
        
        .logout-link:hover {
            background-color: #fff1f1 !important;
            color: #ff4444 !important;
        }
        
        /* Show dropdown when parent is hovered */
        .user-profile:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body>
<?php
// Include the database connection file
include 'config.php'; // Ensure this path is correct based on your project structure
?>
<header class="header">
    <nav class="nav-container">
        <!-- Logo -->
         <a href="index.php" style="text-decoration: none;">
        <div class="logo">
            <i class="fas fa-charging-station"></i>
            <span class="logo-text">E<span class="highlight">V</span>olve</span>
        </div>
        </a>
        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="#searchInput" class="nav-link active">
                <i class="fas fa-search"></i>
                Find Stations
            </a>
            <a href="#" class="nav-link" onclick="toggleBookingPanel()">
                <i class="fas fa-calendar-check"></i>
                My Bookings
            </a>
            <a href="user_stations.php" class="nav-link">
                <i class="fas fa-charging-station"></i>
                Station
            </a>
            <a href="#about" class="nav-link">
                <i class="fas fa-info-circle"></i>
                About Us
            </a>
            
            <!-- User Profile Section
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-profile">
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
                <button class="nav-link login-btn" id="loginSignupBtn" onclick="showLoginModal()">
                    <i class="fas fa-user"></i>
                    Login/Signup
                </button>
            <?php endif; ?> -->
        </div>
    </nav>
</header>
<script>
// Function to toggle booking panel
function toggleBookingPanel() {
    // Implementation not shown in provided code
    return false;
}

// Function to show login modal
function showLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.style.display = 'flex';
        showLoginTab(new Event('click')); // Reset to login tab by default
    }
}

// Function to show login tab
function showLoginTab(event) {
    event.preventDefault();
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    const loginTab = document.querySelector('.tab:first-child');
    const loginForm = document.getElementById('loginForm');
    
    if (loginTab) loginTab.classList.add('active');
    if (loginForm) loginForm.classList.add('active');
}

// Function to show signup tab
function showSignupTab(event) {
    event.preventDefault();
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    const signupTab = document.querySelector('.tab:last-child');
    const signupForm = document.getElementById('signupForm');
    
    if (signupTab) signupTab.classList.add('active');
    if (signupForm) signupForm.classList.add('active');
}

// Function to close login modal
function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.style.display = 'none';
        // Clear forms and error messages
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        if (loginForm) loginForm.reset();
        if (signupForm) signupForm.reset();
        document.querySelectorAll('.validation-message').forEach(msg => msg.textContent = '');
    }
}

// Function to scroll to support section
function scrollToSupport(event) {
    event.preventDefault();
    const supportSection = document.querySelector('#support');
    supportSection.scrollIntoView({ behavior: 'smooth' });
}

// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggle on click (optional, in addition to hover)
    const userProfile = document.querySelector('.user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', function(e) {
            const dropdown = this.querySelector('.dropdown-content');
            if (dropdown) {
                // Toggle dropdown visibility
                const isVisible = dropdown.style.display === 'block';
                dropdown.style.display = isVisible ? 'none' : 'block';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userProfile.contains(e.target)) {
                const dropdown = userProfile.querySelector('.dropdown-content');
                if (dropdown) {
                    dropdown.style.display = 'none';
                }
            }
        });
    }
});
</script>
</body>
</html>
