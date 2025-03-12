<div class="sidebar" style="
    width: 250px;
    height: 100%;
    position: fixed;
    left: 0;
    top: 0;
    padding-top: 70px;
    background-color: #343a40;
    color: white;
    z-index: 1000;
">
    <div class="d-flex flex-column p-3">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item mb-2">
                <a href="station-owner-dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'station-owner-dashboard.php' ? 'active' : ''; ?>">
                    <i class='bx bxs-dashboard me-2'></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a href="add_station.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'add_station.php' ? 'active' : ''; ?>">
                    <i class='bx bx-plus-circle me-2'></i>
                    Add New Station
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a href="so_profile.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'so_profile.php' ? 'active' : ''; ?>">
                    <i class='bx bx-user me-2'></i>
                    Profile
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a href="manage_stations.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_stations.php' ? 'active' : ''; ?>">
                    <i class='bx bx-charging-station me-2'></i>
                    Manage Stations
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a href="view_bookings.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'view_bookings.php' ? 'active' : ''; ?>">
                    <i class='bx bx-calendar me-2'></i>
                    View Bookings
                </a>
            </li>
            
            <li class="nav-item">
                <a href="../logout.php" class="nav-link text-white">
                    <i class='bx bx-log-out me-2'></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
    .nav-link {
        border-radius: 5px;
        margin-bottom: 5px;
        transition: all 0.3s;
    }
    
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .nav-link.active {
        background-color: #0d6efd !important;
    }
    
    .nav-link i {
        font-size: 1.1rem;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding-top: 0;
        }
        
        .main-content {
            margin-left: 0 !important;
        }
    }
</style> 