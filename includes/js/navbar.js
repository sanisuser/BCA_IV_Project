/**
 * navbar.js
 * 
 * Mobile menu toggle functionality for BookHub navigation.
 */

function toggleMenu() {
    const menu = document.getElementById('mobile-menu');
    const navbar = document.querySelector('.navbar');
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
        if (navbar) navbar.classList.remove('mobile-menu-open');
    } else {
        menu.style.display = 'block';
        if (navbar) navbar.classList.add('mobile-menu-open');
    }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('mobile-menu');
    const toggle = document.querySelector('.mobile-toggle');
    const navbar = document.querySelector('.navbar');
    
    if (menu && toggle && !menu.contains(event.target) && !toggle.contains(event.target)) {
        menu.style.display = 'none';
        if (navbar) navbar.classList.remove('mobile-menu-open');
    }
});

// Close mobile menu on window resize to desktop
window.addEventListener('resize', function() {
    const menu = document.getElementById('mobile-menu');
    if (window.innerWidth > 768 && menu) {
        menu.style.display = 'none';
        const navbar = document.querySelector('.navbar');
        if (navbar) navbar.classList.remove('mobile-menu-open');
    }
});

// Mobile: hide search bar on scroll up, show on scroll down
(function() {
    let lastY = window.scrollY;
    let ticking = false;
    const threshold = 10;

    function onScroll() {
        if (window.innerWidth > 768) return;

        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        const y = window.scrollY;
        const delta = y - lastY;

        if (Math.abs(delta) < threshold) return;

        if (delta < 0) {
            // scrolling up
            navbar.classList.add('mobile-search-hidden');
        } else {
            // scrolling down
            navbar.classList.remove('mobile-search-hidden');
        }

        lastY = y;
    }

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                onScroll();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
})();
