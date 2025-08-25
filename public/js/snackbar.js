/**
 * Universal Snackbar System
 * Consistent notification system for all pages
 */

// Snackbar notification function
function showSnackbar(message, type = 'info') {
    // Remove existing snackbar if any
    const existingSnackbar = document.querySelector('.snackbar');
    if (existingSnackbar) {
        existingSnackbar.remove();
    }

    // Create new snackbar
    const snackbar = document.createElement('div');
    snackbar.className = `snackbar ${type}`;
    
    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-times-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };
    
    snackbar.innerHTML = `
        <span class="snackbar-icon">${icons[type] || icons.info}</span>
        <span class="snackbar-message">${message}</span>
    `;
    
    document.body.appendChild(snackbar);
    
    // Show snackbar
    setTimeout(() => snackbar.classList.add('show'), 100);
    
    // Hide snackbar after 4 seconds
    setTimeout(() => {
        snackbar.classList.remove('show');
        setTimeout(() => snackbar.remove(), 300);
    }, 4000);
}

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.showSnackbar = showSnackbar;
}
