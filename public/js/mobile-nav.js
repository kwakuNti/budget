// Mobile Menu functionality
function toggleMobileMenu() {
    const nav = document.getElementById('headerNav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (nav && toggle) {
        nav.classList.toggle('mobile-open');
        toggle.classList.toggle('active');
    }
}

function closeMobileMenu() {
    const nav = document.getElementById('headerNav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (nav && toggle) {
        nav.classList.remove('mobile-open');
        toggle.classList.remove('active');
    }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(event) {
    const nav = document.getElementById('headerNav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (nav && toggle && !nav.contains(event.target) && !toggle.contains(event.target)) {
        closeMobileMenu();
    }
});

// Close mobile menu when window is resized to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        closeMobileMenu();
    }
});

// Add click listeners to nav items to close mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', closeMobileMenu);
    });
});
