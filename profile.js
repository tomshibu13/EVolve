document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordButton = document.getElementById('toggle-password');
    const passwordSection = document.getElementById('password-section');

    togglePasswordButton.addEventListener('click', function() {
        passwordSection.style.display = passwordSection.style.display === 'none' ? 'block' : 'none';
    });

    const profilePictureInput = document.getElementById('profilePictureInput');
    const profileImage = document.getElementById('profileImage');

    profileImage.addEventListener('click', function() {
        profilePictureInput.click();
    });

    profilePictureInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // User profile dropdown toggle on click
    const userProfile = document.querySelector('.user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userProfile.contains(e.target) && userProfile.classList.contains('active')) {
                userProfile.classList.remove('active');
            }
        });
    }

    // Handle navigation active states
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Add tooltip functionality for icon-only navigation
    const links = document.querySelectorAll('.nav-link');
    const tooltips = {
        'fa-search': 'Find Stations',
        'fa-calendar-check': 'My Bookings',
        'fa-cog': 'Services',
        'fa-info-circle': 'About Us',
        'fa-user': 'Login/Signup'
    };

    links.forEach(link => {
        const icon = link.querySelector('i');
        if (icon) {
            for (const [className, tooltip] of Object.entries(tooltips)) {
                if (icon.classList.contains(className)) {
                    link.setAttribute('title', tooltip);
                    break;
                }
            }
        }
    });

    // Example function to toggle booking panel
    function toggleBookingPanel() {
        // Logic to toggle the booking panel
        console.log("Toggling booking panel");
        // Example: document.getElementById('bookingPanel').classList.toggle('active');
    }

    // Example function to show login modal
    function showLoginModal() {
        // Logic to show login modal
        console.log("Showing login modal");
        // Example: document.getElementById('loginModal').style.display = 'block';
    }
});


