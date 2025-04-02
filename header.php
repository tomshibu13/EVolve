<?php
require_once 'config.php';
?>
<header class="header">
    <nav class="nav-container">
        <a href="index.php" class="logo">
            <i class="fas fa-charging-station"></i>
            <span class="logo-text">E<span class="highlight">V</span>olve</span>
        </a>
        
        <button class="mobile-menu-toggle d-md-none" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links" id="navLinks">
            <button class="mobile-menu-close d-md-none" id="mobileMenuClose">
                <i class="fas fa-times"></i>
            </button>

            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="user_stations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user_stations.php' ? 'active' : ''; ?>">
                <i class="fas fa-charging-station"></i>
                Stations
            </a>
            <a href="my-bookings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-bookings.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                My Bookings
            </a>
            <a href="about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i>
                About
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-profile" id="userProfile">
                    <span class="username">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                    </span>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="profile.php">
                            <i class="fas fa-user-circle"></i>
                            Profile
                        </a>
                        <a href="my-bookings.php">
                            <i class="fas fa-calendar-check"></i>
                            My Bookings
                        </a>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        <a href="logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="#" onclick="showLoginModal()" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        document.getElementById('navLinks').classList.add('active');
    });

    document.getElementById('mobileMenuClose').addEventListener('click', function() {
        document.getElementById('navLinks').classList.remove('active');
    });

    // User profile dropdown
    const userProfile = document.getElementById('userProfile');
    const userDropdown = document.getElementById('userDropdown');

    if (userProfile) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!userProfile.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
</script>

<style>
    /* Mobile menu styles */
    .mobile-menu-toggle,
    .mobile-menu-close {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
    }

    /* Add these dropdown styles */
    .user-profile {
        position: relative;
        cursor: pointer;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        border-radius: 4px;
        min-width: 200px;
        z-index: 1000;
    }

    .dropdown-menu.active {
        display: block;
    }

    .dropdown-menu a {
        display: block;
        padding: 10px 15px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    .dropdown-menu a:hover {
        background-color: #f5f5f5;
    }

    .dropdown-menu i {
        margin-right: 8px;
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        .mobile-menu-toggle,
        .mobile-menu-close {
            display: block;
        }
        
        .nav-links {
            display: none;
        }
        
        .nav-links.active {
            display: block;
        }
    }

    /* Desktop styles */
    @media (min-width: 769px) {
        .mobile-menu-toggle,
        .mobile-menu-close {
            display: none !important;
        }
    }
</style>
