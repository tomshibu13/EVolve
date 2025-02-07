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
});


