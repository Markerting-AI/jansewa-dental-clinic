// ================= STICKY NAVBAR =================

const navbar = document.getElementById("navbar");

window.addEventListener("scroll", () => {
    if (window.scrollY > 30) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});

// ================= HERO SLIDER =================

const slides = document.querySelectorAll(".hero-slider img, .hero-slider video");

let currentSlide = 0;
let slideTimer;

function showNextSlide() {
    // Hide current slide
    slides[currentSlide].classList.remove("active-slide");

    // Stop and reset current video if leaving a video slide
    if (slides[currentSlide].tagName === "VIDEO") {
        slides[currentSlide].pause();
        slides[currentSlide].currentTime = 0;
    }

    // Move to next slide
    currentSlide = (currentSlide + 1) % slides.length;

    // Show next slide
    slides[currentSlide].classList.add("active-slide");

    // If next slide is a video
    if (slides[currentSlide].tagName === "VIDEO") {
        slides[currentSlide].currentTime = 0;
        slides[currentSlide].play();

        slides[currentSlide].addEventListener("ended", showNextSlide, { once: true });
    } else {
        // If it's an image, wait 7 seconds
        clearTimeout(slideTimer);
        slideTimer = setTimeout(showNextSlide, 7000);
    }
}

// Start the slider
slideTimer = setTimeout(showNextSlide, 7000);