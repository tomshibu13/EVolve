<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
       /* Header and Navigation */
.header {
    background-color: rgba(255, 255, 255, 0.98);
    padding: 1.2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(8px);
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
}

/* Logo Styles */
.logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.8rem;
    font-weight: 800;
    color: #0066FF;
    text-decoration: none;
}

/* Navigation Links */
.nav-links {
    display: flex;
    gap: 2.5rem;
    align-items: center;
}

.nav-links a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    font-size: 1.1rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all var(--transition-speed) ease;
    position: relative;
}

/* Hover Effects */
.nav-links a:hover {
    color: var(--primary-color);
    transform: translateY(-2px);
}

/* Active Link Style */
.nav-links a.active {
    color: var(--primary-color);
    background-color: rgba(42, 157, 143, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-container {
        padding: 0 1rem;
    }
    
    .nav-links {
        gap: 1.5rem;
    }
    
    .nav-links a {
        font-size: 1rem;
        padding: 0.4rem 0.8rem;
    }
    
    .logo {
        font-size: 1.5rem;
    }
}

.nav-link:hover {
    text-decoration: underline;
}

.logo-text:hover {
    text-decoration: underline;
}
/* Header and Navigation */
.header {
    background-color: rgba(255, 255, 255, 0.98);
    padding: 1.2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(8px);
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
}

/* Logo Styles */
.logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.8rem;
    font-weight: 800;
    color: #0066FF;
    text-decoration: none;
}

/* Navigation Links */
.nav-links {
    display: flex;
    gap: 2.5rem;
    align-items: center;
}

.nav-links a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    font-size: 1.1rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all var(--transition-speed) ease;
    position: relative;
}

/* Hover Effects */
.nav-links a:hover {
    color: var(--primary-color);
    transform: translateY(-2px);
}

/* Active Link Style */
.nav-links a.active {
    color: var(--primary-color);
    background-color: rgba(42, 157, 143, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-container {
        padding: 0 1rem;
    }
    
    .nav-links {
        gap: 1.5rem;
    }
    
    .nav-links a {
        font-size: 1rem;
        padding: 0.4rem 0.8rem;
    }
    
    .logo {
        font-size: 1.5rem;
    }
}

.nav-link:hover {
    text-decoration: underline;
}

.logo-text:hover {
    text-decoration: underline;
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
