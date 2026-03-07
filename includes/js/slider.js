/**
 * slider.js
 * 
 * Homepage hero slider functionality.
 * Auto-rotates slides every 5 seconds.
 */

(function() {
    let currentSlide = 0;
    let slideInterval;
    const slideDuration = 5000;

    function showSlide(index) {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');

        if (slides.length === 0) return;

        if (index >= slides.length) currentSlide = 0;
        else if (index < 0) currentSlide = slides.length - 1;
        else currentSlide = index;

        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            if (i === currentSlide) {
                slide.classList.add('active');
            }
        });

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

    function initSlider() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');

        if (slides.length === 0) return;

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToSlide(index);
                resetAutoSlide();
            });
        });

        const slider = document.querySelector('.slider-container');
        if (slider) {
            slider.addEventListener('mouseenter', stopAutoSlide);
            slider.addEventListener('mouseleave', startAutoSlide);
        }

        showSlide(0);
        startAutoSlide();
    }

    document.addEventListener('DOMContentLoaded', initSlider);

    window.changeSlide = changeSlide;
    window.goToSlide = function(index, reset) {
        goToSlide(index);
        if (reset) resetAutoSlide();
    };
})();
