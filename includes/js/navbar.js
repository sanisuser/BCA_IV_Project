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

/**
 * Hero Slider functionality
 * Auto-rotates slides every 5 seconds
 */
let currentSlide = 0;
let slideInterval;
const slideDuration = 5000; // 5 seconds

function initSlider() {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    
    if (slides.length === 0) return;
    
    // Start auto-rotation - DISABLED
    // startAutoSlide();
    
    // Add click handlers to dots
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            goToSlide(index);
            resetAutoSlide();
        });
    });
    
    // Pause on hover
    const slider = document.querySelector('.slider-container');
    if (slider) {
        slider.addEventListener('mouseenter', stopAutoSlide);
        slider.addEventListener('mouseleave', startAutoSlide);
    }
}

function showSlide(index) {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    
    if (slides.length === 0) return;
    
    // Wrap around
    if (index >= slides.length) currentSlide = 0;
    else if (index < 0) currentSlide = slides.length - 1;
    else currentSlide = index;
    
    // Update slides
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (i === currentSlide) {
            slide.classList.add('active');
        }
    });
    
    // Update dots
    dots.forEach((dot, i) => {
        dot.classList.remove('active');
        if (i === currentSlide) {
            dot.classList.add('active');
        }
    });
}

function changeSlide(direction) {
    showSlide(currentSlide + direction);
    resetAutoSlide();
}

function goToSlide(index) {
    showSlide(index);
}

function startAutoSlide() {
    stopAutoSlide();
    slideInterval = setInterval(() => {
        changeSlide(1);
    }, slideDuration);
}

function stopAutoSlide() {
    if (slideInterval) {
        clearInterval(slideInterval);
    }
}

function resetAutoSlide() {
    stopAutoSlide();
    startAutoSlide();
}

// Initialize slider when DOM is ready
document.addEventListener('DOMContentLoaded', initSlider);
