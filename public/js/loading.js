// Loading Screen Utility

class LoadingScreen {
    constructor() {
        this.loadingElement = null;
        this.init();
    }

    init() {
        // First, try to use existing loading screen element
        this.loadingElement = document.getElementById('loadingScreen');
        
        if (!this.loadingElement) {
            // Create loading screen HTML only if it doesn't exist
            this.loadingElement = document.createElement('div');
            this.loadingElement.id = 'loadingScreen';
            this.loadingElement.className = 'loading-screen';
            this.loadingElement.style.display = 'none';
            this.loadingElement.innerHTML = `
                <div class="loading-logo">
                    <div class="loading-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="loading-text">Budgetly</div>
                </div>
                <div class="loading-message">
                    <p>Loading<span class="loading-dots-text">...</span></p>
                </div>
            `;
            
            // Add to body
            document.body.appendChild(this.loadingElement);
        }
        
        // Ensure it's hidden initially
        this.hide();
    }

    show() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'flex';
            this.loadingElement.classList.remove('hide');
            // Force reflow
            this.loadingElement.offsetHeight;
        } else {
            console.error('LoadingScreen: No element to show');
        }
    }

    hide() {
        if (this.loadingElement) {
            this.loadingElement.style.display = 'none';
            this.loadingElement.classList.add('hide');
        } else {
            console.error('LoadingScreen: No element to hide');
        }
    }

    // Auto-hide after specified time (default 2 seconds)
    autoHide(delay = 2000) {
        setTimeout(() => {
            this.hide();
        }, delay);
    }
}

// Don't auto-initialize - let pages control when to initialize
// Pages will call: window.budgetlyLoader = new LoadingScreen();

// Expose LoadingScreen class globally
window.LoadingScreen = LoadingScreen;

// Mark that loading.js is loaded
window.LoadingScreenReady = true;

// Utility functions for easy use
function showLoader() {
    if (window.budgetlyLoader) {
        window.budgetlyLoader.show();
    }
}

function hideLoader() {
    if (window.budgetlyLoader) {
        window.budgetlyLoader.hide();
    }
}

function showLoaderFor(duration = 2000) {
    if (window.budgetlyLoader) {
        window.budgetlyLoader.show();
        window.budgetlyLoader.autoHide(duration);
    }
}