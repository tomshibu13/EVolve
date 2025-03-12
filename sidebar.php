<div class="sidebar" style="
    width: 250px;
    height: 100%;
    position: fixed;
    left: 0;
    top: 0;
    padding-top: 70px;
    background-color: #343a40;
    color: white;
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
                <a href="profile.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class='bx bx-user me-2'></i>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link text-white">
                    <i class='bx bx-log-out me-2'></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div> 