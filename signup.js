document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.querySelector('form[action="register_process.php"]');
    
    if (signupForm) {
        signupForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                
                // Send the registration request
                const response = await fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Registration successful! Please check your email for verification.');
                    window.location.href = 'verify_otp.php';
                } else {
                    alert(data.message || 'Registration failed. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during registration. Please try again.');
            }
        });
    }
}); 