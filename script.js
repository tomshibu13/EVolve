// Add these functions to your existing script.js

// 3D Tilt Effect for Stat Items
const statItems = document.querySelectorAll('.stat-item');

statItems.forEach(item => {
    item.addEventListener('mousemove', (e) => {
        const rect = item.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = (y - centerY) / 10;
        const rotateY = (centerX - x) / 10;
        
        item.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
    });
    
    item.addEventListener('mouseleave', () => {
        item.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
    });
});

// Smooth Parallax Effect
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const background = document.querySelector('.background-media');
    background.style.transform = `translateY(${scrolled * 0.5}px)`;
});

// Dynamic Background Particles
const createParticles = () => {
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'particles-container';
    document.querySelector('.top-image-section').appendChild(particlesContainer);

    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = `${Math.random() * 100}vw`;
        particle.style.animationDelay = `${Math.random() * 5}s`;
        particle.style.animationDuration = `${5 + Math.random() * 10}s`;
        particlesContainer.appendChild(particle);
    }
};

createParticles();

// Enhanced Scroll Indicator
const scrollIndicator = document.createElement('div');
scrollIndicator.className = 'scroll-indicator';
scrollIndicator.innerHTML = `
    <div class="scroll-line"></div>
    <span style="color: white; font-size: 0.8rem;">Scroll Down</span>
`;
document.querySelector('.top-image-section').appendChild(scrollIndicator);    