/**
 * Navigation JavaScript
 * Handles dropdown functionality for better UX
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Navigation script loaded');
    initializeNavigation();
});

function initializeNavigation() {
    const dropdownItems = document.querySelectorAll('.nav-dropdown');
    console.log('Found dropdown items:', dropdownItems.length);
    
    dropdownItems.forEach(dropdown => {
        const link = dropdown.querySelector('a');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        // Add click functionality to dropdown links
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Close all other dropdowns
            dropdownItems.forEach(otherDropdown => {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('active');
                }
            });
            
            // Toggle this dropdown
            dropdown.classList.toggle('active');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            dropdownItems.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Close dropdowns on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdownItems.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
}

// Mobile menu toggle functionality
function toggleMobileMenu() {
    const nav = document.querySelector('nav ul');
    nav.classList.toggle('mobile-open');
}